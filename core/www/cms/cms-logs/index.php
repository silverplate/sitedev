<?php

require_once '../prepend.php';
require_once 'filter-lib.php';

$page = new App_Cms_Back_Page();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
    $filter = bo_log_get_filter();

    $list_xml  = '<local-navigation type="content_filter" is-date="true"';
    $list_xml .= ' today="' . date('Y-m-d') . '" week="' . date('Y-m-d', strtotime('-1 week')) . '" month="' . date('Y-m-d', strtotime('-1 month')) . '" all_from="' . date('Y-m-d', strtotime('-5 years')) . '" all_till="' . date('Y-m-d', strtotime('+5 years')) . '"';
    $list_xml .= ' from="' . date('Y-m-d', $filter['from_date']) . '" till="' . date('Y-m-d', $filter['till_date']) . '"';
    foreach (array('open') as $item) {
        if ($filter['is_' . $item]) $list_xml .= ' is-' . $item . '="true"';
    }
    $list_xml .= '>';

    $list_xml .= '<filter-param type="multiple" name="users"';
    if ($filter['is_users']) $list_xml .= ' is-selected="true"';
    $list_xml .= '><title><![CDATA[Пользователь]]></title>';
    foreach (App_Cms_Back_User::GetList() as $item) {
        $list_xml .= '<item value="' . $item->GetId() . '"';
        if (is_array($filter['users']) && in_array($item->GetId(), $filter['users'])) {
            $list_xml .= ' is-selected="true"';
        }
        $list_xml .= '><![CDATA[' . $item->GetTitle() . ']]></item>';
    }
    $list_xml .= '</filter-param>';

    $list_xml .= '<filter-param type="multiple" name="sections"';
    if ($filter['is_sections']) $list_xml .= ' is-selected="true"';
    $list_xml .= '><title><![CDATA[Раздел]]></title>';
    foreach (App_Cms_Back_Log::GetList() as $item) {
        $list_xml .= '<item value="' . $item->GetId() . '"';
        if (is_array($filter['sections']) && in_array($item->GetId(), $filter['sections'])) {
            $list_xml .= ' is-selected="true"';
        }
        $list_xml .= '><![CDATA[' . $item->GetTitle() . ']]></item>';
    }
    $list_xml .= '</filter-param>';

    $list_xml .= '<filter-param type="multiple" name="actions"';
    if ($filter['is_actions']) $list_xml .= ' is-selected="true"';
    $list_xml .= '><title><![CDATA[Действие]]></title>';
    foreach (App_Cms_Back_Log::GetActions() as $id => $title) {
        $list_xml .= '<item value="' . $id . '"';
        if (is_array($filter['actions']) && in_array($id, $filter['actions'])) {
            $list_xml .= ' is-selected="true"';
        }
        $list_xml .= '><![CDATA[' . $title . ']]></item>';
    }
    $list_xml .= '</filter-param>';
    $list_xml .= '</local-navigation>';

    $page->AddContent('<module type="simple">' . $list_xml . '</module>');
}

$page->Output();
