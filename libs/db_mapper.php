<?php

abstract class DbMapper
{
    protected $_db;
    protected $_linksParam = array();
    protected $_files;
    protected $_images;


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


    /*
    *
    * Далее обычно повторяющаяся для большинства объектов часть
    *
    **/

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


    /*
    *
    * Связи
    *
    **/

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
                array_push($result, $item->$key);
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
                $obj->setAttribute($pri, $this->id);

                if (is_array($item)) {
                    $obj->setAttribute($key, $id);

                    foreach ($item as $attribute => $value) {
                        $obj->setAttribute($attribute, $value);
                    }

                } else {
                    $obj->setAttribute($key, $item);
                }

                array_push($this->_links[$_name], $obj);
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


    /*
    *
    * Тэги
    *
    **/

    public function computeTags()
    {
        $objectTypeId = Tag::getTypeId(strtolower(get_class($this)));

        if ($objectTypeId) {
            $ids = Db::get()->getList('
                SELECT
                    t.' . Tag::getPri() . '
                FROM
                    ' . DB_PREFIX . 'tag_to_object AS l,
                    ' . Tag::getTbl() . ' AS t
                WHERE
                    l.object_type_id = ' . get_db_data($objectTypeId) . ' AND
                    l.object_id = ' . get_db_data($this->getId()) . ' AND
                    l.' . Tag::getPri() . ' = t.' . Tag::getPri() . '
            ');

            if (0 < count($ids)) {
                return Tag::getList(array(Tag::getPri() => $ids));
            }
        }

        return array();
    }

    public function updateTags(array $_ids)
    {
        $objectTypeId = Tag::getTypeId(strtolower(get_class($this)));
        if ($objectTypeId) {
            Db::get()->execute('
                DELETE FROM ' . DB_PREFIX . 'tag_to_object
                WHERE object_type_id = ' . get_db_data($objectTypeId) . ' AND
                object_id = ' . get_db_data($this->getId())
            );

            foreach ($_ids as $id) {
                $fields = DB::get()->getQueryFields(array('object_type_id' => $objectTypeId,
                                                          'object_id' => $this->getId(),
                                                          Tag::getPri() => $id), 'insert');

                Db::get()->execute('INSERT INTO ' . DB_PREFIX . 'tag_to_object' . $fields);
            }
        }
    }

    public function getTags($_compute = false)
    {
        if (is_null($this->_tags) || $_compute) {
            $this->_tags = $this->computeTags();
        }

        return $this->_tags;
    }

    public function getTagIds()
    {
        return array_keys($this->getTags());
    }

    public function getTagsXml()
    {
        if ($this->getTags()) {
            $xml = '<tags>';

            foreach ($this->getTags() as $item) {
                $xml .= $item->getXml();
            }

            $xml .= '</tags>';
            return $xml;
        }

        return false;
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
        if (property_exists($this, '_tags')) {
            $this->updateTags(array());
        }

        foreach ($this->getFiles() as $file) {
            $file->delete();
        }

        if (
            method_exists($this, 'getFilePath') &&
            is_directory_empty($this->getFilePath())
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

    public function uploadFile($name, $tmpName)
    {
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
                    if (
                        $item != '.' &&
                        $item != '..' &&
                        is_file($this->getFilePath() . $item)
                    ) {
                        $file = new File($this->getFilePath() . $item, DOCUMENT_ROOT, '/');
                        $this->_files[strtolower($file->getFileName())] = $file;
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
            if ($this->getFiles()) {
                foreach ($this->getFiles() as $file) {
                    if (Image::isImageExtension($file->getExtension())) {
                        $image = new Image(
                            $file->getPath(),
                            $file->getPathStartsWith(),
                            $file->getUriStartsWith()
                        );
                        $this->_images[strtolower($image->getFileName())] = $image;
                    }
                }
            }
        }
        return $this->_images;
    }

    public function getIlluByFileName($filename)
    {
        $files = $this->getImages();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getIlluByName($name)
    {
        foreach ($this->getImages() as $file) {
            if ($name == $file->getName()) {
                return $file;
            }
        }
        return false;
    }

    public function getIllu($name)
    {
        return $this->getIlluByName($name);
    }

    public function getFileByFileName($filename)
    {
        $files = $this->getFiles();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getFileByName($name)
    {
        foreach ($this->getFiles() as $file) {
            if ($name == $file->getName()) {
                return $file;
            }
        }
        return false;
    }

    public function getFile($name)
    {
        return $this->getFileByName($name);
    }
}
