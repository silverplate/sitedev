<?php

abstract class DbMapper
{
    protected $_db;
    protected $_links = array();
    protected $_linksParam = array();
    protected $files;
    protected $images;


    public function getXml($_node = null, $_xml = null, array $_attrs = array())
    {
        $xml = empty($_xml) ? array() : array($_xml);
        $node = empty($_node) ? 'item' : $_node;
        $attrs = empty($_attrs) ? array() : $_attrs;

        $result = '<' . $node;
        foreach ($attrs as $name => $value) {
            $result .= ' ' . self::normalizeXmlName($name) . '="' . $value . '"';
        }

        $result .= '>';
        $result .= implode('', $xml);
        $result .= '</' . $node . '>';

        return $result;
    }

    public function updateLinks($_name, $_value = null)
    {
        if ($this->getLinks($_name)) {
            foreach ($this->getLinks($_name) as $item) {
                $item->delete();
            }

            $this->setLinks($_name);
        }

        if (!empty($_value)) {
            $this->setLinks($_name, $_value);

            foreach ($this->getLinks($_name) as $item) {
                $item->create();
            }
        }
    }


    /*
    *
    * Далее нужен рефакторинг
    *
    **/

    public static function normalizeXmlName($name) {
        return str_replace('_', '-', self::getUnderlinedStyleName($name));
    }

    public static function getNoEmptyCdataNodeXml($nodeName, $cdata = null, array $attrs = array()) {
        return
            empty($cdata) && !$attrs
            ? ''
            : self::getCdataNodeXml($nodeName, $cdata, $attrs);
    }

    public static function getCdataNodeXml($nodeName, $cdata = null, array $attrs = array()) {
        $nodeName = self::normalizeXmlName($nodeName);

        $xml = '<' . $nodeName;
        foreach ($attrs as $name => $value) {
            $xml .= ' ' . self::normalizeXmlName($name) . '="' . $value . '"';
        }

        if ($cdata) {
            $xml .= '><![CDATA[' . $cdata . ']]></' . $nodeName . '>';
        } else {
            $xml .= ' />';
        }

        return $xml;
    }

//     public function getCamelStyleName($name) {
//         $result = '';
//         for ($i = 0; $i < strlen($name); $i++) {
//             $result .= '_' == $name{$i} && strlen($result) > 0
//                 ? strtoupper($name{++$i})
//                 : strtolower($name{$i});
//         }
//         return $result;
//     }

    public static function getUnderlinedStyleName($value) {
        $result = '';
        for ($i = 0; $i < strlen($value); $i++) {
            if (
                $i != 0 &&
                !in_array($value{$i}, array('_', '-')) &&
                $value{$i} == strtoupper($value{$i})
            ) {
                $result .= '_';
            }
            $result .= strtolower($value{$i});
        }
        return $result;
    }

    public function getDb() {
        return $this->_db;
    }

    public function getPrimaryKey() {
        return $this->_db->getPrimaryKey();
    }

    public function getAttributeObject($name) {
        return $this->_db->getAttributeObject($name);
    }

    public function getAttributeName($name) {
        $attribute = $this->getUnderlinedStyleName($name);

        switch ($attribute) {
            case 'id':
                return $this->_db->getPri();

            default:
                if (in_array($name, $this->_db->getAttributes())) {
                    return $name;

                } elseif (in_array($attribute, $this->_db->getAttributes())) {
                    return $attribute;

                } else {
                    throw new Exception(
                        'There is no a such attribute "' . $attribute . '" ' .
                        '(original "' . $name . '") ' .
                        'in ' . get_class($this->_db) . '.'
                    );
                }
        }
    }

    public function __get($name) {
        return $this->_db->getAttribute(
            $this->getAttributeName($name)
        );
    }

    public function __set($name, $value) {
        return $this->_db->setAttribute(
            $this->getAttributeName($name),
            $value
        );
    }

    public function getAttribute($name) {
        return $this->__get($name);
    }

    public function setAttribute($name, $value) {
        return $this->__set($name, $value);
    }

    public function hasAttribute($name) {
        return $this->_db->HasAttribute($name);
    }

    public function setId($value) {
        $this->id = $value;
    }

    public function create() {
        return $this->_db->create();
    }

    public function update() {
        return $this->_db->update();
    }

    public function delete() {
        foreach ($this->getFiles() as $file) {
            $file->delete();
        }

        if (
            method_exists($this, 'getFilePath') &&
            is_directory_empty($this->getFilePath())
        ) {
            rmdir($this->getFilePath());
        }

        foreach (array_keys($this->_links) as $item) {
            $this->updateLinks($item);
        }

        return $this->_db->delete();
    }

    public function getTitle() {
        return $this->_db->getTitle();
    }

    public function getId() {
        return $this->_db->getId();
    }

    public function getDbId()
    {
        return $this->_db->getDbId();
    }

    public function uploadFile($name, $tmpName) {
        if (!empty($name) && !empty($tmpName)) {
            $name = File::normalizeName($name);

            if (is_file($this->getFilePath() . $name)) {
                unlink($this->getFilePath() . $name);
            } else {
                create_directory($this->getFilePath(), true);
            }

            move_uploaded_file($tmpName, $this->getFilePath() . $name);
            chmod($this->getFilePath() . $name, 0777);
        }
    }

    public function getFiles() {
        if (is_null($this->files)) {
            $this->files = array();
            if (
                method_exists($this, 'getFilePath') &&
                $this->getFilePath() &&
                is_dir($this->getFilePath())
            ) {
                $handle = opendir($this->getFilePath());
                while (false !== $item = readdir($handle)) {
                    if (
                        $item != '.' &&
                        $item != '..' &&
                        is_file($this->getFilePath() . $item)
                    ) {
                        $file = new File($this->getFilePath() . $item, DOCUMENT_ROOT, '/');
                        $this->files[strtolower($file->getFileName())] = $file;
                    }
                }
                closedir($handle);
            }
        }
        return $this->files;
    }

    public function getImages() {
        if (is_null($this->images)) {
            $this->images = array();
            if ($this->getFiles()) {
                foreach ($this->getFiles() as $file) {
                    if (Image::isImageExtension($file->getExtension())) {
                        $image = new Image(
                            $file->getPath(),
                            $file->getPathStartsWith(),
                            $file->getUriStartsWith()
                        );
                        $this->images[strtolower($image->getFileName())] = $image;
                    }
                }
            }
        }
        return $this->images;
    }

    public function getIlluByFileName($filename) {
        $files = $this->getImages();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getIlluByName($name) {
        foreach ($this->getImages() as $file) {
            if ($name == $file->getName()) {
                return $file;
            }
        }
        return false;
    }

    public function getIllu($name) {
        return $this->getIlluByName($name);
    }

    public function getFileByFileName($filename) {
        $files = $this->getFiles();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getFileByName($name) {
        foreach ($this->getFiles() as $file) {
            if ($name == $file->getName()) {
                return $file;
            }
        }
        return false;
    }

    public function getFile($name) {
        return $this->getFileByName($name);
    }
}
