<?php

require('../prepend.php');

global $gCustomUrls;
$data = $_POST;

if (!empty($data['branches'])) {
	$changed = array();
	$parent = array();
	$objects = App_Cms_Document::getList();

	foreach ($data['branches'] as $i) {
		$order = $newOrder = array();

		for ($j = 0; $j < count($data['branch_' . $i]); $j++) {
			$id = $data['branch_' . $i][$j];
			if (!isset($objects[$id])) {
			    return false;
			}

			$parent[$id] = $i;
			$newOrder[$id] = $j + 1;
		}

		$k = 0;
		foreach ($objects as $j) {
			if (in_array($j->getId(), $data['branch_' . $i])) {
				$order[++$k] = $j->getAttribute('sort_order');
			}
		}

		for ($j = 0; $j < count($data['branch_' . $i]); $j++) {
			$id = $data['branch_' . $i][$j];
			$objects[$id]->setAttribute('sort_order', $order[$newOrder[$id]]);
			array_push($changed, $id);
		}
	}

	foreach ($objects as $i) {
		if (isset($parent[$i->getId()])) {
			$isRoot = $i->getAttribute('folder') != '/'
			       || $parent[$i->getId()] == '';

			$isUnique = App_Cms_Document::checkUnique($parent[$i->getId()],
			                                  $i->getAttribute('folder'), $i->getId());

			$isNotCustomUrl = empty($gCustomUrls) || !in_array(trim($objects[$i->getId()]->getAttribute('uri'), '/'),
			                                                   $gCustomUrls);

			if ($isRoot && $isUnique && $isNotCustomUrl) {
			    $parentId = empty($parent[$i->getId()])
			              ? 'NULL'
			              : $parent[$i->getId()];

				$objects[$i->getId()]->setAttribute('parent_id', $parentId);
				array_push($changed, $i->getId());
			}
		}
	}

	foreach (array_unique($changed) as $i) {
		$objects[$i]->update();
	}

	App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_MODIFY, null, 'Сортировка');
}