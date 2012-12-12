<?php

require('../prepend.php');

$page = new BoPage();
$page->SetTitle($g_section->GetTitle());

if ($page->IsAuthorized()) {
	if (isset($_GET['id'])) {
		$obj = BoUser::Load($_GET['id']);
		if (!$obj) unset($obj);

	} elseif (isset($_GET['NEW'])) {
		$obj = new BoUser;
	}

	if (isset($obj)) {
		$form = new Form();
		$form->Load('form.xml');

		foreach (BoSection::GetList() as $item) {
			$form->Elements['sections']->AddOption($item->GetId(), $item->GetTitle());
		}

		if ($obj->GetId()) {
			$form->FillFields($obj->GetAttributeValues());
			$form->Elements['passwd']->SetValue();
			$form->Elements['sections']->SetValue($obj->GetLinkIds('sections'));

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
				if (BoUser::CheckUnique($form->Elements['login']->GetValue(), $obj->GetId())) {
					$obj->DataInit($form->GetSqlValues());

					$password = $form->Elements['passwd']->GetValue();
					if (isset($password['password'])) {
						$obj->SetPassword($password['password']);
					}

					if ($form->Elements['status_id']->GetValue() != 1) {
						$obj->SetAttribute('status_id', 2);
					}

					if ($obj->GetAttribute('ip_restriction')) {
						$obj->SetAttribute('ip_restriction', implode("\r\n", list_to_array($obj->GetAttribute('ip_restriction'))));
					}

					if (isset($form->Buttons['insert']) && $form->Buttons['insert']->IsSubmited()) {
						$obj->Create();
						BoLog::LogModule(BoLog::ACT_CREATE, $obj->GetId(), $obj->GetTitle());
					} else {
						$obj->Update();
						BoLog::LogModule(BoLog::ACT_MODIFY, $obj->GetId(), $obj->GetTitle());
					}

					if (isset($form->Elements['sections'])) {
						$obj->UpdateLinks('sections', $form->Elements['sections']->GetValue());
					}

					if ($form->Elements['passwd']->GetValue() && Session::Get()->GetUserId() != $obj->GetId()) {
						Session::Clean($obj->GetId());
					}

					goToUrl($page->Url['path'] . '?id=' . $obj->GetId() . '&OK');

				} else {
					$form->UpdateStatus = FORM_ERROR;

					$form->Elements['login']->SetUpdateType(FIELD_ERROR_EXIST);
					$form->Elements['login']->SetErrorValue($form->Elements['login']->GetValue());
					$form->Elements['login']->SetValue($obj->GetAttribute('login'));
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

	$list_xml = '<local_navigation>';
	foreach (BoUser::GetList() as $item) {
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