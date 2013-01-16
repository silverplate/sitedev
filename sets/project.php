<?php

global $gEnv,
       $gSiteKey,
       $gSiteTitle,
       $gHost,
       $gDbConnectionString,
       $gMail,
       $gBackOfficeMail,
       $gCustomUrls;

if (empty($gSiteKey)) {
    $gSiteKey = 'sitekey';
}

if (empty($gSiteTitle)) {
    $gSiteTitle = 'Система управления SiteDev';
}

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

$gCustomUrls = array();

switch ($gEnv) {
    case 'development':
        ini_set('display_errors', 1);

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'sitedev';
        }

        break;

    case 'staging':
        ini_set('display_errors', 1);

        if (empty($gDbConnectionString)) {
            $gDbConnectionString = 'mysql://u:p@host/db';
        }

        if (empty($gHost)) {
            $gHost = 'dev.sitedev.ru';
        }

        break;

    case 'production':
        ini_set('display_errors', 0);

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
