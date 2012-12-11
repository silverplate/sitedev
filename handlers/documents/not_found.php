<?php

require_once 'common.php';

class       DocumentNotFound
extends     DocumentCommon
implements  DocumentHandlerInterface
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
