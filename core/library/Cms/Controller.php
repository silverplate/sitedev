<?php

abstract class Core_Cms_Controller extends App_ActiveRecord
{
	private static $Base;
	const TABLE = 'fo_controller';

    public function getClassName()
    {
        if ($this->type_id == 1) {
            $class = 'App_Cms_Document_Controller_';

        } else if ($this->type_id == 2) {
            $class = 'App_Cms_Document_Data_Controller_';

        } else {
            throw new Exception('Unkown controller type');
        }

        return $class . Ext_File::computeName($this->filename);
    }

	public static function getPathByType($_id)
	{
		switch ($_id) {
			case 1: return DOCUMENT_CONTROLLERS;
			case 2: return DATA_CONTROLLERS;
		}

		return false;
	}

	public function getFolder()
	{
		return self::getPathByType($this->type_id);
	}

	public function getFilename()
	{
	    return $this->getFolder() && $this->filename
	         ? $this->getFolder() . $this->filename
	         : false;
	}

	public function GetContent() {
		return $this->GetFilename() && is_file($this->GetFilename())
			? file_get_contents($this->GetFilename())
			: false;
	}

	public function CheckUnique() {
		$attributes = array();
		foreach ($this->_attributes as $attr) {
			if ($attr->IsUnique()) {
				$attributes[$attr->GetName()] = $attr->GetValue(false);
			}
		}

		return !$attributes || !self::getList($attributes, array('count' => 1), array(self::GetPri() . ' != ' . App_Db::escape($this->GetId())));
	}

	private function GetPrefix() {
		switch ($this->typeId) {
			case 1:
				return 'с';
			case 2:
				return 'б';
			default:
				return false;
		}
	}

	public function getXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(get_called_class());
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->isPublished) $result .= ' is_published="true"';
				if ($this->GetPrefix()) $result .= ' prefix="' . $this->GetPrefix() . '"';
				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function delete() {
		App_Db::Get()->Execute('UPDATE ' . App_Cms_Document::GetTbl() . ' SET ' . self::GetPri() . ' = "" WHERE ' . self::GetPri() . ' = ' . App_Db::escape($this->GetId()));
		App_Db::Get()->Execute('UPDATE ' . App_Cms_Document_Data::GetTbl() . ' SET ' . self::GetPri() . ' = "" WHERE ' . self::GetPri() . ' = ' . App_Db::escape($this->GetId()));

		Ext_File::deleteFile($this->getFilename());
		parent::delete();
	}

	public function Update() {
		$prev = self::load($this->GetId());
		parent::Update();

		if ($prev->GetFilename() && $prev->GetFilename() != $this->GetFilename()) {
			rename($prev->GetFilename(), $this->GetFilename());
		}
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->_attributes as $item) {
			$this->_attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new App_ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 10, true);
			self::$Base->AddAttribute('type_id', 'int', null, null, true);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('filename', 'varchar', 30, null, true);
			self::$Base->AddAttribute('is_document_main', 'boolean');
			self::$Base->AddAttribute('is_multiple', 'boolean');
			self::$Base->AddAttribute('is_published', 'boolean');
		}

		return self::$Base;
	}

	public static function GetPri($_is_table = false) {
		return self::GetBase()->GetPrimary($_is_table);
	}

	public static function GetTbl() {
		return self::GetBase()->GetTable();
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}

	public static function load($_value, $_attribute = null) {
		return parent::load(get_called_class(), $_value, $_attribute);
	}

	public static function getList($_attributes = array(), $_parameters = array(), $_row_conditions = array()) {
		$parameters = $_parameters;
		if (!isset($parameters['sort_order'])) {
			$parameters['sort_order'] = 'type_id, title';
		}

		return parent::getList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->getAttrNames(),
			$_attributes,
			$parameters,
			$_row_conditions
		);
	}
}

?>
