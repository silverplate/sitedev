<?php

class TemplateDb extends ActiveRecordExtended
{
    private static $_base;
    const TABLE = 'fo_template';

    public function delete()
    {
        Db::get()->execute('UPDATE ' . Document::getTbl() . ' ' .
                           'SET ' . $this->getPri() . ' = NULL ' .
                           'WHERE ' . $this->getPri() . ' = ' . $this->getDbId());

        return parent::delete();
    }

    public static function getBase()
    {
        if (!isset(self::$_base)) {
            $table = self::computeTblName();
            self::$_base = new ActiveRecord($table);
            self::$_base->addAttribute($table . '_id', 'int', null, true);
            self::$_base->addAttribute('title', 'string');
            self::$_base->addAttribute('filename', 'string');
            self::$_base->addAttribute('is_document_main', 'boolean');
            self::$_base->addAttribute('is_multiple', 'boolean');
            self::$_base->addAttribute('is_published', 'boolean');
        }

        return self::$_base;
    }
}
