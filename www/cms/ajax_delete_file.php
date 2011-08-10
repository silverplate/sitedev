<?php

require('prepend.php');
$result = 0;

if (isset($_POST['f']) && is_file($_POST['f'])) {
	unlink($_POST['f']);

	if (is_directory_empty(dirname($_POST['f']))) {
		rmdir(dirname($_POST['f']));
	}

	$result = 1;
}

header('Content-type: text/html; charset=windows-1251');
echo $result;

?>