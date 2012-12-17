<?php

abstract class Core_Form_Element_AddingFiles extends Core_Form_Element
{
    public function ComputeValue($_data) {
        $value = array();
        $data = is_array($_data) && isset($_data[$this->Name]) ? $_data[$this->Name] : $_data;

        if (is_array($data)) {
            if (isset($data['name']) && isset($data['tmp_name'])) {
                if (is_array($data['name']) && is_array($data['tmp_name'])) {
                    for ($i = 0; $i < count($data['name']); $i++) {
                        if (isset($data['name'][$i]) && $data['name'][$i] && isset($data['tmp_name'][$i]) && $data['tmp_name'][$i]) {
                            array_push($value, array(
                            'name' => $data['name'][$i],
                            'tmp_name' => $data['tmp_name'][$i]
                            ));
                        }
                    }

                } elseif ($data['name'] && $data['tmp_name']) {
                    array_push($value, array(
                    'name' => $data['name'],
                    'tmp_name' => $data['tmp_name']
                    ));
                }
            }

        } elseif ($data && is_file($data)) {
            $value = array(
                    'path' => $data,
                    'url' => str_replace(DOCUMENT_ROOT, '/', $data),
                    'size' => format_number(filesize($data) / 1024, 2)
            );
        }

        return $value;
    }

    public function CheckValue($_value = null) {
        if ($this->IsRequired && !$_value) {
            return FIELD_ERROR_REQUIRED;

        } elseif (!$_value) {
            return FIELD_NO_UPDATE;

        } else {
            return FIELD_SUCCESS;
        }
    }

    public function GetSqlValue() {
        if ($this->UpdateType == FIELD_SUCCESS) {
            $value = $this->GetValue();
            if ($value) {
                if (is_array($value)) {
                    if (isset($value['path'])) {
                        return array($this->Name => translit(basename($value['path'])));

                    } else {
                        $result = array();
                        foreach ($value as $file) {
                            if (isset($file['name'])) {
                                array_push($result, translit($file['name']));
                            }
                        }
                        if ($result) {
                            return array($this->Name => implode(', ', $result));
                        }
                    }

                } elseif ($value) {
                    return array($this->Name => $value);
                }
            }
        }

        return false;
    }
}
