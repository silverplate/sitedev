<?php

class       DocumentDataApplyImages
extends     DocumentDataHandler
implements  DocumentDataHandlerInterface
{
    public function execute()
    {
        $this->setContent(Image::applyXmlImages($this->getContent(),
                                                $this->Document->getImages()));
    }
}
