<?php

abstract class Core_Cms_Bo_Page404 extends Core_Cms_BoPage
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setTemplate(TEMPLATES . 'bo_404.xsl');
    }

    public function Output()
    {
        header('HTTP/1.0 404 Not Found');
        parent::output();
    }
}
