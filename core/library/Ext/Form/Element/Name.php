<?php

class Ext_Form_Element_Name extends Ext_Form_Element
{
    public function computeValue($_data)
    {
        $value = array();
        $prefixes = array('', $this->getName() . '-');

        foreach ($prefixes as $prefix) {
            foreach (array('last-name', 'first-name', 'middle-name') as $name) {
                if (isset($_data[$prefix . $name])) {
                    $value[$name] = $_data[$prefix . $name];
                }
            }

            if (count($value) > 0) {
                return $value;
            }
        }

        return false;
    }

    public function checkValue($_value = null)
    {
        if (
            $this->isRequired() &&
            (empty($_value['first-name']) || empty($_value['last-name']))
        ) {
            return self::ERROR_REQUIRED;

        } else if (
            !isset($_value['first-name']) &&
            !isset($_value['last-name']) &&
            !isset($_value['middle-name'])
        ) {
            return self::NO_UPDATE;

        } else {
            return self::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == self::SUCCESS) {
            return $this->getValue();

        } else {
            return false;
        }
    }
}
