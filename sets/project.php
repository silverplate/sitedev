<?php

global $gEnv,
       $gSiteKey,
       $gSiteTitle,
       $gHost,
       $gDbConnectionString,
       $gMail,
       $gBackOfficeMail,
       $gCustomUrls,
       $gAdminEmails;


// Ключ и заголовк

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
        App_Error::init(
            App_Error::MODE_DEVELOPMENT,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'sitedev';
        }

        break;

    case 'staging':
        App_Error::init(
            App_Error::MODE_DEVELOPMENT,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'dev.sitedev.ru';
        }

        break;

    case 'production':
        App_Error::init(
            App_Error::MODE_PRODUCTION,
            SETS . 'error.log',
            $gAdminEmails
        );

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'sitedev.ru';
        }

        break;

    default:
        throw new Exception('Unknown site');
}
