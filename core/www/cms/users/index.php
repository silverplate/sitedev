<?php

require_once('../prepend.php');
require_once('filter_lib.php');

$page = new BoPage();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = User::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new User;
	}

	if (isset($obj)) {
		$form = new Form();
		$form->Load('form.xml');

		if ($obj->GetId()) {
			$form->FillFields($obj->GetAttributeValues());
			$form->Elements['passwd']->SetValue();

			if ($obj->GetAttribute('status_id') != 1) {
				$form->Elements['status_id']->SetValue(0);
			}

			$form->CreateButton('Сохранить', 'update');
			$form->CreateButton('Удалить', 'delete');

		} else {
			$form->CreateButton('Сохранить', 'insert');
			$form->Elements['passwd']->SetRequired(true);
		}

		$form->Execute();

		if ($form->UpdateStatus == FORM_UPDATED) {
			if (isset($form->Buttons['delete']) && $form->Buttons['delete']->IsSubmited()) {
				$obj->Delete();
				BoLog::LogModule(BoLog::ACT_DELETE, $obj->GetId(), $obj->GetTitle());
				goToUrl($page->Url['path'] . '?DEL');

			} elseif ((isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) || (isset($form->Buttons['update']) && $form->Buttons['update']->IsSubmited())) {
				if (User::CheckUnique($form->Elements['email']->GetValue(), $obj->GetId())) {
					$obj->DataInit($form->GetSqlValues());

					$password = $form->Elements['passwd']->GetValue();
					if (isset($password['password'])) {
						$obj->SetPassword($password['password']);
					}

					if ($form->Elements['status_id']->GetValue() != 1) {
						$obj->SetAttribute('status_id', 2);
					}

					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
					} else {
						$obj->Update();
						BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());
					}

					goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&OK');

				} else {
					$form->UpdateStatus = FORM_ERROR;
					$form->Elements['email']->SetUpdateType(FIELD_ERROR_EXIST);
					$form->Elements['email']->SetErrorValue($form->Elements['email']->GetValue());
					$form->Elements['email']->SetValue($obj->GetAttribute('email'));
				}
			}
		}

		if ($form->UpdateStatus == FORM_ERROR) {
			$page->SetUpdateStatus('error');

		} elseif (isset($_GET['OK'])) {
			$page->SetUpdateStatus('success');
		}

	} elseif (isset($_GET['DEL'])) {
		$page->SetUpdateStatus('success', 'Пользователь удален');
	}

	$filter = obj_get_filter();
	$list_xml = '<local_navigation type="filter"';

	$options = array('open', 'name', 'email');
	foreach ($options as $item) {
		if (isset($filter['is_' . $item]) && $filter['is_' . $item]) $list_xml .= ' is_' . $item . '="true"';
	}

	$list_xml .= '>';

	foreach (array('name', 'email') as $item) {
		if (isset($filter[$item]) && $filter[$item]) {
			$list_xml .= '<filter_' . $item . '><![CDATA[' . $filter[$item] . ']]></filter_' . $item . '>';
		}	
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