<?php

class BoPage extends Page {
	private $UpdateStatus = array();

	public function __construct($_is_authorize = true) {
		parent::__construct();

		if ($_is_authorize) {
			if ($this->IsAllowed()) $this->SetTemplate(TEMPLATES . 'bo.xsl');
			elseif ($this->IsAuthorized()) $this->SetTemplate(TEMPLATES . 'bo_404.xsl');
			else $this->SetTemplate(TEMPLATES . 'bo_403.xsl');
		}

		$this->AddSystem($this->GetUserNavigationXml());
		$this->AddContent($this->GetUserSectionsXml());
	}

	public function IsAuthorized() {
		global $g_user;
		return isset($g_user) && $g_user;
	}

	public function IsAllowed() {
		global $g_user, $g_section, $g_section_start_url;
		return $this->IsAuthorized() && (isset($g_section) && $g_section && $g_user->IsSection($g_section->GetId()) || $this->Url['path'] == $g_section_start_url);
	}

	protected function GetUserNavigationXml() {
		global $g_user;

		$result = '';

		if (isset($g_user) && $g_user) {
			$result .= '<navigation>';
			foreach ($g_user->GetSections() as $item) {
				$result .= $item->GetXml('bo_navigation', 'item');
			}
			$result .= '</navigation>';
		}

		return $result;
	}

	protected  function GetUserSectionsXml() {
		global $g_user, $g_section;

		$sections_xml = '';

		if (isset($g_user) && $g_user) {
			$user_sections_list = $g_user->GetSections();

			if ($user_sections_list) {
				foreach ($user_sections_list AS $key => $section) {
					$append_xml = $section->GetAttribute('description')
						? '<description><![CDATA[' . $section->GetAttribute('description') . ']]></description>'
						: '';
					$sections_xml .= $section->GetXml('bo_navigation', 'item', $append_xml);
				}
			}

			unset($user_sections_list, $item, $section);

			if($sections_xml) $sections_xml = '<cms_sections>' . $sections_xml . '</cms_sections>';
		}

		return $sections_xml;
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
		$this->AddSystem(Session::Get()->GetXml(null, Session::Get()->GetWorkmateXml()));

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
