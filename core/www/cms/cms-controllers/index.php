<?php

require('../prepend.php');

$page = new App_Cms_Bo_Page();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = App_Cms_Controller::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new App_Cms_Controller();
	}

	if (isset($obj)) {
		$form = new App_Form();
		$form->Load('form.xml');

		if ($obj->GetId()) {
			$form->FillFields($obj->toArray());
			$form->Elements['content']->SetValue($obj->GetContent());

			if ($obj->isDocumentMain) {
				$form->Elements['type_id']->SetValue(3);
			}

			$form->CreateButton('Сохранить', 'update');
			$form->CreateButton('Удалить', 'delete');
		} else {
			$form->CreateButton('Сохранить', 'insert');
		}

		$form->Execute();

		if ($form->UpdateStatus == FORM_UPDATED) {
			$obj->fillWithData($form->GetSqlValues());

			if ($form->Elements['type_id']->GetValue() == 3) {
				$obj->typeId = 1;
				$obj->isDocumentMain = 1;
			}

			if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
				$obj->Delete();
				App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
				goToUrl($page->Url['path'] . '?DEL');

			} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
				if ($obj->CheckUnique()) {
					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						if (!is_file($obj->GetFilename()) || $form->Elements['content']->GetValue() != '') {
							Ext_File::write(
							    $obj->getFilename(),
								$form->Elements['content']->GetValue()
							);
						}

						App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_CREATE, $obj->GetId(), $obj->GetTitle());

					} else {
						$obj->Update();

						Ext_File::write(
						    $obj->getFilename(),
						    $form->Elements['content']->GetValue()
						);

						App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());
					}

					if ($obj->isDocumentMain) {
						App_Db::Get()->Execute('UPDATE ' . App_Cms_Controller::GetTbl() . ' SET is_document_main = 0 WHERE is_document_main = 1 AND ' . App_Cms_Controller::GetPri() . ' != ' . App_Db::escape($obj->GetId()));
					}

					reload('?id=' . $obj->GetId() . '&OK');

				} else {
					$form->UpdateStatus = FORM_ERROR;
					$form->Elements['filename']->SetUpdateType(FIELD_ERROR_EXIST);
					$form->Elements['filename']->SetErrorValue($form->Elements['filename']->GetValue());
					$form->Elements['filename']->SetValue($obj->filename);
				}
			}

		} elseif ($form->UpdateStatus == FORM_ERROR) {
			$page->SetUpdateStatus('error');

		} elseif (isset($_GET['OK'])) {
			$page->SetUpdateStatus('success');

		}

	} elseif (isset($_GET['DEL'])) {
		$page->SetUpdateStatus('success', 'Обработчик удален');
	}

	$list_xml = '<local_navigation>';
	foreach (App_Cms_Controller::GetList() as $item) {
		$list_xml .= $item->GetXml('bo_list', 'item');
	}
	$list_xml .= '</local_navigation>';

	if (isset($obj)) {
		$module = '<module type="simple" is_able_to_add="true"';

		if ($obj->GetId()) {
			$module .= ' id="' . $obj->GetId() . '">';
			$module .= '<title><![CDATA[' . $obj->GetTitle() . ']]></title>';
		} else {
			$module .= ' is_new="true">';
			$module .= '><title><![CDATA[Добавление]]></title>';
		}

		$module .= $form->GetXml();
		$module .= $list_xml;
		$module .= '</module>';

		$page->AddContent($module);

	} else {
		$about = $g_section->description ? '<p class="first">' . $g_section->description . '</p>' : '';
		$page->AddContent('<module type="simple" is_able_to_add="true">' . $list_xml . '<content><html><![CDATA[' . $about . ']]></html></content></module>');
	}
}

$page->Output();
