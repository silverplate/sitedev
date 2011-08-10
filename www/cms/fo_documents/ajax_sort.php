<?php

require('../prepend.php');

$data = $_POST;

if (isset($data['items']) && $data['items']) {
	$new_sort_order = array();
	for ($i = 0; $i < count($data['items']); $i++) {
		$new_sort_order[$data['items'][$i]] = $i;
	}

	$current_sort_order = array();
	$objects = DocumentData::GetList(array(DocumentData::GetPri() => $data['items']));
	foreach ($objects as $item) {
		array_push($current_sort_order, $item->GetAttribute('sort_order'));
	}

	foreach ($objects as $item) {
		$new_item_sort_order = $current_sort_order[$new_sort_order[$item->GetId()]];
		if ($new_item_sort_order) {
			$item->UpdateAttribute('sort_order', $new_item_sort_order);
		}
	}

	BoLog::LogModule(BoLog::ACT_MODIFY, null, 'Сортировка');
}

?>