<?php

abstract class Core_Cms_Bo_UserToSection extends App_ActiveRecord
{
	private static $_base;

	const TABLE = 'bo_user_to_section';

	public static function getFirstKey($_isTable = false)
	{
		return App_Cms_Bo_User::GetPri($_isTable);
	}

	public static function GetFirstKeyTable() {
		return App_Cms_Bo_User::GetTbl();
	}

	public static function GetSecondKey($_is_table = false) {
		return App_Cms_Bo_Section::GetPri($_is_table);
	}

	public static function GetSecondKeyTable() {
		return App_Cms_Bo_Section::GetTbl();
	}

	public static function GetList($_attributes = array()) {
		$tables = array(self::GetTbl());
		$attributes = $_attributes;
		$row_conditions = array();

		if (isset($attributes[self::getFirstKey()])) {
			if (is_array($_attributes[self::getFirstKey()])) {
				array_push($row_conditions, self::GetTbl() . '.' . self::getFirstKey() . ' IN (' . App_Db::Get()->EscapeList($_attributes[self::getFirstKey()]) . ')');
			} else {
				array_push($row_conditions, self::GetTbl() . '.' . self::getFirstKey() . ' = ' . App_Db::escape($_attributes[self::getFirstKey()]));
			}
			unset($attributes[self::getFirstKey()]);
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
			array_push($row_conditions, self::GetFirstKeyTable() . '.status_id ' . ($attributes['is_published'] == 1 ? ' = 1' : ' != 1'));
			array_push($row_conditions, self::getFirstKey(true) . ' = ' . self::GetTbl() . '.' . self::getFirstKey());

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
		if (!isset(self::$_base)) {
			self::$_base = new App_ActiveRecord(self::ComputeTblName());
			self::$_base->AddForeignKey(App_Cms_Bo_User::GetBase(), true);
			self::$_base->AddForeignKey(App_Cms_Bo_Section::GetBase(), true);
		}

		return self::$_base;
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
