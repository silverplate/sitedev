<?php

require_once('../prepend.php');

function mce_get_images($_dir) {
	global $g_root_dir;
	$result = array();

	$dir = rtrim($_dir, '/') . '/';
	if (is_dir($dir)) {
		$dir_handle = opendir($dir);
		while ($item = readdir($dir_handle)) {
			if ($item != '.' && $item != '..') {
				if (is_dir($dir . $item)) {
					$result = array_merge($result, mce_get_images($dir . $item));
				} elseif (Image::IsImageExtension(get_file_extension($item))) {
					$result[str_replace(DOCUMENT_ROOT, '/', $dir) . $item] = str_replace($g_root_dir, '', $dir) . $item;
				}
			}
		}
		closedir($dir_handle);
	}

	return $result;
}

$g_root_dir = (isset($_GET['dir']) && $_GET['dir'] && strpos($_GET['dir'], DOCUMENT_ROOT) !== false)
	? $_GET['dir']
	: DOCUMENT_ROOT . 'f/';

$images_js = '';
$images = mce_get_images($g_root_dir);
asort($images);

foreach ($images as $file => $title) {
	if ($images_js) $images_js .= ', ';
	$images_js .= '[\'' . $title . '\', \'' . $file . '\']';
}

echo 'var tinyMCEImageList = new Array(', $images_js, ');';

?>