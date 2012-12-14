<?php

abstract class Core_BackOffice
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

            BoLog::logModule(BoLog::ACT_MODIFY, null, 'Сортировка');
        }
    }
}
