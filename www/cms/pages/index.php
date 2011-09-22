<?php

require('../prepend.php');

$page = new BoPage();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = Document::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new Document;
	}

	if (isset($obj)) {
	    $templateKey = TemplateDb::getPri();
	    $documentTbl = Document::getTbl();
	    $documentKey = Document::getPri();

		$form = new Form();
		$form->Load('document_form.xml');

		foreach (DocumentNavigation::GetList(array('is_published' => 1)) as $item) {
			$form->Elements['navigations']->AddOption($item->GetId(), $item->GetTitle());
		}

		$tmp = array();
		foreach ($form->Elements as $name => $ele) {
			if ($name == 'fo_handler_id') {
			    $key = Handler::getPri();
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

		$handler_row_conditions = array();
		$handler_self_condition = isset($obj) && $obj && $obj->GetAttribute(Handler::GetPri()) ? ' OR ' . Handler::GetPri() . ' = ' . get_db_data($obj->GetAttribute(Handler::GetPri())) : '';
		$used = Db::Get()->GetList('SELECT ' . Handler::GetPri() . ' FROM ' . Document::GetTbl() . ' WHERE ' . Handler::GetPri() . ' != ""' . (isset($obj) ? ' AND ' . Document::GetPri() . ' != ' . get_db_data($obj->GetId()) : '') . ' GROUP BY ' . Handler::GetPri());
		if ($used) array_push($handler_row_conditions, '(is_multiple = 1 OR ' . Handler::GetPri() . ' NOT IN (' . get_db_data($used) . ')' . $handler_self_condition . ')');
		array_push($handler_row_conditions, $handler_self_condition ? '(is_published = 1' . $handler_self_condition . ')' : 'is_published = 1');

		foreach (Handler::GetList(array('type_id' => 1), null, $handler_row_conditions) as $item) {
			$form->Elements[Handler::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
		}

        $usedCond = '';
        if ($obj->getId()) {
            $usedCond = "WHERE $documentKey != " . $obj->getDbId();
        }

        $mainTemplateId = null;
        $used = Db::get()->getList("SELECT $templateKey
                                    FROM $documentTbl
                                    $usedCond
                                    GROUP BY $templateKey");
        $templates = array();
        $templatesParams = array('sort_order' => 'is_document_main DESC, title');
        foreach (Template::getList(null, $templatesParams) as $id => $item) {
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
			foreach (User::GetAuthGroups() as $id => $params) {
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
			$is_unique = (!isset($form->Elements['parent_id']) || Document::CheckUnique($form->Elements['parent_id']->GetValue(), $form->Elements['folder']->GetValue(), $obj->GetId()));

			if ($is_root && $is_unique) {
				$obj->DataInit($form->GetSqlValues());
				if (!$obj->getAttribute('parent_id')) {
				    $obj->setAttribute('parent_id', 'NULL');
				}

				if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
					$obj->Delete();
					BoLog::LogModule(BoLog::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
					reload('?DEL');

				} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
					} else {
						$obj->Update();
						BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());

						foreach (DocumentData::GetList(array(Document::GetPri() => $obj->GetId(), 'is_mount' => 1)) as $data) {
							if (isset($_POST['document_data_form_ele_' . $data->GetId()])) {
								$data->UpdateAttribute('content', $data->GetParsedContent($_POST['document_data_form_ele_' . $data->GetId()]));
								BoLog::LogModule(BoLog::ACT_MODIFY, $data->GetId(), 'Блоки данных. Документ ' . $obj->GetId());
							}
						}
					}

					$files = &$form->Elements['files']->GetValue();
					if ($files && is_array($files) && isset($files[0])) {
						foreach ($files as $file) {
							if (isset($file['name']) && isset($file['tmp_name'])) {
								$obj->UploadFile($file['name'], $file['tmp_name']);
							}
						}
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
