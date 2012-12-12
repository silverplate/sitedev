<?php

abstract class Core_DocumentDataApplyImages extends DocumentDataHandler
{
    public function execute()
    {
        $this->setContent(App_Image::applyXmlImages(
            $this->getContent(),
            $this->Document->getImages()
        ));
    }
}
