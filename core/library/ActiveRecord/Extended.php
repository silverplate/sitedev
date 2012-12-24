<?php

abstract class Core_ActiveRecord_Extended extends Core_ActiveRecord
{
    /**
     *
     * Обычно повторяющаяся для большинства объектов часть
     *
     */

    public static function getList($_conds = array(), $_params = array(), $_rowConds = array())
    {
        return parent::getList(get_called_class(),
                               self::getTbl(),
                               self::getObjectBase()->getAttrNames(),
                               $_conds,
                               $_params,
                               $_rowConds);
    }

    public static function getCount($_conds = array(), $_rowConds = array())
    {
        return parent::getCount(get_called_class(),
                                self::getTbl(),
                                $_conds,
                                $_rowConds);
    }

    public static function isUnique($_attribute, $_value, $_exclude = null)
    {
        return parent::isUnique(get_called_class(),
                                self::getTbl(),
                                self::getPri(),
                                $_attribute,
                                $_value,
                                $_exclude);
    }

    public static function load($_value, $_attribute = null)
    {
        return parent::load(get_called_class(),
                            $_value,
                            $_attribute);
    }

    public function __construct()
    {
        parent::__construct(self::getTbl());

        foreach (self::getObjectBase()->_attributes as $item) {
            $this->_attributes[$item->getName()] = clone($item);
        }
    }

    public static function getObjectBase()
    {
        return call_user_func(array(get_called_class(), 'getBase'));
    }

    public static function getPri($_isTable = false)
    {
        return self::getObjectBase()->getPrimary($_isTable);
    }

    public static function getTbl()
    {
        return self::getObjectBase()->getTable();
    }

    public static function computeTblName()
    {
        $class = get_called_class();
        return DB_PREFIX . $class::TABLE;
    }
}
