<?php

abstract class Core_Cms_Document_DataContentType extends App_ActiveRecord
{
	private static $Base;
	const TABLE = 'fo_data_content_type';

	public static function Import() {
		$list = array(
			array('string', 'Строка', 1),
			array('text', 'Текст', 1),
			array('html', 'Визуальный редактор', 0),
			array('data', 'Дата', 0),
			array('integer', 'Целое число', 1),
			array('float', 'Дробное число', 0),
			array('xml', 'XML', 1)
		);

		$class = get_called_class();

		foreach ($list as $item) {
			$obj = new $class;
			$obj->SetAttribute(App_Cms_Document_DataContentType::GetPri(), $item[0]);
			$obj->SetAttribute('title', $item[1]);
			$obj->SetAttribute('is_published', $item[2]);
			$obj->Create();
		}
	}

	public function Delete() {
		App_Db::Get()->Execute('UPDATE ' . App_Cms_Document_Data::GetTbl() . ' SET ' . self::GetPri() . ' = "" WHERE ' . self::GetPri() . ' = ' . App_Db::escape($this->GetId()));
		parent::Delete();
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->Attributes as $item) {
			$this->Attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new App_ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 10, true);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('is_published', 'boolean');
			self::$Base->AddAttribute('sort_order', 'int');
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

	public static function Load($_value, $_attribute = null) {
		return parent::Load(get_called_class(), $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array()) {
		return parent::GetList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters
		);
	}
}

?>
