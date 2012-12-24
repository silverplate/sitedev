<?php

require_once('../prepend.php');
require_once('filter_lib.php');

$filter = bo_log_get_filter(true);
$result_items = bo_log_filter($filter);

if (!$result_items['items'] && $result_items['total'] > 0 && $filter['page'] != 1) {
	$filter['page'] = 1;
	$result_items = bo_log_filter($filter);
}

$page = new App_Cms_Page();
$page->SetRootNodeName('http_request');
$page->SetRootNodeAttribute('type', 'bo_logs');

$page->SetTemplate(TEMPLATES . 'bo_http_requests.xsl');

if ($result_items['items']) {
	$users = App_Cms_Bo_User::GetList();
	$sections = App_Cms_Bo_Section::GetList();
	$actions = App_Cms_Bo_Log::GetActions();

	foreach ($result_items['items'] as $item) {
		$xml = array();
		if ($item->GetAttribute(App_Cms_Bo_User::GetPri()) && isset($users[$item->GetAttribute(App_Cms_Bo_User::GetPri())])) {
			$xml['user'] = $users[$item->GetAttribute(App_Cms_Bo_User::GetPri())]->GetTitle();

		} else if ($item->userName) {
			$xml['user'] = $item->userName;
		}

		if ($item->GetAttribute(App_Cms_Bo_Section::GetPri()) && isset($sections[$item->GetAttribute(App_Cms_Bo_Section::GetPri())])) {
			$xml['section'] = $sections[$item->GetAttribute(App_Cms_Bo_Section::GetPri())]->GetTitle();

		} else if ($item->sectionName) {
			$xml['section'] = $item->sectionName;
		}

		if (isset($actions[$item->actionId])) {
			$xml['action'] = $actions[$item->actionId];
		}

		$append_xml = '';
		foreach ($xml as $name => $value) {
			$append_xml .= "<{$name}><![CDATA[{$value}]]></{$name}>";
		}

		$page->AddContent($item->GetXml('bo_list', 'item', $append_xml));
	}

	$page->AddContent('<list_navigation page="' . $filter['page'] . '" per_page="' . $filter['per_page'] . '" total="' . $result_items['total'] . '" />');
}

header('Content-type: text/html; charset=utf-8');
$page->Output();

?>