<?php

require_once('../prepend.php');
require_once('filter_lib.php');

$filter = bo_log_get_filter(true);
$result_items = bo_log_filter($filter);

if (!$result_items['items'] && $result_items['total'] > 0 && $filter['page'] != 1) {
	$filter['page'] = 1;
	$result_items = bo_log_filter($filter);
}

$page = new Page();
$page->SetRootNodeName('http_request');
$page->SetRootNodeAttribute('type', 'bo_logs');

$page->SetTemplate(TEMPLATES . 'bo_http_requests.xsl');

if ($result_items['items']) {
	$users = BoUser::GetList();
	$sections = BoSection::GetList();
	$actions = BoLog::GetActions();

	foreach ($result_items['items'] as $item) {
		$xml = array();
		if ($item->GetAttribute(BoUser::GetPri()) && isset($users[$item->GetAttribute(BoUser::GetPri())])) {
			$xml['user'] = $users[$item->GetAttribute(BoUser::GetPri())]->GetTitle();
		} elseif ($item->GetAttribute('user_name')) {
			$xml['user'] = $item->GetAttribute('user_name');
		}

		if ($item->GetAttribute(BoSection::GetPri()) && isset($sections[$item->GetAttribute(BoSection::GetPri())])) {
			$xml['section'] = $sections[$item->GetAttribute(BoSection::GetPri())]->GetTitle();
		} elseif ($item->GetAttribute('section_name')) {
			$xml['section'] = $item->GetAttribute('section_name');
		}

		if (isset($actions[$item->GetAttribute('action_id')])) {
			$xml['action'] = $actions[$item->GetAttribute('action_id')];
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