<?php

require_once(realpath(dirname(__FILE__) . '/../../libs') . '/libs.php');
require_once(SETS . 'project.php');
$result = array();

// Init for DB
$bo_sections = array(
	array('title' => 'Страницы', 'uri' => 'fo_documents', 'description' => 'Работа с навигацией и информационным наполнением страниц сайта.'),
	array('title' => 'Обработчики', 'uri' => 'fo_handlers', 'description' => 'Управление обработчиками страниц сайта и блоков данных.'),
	array('title' => 'Типы навигации', 'uri' => 'fo_navigation', 'description' => 'Редактирование типов навигации.', 'is_published' => 0),
	array('title' => 'Пользователи', 'uri' => 'fo_users', 'description' => 'Редактирование пользователей сайта.'),
	//array('title' => 'Новости', 'uri' => 'fo_news', 'description' => 'Раздел позволяет редактировать новости.'),
	//array('title' => 'Категории новостей', 'uri' => 'fo_news_category', 'description' => 'Редактирование новостных категорий.'),
	array('title' => 'Пользователи СУ', 'uri' => 'bo_users', 'description' => 'Редактирование пользователей СУ.'),
	array('title' => 'Разделы СУ', 'uri' => 'bo_sections', 'description' => 'Редактирование разделов СУ.'),
	array('title' => 'Группы разделов СУ', 'uri' => 'bo_section_groups', 'description' => 'Распределение разделов СУ по группам.', 'is_published' => 0),
	array('title' => 'Логи СУ', 'uri' => 'bo_logs', 'description' => 'Просмотр действий пользователей системы управления.', 'is_published' => 0)
);

$bo_users = array(
	array('title' => 'Разработчик', 'login' => 'developer', 'passwd' => get_random_string_optimized(8), 'email' => 'support@sitedev.ru')
);

$fo_handlers = array(
	'common' => array('title' => 'Страница сайта', 'type_id' => 1, 'filename' => 'common.php', 'is_document_main' => 1, 'is_multiple' => 1),
	'not_found' => array('title' => 'Документ не найден', 'type_id' => 1, 'filename' => 'not_found.php', 'is_document_main' => 0, 'is_multiple' => 0),
	//'news' => array('title' => 'Новости', 'type_id' => 1, 'filename' => 'news.php'),
	//'last_news' => array('title' => 'Последние новости', 'type_id' => 2, 'filename' => 'last_news.php'),
);

$fo_documents = array(
	array(
		'/' => array('title' => SITE_TITLE, 'folder' => '/', 'handler' => 'common', 'navigations' => array('main')),
		//'/ru/' => array('title' => SITE_TITLE, 'folder' => 'ru', 'handler' => 'common', 'navigations' => array('main')),
		//'/en/' => array('title' => SITE_TITLE, 'folder' => 'en', 'handler' => 'common', 'navigations' => array('main'))
	),
	array(
		'/not_found/' => array('title' => 'Документ не найден', 'folder' => 'not_found', 'handler' => 'not_found'),
		//'/ru/not_found/' => array('title' => 'Документ не найден', 'folder' => 'not_found', 'handler' => 'common'),
		//'/ru/news/' => array('title' => 'Новости', 'folder' => 'news', 'handler' => 'news', 'navigations' => array('main')),
		//'/en/not_found/' => array('title' => 'Document not found', 'folder' => 'not_found', 'handler' => 'common'),
		//'/en/news/' => array('title' => 'News', 'folder' => 'news', 'handler' => 'news', 'navigations' => array('main')),
	)
);

$fo_navigations = array(
	'main' => array('title' => 'Основная', 'name' => 'main', 'type' => 'tree'),
	'service' => array('title' => 'Сервисная', 'name' => 'service', 'type' => 'list', 'is_published' => 0)
);

$fo_data_content_type = array(
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

$fo_data = array(
	'/' => array(
		array('title' => 'Содержание', 'tag' => 'html', DocumentDataContentType::GetPri() => 'text', 'content' => $content1),
	),
	'/not_found/' => array(
		array('title' => 'Содержание', 'tag' => 'html', DocumentDataContentType::GetPri() => 'text', 'content' => $content2)
	),
	//'/ru/' => array(
	//	array('title' => 'Содержание', 'tag' => 'html', DocumentDataContentType::GetPri() => 'text', 'content' => $content1),
	//	//array('title' => 'Количество последних новостей', 'tag' => 'last_news', DocumentDataContentType::GetPri() => 'integer', 'handler' => 'last_news', 'content' => '3')
	//),
	//'/ru/not_found/' => array(
	//	array('title' => 'Содержание', 'tag' => 'html', DocumentDataContentType::GetPri() => 'text', 'content' => $content2)
	//),
	//'/en/' => array(
		//array('title' => 'Количество последних новостей', 'tag' => 'last_news', DocumentDataContentType::GetPri() => 'integer', 'handler' => 'last_news', 'content' => '3')
	//)
);

//$news1_title = 'Фондовые торги в&nbsp;Японии завершились падением индекса Nikkei на&nbsp;фоне новостей из&nbsp;США';
//$news1_annce = 'Рынок акций в&nbsp;Японии 15&nbsp;августа 2007&nbsp;г. закрылся падением индекса Nikkei до рекордно низкой отметки, начиная с&nbsp;декабря 2006&nbsp;г.';
//$news1_cont  = "<p>Поводом к&nbsp;неудачному выступлению японского рынка послужили пессимистичные новости с&nbsp;американских биржевых площадок. Накануне фондовые торги в&nbsp;США завершились значительным понижением основных сводных индексов на&nbsp;фоне продолжающегося кризиса в&nbsp;сфере ипотечного кредитования, который приобретает все&nbsp;больший глобальный масштаб.</p>\r\n";
//$news1_cont .= "<p>Так, крупнейшие игроки финансового сектора Японии уже предоставили общественности информацию о&nbsp;конкретных негативных эффектах от&nbsp;кредитного кризиса на&nbsp;корпоративную отчетность. Финансовая корпорация Mitsubishi UFJ&nbsp;Financial Group сообщила о&nbsp;том, что&nbsp;на конец июля 2007&nbsp;г. потеряла около 5&nbsp;млрд иен (42,6 млн долл.) в&nbsp;связи с&nbsp;кризисом subprime в&nbsp;США. Третий по&nbsp;величине банк в&nbsp;Японии Sumitomo Mitsui Financial Group заявил о&nbsp;том, что&nbsp;за апрель-июнь 2007&nbsp;г. понес убытки в&nbsp;размере &laquo;нескольких миллиардов иен&raquo; после продажи ценных бумаг общей стоимостью в&nbsp;350&nbsp;млрд иен, обеспеченных ипотечными кредитами США.</p>\r\n";
//$news1_cont .= "<p>Несмотря на&nbsp;то что&nbsp;аналитики отмечают, что&nbsp;потери ведущих финансовых компаний скорее психологические и&nbsp;оказывают негативное влияние на&nbsp;общий индекс доверия японских компаний к&nbsp;глобальному финансовому рынку, котировки в&nbsp;банковском секторе Японии продолжают находиться в&nbsp;нисходящем тренде. Акции Mitsubishi UFJ&nbsp;Financial Group подешевели на&nbsp;5,3%, опустившись до минимальной отметки, начиная с&nbsp;23&nbsp;мая 2006&nbsp;г., а&nbsp;бумаги Sumitomo Mitsui Financial Group&nbsp;&mdash; на&nbsp;5,9%, зафиксировав рекордную низкую стоимость, начиная с&nbsp;10&nbsp;мая 2004&nbsp;г.</p>\r\n";
//$news1_cont .= "<p>Не лучшие результаты продемонстрировали и&nbsp;крупнейшие японские экспортеры. На негативную динамику их&nbsp;котировок существенное влияние оказали опасения того, что&nbsp;потребительский спрос в&nbsp;США может сократиться на&nbsp;фоне проблем в&nbsp;сфере ипотечного кредитования и&nbsp;прогнозов замедления темпов роста американской экономики.</p>";

//$news2_title = 'Flossie grazes Hawaii, depression forms in&nbsp;Gulf';
//$news2_annce = 'Flossie weakened to&nbsp;a&nbsp;tropical storm as&nbsp;it began to&nbsp;pass south of&nbsp;the&nbsp;&laquo;Big Island&raquo; of&nbsp;Hawaii late Tuesday. A&nbsp;tropical depression formed in&nbsp;the&nbsp;Gulf of&nbsp;Mexico. In&nbsp;the&nbsp;open Atlantic, Tropical Storm Dean is&nbsp;expected to&nbsp;become a&nbsp;hurricane by&nbsp;Friday.';
//$news2_cont  = "<p>As&nbsp;of 11&nbsp;p.m. in&nbsp;Hawaii (5 a.m. ET), Flossie had&nbsp;weakened from a&nbsp;Category 1&nbsp;hurricane to&nbsp;a&nbsp;tropical storm with maximum sustained winds of&nbsp;about 70&nbsp;mph. The&nbsp;storm was&nbsp;located about 175&nbsp;miles south-southwest of&nbsp;Hilo and&nbsp;about 280&nbsp;miles south-southeast of&nbsp;Honolulu. Flossie was&nbsp;moving west-northwest at&nbsp;about 10&nbsp;mph, according to&nbsp;the&nbsp;Central Pacific Hurricane Center in&nbsp;Honolulu.</p>\r\n";
//$news2_cont .= "<p>The&nbsp;storm is&nbsp;expected to&nbsp;weaken further over the&nbsp;next 24&nbsp;hours.</p>\r\n";
//$news2_cont .= "<p>CNN&nbsp;meteorologist Reynolds Wolf, on&nbsp;Hawaii's Big&nbsp;Island, said winds were picking up&nbsp;late Tuesday and&nbsp;waves were growing higher.</p>\r\n";
//$news2_cont .= "<p>&laquo;The Big&nbsp;Island will see&nbsp;the&nbsp;onset of&nbsp;tropical storm-force winds, 39&nbsp;mph&nbsp;and higher, this evening,&raquo; the&nbsp;hurricane center said in&nbsp;its 8&nbsp;p.m. advisory. &laquo;East to&nbsp;southeast winds of&nbsp;40&nbsp;to&nbsp;50&nbsp;mph&nbsp;with higher gusts are&nbsp;likely as&nbsp;Hurricane Flossie passes south of&nbsp;The&nbsp;Big&nbsp;Island this evening and&nbsp;overnight.&raquo;</p>\r\n";
//$news2_cont .= "<p>Forecasters said the&nbsp;surf facing the&nbsp;south shore of&nbsp;the&nbsp;Big&nbsp;Island will remain at&nbsp;20&nbsp;to&nbsp;25&nbsp;feet Tuesday, and&nbsp;surf along east-facing shores will be&nbsp;about 10&nbsp;to&nbsp;12&nbsp;feet.</p>\r\n";
//$news2_cont .= "<p>Hawaii residents rushed to&nbsp;supermarkets, loading up&nbsp;on&nbsp;water, batteries and&nbsp;nonperishable foods such as&nbsp;peanut butter, noodles and&nbsp;bread. But&nbsp;many of&nbsp;the&nbsp;items were picked over.</p>";

//$fo_news = array(
//	array('lang' => 'ru', 'publishing_date' => '2007-08-15', 'title' => $news1_title, 'announcement' => $news1_annce, 'content' => $news1_cont),
//	array('lang' => 'en', 'publishing_date' => '2007-02-06', 'title' => $news2_title, 'announcement' => $news2_annce, 'content' => $news2_cont)
//);


// Create tables
$sql_tables = file_get_contents('tables.sql');
$sql_tables = str_replace('~db prefix~', DB_PREFIX, $sql_tables);
//$sql_tables = str_replace(' ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', '', $sql_tables);
Db::Get()->MultiExecute($sql_tables);

// Insert start entries
// Sections
$bo_section_objs = array();
foreach ($bo_sections as $i) {
	$obj = new BoSection;
	$obj->DataInit($i);
	if (!isset($i['is_published'])) {
		$obj->SetAttribute('is_published', 1);
	}
	$obj->Create();
	$bo_section_objs[$obj->GetId()] = $obj;
}
$result['BO sections'] = count($bo_section_objs);

// Users and user to section links
$bo_user_objs = array();
foreach ($bo_users as $i) {
	$obj = new BoUser;
	$obj->DataInit($i);
	$obj->SetPassword($i['passwd']);
	if (!isset($i['status_id'])) {
		$obj->SetAttribute('status_id', 1);
	}
	$obj->Create();
	$bo_user_objs[$obj->GetId()] = $obj;
	foreach (array_keys($bo_section_objs) as $j) {
		$link = new BoUserToSection;
		$link->SetAttribute(BoUser::GetPri(), $obj->GetId());
		$link->SetAttribute(BoSection::GetPri(), $j);
		$link->Create();
	}
}
$result['BO users'] = count($bo_user_objs);

// Handlers
$fo_handler_objs = array();
foreach ($fo_handlers as $key => $i) {
	$obj = new Handler;
	$obj->DataInit($i);
	if (!isset($i['is_published'])) {
		$obj->SetAttribute('is_published', 1);
	}
	$obj->Create();
	$fo_handler_objs[$key] = $obj;
}
$result['FO handlers'] = count($fo_handler_objs);


// Navigation
$fo_navigation_objs = array();
foreach ($fo_navigations as $key => $i) {
	$obj = new DocumentNavigation;
	$obj->DataInit($i);
	if (!isset($i['is_published'])) {
		$obj->SetAttribute('is_published', 1);
	}
	$obj->Create();
	$fo_navigation_objs[$key] = $obj;
}
$result['FO navigation'] = count($fo_navigation_objs);

// Documents
$fo_document_objs = array();
foreach ($fo_documents as $level) {
	foreach ($level as $uri => $i) {
		$obj = new Document;
		$obj->DataInit($i);

		if (!isset($i['is_published'])) {
			$obj->SetAttribute('is_published', 1);
		}

		if (isset($i['handler']) && isset($fo_handler_objs[$i['handler']])) {
			$obj->SetAttribute(Handler::GetPri(), $fo_handler_objs[$i['handler']]->GetId());
		}

		$parent_uri = str_replace($i['folder'] . '/', '', $uri);
		if (isset($fo_document_objs[$parent_uri])) {
			$obj->SetAttribute('parent_id', $fo_document_objs[$parent_uri]->GetId());
		}

		$obj->Create();
		$fo_document_objs[$uri] = $obj;

		if (isset($i['navigations']) && is_array($i['navigations'])) {
			$links = array();
			foreach ($i['navigations'] as $j) {
				if (isset($fo_navigation_objs[$j])) {
					array_push($links, $fo_navigation_objs[$j]->GetId());
				}
			}
			if ($links) $obj->UpdateLinks('navigations', $links);
		}
	}
}
$result['FO documents'] = count($fo_document_objs);

// Data content type
$fo_data_content_type_objs = array();
foreach ($fo_data_content_type as $id => $i) {
	$obj = new DocumentDataContentType;
	$obj->DataInit($i);
	$obj->SetId($id);
	if (!isset($i['is_published'])) {
		$obj->SetAttribute('is_published', 1);
	}
	$obj->Create();
	$fo_data_content_type_objs[$obj->GetId()] = $obj;
}
$result['FO data content type'] = count($fo_data_content_type_objs);

// Document data
$fo_data_objs = array();
foreach ($fo_data as $uri => $blocks) {
	if (isset($fo_document_objs[$uri])) {
		foreach ($blocks as $i) {
			$obj = new DocumentData;
			$obj->DataInit($i);
			$obj->SetAttribute(Document::GetPri(), $fo_document_objs[$uri]->GetId());

			if (!isset($i['is_published'])) {
				$obj->SetAttribute('is_published', 1);
			}

			if (!isset($i['is_mount'])) {
				$obj->SetAttribute('is_mount', 1);
			}

			if (isset($i['handler']) && isset($fo_handler_objs[$i['handler']])) {
				$obj->SetAttribute(Handler::GetPri(), $fo_handler_objs[$i['handler']]->GetId());
			}

			$obj->Create();
			$fo_data_objs[$obj->GetId()] = $obj;
		}
	}

}
$result['FO data'] = count($fo_data_objs);

// News
//$fo_news_objs = array();
//foreach ($fo_news as $i) {
//	$obj = new News;
//	$obj->DataInit($i);
//	if (!isset($i['is_published'])) {
//		$obj->SetAttribute('is_published', 1);
//	}
//	$obj->Create();
//	$fo_news_objs[$obj->GetId()] = $obj;
//}
//$result['FO news'] = count($fo_news_objs);

// Сообщение о результате
echo '<p>Таблицы в базу данных добавлены:</p><pre>';
print_r($result);
echo '</pre>';

$permissions = array(
	array(LIBRARIES, false),
	array(HANDLERS . '*', true),
	array(DOCUMENT_ROOT . 'f/', true)
);

echo '<p>Для доступа <a href="/cms/">в систему управления</a> используйте логин ';
echo '<b><code>' . $bo_users[0]['login'] . '</code></b> ';
echo 'и пароль <b><code>' . $bo_users[0]['passwd'] . '</code></b>.</p>';

echo '<p style="color: #c00;">Не забудьте установить права:</p>';
foreach ($permissions as $path) {
	echo '<code>chmod ';
	if ($path[1]) echo '-R ';
	echo '0777 ' . $path[0] . '</code><br>';
}

echo '<p style="color: #c00;">И удалить файлы установки:</p>';
echo '<code>rm -rf ' . dirname(__FILE__) . '/</code>';

?>