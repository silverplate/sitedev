<?php

class BackOffice
{
    /**
     * Сортировка списка.
     *
     * @param string $_class Название класса объекты, которого нужно сортировать.
     */
    public static function ajaxSort($_class)
    {
        if (!empty($_POST['items'])) {
            $tmp = new $_class;
            $key = $tmp->getDb()->getPrimary();

            $newSortOrder = array();
            for ($i = 0; $i < count($_POST['items']); $i++) {
                $newSortOrder[$_POST['items'][$i]] = $i;
            }

            $currentSortOrder = array();
            $objects = call_user_func_array(array($_class, 'getList'),
                                            array(array($key => $_POST['items'])));

            foreach ($objects as $item) {
                array_push($currentSortOrder, $item->sortOrder);
            }

            foreach ($objects as $item) {
                $newItemSortOrder = $currentSortOrder[$newSortOrder[$item->getId()]];

                if ($newItemSortOrder) {
                    $item->getDb()->updateAttribute(
                        'sort_order',
                        $newItemSortOrder
                    );
                }
            }

            BoLog::logModule(BoLog::ACT_MODIFY, null, 'Сортировка');
        }
    }
}

function bo_ajax_tree_output($_class_name) {
    global $g_open_branches;

    $data = $_POST;
    $g_parent_id = isset($data['parent_id']) ? $data['parent_id'] : '';
    $g_selected_ids = isset($data['selected_ids']) && is_array($data['selected_ids']) ? $data['selected_ids'] : array();
    $current_id = isset($data['current_object_id']) ? $data['current_object_id'] : '';

    $page = new Page;
    $page->SetTemplate(TEMPLATES . 'bo_http_requests.xsl');
    $page->SetRootNodeName('http_request');
    if (isset($data['type'])) $page->SetRootNodeAttribute('type', 'tree_' . $data['type']);
    if (isset($data['module_name'])) $page->SetRootNodeAttribute('module_name', $data['module_name']);
    if (isset($data['field_name'])) $page->SetRootNodeAttribute('field_name', $data['field_name']);

    if ($g_parent_id) {
        $page->SetRootNodeAttribute('parent_id', $g_parent_id);
    }

    if ($current_id) {
        $page->SetRootNodeAttribute('current_object_id', $current_id);
    }

    foreach ($g_selected_ids as $i) {
        $page->AddContent('<selected><![CDATA[' . $i . ']]></selected>');
    }

    if ($g_selected_ids) {
        $g_open_branches = call_user_func_array(
            $_class_name . '::GetMultiAncestors',
            array($g_selected_ids)
        );

    } else {
        $g_open_branches = array();
    }

    $cookie_branch_name = 'bo_tree';
    if (isset($data['module_name'])) $cookie_branch_name .= '_' . $data['module_name'];
    if (isset($data['field_name'])) $cookie_branch_name .= '_' . $data['field_name'];

    if (!empty($_COOKIE[$cookie_branch_name])) {
        foreach(explode('|', $_COOKIE[$cookie_branch_name]) as $item) {
            if (!in_array($item, $g_open_branches)) array_push($g_open_branches, $item);
        }
    }

    if (isset($data['type']) && $data['type'] == 'single' && !$g_parent_id) {
        $obj = new $_class_name;

        if ('integer' == $obj->GetPrimaryKey()->GetType()) {
            $obj->SetId(0);
        }

        if ($obj->HasAttribute('title')) {
            $obj->SetAttribute('title', 'Нет');

        } elseif ($obj->HasAttribute('name')) {
            $obj->SetAttribute('name', 'Нет');
        }

        $obj->SetAttribute('is_published', 1);
        $xml = $obj->GetXml('bo_list', 'item', bo_ajax_get_branch_xml($_class_name, $g_parent_id, $current_id));
    } else {
        $xml = bo_ajax_get_branch_xml($_class_name, $g_parent_id, $current_id);
    }

    $page->AddContent($xml);

    header('Content-type: text/html; charset=utf-8');
    $page->Output();
}

function bo_ajax_get_branch_xml($_class_name, $_parent_id, $_exclude_id) {
    global $g_open_branches;

    $result = '';
    $conditions = array('parent_id' => empty($_parent_id) ? 'NULL' : $_parent_id);
    $row_conditions = array();

    if ($_exclude_id) {
        array_push(
            $row_conditions,
            call_user_func($_class_name . '::GetPri') . ' != ' . Db::escape($_exclude_id)
        );
    }

    $list = call_user_func_array(
        $_class_name . '::GetList',
        array($conditions, array(), $row_conditions)
    );

    foreach ($list as $item) {
        if ($item->IsChildren($_exclude_id) && in_array($item->GetId(), $g_open_branches)) {
            $result .= $item->GetXml('bo_list', 'item', bo_ajax_get_branch_xml($_class_name, $item->GetId(), $_exclude_id));
        } elseif ($item->IsChildren($_exclude_id)) {
            $result .= $item->GetXml('bo_list', 'item', null, array('has_children' => 'true'));
        } else {
            $result .= $item->GetXml('bo_list', 'item');
        }
    }

    return $result;
}
