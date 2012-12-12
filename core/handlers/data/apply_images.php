<?php

class       DocumentDataApplyImages
extends     DocumentDataHandler
{
    public function execute()
    {
        $this->setContent(App_Image::applyXmlImages(
            $this->getContent(),
            $this->Document->getImages()
        ));
    }
}
