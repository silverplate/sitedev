<?php

abstract class Core_Cms_Back_Page extends App_Cms_Page
{
	private $UpdateStatus = array();

	public function __construct($_is_authorize = true) {
		parent::__construct();

		if ($_is_authorize) {
			if ($this->IsAllowed()) $this->SetTemplate(TEMPLATES . 'bo.xsl');
			elseif ($this->IsAuthorized()) $this->SetTemplate(TEMPLATES . 'bo_404.xsl');
			else $this->SetTemplate(TEMPLATES . 'bo_403.xsl');
		}

		$this->AddSystem($this->_getUserNavigationXml());
		$this->AddContent($this->_getUserSectionsXml());
	}

	public function IsAuthorized() {
		global $g_user;
		return isset($g_user) && $g_user;
	}

	public function IsAllowed() {
		global $g_user, $g_section, $g_section_start_url;
		return $this->IsAuthorized() && (isset($g_section) && $g_section && $g_user->IsSection($g_section->GetId()) || $this->Url['path'] == $g_section_start_url);
	}

	protected function _getUserNavigationXml()
	{
		global $g_user;

		$xml = '';

		if (!empty($g_user)) {
			foreach ($g_user->getSections() as $item) {
				$xml .= $item->getXml();
			}

			$xml = Ext_Xml::node('navigation', $xml);
		}

		return $xml;
	}

	protected function _getUserSectionsXml()
	{
		global $g_user;

		$xml = '';

		foreach ($g_user->getSections() as $key => $section) {
			$xml .= $section->getXml(
		        null,
		        Ext_Xml::notEmptyCdata('description', $section->description)
	        );
		}

		return Ext_Xml::notEmptyNode('cms-sections', $xml);
	}

	public function SetUpdateStatus($_type, $_message = null) {
		$this->UpdateStatus = array('type' => $_type, 'message' => $_message);
	}

	public function GetXml() {
		global $g_user;

		if (defined('SITE_TITLE')) {
			$this->AddSystem('<title><![CDATA[' . SITE_TITLE . ']]></title>');
		}

		if ($g_user) $this->AddSystem($g_user->GetXml('bo_user'));
		$this->AddSystem(App_Cms_Session::Get()->getXml(null, App_Cms_Session::Get()->GetWorkmateXml()));

		if ($this->UpdateStatus) {
			$update_status = '<update_status type="' . $this->UpdateStatus['type'] . '">';

			if ($this->UpdateStatus['message']) {
				$update_status .= '<![CDATA[' . $this->UpdateStatus['message'] . ']]>';
			}

			$this->AddContent($update_status . '</update_status>');
		}

		return parent::GetXml();
	}
}

?>
