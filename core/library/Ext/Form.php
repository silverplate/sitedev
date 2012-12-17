<?php

class Ext_Form
{
    const NO_UPDATE   = 'no-update';
    const SUCCESS     = 'success';
    const ERROR       = 'error';
    const WAS_SUCCESS = 'was-success';
    const COOKIE_NAME = 'form-was-success';

    protected $_updateStatus;
    protected $_resultMessage;
    protected $_groups = array();
    protected $_elements = array();
    protected $_buttons = array();

    public function __construct()
    {
        $this->setUpdateStatus(self::NO_UPDATE);
    }

    public function __isset($_name)
    {
        return property_exists($this, $_name) || $this->getElement($_name);
    }

    public function __get($_name)
    {
        $ele = $this->getElement($_name);
        if ($ele) return $ele;
        else      throw new Exception("There is no form element `$_name`.");
    }

    public function __set($_name, $_value)
    {
        $ele = $this->getElement($_name);
        if ($ele) return $ele->setValue($_value);
        else      throw new Exception("There is no form element `$_name`.");
    }

    public function computeInnerName($_name)
    {
        if (isset($this->_elements[$_name])) {
            return $_name;

        } else {
            $name = Ext_String::underline($_name);
            if (isset($this->_elements[$name])) {
                return $name;
            }

            $name = Ext_String::dash($_name);
            if (isset($this->_elements[$name])) {
                return $name;
            }
        }

        return false;
    }

    public function getGroups()
    {
        return $this->_groups;
    }

    public function getElements()
    {
        return $this->_elements;
    }

    public function getElement($_name)
    {
        $name = $this->computeInnerName($_name);
        if ($name) return $this->_elements[$name];
        else       return false;
    }

    /**
     * @param string $_name
     * @param string $_title
     * @return Ext_Form_Group
     */
    public function createGroup($_name = null, $_title = null)
    {
        $this->_groups[$_name] = new Ext_Form_Group($_name, $_title);

        if (isset($_COOKIE['form-group']) && $_COOKIE['form-group'] == $_name) {
            $this->_groups[$_name]->isSelected(true);
        }

        return $this->_groups[$_name];
    }

    /**
     * @param string $_name
     * @param string $_type
     * @param string $_label
     * @param boolean $_isRequired
     * @return Ext_Form_Element
     */
    public function createElement($_name, $_type, $_label = null, $_isRequired = false)
    {
        $name = Ext_String::upperCase($_type);

        $class = 'Ext_Form_Element_' . $name;
        $file = dirname(__FILE__) . '../Project/Form/Element/' . $name . '.php';

        if (!is_file($file)) {
            $class = 'Ext_Form_Element_' . $name;
            $file = dirname(__FILE__) . '/Form/Element/' . $name . '.php';
        }

        if (!is_file($file)) {
            $class = 'Ext_Form_Element';
            $file = dirname(__FILE__) . '/Form/Element.php';
        }

        require_once $file;

        $this->_elements[$_name] = new $class($_name, $_type, $_label, $_isRequired);
        return $this->_elements[$_name];
    }

    public function delete($_name)
    {
        $name = $this->computeInnerName($_name);

        if ($name) {
            unset($this->_elements[$name]);
            foreach ($this->_groups as $group) {
                $group->deleteElement($name);
            }

        } else {
            throw new Exception("There is no form element `$_name`.");
        }
    }

    public function rename($_from, $_to)
    {
        $this->$_from->setName($_to);
        $elements = array();

        foreach (array_keys($this->_elements) as $name) {
            $elements[$this->$name->getName()] = $this->$name;
        }

        $this->_elements = $elements;
    }

    public function createButton($_label, $_name = null, $_type = null)
    {
        $name = empty($_name) ? 'submit' : $_name;
        $this->_buttons[$name] = new Ext_Form_Button($name, $_label, $_type);
        return $this->_buttons[$name];
    }

    public function getButtons()
    {
        return $this->_buttons;
    }

    public static function createByXml($_xml)
    {
        $form = new Ext_Form();
        $xpath = new DOMXPath(DOMApp_Cms_Document::loadXML("<root>$_xml</root>"));
        $groups = $xpath->evaluate('group');

        if ($groups->length > 0) {
            foreach ($groups as $group) {
                $name = $group->getAttribute('name');
                $title = Ext_Dom::getChildByName($group, 'title');
                $form->createGroup($name, $title ? $title->nodeValue : null);

                foreach ($xpath->evaluate('element', $group) as $element) {
                    $form->createElementByDom($element, $name);
                }
            }

        } else {
            foreach ($xpath->evaluate('element') as $item) {
                $form->createElementByDom($item);
            }
        }

        foreach ($xpath->evaluate('button') as $item) {
            $form->createButton(
                Ext_Dom::getChildByName($item, 'label')->nodeValue,
                $item->getAttribute('name'),
                $item->getAttribute('type')
            );
        }

        return $form;
    }

    public function createElementByDom(DOMNode $_element, $_groupName = null)
    {
        $labelEle = Ext_Dom::getChildByName($_element, 'label');
        $label = $labelEle && $labelEle->nodeValue ? $labelEle->nodeValue : null;

        $element = $this->createElement(
            $_element->getAttribute('name'),
            $_element->getAttribute('type'),
            $label,
            $_element->hasAttribute('is-required')
        );

        if (!$element) {
            throw new Exception('Unable to create form element.');
        }

        if ($_groupName) {
            $this->_groups[$_groupName]->addElement($element);
        }

        foreach (
            array('description', 'label-description', 'input-description') as
            $item
        ) {
            $descriptionEle = Ext_Dom::getChildByName($_element, $item);
            if ($descriptionEle) {
                call_user_func_array(
                    array($element, 'set' . Ext_String::upperCase($item)),
                    array($descriptionEle->nodeValue)
                );
            }
        }

        $optionsEle = Ext_Dom::getChildByName($_element, 'options');
        if ($optionsEle) {
            $optionGroups = $optionsEle->getElementsByTagName('group');

            if ($optionGroups->length > 0) {
                foreach ($optionGroups as $optionGroupEle) {
                    $optionGroupTitleEle = Ext_Dom::getChildByName($optionGroupEle, 'title');

                    if ($optionGroupTitleEle) {
                        $optionGroup = $element->addOptionGroup($optionGroupTitleEle->nodeValue);

                        foreach ($optionGroupEle->getElementsByTagName('item') as $option) {
                            $optionLabel = $option->nodeValue;
                            $optionValue = $option->hasAttribute('value')
                                         ? $option->getAttribute('value')
                                         : $optionLabel;

                            $element->addOption($optionValue, $optionLabel, $optionGroup);
                        }
                    }
                }

            } else {
                foreach ($optionsEle->getElementsByTagName('item') as $option) {
                    $optionLabel = $option->nodeValue;
                    $optionValue = $option->hasAttribute('value')
                                 ? $option->getAttribute('value')
                                 : $optionLabel;

                    $element->addOption($optionValue, $optionLabel);
                }
            }
        }

        $valueEle = Ext_Dom::getChildByName($_element, 'value');
        if ($valueEle && $valueEle->childNodes->length > 0) {
            if (
                $valueEle->firstChild->nodeType == XML_ELEMENT_NODE ||
                $valueEle->childNodes->length > 1
            ) {
                foreach ($valueEle->childNodes as $value) {
                    if ($value->nodeType == XML_ELEMENT_NODE) {
                        $element->setValue($value->nodeName, $value->nodeValue);
                    }
                }

            } else if ($valueEle->firstChild->nodeValue) {
                $element->setValue($valueEle->firstChild->nodeValue);
            }
        }

        $additional = Ext_Dom::getChildByName($_element, 'additional');
        if ($additional) {
            foreach ($additional->attributes as $item) {
                $element->setAdditionalXmlAttribute($item->name, $item->value);
            }

            foreach ($additional->childNodes as $item) {
                if ($item->nodeType == XML_ELEMENT_NODE) {
                    $element->addAdditionalXml($_element->ownerDocument->saveXml($item));
                }
            }
        }
    }

    public function getXml($_xml = null, $_attrs = null, $_nodeName = null)
    {
        $attrs = empty($_attrs) ? array() : $_attrs;
        if ($this->getUpdateStatus()) {
            $attrs['status'] = $this->getUpdateStatus();
        }

        $xml = Ext_Xml::notEmptyCdata('result-message', $this->_resultMessage);
        if ($_xml) $xml .= $_xml;

        $elements = count($this->_groups) > 0 ? $this->_groups : $this->_elements;

        foreach ($elements as $item) {
            $xml .= $item->getXml();
        }

        foreach ($this->_buttons as $item) {
            $xml .= $item->getXml();
        }

        return Ext_Xml::node(empty($_nodeName) ? 'form' : $_nodeName, $xml, $attrs);
    }

    public function isSubmited($_button = null)
    {
        if (is_null($_button)) {
            foreach ($this->_buttons as $button) {
                if ($button->isSubmited()) {
                    return true;
                }
            }

        } else if (
            isset($this->_buttons[$_button]) &&
            $this->_buttons[$_button]->isSubmited()
        ) {
            return true;
        }

        return false;
    }

    public function isSuccess()
    {
        return $this->getUpdateStatus() == self::SUCCESS;
    }

    public function run()
    {
        if ($this->isSubmited()) {
            $this->setUpdateStatus(self::SUCCESS);

            foreach ($this->_elements as $name => $item) {
                $item->computeUpdateStatus(
                    $item->getType() == 'file' || $item->getType() == 'image'
                    ? $_FILES
                    : $_POST
                );

                if ($item->isError()) {
                    $this->setUpdateStatus(self::ERROR);
                }
            }

        } else if ($this->applyCookieStatus()) {
            self::clearCookieStatus();

        } else {
            $this->setUpdateStatus(self::NO_UPDATE);
        }
    }

    public function getUpdateStatus()
    {
        return $this->_updateStatus;
    }

    public function setUpdateStatus($_status)
    {
        $this->_updateStatus = $_status;
        return $this;
    }

    public function getResultMessage()
    {
        return $this->_resultMessage;
    }

    public function setResultMessage($_message)
    {
        $this->_resultMessage = $_message;
        return $this;
    }

    public function fill($_values)
    {
        foreach ($this->_elements as $element) {
            $value = $element->computeValue($_values);

            if ($value !== false) {
                $element->setValue($value);
            }
        }
    }

    public function getValues()
    {
        $result = array();

        foreach ($this->_elements as $element) {
            $value = $element->getValues();

            if ($value) {
                $result = array_merge($result, $value);
            }
        }

        return $result;
    }

    public function uploadFiles($_uploadDir, $_fileNameType = 'real')
    {
        if ($_uploadDir) {
            $uploaded = array();
            $deleted = array();

            foreach ($this->_elements as $element) {
                if (
                    $element->isSuccess() &&
                    in_array($element->getType(), array('file', 'image'))
                ) {
                    $value      = $element->getValue();
                    $isUploaded = !empty($value) && !empty($value['name']) && !empty($value['tmp_name']);
                    $isDelete   = !empty($_POST[$element->getName() . '-delete']);
                    $isExist    = !empty($_POST[$element->getName() . '-exist']) && is_file($_POST[$element->getName() . '-exist']);

                    if ($isExist && ($isUploaded || $isDelete)) {
                        Ext_File::deleteFile($_POST[$element->getName() . '-exist']);

                        if ($isDelete) {
                            $deleted[$element->getName()] = $_POST[$element->getName() . '-exist'];
                        }
                    }

                    if ($isUploaded) {
                        switch ($_fileNameType) {
                            case 'field':
                                $fileName = $element->getName();
                                $extension = Ext_File::computeExt($value['name']);

                                if ($extension) {
                                    $fileName .= '.' . $extension;
                                }

                                break;

                            case 'real':
                            default:
                                $fileName = $value['name'];
                                break;
                        }

                        Ext_File::createDir($_uploadDir);

                        move_uploaded_file($value['tmp_name'], $_uploadDir . '/' . $fileName);
                        @chmod($_uploadDir . '/' . $fileName, 0777);

                        $uploaded[$element->getName()] = $_uploadDir . '/' . $fileName;
                    }

                    if (Ext_File::isDirEmpty($_uploadDir)) {
                        rmdir($_uploadDir);
                    }
                }
            }

            return array('uploaded' => $uploaded, 'deleted' => $deleted);
        }

        return false;
    }

    public static function saveCookieStatus()
    {
        setcookie(self::COOKIE_NAME, 1, 0, '/');
    }

    public static function clearCookieStatus()
    {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
    }

    public static function wasCookieStatus()
    {
        return !empty($_COOKIE[self::COOKIE_NAME]);
    }

    public function applyCookieStatus($_message = null)
    {
        if (self::wasCookieStatus()) {
            $this->setUpdateStatus(self::WAS_SUCCESS);

            if (!empty($_message)) {
                $this->setResultMessage($_message);
            }

            return true;
        }

        return false;
    }

    public static function getCookieStatusXml($_message = null)
    {
        if (self::wasCookieStatus()) {
            $message = empty($_message)
                     ? $this->getResultMessage()
                     : $_message;

            return Ext_Xml::node(
                'form-status',
                Ext_Xml::notEmptyCdata('result-message', $message),
                array('status' => self::WAS_SUCCESS)
            );
        }
    }
}
