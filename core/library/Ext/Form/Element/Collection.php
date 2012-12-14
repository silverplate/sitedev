<?php

class Ext_Form_Element_Collection extends Ext_Form_Element
{
    public function computeValue($_data)
    {
        $value = array();

        if (isset($_data[$this->getName()])) {
            $value = $_data[$this->getName()];

            if (!is_array($value)) {
                $value = array($value);
            }
        }

        return $value;
    }

    public function checkValue($_value = null)
    {
        if ($this->isRequired() && (empty($_value) || !is_array($_value))) {
            return self::ERROR_REQUIRED;

        } else if (is_null($_value)) {
            return self::NO_UPDATE;

        } else {
            return self::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == self::SUCCESS) {
            return array($this->getName() => implode(',', $this->getValue()));

        } else {
            return false;
        }
    }
}
