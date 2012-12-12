<?php

require('../prepend.php');

$page = new BoPage();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = Handler::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new Handler;
	}

	if (isset($obj)) {
		$form = new Form();
		$form->Load('form.xml');

		if ($obj->GetId()) {
			$form->FillFields($obj->GetAttributeValues());
			$form->Elements['content']->SetValue($obj->GetContent());

			if ($obj->GetAttribute('is_document_main')) {
				$form->Elements['type_id']->SetValue(3);
			}

			$form->CreateButton('Сохранить', 'update');
			$form->CreateButton('Удалить', 'delete');
		} else {
			$form->CreateButton('Сохранить', 'insert');
		}

		$form->Execute();

		if ($form->UpdateStatus == FORM_UPDATED) {
			$obj->DataInit($form->GetSqlValues());

			if ($form->Elements['type_id']->GetValue() == 3) {
				$obj->SetAttribute('type_id', 1);
				$obj->SetAttribute('is_document_main', 1);
			}

			if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
				$obj->Delete();
				BoLog::LogModule(BoLog::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
				goToUrl($page->Url['path'] . '?DEL');

			} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
				if ($obj->CheckUnique()) {
					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						if (!is_file($obj->GetFilename()) || $form->Elements['content']->GetValue() != '') {
							write_file(
								$obj->GetFilename(),
								$form->Elements['content']->GetValue()
							);
						}
						BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
					} else {
						$obj->Update();
						write_file($obj->GetFilename(), $form->Elements['content']->GetValue());
						BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());
					}

					if ($obj->GetAttribute('is_document_main')) {
						Db::Get()->Execute('UPDATE ' . Handler::GetTbl() . ' SET is_document_main = 0 WHERE is_document_main = 1 AND ' . Handler::GetPri() . ' != ' . Db::escape($obj->GetId()));
					}

					reload('?id=' . $obj->GetId() . '&OK');

				} else {
					$form->UpdateStatus = FORM_ERROR;
					$form->Elements['filename']->SetUpdateType(FIELD_ERROR_EXIST);
					$form->Elements['filename']->SetErrorValue($form->Elements['filename']->GetValue());
					$form->Elements['filename']->SetValue($obj->GetAttribute('filename'));
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
	foreach (Handler::GetList() as $item) {
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
		$about = $g_section->GetAttribute('description') ? '<p class="first">' . $g_section->GetAttribute('description') . '</p>' : '';
		$page->AddContent('<module type="simple" is_able_to_add="true">' . $list_xml . '<content><html><![CDATA[' . $about . ']]></html></content></module>');
	}
}

$page->Output();
