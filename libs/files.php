<?php

function get_file_name($_file) {
	$info = pathinfo($_file);
	return isset($info['extension']) && $info['extension']
		? substr($info['basename'], 0, strlen($info['basename']) - strlen($info['extension']) - 1)
		: $info['basename'];
}

function get_file_extension($_file) {
	$info = pathinfo($_file);
	return isset($info['extension']) ? strtolower($info['extension']) : '';
}

function createDirectory($_dir, $_isRecursive = true)
{
	if (!is_dir($_dir)) {
		$mask = umask(0);
		mkdir($_dir, 0777, $_isRecursive);
		umask($mask);
	}
}

function create_directory($_dir, $_isRecursive = false) {
    createDirectory($_dir, $_isRecursive);
}

function remove_file($_file) {
	return is_file($_file) ? unlink($_file) : false;
}

function removeDirectory($_dir, $_isSselfDelete = true, $_isOnlyFiles = false)
{
    remove_directory($_dir, $_isSselfDelete, $_isOnlyFiles);
}

function remove_directory($_dir, $_is_self_delete = true, $_is_only_files = false) {
	$dir = rtrim($_dir, '/') . '/';
	if (is_dir($dir)) {
		$dir_handle = opendir($dir);
		$item = readdir($dir_handle);

		while ($item) {
			if ($item != '.' && $item != '..') {
				if (is_dir($dir . '/' . $item)) {
					if (!$_is_only_files) remove_directory($dir . '/' . $item);
				} else {
					unlink($dir . '/' . $item);
				}
			}

			$item = readdir($dir_handle);
		}

		if ($_is_self_delete) rmdir($dir);
		closedir($dir_handle);
	}
}

function empty_directory($_dir, $_is_only_files = false) {
	remove_directory($_dir, false, $_is_only_files);
}

function is_directory_empty($_dir, $_ignore = null) {
	if (is_dir($_dir)) {
		$dir_handle = opendir($_dir);
		$ignore = (is_null($_ignore)) ? array() : explode(' ', strtolower($_ignore));
		$item = readdir($dir_handle);

		while ($item) {
			if ($item != '.' && $item != '..' && (!$ignore || !in_array(strtolower($item), $ignore))) {
				return false;
			}

			$item = readdir($dir_handle);
		}

		closedir($dir_handle);
		return true;
	}
	return false;
}

function move_directory($_from, $_to) {
	$from = rtrim($_from, '/') . '/';
	$to = rtrim($_to, '/') . '/';

	if (is_dir($from)) {
		create_directory($to, true);

		$dir_handle = opendir($from);
		$item = readdir($dir_handle);

		while ($item) {
			if ($item != '.' && $item != '..' && $to != $from . $item . '/') {
				if (is_dir($from . $item)) {
					move_directory($from . $item, $to . $item);
					remove_directory($from . $item);
				} else {
					rename($from . $item, $to . $item);
				}
			}

			$item = readdir($dir_handle);
		}

		closedir($dir_handle);
		if (is_directory_empty($from)) rmdir($from);
	}
}

function get_file_by_name($_dir, $_name) {
	if (is_dir($_dir)) {
		$dir = rtrim($_dir, '/') . '/';
		foreach (array('.*', '*') as $try) {
			$search = glob($dir . $_name . $try);
			if ($search) return $search[0];
		}
	}
	return false;
}

function write_log($_log_file, $_label) {
	write_file($_log_file, implode("\t", array(date('Y-m-d H:i:s'), $_label)) . "\n", 'append');
}

function write_file($_file, $_content, $_mode = null) {
    $mode = !empty($_mode) && strpos($_mode, 'append') !== false
          ? FILE_APPEND
          : null;

	@file_put_contents($_file, $_content, $mode);
	@chmod($_file, 0777);
}

function get_max_upload_size() {
	return (int) ini_get('upload_max_filesize');
}

function get_size_measure($_size) {
	$result = array();

	if ($_size / (1024 * 1024) > 0.5) {
		$result['value'] = $_size / (1024 * 1024);
		$result['measure'] = 'МБ';
		$result['measure_en'] = 'MB';

	} elseif ($_size / 1024 > 0.5) {
		$result['value'] = $_size / 1024;
		$result['measure'] = 'КБ';
		$result['measure_en'] = 'KB';

	} else {
		$result['value'] = $_size;
		$result['measure'] = 'байт';
		$result['measure_en'] = 'bite';
	}

	$result['value'] = format_number($result['value']);
	$result['string'] = $result['value'] . '&nbsp;' . $result['measure'];
	$result['string_en'] = $result['value'] . '&nbsp;' . $result['measure_en'];

	return $result;
}

function goToUrl($_url) {
	header("Location: $_url");
	exit;
}

function reload($_append = null)
{
//     $uri = empty($_SERVER['REQUEST_URI'])
//          ? './'
//          : preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
//
//     goToUrl($uri . $_append);

    goToUrl('./' . $_append);
}

function documentNotFound()
{
    header('HTTP/1.0 404 Not Found');

    if (class_exists('Document')) {
        require_once LIBRARIES . 'page.php';
        require_once LIBRARIES . 'page_fo.php';

        $realUrl = parse_url($_SERVER['REQUEST_URI']);
        $document = Document::load(get_lang_inner_uri() . 'not_found/', 'uri');

        if ($document) {
            if (
                $document->getAttribute('link') &&
                $document->getAttribute('link') != $realUrl['path']
            ) {
                goToUrl($document->getAttribute('link'));

            } else if (
                $document->getHandler() && (
                    $document->getAttribute('is_published') == 1 ||
                    (defined('IS_SHOW_HIDDEN') && IS_SHOW_HIDDEN)
                )
            ) {
                $handler = Document::initHandler($document->getHandler(), $document);
                $handler->execute();
                $handler->output();
                exit();
            }
        }
    }

    echo '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1>';
    echo '<p>The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found on this server.</p><hr />';
    echo '<i>' . $_SERVER['SERVER_SOFTWARE'] . ' at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] . '</i>';
    echo '</body></html>';
    exit();
}
