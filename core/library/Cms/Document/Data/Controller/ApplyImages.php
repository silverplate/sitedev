<?php

abstract class Core_Cms_Document_Data_Controller_ApplyImages
extends App_Cms_Document_Data_Controller
{
    public function execute()
    {
        $this->setContent(App_Image::applyXmlImages(
            $this->getContent(),
            $this->Document->getImages()
        ));
    }
}
