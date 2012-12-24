<?php

require_once realpath(dirname(__FILE__) . '/../../../core/library') . '/libs.php';
require_once CORE_SETS . 'project.php';
$result = array();


// Init for DB

$boSections = array(
    array('title' => 'Страницы', 'uri' => 'pages', 'description' => 'Работа с навигацией и информационным наполнением страниц сайта.'),
    array('title' => 'Пользователи', 'uri' => 'users', 'description' => 'Редактирование пользователей сайта.', 'is_published' => 0),
    array('title' => 'Обработчики', 'uri' => 'controllers', 'description' => 'Управление обработчиками страниц сайта и блоков данных.'),
    array('title' => 'Шаблоны', 'uri' => 'templates', 'description' => 'Управление шаблонами сайта.'),
    array('title' => 'Типы навигации', 'uri' => 'navigation', 'description' => 'Редактирование типов навигации.', 'is_published' => 0),
    array('title' => 'Пользователи СУ', 'uri' => 'cms-users', 'description' => 'Редактирование пользователей СУ.'),
    array('title' => 'Разделы СУ', 'uri' => 'cms-sections', 'description' => 'Редактирование разделов СУ.'),
    array('title' => 'Логи СУ', 'uri' => 'cms-logs', 'description' => 'Просмотр действий пользователей системы управления.', 'is_published' => 0)
);

$boUsers = array(
    array('title' => 'Разработчик', 'login' => 'developer', 'passwd' => Ext_String::getRandomReadable(8), 'email' => 'support@sitedev.ru')
);

$foControllers = array(
    'common' => array('title' => 'Страница сайта', 'type_id' => 1, 'filename' => 'Common.php', 'is_document_main' => 1, 'is_multiple' => 1),
    'not-found' => array('title' => 'Документ не найден', 'type_id' => 1, 'filename' => 'NotFound.php', 'is_document_main' => 0, 'is_multiple' => 0),
    'apply-images' => array('title' => 'Подставить изображения', 'type_id' => 2, 'filename' => 'ApplyImages.php', 'is_document_main' => 0, 'is_multiple' => 1),
    'subpage-navigation' => array('title' => 'Вложенная навигация', 'type_id' => 2, 'filename' => 'SubpageNavigation.php', 'is_document_main' => 0, 'is_multiple' => 1)
);

$templates = array(
    'common' => array('title' => 'Основной', 'filename' => 'fo.xsl'),
    'modules' => array('title' => 'Общее', 'filename' => 'fo-common.xsl', 'is_document_main' => 0)
);

$foDocuments = array(
    array('/' => array('title' => SITE_TITLE,
                       'folder' => '/',
                       'сontroller' => 'common',
                       'template' => 'common',
                       'navigations' => array('main'))),
    array('/not-found/' => array('title' => 'Документ не найден',
                                 'folder' => 'not-found',
                                 'сontroller' => 'not-found',
                                 'template' => 'common'))
);

$foNavigations = array(
    'main' => array('title' => 'Основная', 'name' => 'main', 'type' => 'tree'),
    'service' => array('title' => 'Сервисная', 'name' => 'service', 'type' => 'list', 'is_published' => 0)
);

$foDataContentType = array(
    'string' => array('title' => 'Строка'),
    'text' => array('title' => 'Текст'),
    'integer' => array('title' => 'Целое число'),
    'xml' => array('title' => 'XML')
);

$content1  = "<h2>Шарапова поможет детям Чернобыля</h2>\r\n";
$content1 .= "<p>Российская теннисистка Мария Шарапова, являющаяся послом доброй воли ООН, планирует посетить Чернобыль. Как сообщает AP, Шарапова посетит область, прилегающую к&nbsp;АЭС, летом следующего года после &laquo;Уимблдона&raquo;. Спортсменка хочет встретиться с&nbsp;детьми-сиротами, живущими недалеко от&nbsp;места аварии.</p>\r\n";
$content1 .= "<p>&laquo;Поездка займет несколько дней, поскольку у&nbsp;меня не&nbsp;так много свободного времени. Всего 28&nbsp;дней в&nbsp;году я&nbsp;могу уделить подобным мероприятиям. Поездка в&nbsp;Чернобыль&nbsp;&mdash; только начало моей деятельности. Я&nbsp;хочу помочь детям, пострадавшим от&nbsp;катастрофы, и&nbsp;посмотреть прямо сейчас, как&nbsp;идет строительство больниц и&nbsp;реабилитационных центров&raquo;.</p>\r\n";
$content1 .= "<p>&laquo;Жестоко, что&nbsp;они&nbsp;не&nbsp;имеют родителей,&nbsp;&mdash; добавила Шарапова.&nbsp;&mdash; Мои мама и&nbsp;папа очень сильно помогли мне в&nbsp;жизни, постоянно окружая меня заботой&raquo;.</p>\r\n";
$content1 .= "<p>В&nbsp;2004&nbsp;году после победы в&nbsp;чемпионской гонке WTA&nbsp;Мария получила автомобиль стоимостью $56 тыс. Эти деньги теннисистка пожертвовала в&nbsp;фонд помощи погибшим заложникам в&nbsp;бесланской школе. Кроме того, когда Шарапова стала послом доброй воли ООН, она пожертвовала $100 тыс., которые пошли на&nbsp;строительство различных учреждений для&nbsp;детей, пострадавших от&nbsp;чернобыльской катастрофы.</p>";

$content2  = '<p>Неправильно набран адрес, или&nbsp;такой страницы на&nbsp;сайте не&nbsp;существует. Если вы&nbsp;видите, что&nbsp;на&nbsp;сайте есть неработающая ссылка, пожалуйста, сообщите нам об&nbsp;этом.</p>';

$foData = array(
    '/' => array(
        array('title' => 'Содержание', 'tag' => 'html', App_Cms_Document_Data_ContentType::GetPri() => 'text', 'content' => $content1),
    ),
    '/not-found/' => array(
        array('title' => 'Содержание', 'tag' => 'html', App_Cms_Document_Data_ContentType::GetPri() => 'text', 'content' => $content2)
    )
);


// Create tables

$sqlTables = file_get_contents('tables.sql');
$sqlTables = str_replace('~db prefix~', DB_PREFIX, $sqlTables);

App_Db::get()->multiExecute($sqlTables);


// Insert start entries
// Sections

$boSectionObjs = array();
foreach ($boSections as $i) {
    $obj = new App_Cms_Bo_Section;
    $obj->fillWithData($i);
    $obj->isPublished = !empty($i['is_published']);
    $obj->create();

    $boSectionObjs[$obj->getId()] = $obj;
}

$result['BO sections'] = count($boSectionObjs);


// Users and user to section links

$boUserObjs = array();
foreach ($boUsers as $i) {
    $obj = new App_Cms_Bo_User;
    $obj->fillWithData($i);
    $obj->setPassword($i['passwd']);

    if (!isset($i['status_id'])) {
        $obj->statusId = 1;
    }

    $obj->create();

    $boUserObjs[$obj->GetId()] = $obj;
    foreach (array_keys($boSectionObjs) as $j) {
        $link = new App_Cms_Bo_UserToSection();
        $link->boUserId = $obj->getId();
        $link->boSectionId = $j;
        $link->create();
    }
}

$result['BO users'] = count($boUserObjs);


// Controllers

$foControllerObjs = array();
foreach ($foControllers as $key => $i) {
    $obj = new App_Cms_Controller();
    $obj->fillWithData($i);
    $obj->isPublished = !empty($i['is_published']);
    $obj->create();

    $foControllerObjs[$key] = $obj;
}

$result['FO controllers'] = count($foControllerObjs);


// Templates

$templatesObjs = array();
foreach ($templates as $key => $i) {
    $obj = new App_Cms_Template();
    $obj->getDb()->fillWithData($i);
    $obj->isPublished = !empty($i['is_published']);
    $obj->isMultiple = !empty($i['is_multiple']);
    $obj->isDocumentMain = !empty($i['is_document_main']);
    $obj->create();

    $templatesObjs[$key] = $obj;
}

$result['FO templates'] = count($templatesObjs);


// Navigation

$foNavigationObjs = array();
foreach ($foNavigations as $key => $i) {
    $obj = new App_Cms_Document_Navigation();
    $obj->fillWithData($i);
    $obj->isPublished = !empty($i['is_published']);
    $obj->create();

    $foNavigationObjs[$key] = $obj;
}

$result['FO navigation'] = count($foNavigationObjs);


// Documents

$foDocumentObjs = array();
foreach ($foDocuments as $level) {
    foreach ($level as $uri => $i) {
        $obj = new App_Cms_Document();
        $obj->fillWithData($i);
        $obj->isPublished = !empty($i['is_published']);

        if (isset($i['сontroller']) && isset($foControllerObjs[$i['сontroller']])) {
            $obj->controllerId = $foControllerObjs[$i['сontroller']]->getId();
        }

        if (isset($i['template']) && isset($templatesObjs[$i['template']])) {
            $obj->templateId = $templatesObjs[$i['template']]->getId();
        }

        $parentUri = str_replace($i['folder'] . '/', '', $uri);
        if (isset($foDocumentObjs[$parentUri])) {
            $obj->parentId = $foDocumentObjs[$parentUri]->getId();
        }

        $obj->create();
        $foDocumentObjs[$uri] = $obj;

        if (isset($i['navigations']) && is_array($i['navigations'])) {
            $links = array();
            foreach ($i['navigations'] as $j) {
                if (isset($foNavigationObjs[$j])) {
                    array_push($links, $foNavigationObjs[$j]->getId());
                }
            }

            if ($links) {
                $obj->updateLinks('navigations', $links);
            }
        }
    }
}

$result['FO documents'] = count($foDocumentObjs);


// Data content type

$foDataContentTypeObjs = array();
foreach ($foDataContentType as $id => $i) {
    $obj = new App_Cms_Document_Data_ContentType();
    $obj->fillWithData($i);
    $obj->id = $id;
    $obj->isPublished = !empty($i['is_published']);
    $obj->create();

    $foDataContentTypeObjs[$obj->getId()] = $obj;
}

$result['FO data content type'] = count($foDataContentTypeObjs);


// Document data

$foDataObjs = array();
foreach ($foData as $uri => $blocks) {
    if (isset($foDocumentObjs[$uri])) {
        foreach ($blocks as $i) {
            $obj = new App_Cms_Document_Data();
            $obj->fillWithData($i);
            $obj->foDocumentId = $foDocumentObjs[$uri]->getId();
            $obj->isPublished = !empty($i['is_published']);
            $obj->isMount = !empty($i['is_mount']);

            if (
                isset($i['сontroller']) &&
                isset($foControllerObjs[$i['сontroller']])
            ) {
                $obj->foControllerId = $foControllerObjs[$i['сontroller']]->getId();
            }

            $obj->create();
            $foDataObjs[$obj->getId()] = $obj;
        }
    }

}

$result['FO data'] = count($foDataObjs);


// Сообщение о результате

echo '<p>Таблицы в базу данных добавлены:</p><pre>';
print_r($result);
echo '</pre>';

echo '<p>Для доступа <a href="/cms/">в систему управления</a> используйте логин ';
echo '<b><code>' . $boUsers[0]['login'] . '</code></b> ';
echo 'и пароль <b><code>' . $boUsers[0]['passwd'] . '</code></b>.</p>';

$isError = false;
$permissions = array(array(DATA_CONTROLLERS, true),
                     array(DOCUMENT_CONTROLLERS, true),
                     array(DOCUMENT_ROOT . 'f/', true));

foreach ($permissions as $path) {
    if (!system('chmod ' . ($path[1] ? '-R ' : '') . '0777 ' . $path[0])) {
        $isError = true;
    }
}

if ($isError) {
    echo '<p style="color: #c00;">Не забудьте установить права:</p>';
    foreach ($permissions as $path) {
        echo '<code>chmod ';

        if ($path[1]) {
            echo '-R ';
        }

        echo '0777 ' . $path[0] . '</code><br>';
    }

} else if ($permissions) {
    echo '<p>Нужные права на файлы установлены.</p>';
}

if (system('rm -rf ' . dirname(__FILE__))) {
    echo '<p>Установочные файлы удалены.</p>';

} else {
    echo '<p style="color: #c00;">Не забудьте удалить файлы установки:</p>';
    echo '<code>rm -rf ' . dirname(__FILE__) . '/</code>';
}
