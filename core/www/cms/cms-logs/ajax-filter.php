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
$page->SetRootNodeAttribute('type', 'back-logs');

$page->SetTemplate(TEMPLATES . 'back/http-requests.xsl');

if ($result_items['items']) {
	$users = App_Cms_Back_User::GetList();
	$sections = App_Cms_Back_Section::GetList();
	$actions = App_Cms_Back_Log::GetActions();

	foreach ($result_items['items'] as $item) {
		$xml = array();

		if ($item->backUserId && isset($users[$item->backUserId])) {
			$xml['user'] = $users[$item->backUserId]->GetTitle();

		} else if ($item->userName) {
			$xml['user'] = $item->userName;
		}

		if ($item->backSectionId && isset($sections[$item->backSectionId])) {
			$xml['section'] = $sections[$item->backSectionId]->GetTitle();

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

		$page->AddContent($item->getBackOfficeXml($append_xml));
	}

	$page->AddContent('<list_navigation page="' . $filter['page'] . '" per_page="' . $filter['per_page'] . '" total="' . $result_items['total'] . '" />');
}

header('Content-type: text/html; charset=utf-8');
$page->Output();

?>
