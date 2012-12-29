<?php

abstract class Core_Cms_Front_Data extends App_Model
{
    /**
     * @var App_Cms_Front_Controller
     */
    protected $_controller;

    public function __construct()
    {
        parent::__construct();

        $this->addPrimaryKey('string');
        $this->addForeign(App_Cms_Front_Document::createInstance());
        $this->addForeign(App_Cms_Front_Controller::createInstance());
        $this->addForeign(App_Cms_Front_Data_ContentType::createInstance());
        $this->addAttr('auth_status_id', 'integer');
        $this->addAttr('tag', 'string');
        $this->addAttr('title', 'string');
        $this->addAttr('content', 'string');
        $this->addAttr('apply_type_id', 'integer');
        $this->addAttr('is_mount', 'boolean');
        $this->addAttr('is_published', 'boolean');
        $this->addAttr('sort_order', 'integer');
    }

    public static function getApplyTypes()
    {
        return array(
            1 => 'На&nbsp;эту страницу',
            2 => 'На&nbsp;вложенные',
            3 => 'На&nbsp;эту и&nbsp;вложенные'
        );
    }

    public function checkApplyType()
    {
        if (!key_exists((int) $this->applyTypeId, self::getApplyTypes())) {
            $this->applyTypeId = 1;
        }
    }

    public function create()
    {
        $this->checkApplyType();
        return parent::create();
    }

    public function update()
    {
        $this->checkApplyType();
        return parent::update();
    }

    public function getParsedContent($_content)
    {
        switch ($this->frontDataContentTypeId) {
            case 'integer': return (integer) $_content;
            case 'float':   return (float) $_content;
            default:        return Ext_Xml::decodeCdata($_content);
        }
    }

    public function getTypeId()
    {
        return $this->frontDataContentTypeId;
    }

    public function setTypeId($_id)
    {
        $this->frontDataContentTypeId = $_id;
    }

    public function getXml($_additionalXml = null)
    {
        $attrs = array(
            'type-id' => $this->getTypeId(),
            'tag' => $this->tag
        );

        if ($this->isPublished) {
            $attrs['is-published'] = 1;
        }

        if ($this->isMount) {
            $attrs['is-mount'] = 1;
        }

        $xml = array();

        Ext_Xml::append(
            $xml,
            Ext_Xml::notEmptyNode('additional', $_additionalXml)
        );

        if ($this->getController()) {
            Ext_Xml::append(
                $xml,
                Ext_Xml::cdata('controller', $this->getController()->getTitle())
            );
        }

        Ext_Xml::append(
            $xml,
            Ext_Xml::notEmptyCdata('content', $this->content)
        );

        if (
            defined('IS_USERS') &&
            IS_USERS &&
            $this->authStatusId != App_Cms_User::AUTH_GROUP_ALL &&
            App_Cms_User::getAuthGroupTitle($this->authStatusId)
        ) {
            Ext_Xml::append($xml, Ext_Xml::cdata(
                'auth-group',
                App_Cms_User::getAuthGroupTitle($this->authStatusId)
            ));
        }

        return parent::getXml('document-data', $xml, $attrs);
    }

    public function getController()
    {
        if (is_null($this->_controller)) {
            $this->_controller = $this->frontControllerId
                               ? App_Cms_Front_Controller::getById($this->frontControllerId)
                               : false;
        }

        return $this->_controller;
    }

    public function getControllerFile()
    {
        return $this->getController() ? $this->getController()->getFilename() : false;
    }

    public static function initController($_controller, &$_documentData, &$_document)
    {
        require_once $_controller->getFilename();

        $class = $_controller->getClassName();

        if (class_exists($class)) {
            return new $class($_documentData, $_document);
        }

        return false;
    }
}