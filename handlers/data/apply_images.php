<?php

class       DocumentDataApplyImages
extends     DocumentDataHandler
{
    public function execute()
    {
        $this->setContent(Image::applyXmlImages($this->getContent(),
                                                $this->Document->getImages()));
    }
}
