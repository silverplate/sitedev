<?php

class DocumentDataHandler {
	private $DocumentData;
	protected $Document;
	private $Content;
	private $Type;
	protected $_dataDocument;

	public function __construct(&$_data, &$_document = null) {
		$this->DocumentData = $_data;
		$this->Document = $_document;

        $dataDocumentId = $_data->getAttribute(Document::getPri());
        $this->_dataDocument = $_document && $_document->getId() == $dataDocumentId
                             ? $_document
                             : Document::load($dataDocumentId);

		$this->SetContent($this->DocumentData->GetAttribute('content'));
		$this->SetType($this->DocumentData->GetTypeId());
	}

	public function GetContent() {
		return $this->Content;
	}

	public function SetContent($_value) {
		$this->Content = $_value;
	}

	public function GetType() {
		return $this->Type;
	}

	public function SetType($_type) {
		return $this->Type = $_type;
	}

	public function GetXml() {
		$result = '<' . $this->DocumentData->GetAttribute('tag') . '>';

		$result .= $this->GetType() == 'xml'
			? get_cdata_back($this->GetContent())
			: get_cdata($this->GetContent());

		$result .= '</' . $this->DocumentData->GetAttribute('tag') . '>';

		return $result;
	}
}

class DocumentData extends ActiveRecord {
	private static $Base;
	private $Handler;

	const TABLE = 'fo_data';

	public static function GetApplyTypes() {
		return array(1 => 'На&nbsp;эту страницу', 'На&nbsp;вложенные', 'На&nbsp;эту и&nbsp;вложенные');
	}

	public function CheckApplyType() {
		if (!in_array((int) $this->GetAttribute('apply_type_id'), array_keys(self::GetApplyTypes()))) {
			$this->SetAttribute('apply_type_id', 1);
		}
	}

	public function Create() {
		$this->CheckApplyType();
		parent::Create();
	}

	public function Update() {
		$this->CheckApplyType();
		parent::Update();
	}

	public function GetParsedContent($_content) {
		switch ($this->GetAttribute(DocumentDataContentType::GetPri())) {
			case 'integer':
				return (int) $_content;
			case 'float':
				return (float) $_content;
			default:
				return get_cdata_back($_content);
		}
	}

	public function GetTypeId() {
		return $this->GetAttribute(DocumentDataContentType::GetPri());
	}

	public function SetTypeId($_type_id) {
		return $this->SetAttribute(DocumentDataContentType::GetPri(), $_type_id);
	}

	public function GetXml($_additional_xml = null) {
		$result = '<document_data id="' . $this->GetId() . '"';

		if ($this->GetTypeId()) {
			$result .= ' type_id="' . $this->GetTypeId() . '"';
		}

		if ($this->GetAttribute('tag')) {
			$result .= ' tag="' . $this->GetAttribute('tag') . '"';
		}

		if ($this->GetAttribute('is_published') == 1) {
			$result .= ' is_published="true"';
		}

		if ($this->GetAttribute('is_mount') == 1) {
			$result .= ' is_mount="true"';
		}

		$result .= '>';

		if ($this->GetTitle()) {
			$result .= '<title>' . get_cdata($this->GetTitle()) . '</title>';
		}

		if ($this->GetHandler()) {
			$result .= '<handler>' . get_cdata($this->GetHandler()->GetTitle()) . '</handler>';
		}

		if ($this->GetAttribute('content') != '') {
			$result .= '<content>' . get_cdata($this->GetAttribute('content')) . '</content>';
		}

		if (IS_USERS && $this->GetAttribute('auth_status_id') != User::AUTH_GROUP_ALL && User::GetAuthGroupTitle($this->GetAttribute('auth_status_id'))) {
			$result .= '<auth_group>' . get_cdata(User::GetAuthGroupTitle($this->GetAttribute('auth_status_id'))) . '</auth_group>';
		}

		if ($_additional_xml) {
			$result .= '<additional>' . $_additional_xml . '</additional>';
		}

		return $result . '</document_data>';
	}

	public function GetHandler() {
		if (is_null($this->Handler)) {
			$this->Handler = $this->GetAttribute(Handler::GetPri())
				? Handler::Load($this->GetAttribute(Handler::GetPri()))
				: false;
		}

		return $this->Handler;
	}

	public function GetHandlerFile() {
		return $this->GetHandler() ? $this->GetHandler()->GetFilename() : false;
	}

	public static function initHandler($_handler, &$_documentData, &$_document)
	{
        require_once $_handler->getFilename();

        $class = $_handler->getClassName();
        if (class_exists($class)) {
            return new $class($_documentData, $_document);
        }

		return false;
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
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 30, true);
			self::$Base->AddForeignKey(Document::GetBase());
			self::$Base->AddForeignKey(Handler::GetBase());
			self::$Base->AddForeignKey(DocumentDataContentType::GetBase());
			self::$Base->AddAttribute('auth_status_id', 'int');
			self::$Base->AddAttribute('tag', 'varchar', 255);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('content', 'text');
			self::$Base->AddAttribute('apply_type_id', 'int');
			self::$Base->AddAttribute('is_mount', 'boolean');
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

	public static function Load($_value, $_attribute = null) {
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array(), $_row_conditions = array()) {
		return parent::GetList(
			__CLASS__,
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters,
			$_row_conditions
		);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
