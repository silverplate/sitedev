<?php

/**
 * @todo Убрать у путей последний слеш, чтобы было
 * по аналогии со значениями, возвращаемыми методами PHP?
 */
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

define('LIBRARIES', WD . 'libs/');
define('CORE_LIBRARIES', CORE_PATH . 'libs/');

define('OBJECTS', LIBRARIES . 'objects/');
define('CORE_OBJECTS', CORE_LIBRARIES . 'objects/');

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    LIBRARIES,
    CORE_LIBRARIES,
    OBJECTS,
    CORE_OBJECTS
)));

require_once 'strings.php';
require_once 'dates.php';
require_once 'files.php';
require_once 'dom.php';
require_once 'db.php';
require_once 'forms.php';
require_once 'form_elements.php';
require_once 'phpmailer.php';

function __autoload($_className)
{
    $classFile = '';
    $className = preg_replace('/^Core_/', '', $_className);

    for ($i = 0; $i < strlen($className); $i++) {
        if (
            $i != 0 &&
            $className{$i} == strtoupper($className{$i}) &&
            $className{$i} != '_' &&
            $className{$i - 1} != '_'
        ) {
            $classFile .= '_';
        }

        $classFile .= strtolower($className{$i});
    }

    $classFile .= '.php';

    // Вариант, когда директории в названии
    // класса разделены подчеркиванием.

    if (strpos($className, '_') !== false) {
        $classFileAlt = str_replace('_', '/', $className) . '.php';
    }

    $paths = array(LIBRARIES, CORE_LIBRARIES, OBJECTS, CORE_OBJECTS);

    foreach ($paths as $path) {
        if (is_file($path . $classFile)) {
            require_once $path . $classFile;
            break;

        } else if (isset($classFileAlt) && is_file($path . $classFileAlt)) {
            require_once $path . $classFileAlt;
            break;
        }
    }
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
