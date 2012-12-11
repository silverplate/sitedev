<?php

class DocumentToNavigation extends ActiveRecord {
	private static $Base;

	const TABLE = 'fo_document_to_navigation';

	public static function GetFirstKey($_is_table = false) {
		return Document::GetPri($_is_table);
	}

	public static function GetFirstKeyTable() {
		return Document::GetTbl();
	}

	public static function GetSecondKey($_is_table = false) {
		return DocumentNavigation::GetPri($_is_table);
	}

	public static function GetSecondKeyTable() {
		return DocumentNavigation::GetTbl();
	}

	public static function GetList($_attributes = array()) {
		$tables = array(self::GetTbl());
		$attributes = $_attributes;
		$row_conditions = array();

		if (isset($attributes[self::GetFirstKey()])) {
			if (is_array($_attributes[self::GetFirstKey()])) {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetFirstKey() . ' IN (' . Db::Get()->EscapeList($_attributes[self::GetFirstKey()]) . ')');
			} else {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetFirstKey() . ' = ' . Db::escape($_attributes[self::GetFirstKey()]));
			}
			unset($attributes[self::GetFirstKey()]);
		}

		if (isset($attributes[self::GetSecondKey()])) {
			if (is_array($_attributes[self::GetSecondKey()])) {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetSecondKey() . ' IN (' . Db::Get()->EscapeList($_attributes[self::GetSecondKey()]) . ')');
			} else {
				array_push($row_conditions, self::GetTbl() . '.' . self::GetSecondKey() . ' = ' . Db::escape($_attributes[self::GetSecondKey()]));
			}
			unset($attributes[self::GetSecondKey()]);
		}

		if (isset($attributes['is_published'])) {
			array_push($tables, self::GetFirstKeyTable());
			array_push($row_conditions, self::GetFirstKeyTable() . '.is_published = ' . Db::escape($attributes['is_published']));
			array_push($row_conditions, self::GetFirstKey(true) . ' = ' . self::GetTbl() . '.' . self::GetFirstKey());

			array_push($tables, self::GetSecondKeyTable());
			array_push($row_conditions, self::GetSecondKeyTable() . '.is_published = ' . Db::escape($attributes['is_published']));
			array_push($row_conditions, self::GetSecondKey(true) . ' = ' . self::GetTbl() . '.' . self::GetSecondKey());

			unset($attributes['is_published']);
		}

		if ($attributes) {
			$row_conditions = parent::GetQueryCondition($attributes);
		}

		return parent::GetList(
			__CLASS__,
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
			self::$Base = new ActiveRecord(self::ComputeTblName());
			self::$Base->AddForeignKey(Document::GetBase(), true);
			self::$Base->AddForeignKey(DocumentNavigation::GetBase(), true);
		}

		return self::$Base;
	}

	public static function GetTbl() {
		return self::GetBase()->GetTable();
	}

	public static function Load($_value, $_attribute = null) {
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
