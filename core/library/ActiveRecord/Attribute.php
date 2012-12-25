<?php

abstract class Core_ActiveRecord_Attribute
{
    private $_name;
    private $_type;
    private $_length;
    private $_value;
    private $_isPrimary;
    private $_isUnique;

    public function __construct($_name, $_type, $_length = null, $_isPrimary = null, $_isUnique = null, $_value = null)
    {
        $this->_name = $_name;
        $this->_length = $_length;
        $this->_isPrimary = (boolean) $_isPrimary;
        $this->_isUnique = (boolean) $_isUnique;

        $this->setType($_type);

        if ($_value) {
            $this->setValue($_value);
        }
    }

    public function setType($_type)
    {
        $this->_type = $_type == 'char' || $_type == 'varchar'
                     ? 'string'
                     : $_type;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getLength()
    {
        return $this->_length;
    }

    public function setValue($_value)
    {
        switch ($this->_type) {
            case 'integer':
                $this->_value = (integer) $_value;
                break;

            case 'float':
                $this->_value = Ext_Number::number($_value);
                break;

            case 'boolean':
                $this->_value = $_value ? 1 : 0;
                break;

            default:
                $this->_value = $_value;
                break;
        }
    }

    public function getSqlValue()
    {
        return (string) $this->_value == '' ? 'NULL' : App_Db::escape($this->_value);
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function isValue()
    {
        return !empty($this->_value);
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isPrimary()
    {
        return $this->_isPrimary;
    }

    public function isUnique()
    {
        return $this->_isUnique;
    }
}
