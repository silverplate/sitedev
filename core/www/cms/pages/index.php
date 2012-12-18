<?php

require('../prepend.php');

$page = new App_Cms_Bo_Page();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = App_Cms_Document::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new App_Cms_Document();
	}

	if (isset($obj)) {
	    $templateKey = App_Cms_TemplateDb::getPri();
	    $documentTbl = App_Cms_Document::getTbl();
	    $documentKey = App_Cms_Document::getPri();

		$form = new App_Form();
		$form->Load('document_form.xml');

		foreach (App_Cms_Document_Navigation::GetList(array('is_published' => 1)) as $item) {
			$form->Elements['navigations']->AddOption($item->GetId(), $item->GetTitle());
		}

		$tmp = array();
		foreach ($form->Elements as $name => $ele) {
			if ($name == 'fo_controller_id') {
			    $key = App_Cms_Controller::getPri();
				$tmp[$key] = $ele;
				$tmp[$key]->SetName($key);

			} else if ($name == 'fo_template_id') {
				$tmp[$templateKey] = $ele;
				$tmp[$templateKey]->setName($templateKey);

			} else {
				$tmp[$name] = $ele;
			}
		}
		$form->Elements = $tmp;
		unset($tmp);

		$controller_row_conditions = array();
		$controller_self_condition = isset($obj) && $obj && $obj->GetAttribute(App_Cms_Controller::GetPri()) ? ' OR ' . App_Cms_Controller::GetPri() . ' = ' . App_Db::escape($obj->GetAttribute(App_Cms_Controller::GetPri())) : '';
		$used = App_Db::Get()->GetList('SELECT ' . App_Cms_Controller::GetPri() . ' FROM ' . App_Cms_Document::GetTbl() . ' WHERE ' . App_Cms_Controller::GetPri() . ' != ""' . (isset($obj) ? ' AND ' . App_Cms_Document::GetPri() . ' != ' . App_Db::escape($obj->GetId()) : '') . ' GROUP BY ' . App_Cms_Controller::GetPri());
		if ($used) array_push($controller_row_conditions, '(is_multiple = 1 OR ' . App_Cms_Controller::GetPri() . ' NOT IN (' . App_Db::escape($used) . ')' . $controller_self_condition . ')');
		array_push($controller_row_conditions, $controller_self_condition ? '(is_published = 1' . $controller_self_condition . ')' : 'is_published = 1');

		foreach (App_Cms_Controller::GetList(array('type_id' => 1), null, $controller_row_conditions) as $item) {
			$form->Elements[App_Cms_Controller::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
		}

        $usedCond = '';
        if ($obj->getId()) {
            $usedCond = "WHERE $documentKey != " . $obj->getDbId();
        }

        $mainTemplateId = null;
        $used = App_Db::get()->getList("SELECT $templateKey
                                    FROM $documentTbl
                                    $usedCond
                                    GROUP BY $templateKey");
        $templates = array();
        $templatesParams = array('sort_order' => 'is_document_main DESC, title');
        foreach (App_Cms_Template::getList(null, $templatesParams) as $id => $item) {
            if (
                ($obj->getId() && $obj->$templateKey == $id) ||
                ($item->isPublished && ($item->isMultiple || !in_array($id, $used)))
            ) {
                $templates[$id] = $item->getTitle();
            }

            if ($item->isDocumentMain && $item->isPublished) {
                $mainTemplateId = $id;
            }
        }

		foreach ($templates as $id => $title) {
			$form->Elements[$templateKey]->addOption($id, $title);
		}

		if (IS_USERS) {
			$form->Groups['system']->AddElement($form->CreateElement('auth_status_id', 'chooser', 'Страница доступна'));
			foreach (App_Cms_User::GetAuthGroups() as $id => $params) {
				$form->Elements['auth_status_id']->AddOption($id, strtolower_utf8($params['title1']));
			}
		}

		if ($obj->GetId()) {
			$form->FillFields($obj->GetAttributeValues());

			$form->Elements['navigations']->SetValue($obj->GetLinkIds('navigations'));

			foreach ($obj->GetFiles() as $item) {
				$form->Elements['files']->AddAdditionalXml($item->GetXml());
			}

            $form->Groups['content']->addAdditionalXml('<document_data />');
			$form->CreateButton('Сохранить', 'update');
			$form->CreateButton('Удалить', 'delete');

		} else {
            if ($mainTemplateId) {
                $form->Elements[$templateKey]->setValue($mainTemplateId);
            }

            unset($form->Groups['content']);
			$form->CreateButton('Сохранить', 'insert');
		}

		$form->Execute();

		if ($form->UpdateStatus == FORM_UPDATED) {
			$is_root = (!isset($form->Elements['folder']) || $form->Elements['folder']->GetValue() != '/' || $form->Elements['parent_id']->GetValue() == '');
			$is_unique = (!isset($form->Elements['parent_id']) || App_Cms_Document::CheckUnique($form->Elements['parent_id']->GetValue(), $form->Elements['folder']->GetValue(), $obj->GetId()));

			if ($is_root && $is_unique) {
				$obj->DataInit($form->GetSqlValues());
				if (!$obj->getAttribute('parent_id')) {
				    $obj->setAttribute('parent_id', 'NULL');
				}

				if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
					$obj->Delete();
					App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
					reload('?DEL');

				} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
					} else {
						$obj->Update();
						App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());

						foreach (App_Cms_Document_Data::GetList(array(App_Cms_Document::GetPri() => $obj->GetId(), 'is_mount' => 1)) as $data) {
							if (isset($_POST['document_data_form_ele_' . $data->GetId()])) {
								$data->UpdateAttribute('content', $data->GetParsedContent($_POST['document_data_form_ele_' . $data->GetId()]));
								App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_MODIFY, $data->GetId(), 'Блоки данных. Документ ' . $obj->GetId());
							}
						}
					}

					$filesHaveBeenChanged = false;
					$files = &$form->Elements['files']->GetValue();

					if ($files && is_array($files) && isset($files[0])) {
						foreach ($files as $file) {
							if (isset($file['name']) && isset($file['tmp_name'])) {
							    $filesHaveBeenChanged = true;
								$obj->UploadFile($file['name'], $file['tmp_name']);
							}
						}
					}

					if ($filesHaveBeenChanged) {
					    $obj->cleanFileCache();
					}

					$obj->UpdateLinks('navigations', $form->Elements['navigations']->GetValue());
					reload('?id=' . $obj->GetId() . '&OK');
				}

			} else {
				$form->UpdateStatus = FORM_ERROR;
				$form->Elements['folder']->SetUpdateType((!$is_root) ? FIELD_ERROR_SPELLING : FIELD_ERROR_EXIST);
				$form->Elements['folder']->SetErrorValue($form->Elements['folder']->GetValue());
				$form->Elements['folder']->SetValue($obj->GetAttribute('folder'));
			}
		}

		if ($form->UpdateStatus == FORM_ERROR) {
			$page->SetUpdateStatus('error');

		} elseif (isset($_GET['OK'])) {
			$page->SetUpdateStatus('success');
		}

	} elseif (isset($_GET['DEL'])) {
		$page->SetUpdateStatus('success', 'Страница удалена');
	}

	if (isset($obj) && $obj) {
		$module = '<module type="tree" name="' . $g_section->GetName() . '" is_able_to_add="true"';

		if ($obj->GetId()) {
			$module .= ' id="' . $obj->GetId() . '" file_path="' . $obj->GetFilePath() . '">';
			$module .= '<title><![CDATA[<a href="' . $obj->GetUrl() . '?' . ($obj->GetAttribute('is_published') ? 'no_cache' : 'key=' . SITE_KEY) . '" target="_blank" title="Посмотреть на сайте">' . $obj->GetTitle() . '</a>]]></title>';
		} else {
			$module .= ' is_new="true">';
			$module .= '><title><![CDATA[Добавление]]></title>';
		}

		$module .= $form->GetXml();
		$module .= '</module>';

		$page->AddContent($module);

	} else {
		$about = $g_section->GetAttribute('description') ? '<p class="first">' . $g_section->GetAttribute('description') . '</p>' : '';
		$page->AddContent('<module type="tree" name="' . $g_section->GetName() . '" is_able_to_add="true"><content><html><![CDATA[' . $about . ']]></html></content></module>');
	}
}

$page->Output();

?>
