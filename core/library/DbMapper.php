<?php

abstract class Core_DbMapper
{
    /**
     * @var App_ActiveRecordExtended
     */
    protected $_db;

    protected $_linksParam = array();
    protected $_files;
    protected $_images;

    public function getXml($_node = null, $_xml = null, array $_attrs = array())
    {
        return Ext_Xml::node(empty($_node) ? 'item' : $_node, $_xml, $_attrs);
    }

    public function save()
    {
        if ($this->getId()) return $this->update();
        else                return $this->create();
    }

    public function truncate()
    {
        return App_Db::get()->execute('TRUNCATE `' . $this->getDb()->getPri() . '`');
    }


    /**
    *
    * Далее обычно повторяющаяся для большинства объектов часть
    *
    */

    public static function getDbClassName($_class = null)
    {
        return get_called_class() . 'Db';
    }

    public function __construct($_obj = null)
    {
        $class = $this->getDbClassName();
        $this->_db = is_null($_obj) ? new $class : $_obj;
    }

    public static function getList($_conds = array(), $_params = array(), $_rowConds = array())
    {
        $class = get_called_class();
        $dbClass = self::getDbClassName();
        $result = array();

        foreach (
            call_user_func_array($dbClass . '::getList', array($_conds, $_params, $_rowConds)) as
            $id => $item
        ) {
            $result[$id] = new $class($item);
        }

        return $result;
    }

    public static function getCount($_conds = array(), $_rowConds = array())
    {
        return call_user_func_array(self::getDbClassName() . '::getCount', array($_conds, $_rowConds));
    }

    public static function getById($_id)
    {
        return self::getBy($_id);
    }

    public static function getBy()
    {
        $args = func_get_args();
        $class = get_called_class();
        $obj = call_user_func_array(self::getDbClassName() . '::load', $args);
        return false !== $obj ? new $class($obj) : false;
    }


    /**
     *
     * Связи
     *
     */

    public function getLinks($_name, $_isPublished = null)
    {
        if (!isset($this->_links[$_name])) {
            $conds = array(
                call_user_func(array($this->getDbClassName(), 'getPri')) =>
                $this->getId()
            );

            if (!is_null($_isPublished)) {
                $conds['is_published'] = $_isPublished;
            }

            if (isset($this->_linksParam[$_name])) {
                $this->_links[$_name] = call_user_func_array(
                    array($this->_linksParam[$_name], 'getList'),
                    array($conds)
                );

            } else {
                $this->_links[$_name] = array();
            }
        }

        return $this->_links[$_name];
    }

    public function getLinkIds($_name, $_isPublished = null)
    {
        $result = array();

        if (isset($this->_linksParam[$_name])) {
            $class = $this->_linksParam[$_name];
            $keys = array(call_user_func(array($class, 'getFirstKey')),
                          call_user_func(array($class, 'getSecondKey')));

            $pri = call_user_func(array($this->getDbClassName(), 'getPri'));
            $key = $pri == $keys[0] ? $keys[1] : $keys[0];

            foreach ($this->getLinks($_name, $_isPublished) as $item) {
                $result[] = $item->$key;
            }
        }

        return $result;
    }

    public function setLinks($_name, $_value = null)
    {
        $this->_links[$_name] = array();

        if (isset($this->_linksParam[$_name]) && !empty($_value)) {
            $values = is_array($_value) ? $_value : array($_value);
            $class = $this->_linksParam[$_name];
            $keys = array(call_user_func(array($class, 'getFirstKey')),
                          call_user_func(array($class, 'getSecondKey')));

            $pri = call_user_func(array($this->getDbClassName(), 'getPri'));
            $key = $pri == $keys[0] ? $keys[1] : $keys[0];

            foreach ($values as $id => $item) {
                $obj = new $class;
                $obj->$pri = $this->id;

                if (is_array($item)) {
                    $obj->$key = $id;

                    foreach ($item as $attribute => $value) {
                        $obj->$attribute = $value;
                    }

                } else {
                    $obj->$key = $item;
                }

                $this->_links[$_name][] = $obj;
            }
        }
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


    /**
     *
     * Далее нужен рефакторинг
     *
     */

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

    public function getPrimaryKey()
    {
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
                if (in_array($name, $this->_db->getAttrNames())) {
                    return $name;

                } elseif (in_array($attribute, $this->_db->getAttrNames())) {
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
        return $this->_db->__get($name);
    }

    public function __set($name, $value) {
        return $this->_db->__set($name, $value);
    }

    public function getAttribute($name) {
        return $this->__get($name);
    }

    public function hasAttribute($name) {
        return $this->_db->hasAttribute($name);
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
        if (property_exists($this, '_tags')) {
            $this->updateTags(array());
        }

        foreach ($this->getFiles() as $file) {
            $file->delete();
        }

        if (
            method_exists($this, 'getFilePath') &&
            Ext_File::isDirEmpty($this->getFilePath())
        ) {
            rmdir($this->getFilePath());
        }

        if (property_exists($this, '_links')) {
            foreach (array_keys($this->_links) as $item) {
                $this->updateLinks($item);
            }
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


    /**
     *
     * Работа с файлами объекта.
     *
     */

    public function uploadFile($_filename, $_tmpName, $_newName = null)
    {
        $filename = is_null($_newName)
                  ? Ext_File::normalizeName($_filename)
                  : $_newName . '.' . Ext_File::computeExt($_filename);

        $path = $this->getFilePath() . $filename;

        Ext_File::deleteFile($path);
        Ext_File::createDir($this->getFilePath());

        move_uploaded_file($_tmpName, $path);
        @chmod($path, 0777);

        Ext_File_Cache::delete($path);
    }

    public function cleanFileCache()
    {
        foreach ($this->getFiles() as $file) {
            Ext_File_Cache::delete($file->getPath());
        }
    }

    public function resetFiles()
    {
        $this->_files = null;
        $this->_images = null;
    }

    public function getFiles()
    {
        if (is_null($this->_files)) {
            $this->_files = array();

            if (
                method_exists($this, 'getFilePath') &&
                $this->getFilePath() &&
                is_dir($this->getFilePath())
            ) {
                $handle = opendir($this->getFilePath());

                while (false !== $item = readdir($handle)) {
                    $filePath = $this->getFilePath() . '/' . $item;

                    if ($item{0} != '.' && is_file($filePath)) {
                        $file = App_File::factory($filePath);

                        $this->_files[
                            Ext_String::toLower($file->getFileName())
                        ] = $file;
                    }
                }

                closedir($handle);
            }
        }

        return $this->_files;
    }

    public function getImages()
    {
        if (is_null($this->_images)) {
            $this->_images = array();

            foreach ($this->getFiles() as $key => $file) {
                if ($file->isImage()) {
                    $this->_images[$key] = $file;
                }
            }
        }

        return $this->_images;
    }

    public function getIlluByFileName($_filename)
    {
        $files = $this->getImages();

        return $files && key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getIlluByName($_name)
    {
        foreach ($this->getImages() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getIllu($_name)
    {
        $illu = $this->getIlluByName($_name);

        if (!$illu) {
            $illu = $this->getIlluByFileName($_name);
        }

        return $illu;
    }

    public function getFileByFileName($_filename)
    {
        $files = $this->getFiles();

        return $files && key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getFileByName($_name)
    {
        foreach ($this->getFiles() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getFile($_name)
    {
        $file = $this->getFileByName($_name);

        if (!$file) {
            $file = $this->getFileByFileName($_name);
        }

        return $file;
    }
}
