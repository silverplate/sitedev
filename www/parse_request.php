<?php

require_once('prepend.php');

$g_cache = '' == SITE_LANG
	? new ProjectCache()
	: new ProjectCache(null, SITE_LANG);

if ($g_cache->IsAvailable() && $g_cache->IsCache()) {
	echo $g_cache;

} else {
	$real_url = parse_url($_SERVER['REQUEST_URI']);
	$url = parse_url(get_custom_url($_SERVER['REQUEST_URI']));
	$document = Document::Load($url['path'], 'uri');

	if ($document && $document->GetAttribute('link') && $document->GetAttribute('link') != $real_url['path']) {
		goToUrl($document->GetAttribute('link'));

	} else {
		if (
			$document && $document->GetHandler() &&
			($document->GetAttribute('is_published') == 1 || IS_SHOW_HIDDEN) &&
			(!$document->GetAttribute('auth_status_id') || is_null(User::GetAuthGroup()) || $document->GetAttribute('auth_status_id') & User::GetAuthGroup())
		) {
			$handler = Document::InitHandler($document->GetHandlerFile(), $document);
			$handler->Execute();
			$handler->Output();
		} else {
			document_not_found();
		}
	}
}


function get_custom_url($_url) {
	global $g_custom_urls;

	$url = '' != SITE_LANG && 0 === strpos($_url, '/' . SITE_LANG . '/')
		? substr($_url, strlen(SITE_LANG) + 1)
		: $_url;

	if (empty($g_custom_urls)) {
		return $url;

	} else {
		$urls = $g_custom_urls;
		array_walk($urls, 'url_escape');
		preg_match('/^\/(' . implode('|', $urls) . ')\//', $url, $matches);

		return (SITE_LANG ? '/' . SITE_LANG : '') . ($matches ? $matches[0] : $url);
	}
}

function url_escape(&$_item) {
	$_item = preg_replace('/(\/|\-)/', '\\\$1', $_item);
}

?>
