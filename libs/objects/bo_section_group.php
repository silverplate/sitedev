<?php

class BoSectionGroup extends ActiveRecord {
	private static $Base;
	const TABLE = 'bo_section_group';

	public function GetName() {
		return strtolower(str_replace(' ', '_', translit($this->GetTitle())));
	}

	public function GetXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(__CLASS__);
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->GetAttribute('is_published') == 1) $result .= ' is_published="true"';

				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;

			case 'bo_navigation':
				$result .= '<' . $node_name . '';
				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->Attributes as $item) {
			$this->Attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 10, true);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('sort_order', 'int', 11);
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

	public static function Load($_value, $_attribute = null) {
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array()) {
		return parent::GetList(
			__CLASS__,
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters
		);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
