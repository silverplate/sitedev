<?php

abstract class Core_Cms_BackOffice
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
            $objects = call_user_func_array(
                array($_class, 'getList'),
                array(array($key => $_POST['items']))
            );

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

            App_Cms_Bo_Log::logModule(App_Cms_Bo_Log::ACT_MODIFY, null, 'Сортировка');
        }
    }

    public static function ajaxTreeOutput($_className)
    {
        global $gOpenBranches;

        $data = $_POST;
        $parentId = isset($data['parent_id']) ? $data['parent_id'] : '';
        $selectedIds = isset($data['selected_ids']) &&
                       is_array($data['selected_ids'])
                     ? $data['selected_ids']
                     : array();

        $currentId = isset($data['current_object_id'])
                   ? $data['current_object_id']
                   : '';

        $page = new App_Cms_Page();
        $page->setTemplate(TEMPLATES . 'bo_http_requests.xsl');
        $page->setRootNodeName('http_request');

        if (isset($data['type'])) {
            $page->setRootNodeAttribute('type', 'tree_' . $data['type']);
        }

        if (isset($data['module_name'])) {
            $page->setRootNodeAttribute('module_name', $data['module_name']);
        }

        if (isset($data['field_name'])) {
            $page->setRootNodeAttribute('field_name', $data['field_name']);
        }

        if ($parentId) {
            $page->setRootNodeAttribute('parent_id', $parentId);
        }

        if ($currentId) {
            $page->setRootNodeAttribute('current_object_id', $currentId);
        }

        foreach ($selectedIds as $i) {
            $page->addContent(Ext_Xml::cdata('selected', $i));
        }

        if ($selectedIds) {
            $gOpenBranches = call_user_func_array(
                $_className . '::GetMultiAncestors',
                array($selectedIds)
            );

        } else {
            $gOpenBranches = array();
        }

        $cookieBranchName = 'bo-tree';

        if (isset($data['module_name'])) {
            $cookieBranchName .= '-' . $data['module_name'];
        }

        if (isset($data['field_name'])) {
            $cookieBranchName .= '-' . $data['field_name'];
        }

        if (!empty($_COOKIE[$cookieBranchName])) {
            foreach (explode('|', $_COOKIE[$cookieBranchName]) as $item) {
                if (!in_array($item, $gOpenBranches)) {
                    $gOpenBranches[] = $item;
                }
            }
        }

        if (isset($data['type']) && $data['type'] == 'single' && !$parentId) {
            $obj = new $_className;
            $obj->isPublished = 1;

            if ('integer' == $obj->getPrimaryKey()->getType()) {
                $obj->setId(0);
            }

            if ($obj->hasAttribute('title')) {
                $obj->title = 'Нет';

            } else if ($obj->hasAttribute('name')) {
                $obj->name = 'Нет';
            }

            $xml = $obj->getXml(
                'bo_list',
                'item',
                self::ajaxGetBranchXml($_className, $parentId, $currentId)
            );

        } else {
            $xml = self::ajaxGetBranchXml($_className, $parentId, $currentId);
        }

        $page->addContent($xml);

        header('Content-type: text/html; charset=utf-8');
        $page->output();
    }

    public static function ajaxGetBranchXml($_className, $_parentId, $_excludeId)
    {
        global $gOpenBranches;

        $result = '';
        $conditions = array('parent_id' => empty($_parentId) ? 'NULL' : $_parentId);
        $rowConditions = array();

        if ($_excludeId) {
            $rowConditions[] = call_user_func($_className . '::GetPri') . ' != ' .
                               App_Db::escape($_excludeId);
        }

        $list = call_user_func_array(
            $_className . '::getList',
            array($conditions, array(), $rowConditions)
        );

        foreach ($list as $item) {
            if (
                $item->isChildren($_excludeId) &&
                in_array($item->getId(), $gOpenBranches)
            ) {
                $result .= $item->getXml(
                    'bo_list',
                    'item',
                    self::ajaxGetBranchXml($_className, $item->getId(), $_excludeId)
                );

            } else if ($item->isChildren($_excludeId)) {
                $result .= $item->getXml(
                    'bo_list',
                    'item',
                    null,
                    array('has_children' => 'true')
                );

            } else {
                $result .= $item->getXml('bo_list', 'item');
            }
        }

        return $result;
    }
}
