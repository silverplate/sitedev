<?php

class Ext_File
{
    protected $_path;
    protected $_dir;
    protected $_uri;
    protected $_filename;
    protected $_name;
    protected $_ext;
    protected $_pathStartsWith;
    protected $_uriStartsWith;
    protected $_mime;
    protected $_size;

    /**
     * @var array Двухбуквенное обозначение языка (например, en).
     * Если язык не задан, то используется только русский язык.
     */
    protected static $_langs = array();

    /**
     * @param string $_lang
     */
    public static function addLang($_lang)
    {
        self::$_langs[] = $_lang;
    }

    public static function getLangs()
    {
        return self::$_langs;
    }

    public static function createDir($_dir, $_isRecursive = true)
    {
        if (!is_dir($_dir)) {
            $mask = umask(0);
            mkdir($_dir, 0777, $_isRecursive);
            umask($mask);
        }
    }

    public static function removeDir($_dir, $_isSelfDelete = true, $_isOnlyFiles = false)
    {
        $dir = rtrim($_dir, '/') . '/';

        if (is_dir($dir)) {
            $dirHandle = opendir($dir);
            $item = readdir($dirHandle);

            while ($item !== false) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir . $item)) {
                        if (!$_isOnlyFiles) {
                            self::removeDir($dir . $item);
                        }

                    } else {
                        unlink($dir . $item);
                    }
                }

                $item = readdir($dirHandle);
            }

            if ($_isSelfDelete) {
                rmdir($dir);
            }

            closedir($dirHandle);
        }
    }

    public static function isDirEmpty($_dir, $_ignore = null)
    {
        if (is_dir($_dir)) {
            $dirHandle = opendir($_dir);
            $ignore = is_null($_ignore) ? array() : explode(' ', strtolower($_ignore));
            $item = readdir($dirHandle);

            while ($item !== false) {
                if (
                    $item != '.' &&
                    $item != '..' &&
                    !in_array(strtolower($item), $ignore)
                ) {
                    closedir($dirHandle);
                    return false;
                }

                $item = readdir($dirHandle);
            }

            closedir($dirHandle);
            return true;
        }

        return false;
    }

    public static function getByName($_dir, $_name, $_class = null)
    {
        $dir = rtrim($_dir, '/') . '/';

        if (is_dir($dir)) {
            $class = is_null($_class) ? get_called_class() : $_class;

            foreach (array('.*', '*') as $try) {
                $search = glob($dir . $_name . $try);

                if ($search) {
                    return new $class($search[0]);
                }
            }
        }

        return false;
    }

    public static function moveFile($_from, $_to)
    {
        if (is_file($_from)) {
            self::deleteFile($_to);
            return rename($_from, $_to);
        }

        return false;
    }

    public static function computeName($_file)
    {
        return pathinfo($_file, PATHINFO_FILENAME);
    }

    public static function computeExt($_file)
    {
        return pathinfo($_file, PATHINFO_EXTENSION);
    }

    public static function computeSizeMeasure($_size)
    {
        $result = array();

        if ($_size / (1024 * 1024) > 0.5) {
            $result['value'] = $_size / (1024 * 1024);
            $result['measure'] = 'МБ';
            $result['measure-en'] = 'MB';

        } else if ($_size / 1024 > 0.5) {
            $result['value'] = $_size / 1024;
            $result['measure'] = 'КБ';
            $result['measure-en'] = 'KB';

        } else {
            $result['value'] = $_size;
            $result['measure'] = 'байт';
            $result['measure-en'] = 'bite';
        }

        $result['value'] = Ext_Number::format($result['value']);
        $result['string'] = $result['value'] . ' ' . $result['measure'];
        $result['string-en'] = $result['value'] . ' ' . $result['measure-en'];

        return $result;
    }

    public static function compressFile($_srcName, $_dstName)
    {
        $fp = fopen($_srcName, 'r');
        $data = fread($fp, filesize($_srcName));
        fclose($fp);

        $zp = gzopen($_dstName, 'w9');
        gzwrite($zp, $data);
        gzclose($zp);
    }

    public static function isImageExt($_ext)
    {
        return in_array(
            strtolower($_ext),
            array('gif', 'jpeg', 'jpg', 'png', 'tiff')
        );
    }

	public static function normalizeName($_name)
	{
	    $name = strip_tags($_name);
	    $name = html_entity_decode($name, ENT_NOQUOTES, 'utf-8');
	    $name = strtolower(Ext_String::translit($name));
	    $name = preg_replace('/[^\s\-a-z.0-9_]/', '', $name);
	    $name = preg_replace('/_+/', '-', $name);
	    $name = preg_replace('/\s+/', '-', $name);
	    $name = preg_replace('/-+/', '-', $name);

		return $name;
	}

	/**
	 * @param string $_name
	 * @return boolean
	 */
	public static function checkName($_name)
	{
	    return preg_match('/^[\-a-z.0-9]+$/', $_name) > 0;
	}

    public static function deleteFile($_filePath)
    {
        if (is_file($_filePath)) {
            Ext_File_Cache::delete($_filePath);
            return unlink($_filePath);

        } else {
            return false;
        }
    }

    /**
     * @param string $_filePath
     * @param array|string $_content
     * @return integer|false
     */
    public static function log($_filePath, $_content)
    {
        $content = array(date('Y-m-d H:i:s'));

        if (is_array($_content)) {
            foreach ($_content as $key => $item) {
                $item = trim($item);

                if (!is_int($key)) $content[] = $key;
                $content[] = strpos($item, "\t") === false ? $item : "\"$item\"";
            }

        } else {
            $content[] = $_content;
        }

        return self::append($_filePath, implode("\t", $content) . PHP_EOL);
    }

    /**
     * @param string $_filePath
     * @param string $_content
     * @return integer|false
     */
    public static function append($_filePath, $_content)
    {
        return self::write($_filePath, $_content, true);
    }

    /**
     * @param string $_filePath
     * @param string $_content
     * @param boolean $_isAppendMode
     * @return integer|false
     */
    public static function write($_filePath, $_content, $_isAppendMode = false)
    {
        $isNew = !is_file($_filePath);

        if ($isNew) {
            $path = dirname($_filePath);
            if (!is_dir($path)) self::createDir($path);
        }

        $bytes = file_put_contents(
            $_filePath,
            $_content,
            $_isAppendMode ? FILE_APPEND : null
        );

        if ($bytes === false) {
            return false;

        } else {
            if ($isNew) @chmod($_filePath, 0777);
            return $bytes;
        }
    }

    /**
     * @param string $_path
     * @param string $_pathStartsWith
     * @param string $_uriStartsWith
     * @return Ext_File|Ext_Image
     */
    public static function factory($_path, $_pathStartsWith = null, $_uriStartsWith = null)
    {
        $class = get_called_class();
        $cache = Ext_File_Cache::getFile($_path);

        if (!$cache && self::isImage($_path)) {
            $class = 'Ext_Image';
        }

        return $cache ? $cache : new $class($_path, $_pathStartsWith, $_uriStartsWith);
    }

    public function cache()
    {
        return Ext_File_Cache::saveFile($this);
    }

    public function __construct($_path = null, $_pathStartsWith = null, $_uriStartsWith = null)
    {
        if ($_pathStartsWith) {
            $this->setPathStartsWith($_pathStartsWith);
        }

        if ($_uriStartsWith) {
            $this->setUriStartsWith($_uriStartsWith);
        }

        if ($_path) {
            $this->setPath($_path);
        }
    }

    public function setPathStartsWith($_path)
    {
        $this->_pathStartsWith = $_path;
    }

    public function getPathStartsWith()
    {
        return $this->_pathStartsWith;
    }

    public function setUriStartsWith($_uri)
    {
        $this->_uriStartsWith = $_uri;
    }

    public function getUriStartsWith()
    {
        return $this->_uriStartsWith;
    }

    public static function computeUri($_path = null, $_pathStart = null, $_uriStart = null)
    {
        if ($_path) {
            $pathStart = is_null($_pathStart) ? DOCUMENT_ROOT : $_pathStart;
            $uriStart = is_null($_uriStart) ? '/' : $_uriStart;

            return $pathStart ? str_replace($pathStart, $uriStart, $_path) : $_path;
        }

        return false;
    }

    public function setPath($_path)
    {
        $this->_path = $_path;
        $this->_dir = dirname($this->_path);
        $this->_filename = basename($this->_path);
        $this->_ext = self::computeExt($this->_path);
        $this->_name = self::computeName($this->_filename);

        $this->setUri(self::computeUri(
            $this->_path,
            $this->getPathStartsWith(),
            $this->getUriStartsWith()
        ));
    }

    public function delete()
    {
        self::deleteFile($this->getPath());

        if (self::isDirEmpty($this->getDir())) {
            self::removeDir($this->getDir());
        }
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getDir()
    {
        return $this->_dir;
    }

    public function getUri()
    {
        return $this->_uri;
    }

    public function setUri($_uri)
    {
        $this->_uri = $_uri;
    }

    public function getFilename()
    {
        return $this->_filename;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getExt()
    {
        return $this->_ext;
    }

    public function getMime()
    {
        if (!isset($this->_mime)) {
            // $this->_mime = 'application/octet-stream';
            $this->_mime = 'application/' . $this->getExt();
        }

        return $this->_mime;
    }

    public function getSize()
    {
        if (is_null($this->_size)) {
            $this->_size = filesize($this->getPath());

            if (!($this instanceof Ext_Image)) {
                $this->cache();
            }
        }

        return $this->_size;
    }

    public function setSize($_size)
    {
        $this->_size = (int) $_size;
    }

    public function getSizeMeasure()
    {
        return self::computeSizeMeasure($this->getSize());
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $attrs = array(
            'uri' => $this->getUri(),
            'path' => $this->getPath(),
            'filename' => $this->getFilename(),
            'name' => $this->getName(),
            'extension' => $this->getExt()
        );

        if ($_attrs) {
            $attrs = array_merge($attrs, $_attrs);
        }

        $xml = is_array($_xml) ? $_xml : array();
        $size = $this->getSizeMeasure();

        $xml[] = Ext_Xml::cdata(
            'size',
            $size['string'],
            array('xml:lang' => 'ru', 'value' => $size['value'], 'measure' => $size['measure'])
        );

        foreach (self::$_langs as $lang) {
            if (isset($size["string-$lang"])) {
                $xml[] = Ext_Xml::cdata(
                    'size',
                    $size["string-$lang"],
                    array('xml:lang' => $lang, 'value' => $size['value'], 'measure' => $size["measure-$lang"])
                );
            }
        }

        return Ext_Xml::node(empty($_node) ? 'file' : $_node, $xml, $attrs);
    }

    public function getNode($_dom, $_name = null, $_attrs = null)
    {
        $size = $this->getSizeMeasure();
        $node = $_dom->createElement(empty($_name) ? 'file' : $_name);

        if (!empty($_attrs)) {
            foreach ($_attrs as $name => $value) {
                $node->setAttribute(Ext_Xml::normalize($name), $value);
            }
        }

        $node->setAttribute('uri', $this->getUri());
        $node->setAttribute('path', $this->getPath());
        $node->setAttribute('filename', $this->getFilename());
        $node->setAttribute('name', $this->getName());
        $node->setAttribute('extension', $this->getExt());

        $s = $_dom->createElement('size');
        $s->setAttribute('xml:lang', 'ru');
        $s->setAttribute('value', $size['value']);
        $s->setAttribute('measure', $size['measure']);
        $s->appendChild($_dom->createCDATASection($size['string']));
        $node->appendChild($s);

        foreach (self::$_langs as $lang) {
            if (isset($size["string-$lang"])) {
                $s = $_dom->createElement('size');
                $s->setAttribute('xml:lang', $lang);
                $s->setAttribute('value', $size['value']);
                $s->setAttribute('measure', $size["measure-$lang"]);
                $s->appendChild($_dom->createCDATASection($size["string-$lang"]));
                $node->appendChild($s);
            }
        }

        return $node;
    }

    public function isImage($_path = null)
    {
        return self::isImageExt(
            is_null($_path) ? $this->getExt() : self::computeExt($_path)
        );
    }
}
