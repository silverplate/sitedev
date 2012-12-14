<?php

class Ext_Form_Element
{
    const NO_UPDATE      = 'no-update';
    const SUCCESS        = 'success';
    const ERROR_SPELLING = 'error-spelling';
    const ERROR_REQUIRED = 'error-required';
    const ERROR_EXIST    = 'error-exist';

    protected $_name;
    protected $_type;
    protected $_subType;
    protected $_label;
    protected $_description = array();
    protected $_isRequired;
    protected $_value;
    protected $_errorValue;
    protected $_errorMessage;
    protected $_updateStatus;
    protected $_additionalXmlAttributes = array();
    protected $_additionalXml;
    protected $_options = array();
    protected $_optionGroups = array();
    protected $_errorStatuses = array();


    public function __construct($_name, $_type, $_label = null, $_isRequired = false)
    {
        $this->initErrorStatuses();
        $this->_name = $_name;
        $this->_updateStatus = self::NO_UPDATE;
        $this->_type = $_type;

        if ($_label) {
            $this->_label = $_label;
        }

        $this->isRequired($_isRequired);
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($_name)
    {
        $this->_name = $_name;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($_type)
    {
        $this->_type = $_type;
        return $this;
    }

    public function getSubType()
    {
        return $this->_subType;
    }

    public function setSubType($_subType)
    {
        $this->_subType = $_subType;
        return $this;
    }

    public function getLabel()
    {
        return $this->_label;
    }

    public function setLabel($_label)
    {
        $this->_label = $_label;
        return $this;
    }

    public function isRequired($_isRequired = null)
    {
        if (is_null($_isRequired)) {
            return $this->_isRequired;

        } else {
            $this->_isRequired = (boolean) $_isRequired;
            return $this;
        }
    }

    public function getValue($_name = null)
    {
        if (is_null($_name)) {
            return $this->_value;

        } else if (is_array($this->_value) && isset($this->_value[$_name])) {
            return $this->_value[$_name];

        } else {
            return false;
        }
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * @return Ext_Form_Element
     */
    public function setValue()
    {
        if (func_num_args() == 1) {
            $this->_value = func_get_arg(0);

        } else if (func_num_args() == 2) {
            if (!is_array($this->_value)) {
                $this->_value = array();
            }

            $this->_value[func_get_arg(0)] = func_get_arg(1);
        }

        return $this;
    }

    public function getErrorValue($_name = null)
    {
        if (is_null($_name)) {
            return $this->_errorValue;

        } else if (
            is_array($this->_errorValue) &&
            isset($this->_errorValue[$_name])
        ) {
            return $this->_errorValue[$_name];

        } else {
            return false;
        }
    }

    public function setErrorValue()
    {
        if (func_num_args() == 1) {
            $this->_errorValue = func_get_arg(0);

        } else if (func_num_args() == 2) {
            if (!is_array($this->_errorValue)) {
                $this->_errorValue = array();
            }

            $this->_errorValue[func_get_arg(0)] = func_get_arg(1);
        }

        return $this;
    }

    public function initErrorStatuses()
    {
        $this->_errorStatuses = array(
            self::ERROR_SPELLING => 'Некорректное значение.',
            self::ERROR_REQUIRED => 'Поле обязательно для&nbsp;заполнения.',
            self::ERROR_EXIST    => 'Значение уже&nbsp;используется.'
        );
    }

    public function addErrorStatus($_id, $_message)
    {
        $this->_errorStatuses[$_id] = $_message;
    }

    public function getErrorStatusMessageById($_id)
    {
        return isset($this->_errorStatuses[$_id])
             ? $this->_errorStatuses[$_id]
             : false;
    }

    public function computeErrorMessage()
    {
        $message = $this->getErrorStatusMessageById($this->getUpdateStatus());
        return $message ? $message : false;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    public function setErrorMessage($_message)
    {
        $this->_errorMessage = $_message;
        return $this;
    }

    public function getDescription($_name = null)
    {
        $name = is_null($_name) ? 'description' : $_name;
        return empty($this->_description[$name]) ? '' : $this->_description[$name];
    }

    public function setDescription($_description, $_name = null)
    {
        if ($_name) {
            $name = $_name;

        } else {
            $len = Ext_String::getLength(preg_replace('/(?:&[a-z]+;)|(?:&#[0-9]+;)/', ' ', $_description));
            $name = $this instanceof Ext_Form_Element_Text || $len > 50 ? 'label' : 'input';
        }

        $this->_description[$name] = $_description;
        return $this;
    }

    public function setLabelDescription($_description)
    {
        return $this->setDescription($_description, 'label');
    }

    public function getLabelDescription()
    {
        return $this->getDescription('label');
    }

    public function setInputDescription($_description)
    {
        return $this->setDescription($_description, 'input');
    }

    public function getInputDescription()
    {
        return $this->getDescription('input');
    }

    public function isError()
    {
        return in_array($this->_updateStatus, array_keys($this->_errorStatuses));
    }

    public function isSuccess()
    {
        return !$this->isError();
    }

    public function computeUpdateStatus($_data)
    {
        $value = $this->computeValue($_data);

        $this->_updateStatus = $this->checkValue(
            $value === false ? null : $value
        );

        if ($this->isError())  $this->setErrorValue($value);
        else                   $this->setValue($value);

        return $this->_updateStatus;
    }

    public function setUpdateStatus($_status)
    {
        $this->_updateStatus = $_status;
        return $this;
    }

    public function getUpdateStatus()
    {
        return $this->_updateStatus;
    }

    public function setAdditionalXmlAttribute($_name, $_value)
    {
        if (empty($_value)) {
            if (isset($this->_additionalXmlAttributes[$_name])) {
                unset($this->_additionalXmlAttributes[$_name]);
            }

        } else {
            $this->_additionalXmlAttributes[$_name] = $_value;
        }
    }

    public function getAdditionalXmlAttributes()
    {
        return $this->_additionalXmlAttributes;
    }

    public function setAdditionalXml($_xml)
    {
        $this->_additionalXml = $_xml;
        return $this;
    }

    public function addAdditionalXml($_xml)
    {
        $this->_additionalXml .= $_xml;
    }

    public function getAdditionalXml()
    {
        return $this->_additionalXml;
    }

    public function computeValue($_data)
    {
        if (isset($_data[$this->_name])) {
            return $_data[$this->_name];

        } else if (strpos($this->_name, '-') !== false) {
            $name = str_replace('-', '_', $this->_name);
            if (isset($_data[$name])) {
                return $_data[$name];
            }
        }

        return false;
    }

    public function checkValue($_value = null)
    {
        if ($this->_isRequired && empty($_value)) {
            return self::ERROR_REQUIRED;

//         } else if (is_null($_value)) {
//             return self::NO_UPDATE;

        } else {
            return self::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->_updateStatus == self::SUCCESS) {
            return array($this->_name => $this->getValue());

        } else {
            return false;
        }
    }

    public function addOptionGroup($_name)
    {
        $this->_optionGroups[] = $_name;
        return count($this->_optionGroups) - 1;
    }

    public function getOptionGroups()
    {
        return $this->_optionGroups;
    }

    public function addOption($_value, $_label, $_group = null)
    {
        if (is_null($_group)) {
            $this->_options[] = array('value' => $_value, 'label' => $_label);

        } else {
            if (!isset($this->_options['groups'])) {
                $this->_options['groups'] = array();
            }

            if (!isset($this->_options['groups'][$_group])) {
                $this->_options['groups'][$_group] = array();
            }

            $this->_options['groups'][$_group][] = array(
                'value' => $_value,
                'label' => $_label
            );
        }

        return $this;
    }

    public function removeOption($_value)
    {
        $options = array();

        foreach ($this->_options as $key => $option) {
            if ($key == 'groups') {
                if (!isset($options['groups'])) {
                    $options['groups'] = array();
                }

                $options['groups'][$key] = array();

                foreach ($option as $groupOption) {
                    if ($groupOption['value'] != $_value) {
                        $options['groups'][$key][] = $groupOption;
                    }
                }

            } else if ($option['value'] != $_value) {
                $options[] = $option;
            }
        }

        $this->_options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function getOptionsCount()
    {
        $result = count($this->_options);

        if (!empty($this->_options['groups'])) {
            $result--;

            foreach ($this->_options['groups'] as $group) {
                $result += count($group);
            }
        }

        return $result;
    }

    public function getXml()
    {
        $attrs = array(
            'name' => $this->getName(),
            'type' => $this->getType(),
            'update-status' => $this->getUpdateStatus()
        );

        if ($this->getSubType()) {
            $attrs['sub-type'] = $this->getSubType();
        }

        if ($this->_isRequired) {
            $attrs['is-required'] = 'true';
        }

        $xml  = Ext_Xml::notEmptyCdata('label', $this->getLabel());
        $xml .= Ext_Xml::notEmptyCdata('error-message', $this->getErrorMessage());

        $xml .= Ext_Xml::notEmptyNode(
            'additional',
            $this->getAdditionalXml(),
            $this->getAdditionalXmlAttributes()
        );

        foreach ($this->_description as $key => $value) {
            if ($value) {
                if ($key != 'description') {
                    $key = "$key-description";
                }

                $xml .= Ext_Xml::cdata($key, $value);
            }
        }

        if ($this->_value) {
            if (is_array($this->_value)) {
                $xml .= '<value>';

                foreach ($this->_value as $key => $value) {
                    $xml .= Ext_Xml::notEmptyCdata(
                        preg_match('/^[a-z_-]+$/', $key) ? $key : 'item',
                        $value
                    );
                }

                $xml .= '</value>';

            } else {
                $xml .= Ext_Xml::notEmptyCdata('value', $this->_value);
            }
        }

        if ($this->_errorValue) {
            if (is_array($this->_errorValue)) {
                $xml .= '<error-value>';

                foreach ($this->_errorValue as $key => $value) {
                    $xml .= Ext_Xml::notEmptyCdata(
                        preg_match('/^[a-z_]+$/', $key) ? $key : 'item',
                        $value
                    );
                }

                $xml .= '</error-value>';

            } else {
                $xml .= Ext_Xml::notEmptyCdata('error-value', $this->_errorValue);
            }
        }

        if ($this->isError()) {
            $xml .= Ext_Xml::notEmptyCdata(
                'status-error-message',
                self::getErrorStatusMessageById($this->_updateStatus)
            );
        }

        if ($this->_options) {
            $xml .= '<options>';

            if ($this->_optionGroups && !empty($this->_options['groups'][0])) {
                foreach ($this->_optionGroups as $groupId => $groupTitle) {
                    if (!empty($this->_options['groups'][$groupId])) {
                        $xml .= '<group>';
                        $xml .= Ext_Xml::notEmptyCdata('title', $groupTitle);

                        foreach ($this->_options['groups'][$groupId] as $option) {
                            $xml .= Ext_Xml::notEmptyCdata(
                                'item',
                                $option['label'],
                                array('value' => $option['value'])
                            );
                        }

                        $xml .= '</group>';
                    }
                }

            } else {
                foreach ($this->_options as $option) {
                    $xml .= Ext_Xml::notEmptyCdata(
                        'item',
                        $option['label'],
                        array('value' => $option['value'])
                    );
                }
            }

            $xml .= '</options>';
        }

        return Ext_Xml::node('element', $xml, $attrs);
    }
}
