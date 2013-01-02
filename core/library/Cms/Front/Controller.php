<?php

abstract class Core_Cms_Front_Controller extends App_Model
{
    protected $_originalFilename;

    public function __construct()
    {
        parent::__construct();

        $this->addPrimaryKey('string');
        $this->addAttr('type_id', 'integer');
        $this->addAttr('title', 'string');
        $this->addAttr('filename', 'string');
        $this->addAttr('is_document_main', 'boolean');
        $this->addAttr('is_multiple', 'boolean');
        $this->addAttr('is_published', 'boolean');
    }

    public function getClassName()
    {
        if ($this->type_id == 1) {
            $class = 'App_Cms_Front_Document_Controller_';

        } else if ($this->type_id == 2) {
            $class = 'App_Cms_Front_Data_Controller_';

        } else {
            throw new Exception('Unkown controller type');
        }

        return $class . Ext_File::computeName($this->filename);
    }

    public static function getPathByType($_id)
    {
        switch ($_id) {
            case 1: return DOCUMENT_CONTROLLERS;
            case 2: return DATA_CONTROLLERS;
        }

        return false;
    }

    public function getFolder()
    {
        return self::getPathByType($this->type_id);
    }

    public function getFilename()
    {
        return $this->getFolder() && $this->filename
             ? $this->getFolder() . $this->filename
             : false;
    }

    public function getContent()
    {
        return $this->getFilename() && is_file($this->getFilename())
             ? file_get_contents($this->getFilename())
             : false;
    }

    public function checkUnique()
    {
        $where = array(
            'type_id' => $this->typeId,
            'filename' => $this->filename
        );

        if ($this->id) {
            $where[] = $this->getPrimaryKeyWhereNot();
        }

        return 0 == count(self::getList($where, array('limit' => 1)));
    }

    public function getBackOfficeXml($_xml = array(), $_attrs = array())
    {
        $attrs = $_attrs;

        if ($this->typeId == 1)      $attrs['prefix'] = 'с';
        else if ($this->typeId == 2) $attrs['prefix'] = 'б';

        return parent::getBackOfficeXml($_xml, $attrs);
    }

    public function delete()
    {
//         App_Db::get()->execute(
//             'UPDATE ' . App_Cms_Front_Document::getTbl() .
//             ' SET ' . $this->getPrimaryKeyName() . ' = NULL WHERE ' . $this->getPrimaryKeyWhere()
//         );

//         App_Db::get()->execute(
//             'UPDATE ' . App_Cms_Front_Data::getTbl() .
//             ' SET ' . $this->getPrimaryKeyName() . ' = NULL WHERE ' . $this->getPrimaryKeyWhere()
//         );

        Ext_File::deleteFile($this->getFilename());

        return parent::delete();
    }

    public function fillWithData(array $_data)
    {
        parent::fillWithData($_data);

        if (!isset($this->_originalFilename)) {
            $this->_originalFilename = $this->filename;
        }
    }

    public function update()
    {
        if ($this->filename != $this->_originalFilename) {
            Ext_File::moveFile(
                $this->getFolder() . '/' . $this->_originalFilename,
                $this->getFilename()
            );
        }

        return parent::update();
    }

    public static function getList($_where = array(), $_params = array())
    {
        $params = $_params;
        if (!isset($params['order'])) {
            $params['order'] = 'type_id, title';
        }

        return parent::getList($_where, $params);
    }
}
