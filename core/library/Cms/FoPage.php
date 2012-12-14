<?php

abstract class Core_Cms_FoPage extends Core_Cms_Page
{
	protected $IsShowHidden;

	public function __construct() {
		parent::__construct();
		$this->IsShowHidden = defined('IS_SHOW_HIDDEN') && IS_SHOW_HIDDEN;
		if ($this->IsShowHidden) $this->AddSystemAttribute('is_show_hidden');
	}

	public function GetXml() {
		if (SITE_TITLE) $this->AddSystem('<title><![CDATA[' . SITE_TITLE . ']]></title>');
		if (IS_USERS && App_Cms_User::Get()) $this->AddSystem(User::Get()->GetXml('page_system'));
		$this->AddSystem(App_Cms_Session::Get()->GetXml());

		return parent::GetXml();
	}

	public function output($_is_404 = false)
	{
		global $gCache;

		if (isset($_GET['xml']) && defined('IS_ADMIN_MODE') && IS_ADMIN_MODE) {
			// header('Content-type: text/xml; charset=' . ini_get('default_charset'));
			header('Content-type: text/xml; charset=utf-8');
			echo getXmlDocumentForRoot($this->getXml(), $this->getRootNodeName());

		} else if ($this->Template) {
			$content = $this->GetHtml();
			echo $content;

			if ($gCache && $gCache->isAvailable() && !$_is_404) {
				$gCache->set($content);
			}

		} else {
			documentNotFound();
		}
	}
}
