<?php

class Ext_Form_Element_File extends Ext_Form_Element
{
    public function setFile(Ext_File $_file)
    {
        $size = $_file->getSizeMeasure();
        $value = array(
            'name' => $_file->getName(),
            'path' => $_file->getPath(),
            'uri' => $_file->getUri(),
            'ext' => $_file->getExt(),
            'ext-uppercase' => Ext_String::toUpper($_file->getExt()),
            'size' => $size['string']
        );

        foreach (Ext_File::getLangs() as $lang) {
            if (isset($size["string-$lang"])) {
                $value["size-$lang"] = $size["string-$lang"];
            }
        }

        $this->setValue($value);
    }

    public function computeValue($_data)
    {
        $value = array();
        $name = $this->getName();

        if (
            is_array($_data) && !empty($_data[$name]) &&
            is_array($_data[$name]) && !empty($_data[$name]['name'])
        ) {
            $value = array(
                'name' => $_data[$name]['name'],
                'tmp_name' => $_data[$name]['tmp_name']
            );
        }

        return $value;
    }

    public function checkValue($_value = null)
    {
        $isUploaded = empty($_value) ||
                      !is_array($_value) ||
                      empty($_value['name']) ||
                      empty($_value['tmp_name']);

        if ($this->isRequired() && !$isUploaded) {
            return self::ERROR_REQUIRED;

        } else if (!$isUploaded) {
            return self::NO_UPDATE;

        } else {
            return self::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == self::SUCCESS) {
            $value = $this->getValue('name');
            if ($value) {
                return array($this->getName() => $value);
            }
        }

        return false;
    }
}
