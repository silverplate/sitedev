<?php

define('WD', realpath(dirname(__FILE__) . '/../..') . '/');
define('CORE_PATH', WD . 'core/');

define('DOCUMENT_ROOT', WD . 'www/');
define('CORE_DOCUMENT_ROOT', CORE_PATH . 'www/');

define('SETS', WD . 'sets/');
define('CORE_SETS', CORE_PATH . 'sets/');

define('TEMPLATES', WD . 'templates/');
define('CORE_TEMPLATES', CORE_PATH . 'templates/');

define('HANDLERS', WD . 'handlers/');
define('CORE_HANDLERS', CORE_PATH . 'handlers/');

define('LIBRARIES', WD . 'library/');
define('CORE_LIBRARIES', CORE_PATH . 'library/');

define('MODELS', WD . 'models/');

define('DATA_CONTROLLERS', LIBRARIES . 'App/Cms/Document/Data/Handler/');
define('DOCUMENT_CONTROLLERS', LIBRARIES . 'App/Cms/Document/Handler/');

require_once 'strings.php';
require_once 'dates.php';
require_once 'files.php';
require_once 'dom.php';

/**
 * Файлы классов могут находиться в папках ~/core/library, ~/library, ~/models.
 *
 * Префикс Core в названии класса указавает на то, что класс из ядра СУ и файл
 * с классом должен находиться внутри папки ~/core.
 *
 * Префикс Cms указывает на то, что класс нужен для функционирования СУ и файл
 * находится в подпапке Cms, которая может быть как внутри core, так и нет.
 *
 * Префикс App говорит о том, что класс переопределяет один из классов ядра,
 * и в нем может быть уникальный для сайта функционал. Класса с таким префиксом
 * в ядре быть не может.
 *
 * Класс без перечисленных префиксов является единственным для системы, ничего
 * не переопределяет и может находится как в ядре (например, внешняя библиотека
 * PhpMailer), так и только на сайте.
 *
 * @param string $_class
 */
function __autoload($_class)
{
    $path = explode('_', $_class);
    $include = array(MODELS, LIBRARIES);
    $core = array(CORE_LIBRARIES);

    if ($path[0] == 'Core') {
        $include = $core;
        unset($path[0]);
        $path = array_values($path);

    } else if ($path[0] != 'App') {
        $include = array_merge($include, $core);
    }

    $class = $path[count($path) - 1];
//     $class = strtolower(trim(preg_replace('/([A-Z])/', '-\1', $class), '-'));
    $path[count($path) - 1] = "$class.php";
//     $localPath = strtolower(implode('/', $path));
    $localPath = implode('/', $path);

    foreach ($include as $dir) {
        if (is_file($dir . $localPath)) {
            require_once $dir . $localPath;
            break;
        }
    }

//     if ($_class == 'App_ActiveRecord')
//         d($path, $localPath, $include);
}

function get_lang_inner_uri() {
    return defined('SITE_LANG') && SITE_LANG ? '/' . SITE_LANG . '/' : '/';
}

function send_email($_mail_pref, $_email, $_subject, $_body, $_is_html = false, $_useEnv = true)
{
    global $gAdminEmails;

    $env = defined('ENV') ? ENV : 'staging';

    if ($_useEnv && $env == 'development') {
        return false;
    }

    if (is_array($_mail_pref) && !empty($_mail_pref['from'])) {
        $mailer = new phpmailer();
        $mailer->IsMail();
        $mailer->IsHTML($_is_html);
        $mailer->CharSet = 'windows-1251';
        $mailer->From = $_mail_pref['from'];
        $mailer->Sender = $mailer->From;
        $mailer->Subject = $_subject;
        $mailer->Body = $_body;

        if (isset($_mail_pref['signature'])) {
            $mailer->Body .= $_mail_pref['signature'];
        }

        if (isset($_mail_pref['from_name'])) {
            $mailer->FromName = $_mail_pref['from_name'];
        }

        if (isset($_mail_pref['subject'])) {
            $mailer->Subject = $_mail_pref['subject'] . $mailer->Subject;
        }

        if (isset($_mail_pref['bcc'])) {
            foreach (list_to_array($_mail_pref['bcc']) as $item) {
                $mailer->AddBCC($item);
            }
        }

        $isEmail = false;
        $emails = is_array($_email) ? $_email : array($_email);

        foreach ($emails as $email) {
            if (is_email($email)) {
                $isEmail = true;
                $mailer->AddAddress($email);
            }
        }

        if ($_useEnv && $env == 'staging') {
            if (empty($gAdminEmails)) {
                return false;
            }

            $system_body = '<p>Письмо направлено:</p>';

            for ($i = 0; $i < count($emails); $i++) {
                $system_body .= $emails[$i];

                if (count($emails) != $i + 1) {
                    $system_body .= ', ';
                }
            }

            $mailer->ClearAllRecipients();

            foreach ($gAdminEmails as $adminEmail) {
                $mailer->addAddress($adminEmail);
            }

            $mailer->Body =
                '<p>' . $system_body . '</p>' .
                '<p>Оригинал:</p>' .
                ($mailer->ContentType == 'text/plain' ? nl2br($mailer->Body) : $mailer->Body);

            $mailer->IsHTML(true);
        }

        foreach (array('FromName', 'Subject', 'Body') as $name) {
            $mailer->$name = decode($mailer->$name);
        }

        return $isEmail ? $mailer->Send() : false;
    }

    return false;
}

function getAdvParams()
{
    return array('utm_source', 'adv');
}

function getAdvMailParams()
{
    $result = array();
    $params = getAdvParams();
    array_push($params, 'HTTP_REFERER');

    foreach ($params as $item) {
        $value = advGetCookie($item);

        if (!empty($value)) {
            $result[$item] = $value;
        }
    }

    return $result;
}

function advMonitor()
{
    foreach (getAdvParams() as $item) {
        if (!empty($_GET[$item])) {
            advSetCookie($item, $_GET[$item]);
        }
    }

    $envName = 'HTTP_REFERER';
    if (!empty($_SERVER[$envName])) {
        $referer = strtolower($_SERVER[$envName]);
        $url = parse_url($referer);

        if (
            !empty($url['host']) &&
            !empty($_SERVER['HTTP_HOST']) &&
            $url['host'] != strtolower($_SERVER['HTTP_HOST'])
        ) {
            $prev = advGetCookie($envName);
            if (!$prev || $prev != $referer) {
                advSetCookie($envName, $referer);
            }
        }
    }
}

function advSetCookie($_name, $_value)
{
    $name = 'adv_' . strtolower($_name);
    $_COOKIE[$name] = $_value;
    setcookie($name, $_value, 0, '/', '.' . $_SERVER['HTTP_HOST']);
}

function advGetCookie($_name)
{
    $name = 'adv_' . strtolower($_name);
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
}

?>
