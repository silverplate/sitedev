<?php

class Ext_Form_Element_Email extends Ext_Form_Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == self::SUCCESS &&
            $_value != '' &&
            !Ext_String::isEmail($_value)
        ) {
            return self::ERROR_SPELLING;
        }

        return $status;
    }
}
