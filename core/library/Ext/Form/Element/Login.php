<?php

class Ext_Form_Element_Login extends Ext_Form_Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == self::SUCCESS &&
            $_value != '' &&
            !preg_match('/^[a-zA-Z0-9_.-]+$/', $_value)
        ) {
            return self::ERROR_SPELLING;
        }

        return $status;
    }
}
