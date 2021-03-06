<?php

global $gEnv,
       $gSiteKey,
       $gSiteTitle,
       $gHost,
       $gDbConnectionString,
       $gMail,
       $gBackOfficeMail,
       $gCustomUrls,
       $gAdminEmails,
       $gIsCache;


// Ключ и заголовок

if (empty($gSiteKey)) {
    $gSiteKey = 'sitekey';
}

if (empty($gSiteTitle)) {
    $gSiteTitle = 'Система управления SiteDev';
}


// Почта

if (!empty($gSiteTitle)) {
    if (empty($gMail['from']['name'])) {
        $gMail['from']['name'] = $gSiteTitle;
    }

    if (empty($gMail['subject']['append'])) {
        $gMail['subject']['append'] = $gSiteTitle;
    }

    $gBackOfficeMail['subject']['append'] .= ". $gSiteTitle";

    if (empty($gBackOfficeMail['from']['name'])) {
        $gBackOfficeMail['from']['name'] = $gSiteTitle;
    }
}

// if (
//     empty($gAdminEmails) || (
//         count($gAdminEmails) == 1 &&
//         current($gAdminEmails) == 'support@sitedev.ru'
//     )
// ) {
//     $gAdminEmails = array('developer@support.ru');
// }


// Модули с rewrite

$gCustomUrls = array();


// Окружение

switch ($gEnv) {
    case 'development':
        \App\Error::init(
            \App\Error::MODE_DEVELOPMENT,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'sitedev';
        }

        if (empty($gIsCache) && $gIsCache !== false) {
            $gIsCache = false;
        }

        break;

    case 'staging':
        \App\Error::init(
            \App\Error::MODE_DEVELOPMENT,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'dev.sitedev.ru';
        }

        if (empty($gIsCache) && $gIsCache !== false) {
            $gIsCache = false;
        }

        break;

    case 'production':
        \App\Error::init(
            \App\Error::MODE_PRODUCTION,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'sitedev.ru';
        }

        if (empty($gIsCache) && $gIsCache !== false) {
            $gIsCache = true;
        }

        break;

    default:
        throw new \Exception('Unknown site');
}
