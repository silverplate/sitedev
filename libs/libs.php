<?php

define('WD', realpath(dirname(__FILE__) . '/..') . '/');

define('DOCUMENT_ROOT', WD . 'www/');
define('SETS',          WD . 'sets/');
define('TEMPLATES',     WD . 'templates/');
define('HANDLERS',      WD . 'handlers/');
define('LIBRARIES',     WD . 'libs/');
define('OBJECTS',       LIBRARIES . 'objects/');

set_include_path(
    LIBRARIES . PATH_SEPARATOR .
    get_include_path()
);

require_once('strings.php');
require_once('dates.php');
require_once('files.php');
require_once('dom.php');
require_once('db.php');
require_once('forms.php');
require_once('form_elements.php');
require_once('phpmailer.php');

function __autoload($_class_name) {
	$class_file = '';

	for ($i = 0; $i < strlen($_class_name); $i++) {
		if ($i != 0 && $_class_name{$i} == strtoupper($_class_name{$i})) {
			$class_file .= '_';
		}
		$class_file .= strtolower($_class_name{$i});
	}

	$class_file .= '.php';

	if (is_file(LIBRARIES . $class_file)) require_once(LIBRARIES . $class_file);
	if (is_file(OBJECTS . $class_file)) require_once(OBJECTS . $class_file);
}

function get_lang_inner_uri() {
	return defined('SITE_LANG') && SITE_LANG ? '/' . SITE_LANG . '/' : '/';
}

function send_email($_mail_pref, $_email, $_subject, $_body, $_is_html = false, $_useEnv = true)
{
    global $g_admin_email;

    $env = defined('ENV') ? ENV : 'stage';

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

		foreach (array('FromName', 'Subject', 'Body') as $name) {
			$mailer->$name = decode($mailer->$name);
		}

        $isEmail = false;
        $emails = is_array($_email) ? $_email : array($_email);

        foreach ($emails as $email) {
            if (is_email($email)) {
                $isEmail = true;
                $mailer->AddAddress($email);
            }
        }

        if ($_useEnv && $env == 'stage') {
            if (!$g_admin_email) {
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
            $mailer->AddAddress($g_admin_email);
            $mailer->IsHTML(true);
            $mailer->Body = '<p>' . $system_body . '</p>' .
                            '<p>Оригинал:</p>' . $mailer->Body;
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
