<?php

abstract class Core_Cms_Template extends App_DbMapper
{
    protected $_file;
    protected $_originalFilename;

    public function __construct($_obj = null)
    {
        parent::__construct($_obj);
        $this->_originalFilename = $this->filename;
    }

    public static function getFolder()
    {
        return rtrim(TEMPLATES, '\\/');
    }

    public function getFilePath()
    {
        return self::getFolder() . '/' . $this->filename;
    }

    public function getFile()
    {
        if (!isset($this->_file)) {
            $this->_file = is_file($this->getFilePath())
                         ? new Ext_File($this->getFilePath())
                         : false;
        }

        return $this->_file;
    }

    public function getContent()
    {
        return $this->getFile()
             ? file_get_contents($this->getFile()->getPath())
             : false;
    }

    public function setContent($_content)
    {
        $doCreate = !$this->getFile();
        file_put_contents($this->getFilePath(), $_content);

        if ($doCreate) {
            @chmod($this->getFilePath(), 0777);
        }
    }

    public function create()
    {
        return parent::create();
    }

    public function update()
    {
        if ($this->filename != $this->_originalFilename) {
            rename(
                $this->getFolder() . '/' . $this->_originalFilename,
                $this->getFilePath()
            );
        }

        return parent::update();
    }

    public function delete()
    {
        if ($this->getFile()) {
            $this->getFile()->delete();
        }

        return parent::delete();
    }

    public function getXml($_type = null, $_node = null, $_xml = null, array $_attrs = array())
    {
        $xml = empty($_xml) ? array() : array($_xml);
        $node = empty($_node) ? Ext_Xml::normalizeName(__CLASS__) : $_node;
        $attrs = empty($_attrs) ? array() : $_attrs;

        $attrs['id'] = $this->id;
        Ext_Xml::append($xml, Ext_Xml::notEmptyCdata('title', $this->getTitle()));

        switch ($_type) {
            case 'bo-list':
                if ($this->isPublished) {
                    $attrs['is-published'] = 'true';
                }

                if ($this->isDocumentMain) {
                    $attrs['prefix'] = 'о';
                }

                break;
        }

        return parent::getXml($node, implode('', $xml), $attrs);
    }
}
