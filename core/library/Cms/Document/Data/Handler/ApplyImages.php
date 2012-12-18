<?php

abstract class Core_Cms_Document_Data_Handler_ApplyImages
extends App_Cms_Document_Data_Handler
{
    public function execute()
    {
        $this->setContent(App_Image::applyXmlImages(
            $this->getContent(),
            $this->Document->getImages()
        ));
    }
}
