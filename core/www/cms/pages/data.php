<?php

require('../prepend.php');

$page = new App_Cms_Bo_Page();
$page->SetTemplate(TEMPLATES . 'bo_popup.xsl');

$document_id = isset($_GET['parent_id']) && $_GET['parent_id'] ? $_GET['parent_id'] : null;
$id = isset($_GET['id']) && $_GET['id'] ? $_GET['id'] : null;

if (is_null($document_id) || !App_Cms_Document::Load($document_id)) {
	$page->AddContent('<html>Неизвестный документ.</html>');

} else {
	if ($id) {
		$obj = App_Cms_Document_Data::Load($id);
		if (!$obj) unset($obj);
	}

	$form = new App_Form();
	$form->Load('data_form.xml');

	$tmp = array();
	foreach ($form->Elements as $name => $ele) {
		if ($name == 'fo_data_content_type_id') {
			$tmp[App_Cms_Document_Data_ContentType::GetPri()] = $ele;
			$tmp[App_Cms_Document_Data_ContentType::GetPri()]->SetName(App_Cms_Document_Data_ContentType::GetPri());

		} elseif ($name == 'fo_handler_id') {
			$tmp[App_Cms_Handler::GetPri()] = $ele;
			$tmp[App_Cms_Handler::GetPri()]->SetName(App_Cms_Handler::GetPri());

		} else {
			$tmp[$name] = $ele;
		}
	}
	$form->Elements = $tmp;
	unset($tmp);

	foreach (App_Cms_Document_Data_ContentType::GetList(array('is_published' => 1)) as $item) {
		$form->Elements[App_Cms_Document_Data_ContentType::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
	}

	$handler_row_conditions = array();
	$handler_self_condition = isset($obj) && $obj && $obj->GetAttribute(App_Cms_Handler::GetPri()) ? ' OR ' . App_Cms_Handler::GetPri() . ' = ' . App_Db::escape($obj->GetAttribute(App_Cms_Handler::GetPri())) : '';
	$used = App_Db::Get()->GetList('SELECT ' . App_Cms_Handler::GetPri() . ' FROM ' . App_Cms_Document_Data::GetTbl() . ' WHERE ' . App_Cms_Handler::GetPri() . ' != ""' . (isset($obj) ? ' AND ' . App_Cms_Document_Data::GetPri() . ' != ' . App_Db::escape($obj->GetId()) : '') . ' GROUP BY ' . App_Cms_Handler::GetPri());
	if ($used) array_push($handler_row_conditions, '(is_multiple = 1 OR ' . App_Cms_Handler::GetPri() . ' NOT IN (' . App_Db::escape($used) . ')' . $handler_self_condition . ')');
	array_push($handler_row_conditions, $handler_self_condition ? '(is_published = 1' . $handler_self_condition . ')' : 'is_published = 1');

	$form->Elements[App_Cms_Handler::GetPri()]->AddOption('', 'Нет');
	foreach (App_Cms_Handler::GetList(array('type_id' => 2), null, $handler_row_conditions) as $item) {
		$form->Elements[App_Cms_Handler::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
	}

	if (IS_USERS) {
		$form->CreateElement('auth_status_id', 'chooser', 'Данные доступны');
		foreach (App_Cms_User::GetAuthGroups() as $id => $params) {
			$form->Elements['auth_status_id']->AddOption($id, strtolower_utf8($params['title1']));
		}
	}

	foreach (App_Cms_Document_Data::GetApplyTypes() as $id => $title) {
		$form->Elements['apply_type_id']->AddOption($id, strtolower_utf8($title));
	}

	if (!isset($obj) || !$obj) {
		$page->SetTitle('Добавление');
		$obj = new App_Cms_Document_Data();
		$form->CreateButton('Сохранить', 'create');

	} else {
		$page->SetTitle($obj->GetTitle());
		$form->FillFields($obj->GetAttributeValues());
		$form->CreateButton('Сохранить', 'update');
		$form->CreateButton('Удалить', 'delete');
	}

	$obj->SetAttribute(App_Cms_Document::GetPri(), $document_id);
	$form->Execute();

	if ($form->UpdateStatus == FORM_UPDATED) {
		$obj->DataInit($form->GetSqlValues());

		if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
			$obj->Delete();
			App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_DELETE, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(App_Cms_Document::GetPri()));
			goToUrl($page->Url['path'] . '?parent_id=' . $obj->GetAttribute(App_Cms_Document::GetPri()) . '&DEL');

		} elseif (isset($form->Buttons['create']) && $form->Buttons['create']->IsSubmited()) {
			$obj->Create();
			App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_CREATE, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(App_Cms_Document::GetPri()));
			goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&parent_id=' . $obj->GetAttribute(App_Cms_Document::GetPri()) . '&OK');

		} elseif (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited()) {
			$obj->Update();
			App_Cms_Bo_Log::LogModule(App_Cms_Bo_Log::ACT_MODIFY, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(App_Cms_Document::GetPri()));
			goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&parent_id=' . $obj->GetAttribute(App_Cms_Document::GetPri()) . '&OK');
		}
	}

	if ($form->UpdateStatus == FORM_ERROR) {
		$page->SetUpdateStatus('error');

	} elseif (isset($_GET['OK'])) {
		$page->SetUpdateStatus('success');
		$page->AddContent('<update_parent>documentUpdateDataBlocks()</update_parent>');

	} elseif (isset($_GET['DEL'])) {
		$page->SetUpdateStatus('success', 'Данные удалены.');
		$page->AddContent('<update_parent>documentUpdateDataBlocks()</update_parent>');
	}

	$page->AddContent($form->GetXml());
}

$page->Output();

?>
