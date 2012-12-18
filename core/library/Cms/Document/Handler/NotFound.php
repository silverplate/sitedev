<?php

abstract class Core_Cms_Document_Handler_NotFound extends App_Cms_Document_Handler_Common
{
    public function execute()
    {
        parent::execute();
        $this->setRootNodeName('page-not-found');
    }

    public function output()
    {
        header('HTTP/1.0 404 Not Found');
        return parent::output();
    }
}
