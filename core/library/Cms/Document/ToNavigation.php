<?php

abstract class Core_Cms_Document_ToNavigation extends App_ActiveRecord
{
	private static $Base;

	const TABLE = 'fo_document_to_navigation';

	public static function GetFirstKey($_is_table = false) {
		return App_Cms_Document::GetPri($_is_table);
	}

	public static function GetFirstKeyTable() {
		return App_Cms_Document::GetTbl();
	}

	public static function GetSecondKey($_is_table = false) {
		return App_Cms_Document_Navigation::GetPri($_is_table);
	}

	public static function GetSecondKeyTable() {
		return App_Cms_Document_Navigation::GetTbl();
	}

	public static function GetList($_attributes = array()) {
		$tables = array(self::GetTbl());
		$attributes = $_attributes;
		$row_conditions = array();

		if (isset($attributes[self::GetFirstKey()])) {
			if (is_array($_attributes[self::GetFirstKey()])) {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetFirstKey() . ' IN (' . App_Db::Get()->EscapeList($_attributes[self::GetFirstKey()]) . ')');
			} else {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetFirstKey() . ' = ' . App_Db::escape($_attributes[self::GetFirstKey()]));
			}
			unset($attributes[self::GetFirstKey()]);
		}

		if (isset($attributes[self::GetSecondKey()])) {
			if (is_array($_attributes[self::GetSecondKey()])) {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetSecondKey() . ' IN (' . App_Db::Get()->EscapeList($_attributes[self::GetSecondKey()]) . ')');
			} else {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetSecondKey() . ' = ' . App_Db::escape($_attributes[self::GetSecondKey()]));
			}
			unset($attributes[self::GetSecondKey()]);
		}

		if (isset($attributes['is_published'])) {
			array_push($tables, self::GetFirstKeyTable());
			array_push($row_conditions, self::GetFirstKeyTable() . '.is_published = ' . App_Db::escape($attributes['is_published']));
			array_push($row_conditions, self::GetFirstKey(true) . ' = ' . self::GetTbl() . '.' . self::GetFirstKey());

			array_push($tables, self::GetSecondKeyTable());
			array_push($row_conditions, self::GetSecondKeyTable() . '.is_published = ' . App_Db::escape($attributes['is_published']));
			array_push($row_conditions, self::GetSecondKey(true) . ' = ' . self::GetTbl() . '.' . self::GetSecondKey());

			unset($attributes['is_published']);
		}

		if ($attributes) {
			$row_conditions = parent::GetQueryCondition($attributes);
		}

		return parent::GetList(
			get_called_class(),
			$tables,
			self::GetBase()->GetAttributes(true),
			null,
			null,
			$row_conditions
		);
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
			self::$Base->AddForeignKey(App_Cms_Document::GetBase(), true);
			self::$Base->AddForeignKey(App_Cms_Document_Navigation::GetBase(), true);
		}

		return self::$Base;
	}

	public static function GetTbl() {
		return self::GetBase()->GetTable();
	}

	public static function Load($_value, $_attribute = null) {
		return parent::Load(get_called_class(), $_value, $_attribute);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
