<?php

setlocale(LC_ALL, 'ru_RU.CP1251');
date_default_timezone_set('Europe/Moscow');

ini_set('default_charset', 'utf-8');
ini_set('error_reporting', E_ALL);
ini_set('magic_quotes_gpc', 0);

define('SITE_KEY', 'sitekey');
define('SITE_TITLE', 'Sitedev');
define('SITE_URL', 'http://sitedev.ru');
define('IS_CACHE', false);
define('IS_USERS', false);
define('DB_PREFIX', 'sd_');
define('DOM_LOAD_OPTIONS', LIBXML_DTDLOAD + LIBXML_COMPACT + LIBXML_NOENT);

$env = null;
$developmentSites = array('flacon');
$stageSites = array('dev.sitedev.ru');
$productionSites = array('sitedev.ru', 'www.sitedev.ru');

if (empty($_SERVER['HTTP_HOST'])) {
    if (!empty($_SERVER['PWD']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
        $script = $_SERVER['PWD'] . '/' . $_SERVER['SCRIPT_FILENAME'];

        foreach ($stageSites as $item) {
            if (strpos($script, '/' . $item . '/') !== false) {
                $env = 'stage';
                break;
            }
        }

        if (empty($env)) {
            foreach ($developmentSites as $item) {
                if (strpos($script, '/' . $item . '/') !== false) {
                    $env = 'development';
                    break;
                }
            }
        }

        if (empty($env)) {
            foreach ($productionSites as $item) {
                if (strpos($script, '/' . $item . '/') !== false) {
                    $env = 'production';
                    break;
                }
            }
        }
    }

} else if (in_array(strtolower($_SERVER['HTTP_HOST']), $developmentSites)) {
    $env = 'development';

} else if (in_array(strtolower($_SERVER['HTTP_HOST']), $stageSites)) {
    $env = 'stage';

} else if (in_array(strtolower($_SERVER['HTTP_HOST']), $productionSites)) {
    $env = 'production';
}


switch ($env) {
    case 'development':
        define('DB_CONNECTION_STRING', 'mysql://kirill:developer@localhost/test/');
        ini_set('display_errors', 1);
        break;

    case 'stage':
        define('DB_CONNECTION_STRING', 'mysql://user:password@host/dbname/');
        ini_set('display_errors', 1);
        break;

    case 'production':
        define('DB_CONNECTION_STRING', 'mysql://user:password@host/dbname/');
        ini_set('display_errors', 0);
        break;

    default:
        throw new Exception('Unknown site');
}

$gCustomUrls = array();

// $g_langs = array('ru' => array('/', 'Русский'),
//                  'en' => array('/eng/', 'Английский'));

$g_mail = $g_bo_mail = array('subject' => '',
                             'from' => 'support@sitedev.ru',
                             'from_name' => SITE_TITLE,
                             'signature' => "\r\n\n\n--\r\nСлужба поддержки\r\nsupport@sitedev.ru",
                             'bcc' => 'support@sitedev.ru');

$g_bo_mail['subject'] = 'Система управления / ';
Db::get()->execute('SET names utf8');
