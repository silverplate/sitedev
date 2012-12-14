<?php

class Ext_Form_Element_Boolean extends Ext_Form_Element
{
    public function computeValue($_data)
    {
        return empty($_data[$this->getName()]) ? 0 : 1;
    }
}
