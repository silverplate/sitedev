<?php

require('prepend.php');

$page = new App_Cms_Bo_Page();
$page->SetTitle('Система управления');

if ($page->IsAllowed()) {
	/*
	$sections_xml = '';
	foreach ($g_user->GetSections() as $item) {
		$append_xml = $item->description
			? '<description><![CDATA[' . $item->description . ']]></description>'
			: '';
		$sections_xml .= $item->GetXml('bo_navigation', 'item', $append_xml);
	}
	$page->AddContent('<cms_sections>' . $sections_xml . '</cms_sections>');
	*/
}

$page->Output();

?>