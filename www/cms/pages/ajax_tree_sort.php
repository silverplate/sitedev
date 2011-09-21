<?php

require('../prepend.php');

$data = $_POST;
if (isset($data['branches']) && $data['branches']) {
	$changed = array();
	$parent = array();
	$objects = Document::GetList();

	foreach ($data['branches'] as $i) {
		$current_order = $new_order = array();
		for ($j = 0; $j < count($data['branch_' . $i]); $j++) {
			$id = $data['branch_' . $i][$j];
			if (!isset($objects[$id])) return false;

			$parent[$id] = $i;
			$new_order[$id] = $j + 1;
		}

		$k = 0;
		foreach ($objects as $j) {
			if (in_array($j->GetId(), $data['branch_' . $i])) {
				$current_order[++$k] = $j->GetAttribute('sort_order');
			}
		}

		for ($j = 0; $j < count($data['branch_' . $i]); $j++) {
			$id = $data['branch_' . $i][$j];
			$objects[$id]->SetAttribute('sort_order', $current_order[$new_order[$id]]);
			array_push($changed, $id);
		}
	}

	foreach ($objects as $i) {
		if (isset($parent[$i->GetId()])) {
			$is_root = ($i->GetAttribute('folder') != '/' || $parent[$i->GetId()] == '');
			$is_unique = Document::CheckUnique($parent[$i->GetId()], $i->GetAttribute('folder'), $i->GetId());
			$is_not_custom_url = (!isset($g_custom_urls) || !in_array(trim($objects[$i->GetId()]->GetAttribute('uri'), '/'), $g_custom_urls));

			if ($is_root && $is_unique && $is_not_custom_url) {
				$objects[$i->GetId()]->SetAttribute('parent_id', $parent[$i->GetId()]);
				array_push($changed, $i->GetId());
			}
		}
	}

	foreach (array_unique($changed) as $i) {
		$objects[$i]->Update();
	}

	BoLog::LogModule(BoLog::ACT_MODIFY, null, 'Сортировка');
}

?>