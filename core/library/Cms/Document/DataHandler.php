<?php

abstract class Core_Cms_Document_DataHandler
{
	private $DocumentData;
	protected $Document;
	private $Content;
	private $Type;
	protected $_dataDocument;

	public function __construct(&$_data, &$_document = null) {
		$this->DocumentData = $_data;
		$this->Document = $_document;

        $dataDocumentId = $_data->getAttribute(App_Cms_Document::getPri());
        $this->_dataDocument = $_document && $_document->getId() == $dataDocumentId
                             ? $_document
                             : App_Cms_Document::load($dataDocumentId);

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
