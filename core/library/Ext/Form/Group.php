<?php

class Ext_Form_Group
{
    protected $_name;
    protected $_title;
    protected $_isSelected = false;
    protected $_elements = array();
    protected $_additionalXml;

    public function __construct($_name = null, $_title = null)
    {
        if (!is_null($_name)) {
            $this->_name = $_name;
        }

        if (!is_null($_title)) {
            $this->_title = $_title;
        }
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function isSelected($_isSelected = null)
    {
        if (is_null($_isSelected)) {
            return $this->_isSelected;
        } else {
            $this->_isSelected = (boolean) $_isSelected;
            return $this;
        }
    }

    public function getElements()
    {
        return $this->_elements;
    }

    /**
     * @todo Ссылка на элемент передавалась в надежде на то, что при удалении
     * элемента из формы протухнет и ссылка, но элемент остается. Поэтому
     * пришлось делать метод deleteElement, который специально удаляет
     * ненужный элемент.
     *
     * @param Ext_Form_Element $_element
     */
    public function addElement(Ext_Form_Element &$_element)
    {
        $this->_elements[$_element->getName()] = $_element;
    }

    public function deleteElement($_name)
    {
        unset($this->_elements[$_name]);
    }

    public function setAdditionalXml($_value)
    {
        $this->_additionalXml = $_value;
    }

    public function addAdditionalXml($_value)
    {
        $this->_additionalXml .= $_value;
    }

    public function getAdditionalXml()
    {
        return $this->_additionalXml;
    }

    public function getXml()
    {
        $attrs = array();

        if ($this->getName()) {
            $attrs['name'] = $this->getName();
        }

        if ($this->isSelected()) {
            $attrs['is-selected'] = 'true';
        }

        $xml  = Ext_Xml::notEmptyCdata('title', $this->getTitle());
        $xml .= Ext_Xml::notEmptyNode('additional', $this->getAdditionalXml());

        foreach ($this->getElements() as $item) {
            $xml .= $item->getXml();
        }

        return Ext_Xml::node('group', $xml, $attrs);
    }
}
