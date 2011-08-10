<?php

require('../prepend.php');

$page = new BoPage();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = DocumentNavigation::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new DocumentNavigation;
	}

	if (isset($obj)) {
		$form = new Form();
		$form->Load('form.xml');

		foreach (DocumentNavigation::GetTypes() as $id => $params) {
			$form->Elements['type']->AddOption($id, strtolower_utf8($params['title']));
		}

		if ($obj->GetId()) {
			$form->FillFields($obj->GetAttributeValues());
			$form->CreateButton('Сохранить', 'update');
			$form->CreateButton('Удалить', 'delete');
		} else {
			$form->CreateButton('Сохранить', 'insert');
		}

		$form->Execute();

		if ($form->UpdateStatus == FORM_UPDATED) {
			$obj->DataInit($form->GetSqlValues());

			if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
				$obj->Delete();
				BoLog::LogModule(BoLog::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
				goToUrl($page->Url['path'] . '?DEL');

			} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
				if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
					$obj->Create();
					BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
				} else {
					$obj->Update();
					BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());
				}

				goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&OK');
			}

		} elseif ($form->UpdateStatus == FORM_ERROR) {
			$page->SetUpdateStatus('error');

		} elseif (isset($_GET['OK'])) {
			$page->SetUpdateStatus('success');

		}

	} elseif (isset($_GET['DEL'])) {
		$page->SetUpdateStatus('success', 'Тип удален');
	}

	$list_xml = '<local_navigation>';
	foreach (DocumentNavigation::GetList() as $item) {
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

?>