<?php

require_once('../prepend.php');
require_once('filter_lib.php');

$filter = obj_get_filter(true);
$result_items = obj_filter($filter);

if (!$result_items['items'] && $result_items['total'] > 0 && $filter['page'] != 1) {
	$filter['page'] = 1;
	$result_items = obj_filter($filter);
}

$page = new Page();
$page->SetRootNodeName('http_request');
$page->SetRootNodeAttribute('type', 'filter');

if ($filter['selected_id']) {
	$page->SetRootNodeAttribute('selected_id', $filter['selected_id']);
}

$page->SetTemplate(TEMPLATES . 'bo_http_requests.xsl');

if ($result_items['items']) {
	foreach ($result_items['items'] as $item) {
		$page->AddContent($item->GetXml('bo_list', 'item'));
	}

	$page->AddContent('<list_navigation page="' . $filter['page'] . '" per_page="' . $filter['per_page'] . '" total="' . $result_items['total'] . '" />');
}

header('Content-type: text/html; charset=utf-8');
$page->Output();

?>