<?php

class Core_Cms_Ext_Xml extends Ext_Xml
{
    public static function getEntitiesPath()
    {
        return CORE_TEMPLATES . 'entities.dtd';
    }

    public static function getHead($_root = 'root')
    {
        return parent::getHead(self::getEntitiesPath(), $_root);
    }

    public static function getDocument($_xml, $_attrs = null, $_root = 'root')
    {
        return parent::getDocument($_xml, $_attrs, $_root, self::getEntitiesPath());
    }

    public static function getDocumentForXml($_xml, $_root = 'root')
    {
        return parent::getDocumentForXml($_xml, $_root, self::getEntitiesPath());
    }
}
