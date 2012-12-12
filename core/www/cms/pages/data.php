<?php

require('../prepend.php');

$page = new BoPage();
$page->SetTemplate(TEMPLATES . 'bo_popup.xsl');

$document_id = isset($_GET['parent_id']) && $_GET['parent_id'] ? $_GET['parent_id'] : null;
$id = isset($_GET['id']) && $_GET['id'] ? $_GET['id'] : null;

if (is_null($document_id) || !Document::Load($document_id)) {
	$page->AddContent('<html>Неизвестный документ.</html>');

} else {
	if ($id) {
		$obj = DocumentData::Load($id);
		if (!$obj) unset($obj);
	}

	$form = new Form();
	$form->Load('data_form.xml');

	$tmp = array();
	foreach ($form->Elements as $name => $ele) {
		if ($name == 'fo_data_content_type_id') {
			$tmp[DocumentDataContentType::GetPri()] = $ele;
			$tmp[DocumentDataContentType::GetPri()]->SetName(DocumentDataContentType::GetPri());

		} elseif ($name == 'fo_handler_id') {
			$tmp[Handler::GetPri()] = $ele;
			$tmp[Handler::GetPri()]->SetName(Handler::GetPri());

		} else {
			$tmp[$name] = $ele;
		}
	}
	$form->Elements = $tmp;
	unset($tmp);

	foreach (DocumentDataContentType::GetList(array('is_published' => 1)) as $item) {
		$form->Elements[DocumentDataContentType::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
	}

	$handler_row_conditions = array();
	$handler_self_condition = isset($obj) && $obj && $obj->GetAttribute(Handler::GetPri()) ? ' OR ' . Handler::GetPri() . ' = ' . Db::escape($obj->GetAttribute(Handler::GetPri())) : '';
	$used = Db::Get()->GetList('SELECT ' . Handler::GetPri() . ' FROM ' . DocumentData::GetTbl() . ' WHERE ' . Handler::GetPri() . ' != ""' . (isset($obj) ? ' AND ' . DocumentData::GetPri() . ' != ' . Db::escape($obj->GetId()) : '') . ' GROUP BY ' . Handler::GetPri());
	if ($used) array_push($handler_row_conditions, '(is_multiple = 1 OR ' . Handler::GetPri() . ' NOT IN (' . Db::escape($used) . ')' . $handler_self_condition . ')');
	array_push($handler_row_conditions, $handler_self_condition ? '(is_published = 1' . $handler_self_condition . ')' : 'is_published = 1');

	$form->Elements[Handler::GetPri()]->AddOption('', 'Нет');
	foreach (Handler::GetList(array('type_id' => 2), null, $handler_row_conditions) as $item) {
		$form->Elements[Handler::GetPri()]->AddOption($item->GetId(), $item->GetTitle());
	}

	if (IS_USERS) {
		$form->CreateElement('auth_status_id', 'chooser', 'Данные доступны');
		foreach (User::GetAuthGroups() as $id => $params) {
			$form->Elements['auth_status_id']->AddOption($id, strtolower_utf8($params['title1']));
		}
	}

	foreach (DocumentData::GetApplyTypes() as $id => $title) {
		$form->Elements['apply_type_id']->AddOption($id, strtolower_utf8($title));
	}

	if (!isset($obj) || !$obj) {
		$page->SetTitle('Добавление');
		$obj = new DocumentData;
		$form->CreateButton('Сохранить', 'create');

	} else {
		$page->SetTitle($obj->GetTitle());
		$form->FillFields($obj->GetAttributeValues());
		$form->CreateButton('Сохранить', 'update');
		$form->CreateButton('Удалить', 'delete');
	}

	$obj->SetAttribute(Document::GetPri(), $document_id);
	$form->Execute();

	if ($form->UpdateStatus == FORM_UPDATED) {
		$obj->DataInit($form->GetSqlValues());

		if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
			$obj->Delete();
			BoLog::LogModule(BoLog::ACT_DELETE, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(Document::GetPri()));
			goToUrl($page->Url['path'] . '?parent_id=' . $obj->GetAttribute(Document::GetPri()) . '&DEL');

		} elseif (isset($form->Buttons['create']) && $form->Buttons['create']->IsSubmited()) {
			$obj->Create();
			BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(Document::GetPri()));
			goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&parent_id=' . $obj->GetAttribute(Document::GetPri()) . '&OK');

		} elseif (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited()) {
			$obj->Update();
			BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), 'Блоки данных. Документ ' . $obj->GetAttribute(Document::GetPri()));
			goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&parent_id=' . $obj->GetAttribute(Document::GetPri()) . '&OK');
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
