<?php

class Ext_Image extends Ext_File
{
    protected $_width;
    protected $_height;
    protected $_text;

    /**
     * @var resource
     */
    protected $_gd;

	public function computeImageSize()
    {
        $size = getimagesize($this->getPath());
        if (!$size) {
            throw new Exception('Unable to get image size for ' . $this->getPath());
        }

        $this->_width = $size[0];
        $this->_height = $size[1];
        $this->_mime = $size['mime'];

        $this->cache();
    }

    public function getWidth()
    {
        if (!isset($this->_width)) {
            $this->computeImageSize();
        }

        return $this->_width;
    }

    public function setWidth($_width)
    {
        $this->_width = (int) $_width;
    }

    public function getHeight()
    {
        if (!isset($this->_height)) {
            $this->computeImageSize();
        }

        return $this->_height;
    }

    public function setHeight($_height)
    {
        $this->_height = (int) $_height;
    }

    public function getMime()
    {
        if (!isset($this->_mime)) {
            $this->computeImageSize();
        }

        return parent::getMime();
    }

    public function setMime($_mime)
    {
        $this->_mime = $_mime;
    }

    public function getText()
    {
        return $this->_text;
    }

    public function setText($_text)
    {
        $this->_text = $_text;
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $attrs = array('width' => $this->getWidth(), 'height' => $this->getHeight());

        if ($_attrs) {
            $attrs = array_merge($attrs, $_attrs);
        }

        $xml = is_array($_xml) ? $_xml : array();

        if ($this->getText()) {
            $xml[] = Ext_Xml::cdata('text', $this->getText());
        }

        return parent::getXml(empty($_node) ? 'image' : $_node, $xml, $attrs);
    }

    public function getNode($_dom, $_name = null, $_attrs = null)
    {
        $node = parent::getNode($_dom, empty($_name) ? 'image' : $_name, $_attrs);

        $node->setAttribute('width', $this->getWidth());
        $node->setAttribute('height', $this->getHeight());

        if ($this->getText()) {
            $node->appendChild($_dom->createCDATASection($this->getText()));
        }

        return $node;
    }

    public static function resize($_srcImage,
                                  $_dstWidth = null,
                                  $_dstHeight = null,
                                  $_dstFilePath = null,
                                  $_quality = 90)
    {
        if (empty($_dstWidth) && empty($_dstHeight)) {
            throw new Exception('Destination width or height must be set.');
        }

        if ($_srcImage instanceof Ext_Image) {
            $srcImage = $_srcImage;

        } else if ($_srcImage instanceof Ext_File) {
            $srcImage = new Ext_Image($_srcImage->getPath());

        } else {
            $srcImage = new Ext_Image($_srcImage);
        }

        $srcFilePath =  $_srcImage->getPath();
        $srcExtension = $_srcImage->getExt();
        $srcWidth =     $_srcImage->getWidth();
        $srcHeight =    $_srcImage->getHeight();

        $dstWidth = empty($_dstWidth) ? $_dstHeight / $srcHeight * $srcWidth : $_dstWidth;
        $dstHeight = empty($_dstHeight) ? $_dstWidth / $srcWidth * $srcHeight : $_dstHeight;
        $dstFilePath = empty($_dstFilePath) ? $srcFilePath : $_dstFilePath;
        $dstFileInfo = pathinfo($dstFilePath);

        switch (strtolower($srcExtension)) {
            case 'gif':
                $src = imagecreatefromgif($srcFilePath);
                break;

            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($srcFilePath);
                break;

            case 'png':
                $src = imagecreatefrompng($srcFilePath);
                break;
        }

        if (empty($src)) {
            throw new Exception('Unknown image type.');
        }

        // Если исходное изображение больше
        // хотя бы по одной стороне
        if ($srcWidth > $dstWidth || $srcHeight > $dstHeight) {
            $srcRate = $srcWidth / $srcHeight;
            $dstRate = $dstWidth / $dstHeight;
            $cropWidth = $srcWidth;
            $cropHeight = $srcHeight;
            $cropX = 0;
            $cropY = 0;

            // Если отношение ширины к высоте будущего
            // изображения больше, чем у предыдущего
            if ($dstRate > $srcRate) {
                $cropHeight = $srcWidth / $dstRate;
                $cropY = floor(($srcHeight - $cropHeight) / 2);

            // Если меньше
            } else if ($dstRate < $srcRate) {
                $cropWidth = $srcHeight * $dstRate;
                $cropX = floor(($srcWidth - $cropWidth) / 2);
            }

            // Если соотношения отличаются,
            // то нужно обрезать изображение
            if ($cropWidth != $srcWidth || $cropHeight != $srcHeight) {
                $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
                imagecopy($croppedImage, $src, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);
                $src = $croppedImage;
                $srcWidth = $cropWidth;
                $srcHeight = $cropHeight;
            }

            $newImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled($newImage, $src, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' . $dstFileInfo['filename'] . '.jpg';
            Ext_File::createDir($dstFileInfo['dirname']);
            imagejpeg($newImage, $dstFilePathWithType, $_quality);
            @chmod($dstFilePathWithType, 0777);

            if (is_null($_dstFilePath) && $dstFilePathWithType != $srcFilePath) {
                unlink($srcFilePath);
            }

            return new Ext_Image($dstFilePathWithType, DOCUMENT_ROOT, '/');

        // Исходное изображение меньше,
        // поэтому ничего не делаем
        } else {
            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' . $dstFileInfo['filename'] . '.' . $srcExtension;

            if (!is_file($dstFilePathWithType)) {
                Ext_File::createDir($dstFileInfo['dirname']);
                copy($srcFilePath, $dstFilePathWithType);
                @chmod($dstFilePathWithType, 0777);
            }

            return new Ext_Image($dstFilePathWithType, DOCUMENT_ROOT, '/');
        }
    }

    /**
     * @param string $_dstImage
     * @param resource $_replacement
     * @param string|boolean $_backup
     */
    public static function replaceWithGd($_dstImage, $_replacement, $_backup = null)
    {
        if ($_backup) {
            if ($_backup === true) {
                $path = pathinfo($_dstImage);
                $backup = $path['dirname'] . '/' .
                          $path['filename'] . '-origin.' . $path['extension'];
            } else {
                $backup = $_backup;
            }

            // Проверка, чтобы не перезаписать самый первый бэкап,
            // который может содержать самую правильную версию.
            if (!is_file($backup)) {
                copy($_dstImage, $backup);
                @chmod($backup, 0777);
            }
        }

        $result = imagejpeg($_replacement, $_dstImage, 100);
        @chmod($_dstImage, 0777);

        return $result;
    }

    /**
     * @param Ext_Image|string $_srcImage
     * @param string $_watermarkPath
     * @return resource
     */
    public static function getWatermark($_srcImage, $_watermarkPath)
    {
        $margin = array(0, 20, 15, 0);
        $src = $_srcImage instanceof Ext_Image
             ? $_srcImage
             : new Ext_Image($_srcImage);

        $wtrmrk = new Ext_Image($_watermarkPath);

        imagecopy(
            $src->getGd(),
            $wtrmrk->getGd(),
            $src->getWidth() - $wtrmrk->getWidth() - $margin[1],
            $src->getHeight() - $wtrmrk->getHeight() - $margin[2],
            0,
            0,
            $wtrmrk->getWidth(),
            $wtrmrk->getHeight()
        );

        return $src->getGd();
    }

    /**
     * @return resource
     */
    public function getGd()
    {
        if (is_null($this->_gd)) {
            switch ($this->getExt()) {
                case 'jpeg':
                case 'jpg':
                    $this->_gd = imagecreatefromjpeg($this->getPath());
                    break;

                case 'png':
                    $this->_gd = imagecreatefrompng($this->getPath());
                    break;

                case 'gif':
                    $this->_gd = imagecreatefromgif($this->getPath());
                    break;

                default:
                    $this->_gd = imagecreatefromgd($this->getPath());
            }
        }

        return $this->_gd;
    }
}
