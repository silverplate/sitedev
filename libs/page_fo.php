<?php

interface DocumentHandlerInterface {
	public function __construct(&$_document);
	public function Execute();
	public function Output();
}

class FoPage extends Page {
	protected $IsShowHidden;

	public function __construct() {
		parent::__construct();
		$this->IsShowHidden = defined('IS_SHOW_HIDDEN') && IS_SHOW_HIDDEN;
		if ($this->IsShowHidden) $this->AddSystemAttribute('is_show_hidden');
	}

	public function GetXml() {
		if (SITE_TITLE) $this->AddSystem('<title><![CDATA[' . SITE_TITLE . ']]></title>');
		if (IS_USERS && User::Get()) $this->AddSystem(User::Get()->GetXml('page_system'));
		$this->AddSystem(Session::Get()->GetXml());

		return parent::GetXml();
	}

	public function output($_is_404 = false)
	{
		global $gCache;

		if (isset($_GET['xml']) && defined('IS_ADMIN_MODE') && IS_ADMIN_MODE) {
			// header('Content-type: text/xml; charset=' . ini_get('default_charset'));
			header('Content-type: text/xml; charset=utf-8');
			echo getXmlDocumentForRoot($this->getXml(), $this->getRootNodeName());

		} else if ($this->Template) {
			$content = $this->GetHtml();
			echo $content;

			if ($gCache && $gCache->isAvailable() && !$_is_404) {
				$gCache->set($content);
			}

		} else {
			documentNotFound();
		}
	}
}

class DocumentHandler extends FoPage {
	/**
	 * @var Document
	 */
	protected $Document;

	public function __construct(&$_document) {
		parent::__construct();
		$this->Document = $_document;
	}

	public function Execute() {
		if ($this->Document) {
			if (!$this->GetTitle()) {
				$this->SetTitle($this->Document->GetTitle());
			}

			if ($this->Document->GetLang()) {
				$this->SetRootNodeAttribute('xml:lang', $this->Document->GetLang());
			}

            $conditions = array('is_published' => 1);

			$params = array();
			$ancestors = Document::GetAncestors($this->Document->GetId());
			if ($ancestors) $ancestors = array_values(array_diff($ancestors, array($this->Document->GetId())));

			if ($ancestors) {
				array_push($params,
					'((' . Document::GetPri() . ' IN (' . Db::escape($ancestors) . ') AND apply_type_id IN (2, 3)) OR (' .
					Document::GetPri() . ' = ' . Db::escape($this->Document->GetId()) . ' AND apply_type_id IN (1, 3)))'
				);
			} else {
				array_push($params, '(' . Document::GetPri() . ' = ' . Db::escape($this->Document->GetId()) . ' AND apply_type_id IN (1, 3))');
			}

			if (!is_null(User::GetAuthGroup())) {
				array_push($params, '(auth_status_id = 0 OR auth_status_id & ' . User::GetAuthGroup() . ')');
			}

			$data = DocumentData::GetList($conditions, null, $params);
			$dataXml = array();

			foreach ($data as $item) {
				switch ($item->GetTypeId()) {
					case 'image':
						if ($item->GetAttribute('content')) {
							if (strpos($item->GetAttribute('content'), '://') !== true) {
								$image_file_path = DOCUMENT_ROOT . ltrim($item->GetAttribute('content'), '/');
								if (is_file($image_file_path)) {
									$image_file = App_File::factory($image_file_path);

									if ($image_file->isImage()) {
										$item->SetAttribute('content', $image_file->getXml());
										$item->SetTypeId('xml');
									}
								}
							}
						}
						break;

					case 'xml':
						if ($item->GetAttribute('content')) {
						    $data_dom = getXmlObject($item->GetAttribute('content'), 'data');
							$data_images = $data_dom->getElementsByTagName('image');

							foreach ($data_images as $data_image) {
								if ($data_image->hasAttribute('alias')) {
									$image = null;

									if ($data_image->hasAttribute('alias-uri')) {
										$filepath = get_file_by_name(
											rtrim(DOCUMENT_ROOT, '/') . $data_image->getAttribute('alias-uri'),
											$data_image->getAttribute('alias')
										);

										if ($filepath) {
											$image = App_Image::factory($filepath);
										}

									} else {
										$image = $this->Document->getIlluByName($data_image->getAttribute('alias'));
									}

									if (empty($image)) continue;
									$data_image->parentNode->replaceChild($image->getNode($data_dom), $data_image);
								}
							}

							$data_files = $data_dom->getElementsByTagName('file');
							foreach ($data_files as $data_file) {
								if ($data_file->hasAttribute('alias')) {
									if ($data_file->hasAttribute('alias-uri')) {
										$filepath = get_file_by_name(
											rtrim(DOCUMENT_ROOT, '/') . $data_file->getAttribute('alias-uri'),
											$data_file->getAttribute('alias')
										);

										if ($filepath) {
											$file = App_File::factory($file);
										}

									} else {
										$file = $this->Document->getFileByName($data_file->getAttribute('alias'));
									}

									if (empty($file)) continue;
									$data_file->parentNode->replaceChild($file->getNode($data_dom), $data_file);
								}
							}

							$content = '';
							foreach ($data_dom->documentElement->childNodes as $child) {
								$content .= $data_dom->saveXml($child);
							}

							$item->SetAttribute('content', $content);
						}
						break;
				}

				if ($item->GetHandlerFile()) {
					$handler = DocumentData::initHandler($item->getHandler(),
					                                     $item,
					                                     $this->Document);

					$handler->execute();
					array_push($dataXml, $handler->getXml());

				} else {
					$plainData = new DocumentDataHandler($item);
					array_push($dataXml, $plainData->getXml());
				}
			}

            $content = array();
            foreach ($dataXml as $xml) {
                array_push($content, $xml);
            }

            $this->setContent(array_merge($content, $this->getContent()));
		}
	}
}

?>
