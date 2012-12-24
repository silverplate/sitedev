<?php

abstract class Core_ActiveRecord
{
    /**
     * @var string
     */
    protected $_table;

    /**
     * @var array[Core_ActiveRecord_Attribute]
     */
    protected $_attributes;

    /**
     * @var array[array]
     */
    protected $_links = array();

    public function __construct($_table)
    {
        $this->_table = $_table;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function hasAttribute($_name)
    {
        return key_exists($_name, $this->_attributes) ||
               key_exists(Ext_String::underline($_name), $this->_attributes);
    }

    public function __isset($_name)
    {
        return property_exists($this, $_name) || $this->hasAttribute($_name);
    }

    /**
     * Преобразоваывает и ищет атрибут, чтобы вернут его название. Имя id
     * преобразовывается в первичный ключ, *_id преобразовывается в первичный
     * ключ внешней таблицы.
     *
     * @param string $_name
     * @return string|array|false
     */
    public function getAttributeName($_name)
    {
        if (!property_exists($this, $_name)) {
            if (key_exists($_name, $this->_attributes)) {
                return $_name;
            }

            if ($_name == 'id') {
                return $this->getPrimaryKey()->getName();
            }

            $name = Ext_String::underline($_name);

            if (key_exists($name, $this->_attributes)) {
                return $name;
            }

            if (defined(DB_PREFIX) && DB_PREFIX) {
                $name = DB_PREFIX . $name;

                if (key_exists($name, $this->_attributes)) {
                    return $name;
                }
            }
        }

        return false;
    }

    public function __get($_name)
    {
        if (property_exists($this, $_name)) {
            return $this->$_name;
        }

        return $this->getAttrValue($_name);
    }

    public function __set($_name, $_value)
    {
        if (property_exists($this, $_name)) {
            $this->$_name = $_value;
        }

        $this->setAttrValue($_name, $_value);
    }

    /**
     * @param string $_name
     * @throws Exception
     * @return Core_ActiveRecord_Attribute
     */
    public function getAttr($_name)
    {
        $name = $this->getAttributeName($_name);
        if ($name) {
            return $this->_attributes[$name];
        }

        throw new Exception("There is no a such property `$_name`.");
    }

    public function getAttrValue($_name)
    {
        return $this->getAttr($_name)->getValue();
    }

    public function setAttrValue($_name, $_value)
    {
        $this->getAttr($_name)->setValue($_value);
    }

    public function toArray()
    {
        $attrs = array();

        foreach ($this->_attributes as $attr) {
            $attrs[$attr->getName()] = $attr->getValue();
        }

        return $attrs;
    }

    public function getAttrNames($_table = false)
    {
        $names = array_keys($this->_attributes);

        if ($_table) {
            $p = '`' . ($_table === true ? $this->getTable() : $_table) . '`.';

             foreach ($names as $key => $value) {
                 $names[$value] = $p . $value;
             }
        }

        return $names;
    }

    public function addAttribute($_name,
                                 $_type,
                                 $_length = null,
                                 $_isPrimary = false,
                                 $_isUnique = false) {
        $this->_attributes[$_name] = new App_ActiveRecord_Attribute(
            $_name, $_type, $_length, $_isPrimary, $_isUnique
        );
    }

    public function addForeignKey(App_ActiveRecord $_obj, $_isPrimary = false)
    {
        $key = $_obj->getPrimaryKey();
        $this->addAttribute($key->getName(),
                            $key->getType(),
                            $key->getLength(),
                            $_isPrimary);
    }

    public function addPrimaryKey($_name, $_type, $_length = null)
    {
        return $this->addAttribute($_name, $_type, $_length, true);
    }

    public function getPrimaryKey()
    {
        $keys = array();

        foreach ($this->_attributes as $item) {
            if ($item->isPrimary()) {
                $keys[] = $item;
            }
        }

        if (count($keys) == 0) {
            return false;

        } elseif (count($keys) == 1) {
            return $keys[0];

        } else {
            return $keys;
        }
    }

    public function getPrimary($_is_table = false)
    {
        $keys = $this->getPrimaryKey();

        if ($keys) {
            $table = $_is_table ? $this->getTable() . '.' : '';

            if (is_array($keys)) {
                $names = array();

                foreach ($keys as $key) {
                    $names[] = $table . $key->getName();
                }

                if ($names) return $names;

            } else {
                return $table . $keys->getName();
            }
        }

        return false;
    }

    public function getPrimaryKeyStatment($_value = null)
    {
        $key = $this->getPrimaryKey();
        $conditions = array();

        if ($key) {
            if (is_array($key)) {
                for ($i = 0; $i < count($key); $i++) {
                    if ($_value && is_array($_value)) {
                        if (isset($_value[$key[$i]->getName()])) {
                            $value = App_Db::escape($_value[$key[$i]->getName()]);

                        } else if (isset($_value[$i])) {
                            $value = App_Db::escape($_value[$i]);

                        } else {
                            $value = $key[$i]->getSqlValue();
                        }

                    } else {
                        $value = $key[$i]->getSqlValue();
                    }

                    $conditions[] = $key[$i]->getName() . ' = ' . $value;
                }

            } else {
                $value = is_null($_value) ? $key->getSqlValue() : App_Db::escape($_value);
                $conditions[] = $key->getName() . ' = ' . $value;
            }
        }

        return ($conditions) ? implode(' AND ', $conditions) : false;
    }

    public function getDbId()
    {
        return App_Db::escape($this->getId());
    }

    public function getId()
    {
        $key = $this->getPrimaryKey();
        $value = false;

        if (is_array($key)) {
            $value = array();

            foreach ($key as $item) {
                $value[] = array($item->getName() => $item->getValue());
            }

        } else {
            $value = $key->getValue(false);
        }

        return $value;
    }

    public function setId($_value)
    {
        $key = $this->getPrimaryKey();

        if (is_array($key)) {
            if ($_value && is_array($_value)) {
                for ($i = 0; $i < count($key); $i++) {
                    if (isset($_value[$key[$i]->getName()])) {
                        $key[$i]->setValue($_value[$key[$i]->getName()]);

                    } else if (isset($_value[$i])) {
                        $key[$i]->setValue($_value[$i]);
                    }
                }
            }

        } else if ($_value) {
            $key->setValue($_value);
        }
    }

    public function getTitle()
    {
        if (isset($this->title) && $this->title) {
            return $this->title;

        } else if (isset($this->name) && $this->name) {
            return $this->name;

        } else {
            return 'ID ' . $this->getId();
        }
    }

    public function getDate($_name)
    {
        $value = $this->$_name;
        return ($value && $value != '0000-00-00' && $value != '0000-00-00 00:00:00') ? strtotime($value) : false;
    }

    public function setDate($_name, $_date)
    {
        $format = isset($this->_attributes[$_name]) && $this->_attributes[$_name]->getType() == 'datetime' ? 'Y-m-d H:i:s' : 'Y-m-d';
        $this->$_name = date($format, $_date);
    }

    public static function load($_className, $_value, $_attribute = null)
    {
        $obj = new $_className;
        return $obj->fetch($_value, $_attribute) ? $obj : false;
    }

    public function fetch($_value, $_attr = null)
    {
        $condition = $_attr
                   ? $_attr . ' = ' . App_Db::escape($_value)
                   : $this->getPrimaryKeyStatment($_value);

        $data = App_Db::get()->getEntry(
            'SELECT * FROM ' . $this->getTable() .
            ' WHERE ' . $condition
        );

        if ($data) {
            $this->fillWithData($data);

            return true;

        } else {
            return false;
        }
    }

    public function fillWithData(array $_data)
    {
        foreach ($this->_attributes as $item) {
            if (key_exists($item->getName(), $_data)) {
                $item->setValue($_data[$item->getName()]);
            }
        }
    }

    public function create()
    {
        $t = $this->getTable();
        $attributes = array();

        foreach ($this->_attributes as $item) {
            if (!$item->isValue()) {
                if ($item->isPrimary()) {
                    if ($item->getType() == 'string') {
                        $item->setValue(App_Db::get()->getUnique($t, $item->getName(), $item->getLength()));
                    }

                } else if ($item->getName() == 'sort_order') {
                    $item->setValue(App_Db::get()->getNextNumber($t, $item->getName()));

                } else if (
                    $item->getName() == 'creation_date' ||
                    $item->getName() == 'creation_time'
                ) {
                    $item->setValue(
                        $item->getType() == 'integer' ? time() : date('Y-m-d H:i:s')
                    );
                }
            }

            $attributes[$item->getName()] = $item->getSqlValue();
        }

        $result = App_Db::get()->execute(
            'INSERT INTO ' . $t .
            App_Db::get()->getQueryFields($attributes, 'insert', true)
        );

        if ($result && App_Db::get()->getLastInsertedId()) {
            $this->id = App_Db::get()->getLastInsertedId();
        }

        return $result;
    }

    public function update(array $_fields = null)
    {
        $fields = empty($_fields)
                ? App_Db::get()->getQueryFields($this->toArray(), 'update')
                : App_Db::get()->getQueryFields($_fields, 'update');

        return App_Db::get()->execute(
            'UPDATE ' . $this->getTable() .
            $fields .
            'WHERE ' . $this->getPrimaryKeyStatment()
        );
    }

    public function updateAttribute($_name, $_value = null)
    {
        if ($this->_attributes[$_name]) {
            $primary_key_condition = $this->getPrimaryKeyStatment();
            if (!is_null($_value)) $this->$_name = $_value;

            return App_Db::get()->execute(
                'UPDATE ' . $this->getTable() .
                App_Db::get()->getQueryFields(array($this->_attributes[$_name]->getName() => $this->_attributes[$_name]->getSqlValue()), 'update', true) .
                'WHERE ' . $primary_key_condition
            );

        } else {
            return false;
        }
    }

    public function delete()
    {
        foreach (array_keys($this->_links) as $item) {
            $this->updateLinks($item);
        }

        if ($this->getImages()) {
            foreach (array_keys($this->getImages()) as $item) {
                if ($this->getIllu($item)) {
                    $this->getIllu($item)->delete();
                }
            }

            if (Ext_File::isDirEmpty($this->getImagePath())) {
                rmdir($this->getImagePath());
            }
        }

        return App_Db::get()->execute('DELETE FROM ' . $this->getTable() . ' WHERE ' . $this->getPrimaryKeyStatment());
    }

    public static function tableInit($_table, $_id = null, $_is_log = false)
    {
        $class_name = get_called_class();
        $obj = new $class_name($_table);

        if ($_is_log) {
            $log_file = LIBRARIES . Ext_File::computeName(__FILE__) . '.txt';
            Ext_File::write($log_file, $_table . PHP_EOL . PHP_EOL);
            Ext_File::write($log_file, 'self::$Base = new App_ActiveRecord(self::TABLE);' . PHP_EOL);
        }

        $attributes = App_Db::get()->getList('SHOW COLUMNS FROM ' . $_table);
        foreach ($attributes as $item) {
            $length = null;

            if ($item['Type'] == 'tinyint(1)') {
                $type = 'boolean';

            } elseif (preg_match('/^([a-zA-Z]+)\((.+)\)$/', $item['Type'], $match)) {
                $type = $match[1];
                $length = $match[2];

            } else {
                $type = $item['Type'];
            }

            if ($_is_log) {
                Ext_File::write($log_file, 'self::$_base->addAttribute(\'' . $item['Field'] . '\', \'' . $type . '\', ' . (($length) ? $length : 'null') . ', ' . ((strpos($item['Key'], 'PRI') !== false) ? 'true' : 'false') . ', ' . ((strpos($item['Key'], 'UNI') !== false) ? 'true' : 'false') . ');' . "\r", 'append');
            }

            $obj->addAttribute($item['Field'], $type, $length, (strpos($item['Key'], 'PRI') !== false), (strpos($item['Key'], 'UNI') !== false), null);
        }

        if ($_is_log) {
            Ext_File::write($log_file, "\r", 'append');
        }

        if ($_id) {
            $obj->retrieve($_id);
        }

        return $obj;
    }

    public static function getSortAttribute($_tables, $_attributes)
    {
        $try_attributes = array('sort_order', 'title', 'name');

        foreach ($try_attributes as $attribute) {
            if (in_array($attribute, $_attributes)) {
                return $attribute;

            } elseif (is_array($_tables)) {
                foreach ($_tables as $table) {
                    if (in_array($table . '.' . $attribute, $_attributes)) {
                        return $table . '.' . $attribute;
                    }
                }

            } elseif ($_tables && in_array($_tables . '.' . $attribute, $_attributes)) {
                return $_tables . '.' . $attribute;
            }
        }

        return false;
    }

    public static function massDelete($_table, $_conditions = null)
    {
        if ($_conditions) {
            App_Db::get()->execute(
                'DELETE FROM ' . $_table .
                ' WHERE ' . implode(' AND ', App_Db::get()->getWhere($_conditions))
            );

        } else {
            App_Db::get()->execute('TRUNCATE ' . $_table);
        }
    }

    public static function getList($_class, $_tables, $_attributes, $_conditions = array(), $_parameters = array(), $_row = array())
    {
        $result = array();
        $tables = (is_array($_tables)) ? implode(', ', $_tables) : $_tables;

        $sortOrder = isset($_parameters['sort_order'])
                   ? $_parameters['sort_order']
                   : self::getSortAttribute($_tables, $_attributes);

        if ($sortOrder) {
            $sortOrder = ' ORDER BY ' . $sortOrder;
        }

        $limit = '';
        if (isset($_parameters['count'])) {
            $limit .= ' LIMIT ' . (int) $_parameters['count'];
        }

        if (isset($_parameters['offset'])) {
            $limit .= ' OFFSET ' . (int) $_parameters['offset'];
        }

        if ($_row && is_array($_row)) $conditions = $_row;
        else if ($_row)               $conditions = array($_row);
        else                          $conditions = array();

        if ($_conditions) {
            $conditions = array_merge($conditions, App_Db::get()->getWhere($_conditions));
        }

        $condition = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $list = App_Db::get()->getList('SELECT ' . implode(', ', $_attributes) .
                                       ' FROM ' . $tables .
                                       $condition .
                                       $sortOrder .
                                       $limit, 'few');

        if ($list) {
            foreach ($list as $item) {
                $obj = new $_class;
                $obj->fillWithData($item);

                if (is_array($obj->getId())) {
                    $result[] = $obj;

                } else {
                    $result[$obj->getId()] = $obj;
                }
            }
        }

        return $result;
    }

    public static function isUnique($_class, $_table, $_pk, $_attribute, $_value, $_exclude = null)
    {
        return !(self::getList(
            $_class,
            $_table,
            array($_pk),
            array($_attribute => $_value),
            array('count' => 1),
            is_null($_exclude) ? null : array($_pk . ' != ' . App_Db::escape($_exclude))
        ));
    }

    public static function getCount($_class, $_tables, $_conditions = array(), $_row_conditions = array())
    {
        $class_obj = new $_class;
        $tables = (is_array($_tables)) ? implode(', ', $_tables) : $_tables;

        if ($_row_conditions && is_array($_row_conditions)) {
            $conditions = $_row_conditions;
        } elseif ($_row_conditions) {
            $conditions = array($_row_conditions);
        } else {
            $conditions = array();
        }

        if ($_conditions) {
            $conditions = array_merge($conditions, self::getQueryCondition($_conditions));
        }

        $condition = ($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $result = App_Db::get()->getEntry('SELECT COUNT(' . $class_obj->getPrimary(true) . ') AS count FROM ' . $tables . $condition);

        return ($result) ? (int) $result['count'] : 0;
    }

    public function updateLinks($_name, $_value = null)
    {
        if ($this->getLinks($_name)) {
            foreach ($this->getLinks($_name) as $item) {
                $item->delete();
            }

            $this->setLinks($_name);
        }

        if (!is_null($_value)) {
            $this->setLinks($_name, $_value);

            if ($this->getLinks($_name)) {
                foreach ($this->getLinks($_name) as $item) {
                    $item->create();
                }
            }
        }
    }

    public function getXml($_type = null, $_node_name = null, $_append_xml = null, $_append_attributes = null)
    {
        $node_name = ($_node_name) ? $_node_name : 'item';
        $result = '';

        switch ($_type) {
            case 'list':
                $result .= '<' . $node_name . ' id="' . $this->getId() . '"';

                if ($_append_attributes) {
                    foreach ($_append_attributes as $name => $value) {
                        $result .= ' ' . $name . '="' . $value . '"';
                    }
                }

                $result .= '>';

                if ($_append_xml) {
                    $result .= '<title><![CDATA[' . $this->getTitle() . ']]></title>';

                    if (is_array($_append_xml)) {
                        foreach ($_append_xml as $key => $value) {
                            $result .= preg_match('/^[a-z_]+$/', $key)
                                ? "<{$key}><![CDATA[{$value}]]></{$key}>"
                                : '<item key="' . $key . '"><![CDATA[' . $value . ']]></item>';
                        }
                    } else {
                        $result .= $_append_xml;
                    }

                } else {
                    $result .= '<![CDATA[' . $this->getTitle() . ']]>';
                }

                $result .= '</' . $node_name . '>';
                break;

            case 'simple':
            default:
                $result .= '<' . $node_name . ' id="' . $this->getId() . '"';

                if ($_append_attributes) {
                    foreach ($_append_attributes as $name => $value) {
                        $result .= ' ' . $name . '="' . $value . '"';
                    }
                }

                $result .= '>';

                if ($this->getTitle() != '_без названия') {
                    $result .= '<title><![CDATA[' . $this->getTitle() . ']]></title>';
                }

                if ($_append_xml) {
                    if (is_array($_append_xml)) {
                        foreach ($_append_xml as $key => $value) {
                            $result .= preg_match('/^[a-z_]+$/', $key)
                                ? "<{$key}><![CDATA[{$value}]]></{$key}>"
                                : '<item key="' . $key . '"><![CDATA[' . $value . ']]></item>';
                        }
                    } else {
                        $result .= $_append_xml;
                    }
                }

                $result .= '</' . $node_name . '>';
                break;
        }

        return $result;
    }

    public function getFiles()
    {
        if (property_exists($this, '_files')) {
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

        return array();
    }

    public function getImages()
    {
        if (property_exists($this, '_images')) {
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

        return array();
    }

    public function getIllu($filename)
    {
        $files = $this->getImages();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getIlluByName($name)
    {
        foreach ($this->getImages() as $file) {
            if ($name == $file->getName() || $name == $file->getFileName()) {
                return $file;
            }
        }
        return false;
    }

    public function getFile($filename)
    {
        $files = $this->getFiles();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getFileByName($name)
    {
        foreach ($this->getFiles() as $file) {
            if ($name == $file->getName() || $name == $file->getFileName()) {
                return $file;
            }
        }
        return false;
    }

    public function cleanFileCache()
    {
        foreach ($this->getFiles() as $file) {
            Ext_File_Cache::delete($file->getPath());
        }
    }
}
