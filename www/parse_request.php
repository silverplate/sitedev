<?php

require_once 'prepend.php';


$gCache = '' == SITE_LANG
        ? new ProjectCache()
        : new ProjectCache(null, SITE_LANG);

if ($gCache->isAvailable() && $gCache->isCache()) {
    echo $gCache;

} else {
    $realUrl = parse_url($_SERVER['REQUEST_URI']);
    $url = parse_url(getCustomUrl($_SERVER['REQUEST_URI']));
    $document = Document::load($url['path'], 'uri');

    if (
        $document &&
        $document->getAttribute('link') &&
        $document->getAttribute('link') != $realUrl['path']
    ) {
        goToUrl($document->getAttribute('link'));

    } else {
        if (
            $document &&
            $document->getHandler() &&
            ($document->getAttribute('is_published') == 1 || IS_SHOW_HIDDEN) &&
            (!$document->getAttribute('auth_status_id') || is_null(User::getAuthGroup()) || $document->getAttribute('auth_status_id') & User::getAuthGroup())
        ) {
            $handler = Document::initHandler($document->getHandler(), $document);
            $handler->execute();
            $handler->output();

        } else {
            documentNotFound();
        }
    }
}


function getCustomUrl($_url)
{
    global $gCustomUrls;


    $url = '' != SITE_LANG && 0 === strpos($_url, '/' . SITE_LANG . '/')
         ? substr($_url, strlen(SITE_LANG) + 1)
         : $_url;

    if (empty($gCustomUrls)) {
        return $url;

    } else {
        $urls = $gCustomUrls;
        array_walk($urls, 'escapeUrl');
        preg_match('/^\/(' . implode('|', $urls) . ')\//', $url, $matches);
        return (SITE_LANG ? '/' . SITE_LANG : '') . ($matches ? $matches[0] : $url);
    }
}

function escapeUrl(&$_item)
{
    $_item = preg_replace('/(\/|\-)/', '\\\$1', $_item);
}
