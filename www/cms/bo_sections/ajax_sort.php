<?php

require('../prepend.php');

if (isset($_POST['items']) && $_POST['items']) {
	$new_sort_order = array();
	for ($i = 0; $i < count($_POST['items']); $i++) {
		$new_sort_order[$_POST['items'][$i]] = $i;
	}

	$current_sort_order = array();
	$objects = BoSection::GetList(array(BoSection::GetPri() => $_POST['items']));
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