<?php

class Ext_Form_Element_Image extends Ext_Form_Element_File
{
    public function setFile(Ext_Image $_file)
    {
        $size = $_file->getSizeMeasure();
        $value = array(
            'name' => $_file->getName(),
            'path' => $_file->getPath(),
            'uri' => $_file->getUri(),
            'ext' => $_file->getExt(),
            'ext-uppercase' => Ext_String::toUpper($_file->getExt()),
            'size' => $size['string'],
            'width' => $_file->getWidth(),
            'height' => $_file->getHeight()
        );

        foreach (Ext_File::getLangs() as $lang) {
            if (isset($size["string-$lang"])) {
                $value["size-$lang"] = $size["string-$lang"];
            }
        }

        $this->setValue($value);
    }
}
