<?php

class Image extends File
{
	protected $_width;
	protected $_height;
	protected $_type;
	protected $_text;

	public function setPath($_path)
	{
	    parent::setPath($_path);

		$size = getimagesize($this->getPath());
		$this->_width  = $size[0];
		$this->_height = $size[1];
		$this->_type = $size[2];
	}

	public function getWidth() {
		return (int) $this->_width;
	}

	public function getHeight() {
		return (int) $this->_height;
	}

	public function getType() {
		return $this->_type;
	}

	public function setText($_value) {
		$this->_text = $_value;
	}

	public function getText() {
		return $this->_text;
	}

	public function getXml($_typeAttribute = null)
	{
	    $attrs = array('width' => $this->getWidth(),
	                   'height' => $this->getHeight(),
	                   'uri' => $this->getUri(),
	                   'path' => $this->getPath(),
	                   'filename' => $this->getFileName(),
	                   'name' => $this->getName(),
	                   'extension' => $this->getExtension());

        if ($_typeAttribute) {
            $attrs['type'] = $_typeAttribute;
        }

	    return getCdata('image', $this->getText(), $attrs);
	}

    public function getHtml($_attrs = null)
    {
        $attrs = array('width' => $this->getWidth(),
	                   'height' => $this->getHeight(),
	                   'src' => $this->getUri(),
	                   'alt' => $this->getText());

        if ($_attrs) {
            $attrs = array_merge($attrs, $_attrs);
        }

        return getCdata('img', null, $attrs);
    }

// 	public function GetNode(DOMDocument $dom, $_type_attribute = null)
// 	{
// 		$node = $dom->createElement('image');
//
// 		if (!empty($_type_attribute)) {
// 			$node->setAttribute('type', $_type_attribute);
// 		}
//
// 		$node->setAttribute('width', $this->GetWidth());
// 		$node->setAttribute('height', $this->GetHeight());
// 		$node->setAttribute('uri', $this->GetUri());
// 		$node->setAttribute('path', $this->GetPath());
// 		$node->setAttribute('filename', $this->GetFileName());
// 		$node->setAttribute('name', $this->GetName());
// 		$node->setAttribute('extension', $this->GetExtension());
//
// 		if ($this->GetText()) {
// 			$node->appendChild(
// 				$dom->createCDATASection($this->GetText())
// 			);
// 		}
//
// 		return $node;
// 	}

    public static function resize($_srcImage,
                                  $_dstWidth = null,
                                  $_dstHeight = null,
                                  $_dstFilePath = null,
                                  $_quality = 90)
    {
        if (empty($_dstWidth) && empty($_dstHeight)) {
            throw new Exception('Destination width or height must be set');
        }

        if ($_srcImage instanceof Image) {
            $srcFilePath =  $_srcImage->getPath();
            $srcExtension = $_srcImage->getExtension();
            $srcWidth =     $_srcImage->getWidth();
            $srcHeight =    $_srcImage->getHeight();

        } else {
            $srcFilePath =  $_srcImage;
            $srcExtension = get_file_extension($_srcImage);
            $size =         getimagesize($_srcImage);
            $srcWidth =     $size[0];
            $srcHeight =    $size[1];
        }

        $dstWidth = empty($_dstWidth) ? $_dstHeight / $srcHeight * $srcWidth : $_dstWidth;
        $dstHeight = empty($_dstHeight) ? $_dstWidth / $srcWidth * $srcHeight : $_dstHeight;
        $dstFilePath = empty($_dstFilePath) ? $srcFilePath : $_dstFilePath;
        $dstFileInfo = pathinfo($dstFilePath);

        switch (strtolower($srcExtension)) {
            case 'gif':
                $srcImage = imagecreatefromgif($srcFilePath);
                break;

            case 'jpg':
            case 'jpeg':
                $srcImage = imagecreatefromjpeg($srcFilePath);
                break;

            case 'png':
                $srcImage = imagecreatefrompng($srcFilePath);
                break;
        }

        if (empty($srcImage)) {
            throw new Exception('Unknown image type');
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
                imagecopy($croppedImage, $srcImage, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);
                $srcImage = $croppedImage;
                $srcWidth = $cropWidth;
                $srcHeight = $cropHeight;
            }

            $newImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' . $dstFileInfo['filename'] . '.jpg';
            createDirectory($dstFileInfo['dirname']);
            imagejpeg($newImage, $dstFilePathWithType, $_quality);
            chmod($dstFilePathWithType, 0777);

            if (
                is_null($_dstFilePath) &&
                $dstFilePathWithType != $srcFilePath
            ) {
                unlink($srcFilePath);
            }

            return new Image($dstFilePathWithType, DOCUMENT_ROOT, '/');

        // Исходное изображение меньше,
        // поэтому ничего не делаем
        } else {
            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' . $dstFileInfo['filename'] . '.' . $srcExtension;

            if (!is_file($dstFilePathWithType)) {
                createDirectory($dstFileInfo['dirname']);
                copy($srcFilePath, $dstFilePathWithType);
                chmod($dstFilePathWithType, 0777);
            }

            return new Image($dstFilePathWithType, DOCUMENT_ROOT, '/');
        }
    }

    public static function getByFileName(array $_files, $_filename)
    {
        foreach ($_files as $file) {
            if (
                self::isImageExtension($file->getExtension()) &&
                $file->getFileName() == $_filename
            ) {
                if ($file instanceof Image) {
                    return $file;

                } else {
                    return new Image($file->getPath(),
                                     $file->getPathStartsWith(),
                                     $file->getUriStartsWith());
                }
            }
        }

        return false;
    }

    public static function applyXmlImages($_xml, array $_files)
    {
        $dom = getXmlObject($_xml);
        $illus = $dom->getElementsByTagName('illu');
        $i = 0;

        while (true) {
            $illu = $illus->item($i);

            if (!$illu) {
                break;

            } else if ($illu->hasAttribute('alias')) {
                $image = Image::getByFileName($_files, $illu->getAttribute('alias'));

                if ($image) {
                    $illu->removeAttribute('alias');
                    $illu->setAttribute('uri', $image->getUri());
                    $illu->setAttribute('width', $image->getWidth());
                    $illu->setAttribute('height', $image->getHeight());
                    $i++;

                } else {
                    dom_remove($illu);
                }

            } else if ($illu->hasAttribute('uri') && !$illu->hasAttribute('width')) {
                $path = rtrim(DOCUMENT_ROOT, '/') . $illu->getAttribute('uri');
                $i++;

                if (is_file($path)) {
                    $image = new Image($path, DOCUMENT_ROOT, '/');
                    $illu->setAttribute('width', $image->getWidth());
                    $illu->setAttribute('height', $image->getHeight());
                }

            } else {
                $i++;
            }
        }

        return preg_replace('/^<root>|<\/root>$/', '', $dom->saveXml($dom->documentElement));
    }
}
