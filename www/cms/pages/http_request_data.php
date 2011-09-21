<?php

require('../prepend.php');

$page = new Page();
$page->SetRootNodeName('http_request');
$page->SetRootNodeAttribute('type', 'document_data');
$page->SetTemplate(TEMPLATES . 'bo_http_requests.xsl');

$data = $_POST;

if (isset($data['id']) && $data['id']) {
	$page->SetRootNodeAttribute('parent_id', $data['id']);
	$page->AddContent(get_branch_xml($data['id']));
}

header('Content-type: text/html; charset=utf-8');
$page->Output();


function get_branch_xml($_parent_id) {
	$result = '';
	$document = Document::Load($_parent_id);

	foreach (DocumentData::GetList(array(Document::GetPri() => $_parent_id)) as $item) {
		$additional_xml = '';

		switch ($item->GetTypeId()) {
			case 'image':
				if ($document && is_dir($document->GetFilePath())) {
					if ($document->GetImages()) {
						$additional_xml .= '<self>';
						foreach ($document->GetImages() as $image) {
							$additional_xml .= $image->GetXml();
						}
						$additional_xml .= '</self>';
					}
				}

				if (!isset($other_images)) {
					$other_images = data_get_images(DOCUMENT_ROOT . 'f/', $document->GetFilePath());
				}

				if ($other_images) {
					$additional_xml .= '<others>';
					foreach ($other_images as $image) {
						$additional_xml .= $image->GetXml();
					}
					$additional_xml .= '</others>';
				}

				break;
		}

		$result .= $item->GetXml($additional_xml);
	}

	return $result;
}

function data_get_images($_dir, $_exclude_path) {
	$result = array();
	$dir = rtrim($_dir, '/') . '/';
	$exclude_path = rtrim($_exclude_path, '/') . '/';

	if (is_dir($dir)) {
		$dir_handle = opendir($dir);
		while ($item = readdir($dir_handle)) {
			if ($item != '.' && $item != '..') {
				if (is_dir($dir . $item)) {
					$result = array_merge($result, data_get_images($dir . $item, $exclude_path));
				} elseif ($dir != $exclude_path && Image::IsImageExtension(get_file_extension($item))) {
					array_push($result, new Image($dir . $item, DOCUMENT_ROOT, '/'));
				}
			}
		}
		closedir($dir_handle);
	}

	return $result;
}

?>