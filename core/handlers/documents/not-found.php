<?php

require_once 'common.php';

abstract class Core_DocumentNotFound extends App_DocumentCommon
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
