<?php

abstract class Core_DocumentDataApplyImages extends App_Cms_Document_Data_Handler
{
    public function execute()
    {
        $this->setContent(App_Image::applyXmlImages(
            $this->getContent(),
            $this->Document->getImages()
        ));
    }
}
