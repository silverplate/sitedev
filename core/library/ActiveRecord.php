<?php

abstract class Core_ActiveRecord
{
    protected $Table;
    protected $Attributes = array();
    protected $Links = array();
    protected $ForeignKeys = array();

    public function __construct($_table) {
        $this->Table = $_table;
    }

    public function __get($_attribute) {
        $fk_class_name = $fk_class_pk = '';
        if (method_exists($this, 'GetBase')) {
            for ($i = 0; $i < strlen($_attribute); $i++) {
                if ($i == 0 || $_attribute{$i} == '_') {
                    if ($_attribute{$i} == '_' && $i + 1 < strlen($_attribute)) $i++;
                    $fk_class_name .= strtoupper($_attribute{$i});
                } else {
                    $fk_class_name .= $_attribute{$i};
                }
            }
            if (method_exists($fk_class_name, 'GetPri')) $fk_class_pk = call_user_func(array($fk_class_name, 'GetPri'));
        }

        if ($fk_class_pk && in_array($fk_class_pk, array_keys($this->GetBase()->ForeignKeys))) {
            if (!isset($this->ForeignKeys[$fk_class_pk]) || is_null($this->ForeignKeys[$fk_class_pk])) {
                $this->ForeignKeys[$fk_class_pk] = isset($this->Attributes[$fk_class_pk]) && $this->Attributes[$fk_class_pk]->GetValue(false)
                    ? call_user_func(array($fk_class_name, 'Load'), $this->Attributes[$fk_class_pk]->GetValue(false))
                    : false;
            }
            return $this->ForeignKeys[$fk_class_pk];

        } elseif (isset($this->Attributes[$_attribute])) {
            return $this->Attributes[$_attribute]->GetValue(false);

        } else {
            throw new Exception('call to undefined class member: ' . get_class($this) . '::' . $_attribute);
        }
    }

    public function __set($_attribute, $_value) {
        $fk_class_name = $fk_class_pk = '';
        if (method_exists($this, 'GetBase')) {
            for ($i = 0; $i < strlen($_attribute); $i++) {
                if ($i == 0 || $_attribute{$i} == '_') {
                    if ($_attribute{$i} == '_' && $i + 1 < strlen($_attribute)) $i++;
                    $fk_class_name .= strtoupper($_attribute{$i});
                } else {
                    $fk_class_name .= $_attribute{$i};
                }
            }
            if (method_exists($fk_class_name, 'GetPri')) $fk_class_pk = call_user_func(array($fk_class_name, 'GetPri'));
        }

        if ($fk_class_pk && in_array($fk_class_pk, array_keys($this->GetBase()->ForeignKeys))) {
            if ($_value instanceof $fk_class_name) {
                $this->Attributes[$fk_class_pk]->SetValue($_value->GetId());
                $this->ForeignKeys[$fk_class_pk] = $_value;
                return true;

            } else {
                throw new Exception($_attribute . ' must be instance of ' . $fk_class_name);

            }

        } elseif (isset($this->Attributes[$_attribute])) {
            $this->Attributes[$_attribute]->SetValue($_value);
            return true;

        } else {
            throw new Exception('call to undefined class member: ' . get_called_class() . '::' . $_attribute);

        }
    }

    public function __isset($_attribute) {
        $fk_class_name = $fk_class_pk = '';
        if (method_exists($this, 'GetBase')) {
            for ($i = 0; $i < strlen($_attribute); $i++) {
                if ($i == 0 || $_attribute{$i} == '_') {
                    if ($_attribute{$i} == '_' && $i + 1 < strlen($_attribute)) $i++;
                    $fk_class_name .= strtoupper($_attribute{$i});
                } else {
                    $fk_class_name .= $_attribute{$i};
                }
            }
            if (method_exists($fk_class_name, 'GetPri')) $fk_class_pk = call_user_func(array($fk_class_name, 'GetPri'));
        }

        if ($fk_class_pk && in_array($fk_class_pk, array_keys($this->GetBase()->ForeignKeys))) {
            return !is_null($this->ForeignKeys[$fk_class_pk]);

        } else {
            return isset($this->Attributes[$_attribute]);
        }
    }

    public function GetAttributeObject($_attribute) {
        if ($this->HasAttribute($_attribute)) {
            return $this->Attributes[$_attribute];
        } else {
            return false;
        }
    }

    public function HasAttribute($_attribute) {
        return isset($this->Attributes[$_attribute]);
    }

    public function GetAttribute($_attribute, $_is_escaped = false) {
        return isset($this->Attributes[$_attribute])
            ? $this->Attributes[$_attribute]->GetValue($_is_escaped)
            : false;
    }

    public function SetAttribute($_attribute, $_value) {
        if (isset($this->Attributes[$_attribute])) {
            $this->Attributes[$_attribute]->SetValue($_value);
            return true;
        } else {
            return false;
        }
    }

    public static function getQueryCondition($_attributes = array())
    {
        $conditions = array();

        foreach ($_attributes as $attribute => $value) {
            if ($value === 'NULL') {
                array_push($conditions, 'ISNULL(' . $attribute . ')');

//             } else if (!$value) {
//                 array_push($conditions, "$attribute = ''");

            } else if (is_array($value)) {
                array_push($conditions,
                           $attribute . ' IN (' . App_Db::escape($value) . ')');

            } else {
                array_push($conditions,
                           $attribute . ' = ' . App_Db::escape($value));
            }
        }

        return $conditions;
    }

    public function GetTable() {
        return $this->Table;
    }

    public function addAttribute($_name,
                                 $_type,
                                 $_length = null,
                                 $_isPrimary = false,
                                 $_isUnique = false) {
        $this->Attributes[$_name] = new App_ActiveRecord_Attribute(
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

        $this->ForeignKeys[$key->getName()] = null;
    }

    public function addPrimaryKey($_name, $_type, $_length = null)
    {
        return $this->addAttribute($_name, $_type, $_length, true);
    }

    public function getAttributes($_table = false)
    {
        $attributes = array_keys($this->Attributes);
        $prefix = $_table === false
                ? ''
                : '`' . ($_table === true ? $this->getTable() : $_table) . '`.';

        for ($i = 0; $i < count($attributes); $i++) {
            $attributes[$i] = $prefix . $attributes[$i];
        }

        return $attributes;
    }

    public function GetAttributeValues($_is_escaped = false) {
        $attributes = array();

        foreach ($this->Attributes as $item) {
            $attributes[$item->GetName()] = $item->GetValue($_is_escaped);
        }

        return $attributes;
    }

    public function GetPrimaryKey() {
        $keys = array();

        foreach ($this->Attributes as $item) {
            if ($item->IsPrimary()) {
                array_push($keys, $item);
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

    public function getPrimary($_is_table = false) {
        $keys = $this->GetPrimaryKey();

        if ($keys) {
            $table = $_is_table ? $this->GetTable() . '.' : '';

            if (is_array($keys)) {
                $names = array();
                foreach ($keys as $key) {
                    array_push($names, $table . $key->GetName());
                }
                if ($names) return $names;

            } else {
                return $table . $keys->GetName();
            }
        }

        return false;
    }

    public function GetPrimaryKeyStatment($_value = null) {
        $key = $this->GetPrimaryKey();
        $conditions = array();

        if ($key) {
            if (is_array($key)) {
                for ($i = 0; $i < count($key); $i++) {
                    if ($_value && is_array($_value)) {
                        if (isset($_value[$key[$i]->GetName()])) {
                            $value = App_Db::escape($_value[$key[$i]->GetName()]);
                        } elseif (isset($_value[$i])) {
                            $value = App_Db::escape($_value[$i]);
                        } else {
                            $value = $key[$i]->GetValue();
                        }
                    } else {
                        $value = $key[$i]->GetValue();
                    }

                    array_push($conditions, $key[$i]->GetName() . ' = ' . $value);
                }
            } else {
                $value = ($_value) ? App_Db::escape($_value) : $key->GetValue();
                array_push($conditions, $key->GetName() . ' = ' . $value);
            }
        }

        return ($conditions) ? implode(' AND ', $conditions) : false;
    }

    public function getDbId()
    {
        return App_Db::escape($this->getId());
    }

    public function GetId() {
        $key = $this->GetPrimaryKey();
        $value = false;

        if (is_array($key)) {
            $value = array();
            foreach ($key as $item) {
                array_push($value, array($item->GetName() => $item->GetValue(false)));
            }
        } else {
            $value = $key->GetValue(false);
        }

        return $value;
    }

    public function SetId($_value) {
        $key = $this->GetPrimaryKey();

        if (is_array($key)) {
            if ($_value && is_array($_value)) {
                for ($i = 0; $i < count($key); $i++) {
                    if (isset($_value[$key[$i]->GetName()])) {
                        $key[$i]->SetValue($_value[$key[$i]->GetName()]);
                    } elseif (isset($_value[$i])) {
                        $key[$i]->SetValue($_value[$i]);
                    }
                }
            }

        } else if ($_value) {
            $key->SetValue($_value);
        }
    }

    public function GetTitle() {
        if ($this->GetAttribute('title')) {
            return $this->GetAttribute('title');

        } elseif ($this->GetAttribute('name')) {
            return $this->GetAttribute('name');

        } else {
            return 'ID ' . $this->GetId();
        }
    }

    public function GetDate($_name) {
        $value = $this->GetAttribute($_name);
        return ($value && $value != '0000-00-00' && $value != '0000-00-00 00:00:00') ? strtotime($value) : false;
    }

    public function SetDate($_name, $_date) {
        $format = isset($this->Attributes[$_name]) && $this->Attributes[$_name]->GetType() == 'datetime' ? 'Y-m-d H:i:s' : 'Y-m-d';
        $this->SetAttribute($_name, date($format, $_date));
    }

    public static function Load($_class_name, $_value, $_attribute = null) {
        $obj = new $_class_name;
        return ($obj->Retrieve($_value, $_attribute)) ? $obj : false;
    }

    public function Retrieve($_value, $_attribute = null) {
        $condition = (is_null($_attribute) || !in_array($_attribute, $this->GetAttributes()))
            ? $this->GetPrimaryKeyStatment($_value)
            : $_attribute . ' = ' . App_Db::escape($_value);

        $record = App_Db::Get()->GetEntry('SELECT ' . implode(', ', $this->GetAttributes()) . ' FROM ' . $this->GetTable() . ' WHERE ' . $condition);
        if ($record) {
            foreach ($this->Attributes as $item) {
                if (isset($record[$item->GetName()])) {
                    $item->SetValue($record[$item->GetName()]);
                }
            }
            return true;

        } else {
            return false;
        }
    }

    public function Create() {
        $attributes = array();
        foreach ($this->Attributes as $item) {
            if (!$item->IsValue()) {
                if ($item->IsPrimary()) {
                    if ($item->GetType() == 'varchar') {
                        $item->SetValue(App_Db::get()->getUnique($this->GetTable(), $item->GetName(), $item->GetLength()));

//                     } else if ($item->GetType() == 'integer') {
//                         $item->SetValue(App_Db::Get()->GetNextNumber($this->GetTable(), $item->GetName()));
                    }

                } else if ($item->GetName() == 'sort_order') {
                    $item->SetValue(App_Db::Get()->GetNextNumber($this->GetTable(), $item->GetName()));

                } else if (
                    $item->getName() == 'creation_date' ||
                    $item->getName() == 'creation_time'
                ) {
                    $item->setValue($item->getType() == 'integer'
                                    ? time()
                                    : date('Y-m-d H:i:s'));
                }
            }

            $value = null;

            if ($item->IsValue()) {
                $value = $item->GetValue();
            } elseif ('text' == $item->GetType()) {
                $value = '\'\'';
            }

            if (!is_null($value)) {
                $attributes[$item->GetName()] = $value;
            }
        }

        $result = App_Db::Get()->Execute('INSERT INTO ' . $this->GetTable() . App_Db::Get()->GetQueryFields($attributes, 'insert', true));
        if ($result && App_Db::Get()->GetLastInsertedId()) {
            $this->SetId(App_Db::Get()->GetLastInsertedId());
        }
        return $result;
    }

    public function update(array $_fields = null) {
        $fields = empty($_fields)
                ? App_Db::get()->getQueryFields($this->getAttributeValues(true), 'update', true)
                : App_Db::get()->getQueryFields($_fields, 'update');

        return App_Db::get()->execute(
            'UPDATE ' . $this->getTable() .
            $fields .
            'WHERE ' . $this->getPrimaryKeyStatment()
        );
    }

    public function UpdateAttribute($_name, $_value = null) {
        if ($this->Attributes[$_name]) {
            $primary_key_condition = $this->GetPrimaryKeyStatment();
            if (!is_null($_value)) $this->SetAttribute($_name, $_value);
            return App_Db::Get()->Execute('UPDATE ' . $this->GetTable() . App_Db::Get()->GetQueryFields(array($this->Attributes[$_name]->GetName() => $this->Attributes[$_name]->GetValue(true)), 'update', true) . 'WHERE ' . $primary_key_condition);
        } else {
            return false;
        }
    }

    public function Delete() {
        foreach (array_keys($this->Links) as $item) {
            $this->UpdateLinks($item);
        }

        if ($this->GetImages()) {
            foreach (array_keys($this->GetImages()) as $item) {
                if ($this->GetIllu($item)) {
                    $this->GetIllu($item)->Delete();
                }
            }

            if (Ext_File::isDirEmpty($this->getImagePath())) {
                rmdir($this->GetImagePath());
            }
        }

        return App_Db::Get()->Execute('DELETE FROM ' . $this->GetTable() . ' WHERE ' . $this->GetPrimaryKeyStatment());
    }

    public function dataInit($_data)
    {
        foreach ($this->Attributes as $item) {
//             if (isset($_data[$item->GetName()])) {
            if (key_exists($item->getName(), $_data)) {
                $item->setValue($_data[$item->getName()]);
            }
        }
    }

    public static function TableInit($_table, $_id = null, $_is_log = false) {
        $class_name = get_called_class();
        $obj = new $class_name($_table);

        if ($_is_log) {
            $log_file = LIBRARIES . Ext_File::computeName(__FILE__) . '.txt';
            Ext_File::write($log_file, $_table . "\r\r", 'append');
            Ext_File::write($log_file, 'self::$Base = new App_ActiveRecord(self::TABLE);' . "\r", 'append');
        }

        $attributes = App_Db::Get()->GetList('SHOW COLUMNS FROM ' . $_table);
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
                Ext_File::write($log_file, 'self::$Base->AddAttribute(\'' . $item['Field'] . '\', \'' . $type . '\', ' . (($length) ? $length : 'null') . ', ' . ((strpos($item['Key'], 'PRI') !== false) ? 'true' : 'false') . ', ' . ((strpos($item['Key'], 'UNI') !== false) ? 'true' : 'false') . ');' . "\r", 'append');
            }

            $obj->AddAttribute($item['Field'], $type, $length, (strpos($item['Key'], 'PRI') !== false), (strpos($item['Key'], 'UNI') !== false), null);
        }

        if ($_is_log) {
            Ext_File::write($log_file, "\r", 'append');
        }

        if ($_id) {
            $obj->Retrieve($_id);
        }

        return $obj;
    }

    public static function GetSortAttribute($_tables, $_attributes) {
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

    public static function MassDelete($_table, $_conditions = null) {
        if ($_conditions) {
            $conditions = self::GetQueryCondition($_conditions);
            $condition = ($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
            App_Db::Get()->Execute('DELETE FROM ' . $_table . $condition);
        } else {
            App_Db::Get()->Execute('TRUNCATE ' . $_table);
        }
    }

    public static function GetList($_class, $_tables, $_attributes, $_conditions = array(), $_parameters = array(), $_row_conditions = array()) {
        $result = array();
        $tables = (is_array($_tables)) ? implode(', ', $_tables) : $_tables;

        $sort_order = isset($_parameters['sort_order'])
            ? $_parameters['sort_order']
            : self::GetSortAttribute($_tables, $_attributes);

        if ($sort_order) {
            $sort_order = ' ORDER BY ' . $sort_order;
        }

        $limit = '';
        if (isset($_parameters['count'])) {
            $limit .= ' LIMIT ' .  (int) $_parameters['count'];
        }

        if (isset($_parameters['offset'])) {
            $limit .= ' OFFSET ' .  (int) $_parameters['offset'];
        }

        if ($_row_conditions && is_array($_row_conditions)) {
            $conditions = $_row_conditions;
        } elseif ($_row_conditions) {
            $conditions = array($_row_conditions);
        } else {
            $conditions = array();
        }

        if ($_conditions) {
            $conditions = array_merge($conditions, self::GetQueryCondition($_conditions));
        }

        $condition = ($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $list = App_Db::Get()->GetList('SELECT ' . implode(', ', $_attributes) . ' FROM ' . $tables . $condition . $sort_order . $limit, 'few');

        if ($list) {
            foreach ($list as $item) {
                $obj = new $_class;
                //$obj->DataInit($item);

                foreach ($obj->Attributes as $i) {
                    if (isset($item[$i->GetName()])) {
                        $i->SetValue($item[$i->GetName()]);
                    }
                }

                if (is_array($obj->GetId())) {
                    array_push($result, $obj);
                } else {
                    $result[$obj->GetId()] = $obj;
                }
            }
        }

        return $result;
    }

    public static function IsUnique($_class, $_table, $_pk, $_attribute, $_value, $_exclude = null) {
        return !(self::GetList(
            $_class,
            $_table,
            array($_pk),
            array($_attribute => $_value),
            array('count' => 1),
            is_null($_exclude) ? null : array($_pk . ' != ' . App_Db::escape($_exclude))
        ));
    }

    public static function GetCount($_class, $_tables, $_conditions = array(), $_row_conditions = array()) {
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
            $conditions = array_merge($conditions, self::GetQueryCondition($_conditions));
        }

        $condition = ($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $result = App_Db::Get()->GetEntry('SELECT COUNT(' . $class_obj->GetPrimary(true) . ') AS count FROM ' . $tables . $condition);

        return ($result) ? (int) $result['count'] : 0;
    }

    public function UpdateLinks($_name, $_value = null) {
        if ($this->GetLinks($_name)) {
            foreach ($this->GetLinks($_name) as $item) {
                $item->Delete();
            }

            $this->SetLinks($_name);
        }

        if (!is_null($_value)) {
            $this->SetLinks($_name, $_value);

            if ($this->GetLinks($_name)) {
                foreach ($this->GetLinks($_name) as $item) {
                    $item->Create();
                }
            }
        }
    }

    public function GetXml($_type = null, $_node_name = null, $_append_xml = null, $_append_attributes = null) {
        $node_name = ($_node_name) ? $_node_name : 'item';
        $result = '';

        switch ($_type) {
            case 'list':
                $result .= '<' . $node_name . ' id="' . $this->GetId() . '"';

                if ($_append_attributes) {
                    foreach ($_append_attributes as $name => $value) {
                        $result .= ' ' . $name . '="' . $value . '"';
                    }
                }

                $result .= '>';

                if ($_append_xml) {
                    $result .= '<title><![CDATA[' . $this->GetTitle() . ']]></title>';

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
                    $result .= '<![CDATA[' . $this->GetTitle() . ']]>';
                }

                $result .= '</' . $node_name . '>';
                break;

            case 'simple':
            default:
                $result .= '<' . $node_name . ' id="' . $this->GetId() . '"';

                if ($_append_attributes) {
                    foreach ($_append_attributes as $name => $value) {
                        $result .= ' ' . $name . '="' . $value . '"';
                    }
                }

                $result .= '>';

                if ($this->GetTitle() != '_без названия') {
                    $result .= '<title><![CDATA[' . $this->GetTitle() . ']]></title>';
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

    public function getFiles() {
        if (property_exists($this, 'files')) {
            if (is_null($this->files)) {
                $this->files = array();

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

							$this->files[
							    Ext_String::toLower($file->getFileName())
							] = $file;
                        }
                    }

                    closedir($handle);
                }
            }

            return $this->files;
        }

        return array();
    }

    public function getImages() {
        if (property_exists($this, 'images')) {
            if (is_null($this->images)) {
                $this->images = array();

				foreach ($this->getFiles() as $key => $file) {
				    if ($file->isImage()) {
				        $this->images[$key] = $file;
                    }
                }
            }

            return $this->images;
        }

        return array();
    }

    public function getIllu($filename) {
        $files = $this->getImages();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getIlluByName($name) {
        foreach ($this->getImages() as $file) {
            if ($name == $file->getName() || $name == $file->getFileName()) {
                return $file;
            }
        }
        return false;
    }

    public function getFile($filename) {
        $files = $this->getFiles();
        return 0 < count($files) && isset($files[$filename])
            ? $files[$filename]
            : false;
    }

    public function getFileByName($name) {
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
