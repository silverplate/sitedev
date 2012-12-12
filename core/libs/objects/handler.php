<?php

class Handler extends ActiveRecord {
	private static $Base;
	const TABLE = 'fo_handler';


    public function getClassName()
    {
        if ($this->type_id == 1) {
            $class = 'Document';

        } else if ($this->type_id == 2) {
            $class = 'DocumentData';

        } else {
            throw new Exception('Unkown handler type');
        }

        $class .= ucfirst(
            transformUnderlineToCase(
                get_file_name($this->getFilename())
            )
        );

        return $class;
    }

	public static function GetPathByType($_type_id) {
		switch ($_type_id) {
			case 1:
				return HANDLERS . 'documents/';
			case 2:
				return HANDLERS . 'data/';
			default:
				return false;
		}
	}

	public function GetFolder() {
		return self::GetPathByType($this->GetAttribute('type_id'));
	}

	public function GetFilename() {
		return $this->GetFolder() && $this->GetAttribute('filename')
			? $this->GetFolder() . $this->GetAttribute('filename')
			: false;
	}

	public function GetContent() {
		return $this->GetFilename() && is_file($this->GetFilename())
			? file_get_contents($this->GetFilename())
			: false;
	}

	public function CheckUnique() {
		$attributes = array();
		foreach ($this->Attributes as $attr) {
			if ($attr->IsUnique()) {
				$attributes[$attr->GetName()] = $attr->GetValue(false);
			}
		}

		return !$attributes || !self::GetList($attributes, array('count' => 1), array(self::GetPri() . ' != ' . Db::escape($this->GetId())));
	}

	private function GetPrefix() {
		switch ($this->GetAttribute('type_id')) {
			case 1:
				return 'с';
			case 2:
				return 'б';
			default:
				return false;
		}
	}

	public function GetXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(__CLASS__);
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->GetAttribute('is_published') == 1) $result .= ' is_published="true"';
				if ($this->GetPrefix()) $result .= ' prefix="' . $this->GetPrefix() . '"';
				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function Delete() {
		Db::Get()->Execute('UPDATE ' . Document::GetTbl() . ' SET ' . self::GetPri() . ' = "" WHERE ' . self::GetPri() . ' = ' . Db::escape($this->GetId()));
		Db::Get()->Execute('UPDATE ' . DocumentData::GetTbl() . ' SET ' . self::GetPri() . ' = "" WHERE ' . self::GetPri() . ' = ' . Db::escape($this->GetId()));

		remove_file($this->GetFilename());
		parent::Delete();
	}

	public function Update() {
		$prev = self::Load($this->GetId());
		parent::Update();

		if ($prev->GetFilename() && $prev->GetFilename() != $this->GetFilename()) {
			rename($prev->GetFilename(), $this->GetFilename());
		}
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

	public static function Load($_value, $_attribute = null) {
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array(), $_row_conditions = array()) {
		$parameters = $_parameters;
		if (!isset($parameters['sort_order'])) {
			$parameters['sort_order'] = 'type_id, title';
		}

		return parent::GetList(
			__CLASS__,
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$parameters,
			$_row_conditions
		);
	}
}

?>
