<?php

require_once(realpath(dirname(__FILE__) . '/../../libs') . '/libs.php');
require_once(SETS . 'project.php');
require_once(LIBRARIES . 'page.php');
require_once(LIBRARIES . 'page_bo.php');
require_once(LIBRARIES . 'page_bo_404.php');
require_once(LIBRARIES . 'libs_bo.php');

$g_section_start_url = '/cms/';


/*** Authorization
*********************************************************/
if (isset($_POST['auth_submit'])) {
	$try = BoUser::Auth($_POST['auth_login'], $_POST['auth_password']);
	if ($try) {
		Session::Get()->Login(
			$try->GetId(),
			isset($_POST['auth_is_ip_match']),
			isset($_POST['auth_life_span']) ? (int) $_POST['auth_life_span'] : null,
			isset($_POST['auth_timeout']) ? (int) $_POST['auth_timeout'] : null,
			isset($_POST['auth_is_remember_me']) ? strtotime('+3 month') : null
		);

		Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_LOGIN);
		BoLog::Log(BoLog::ACT_LOGIN, array('user' => $try));

	} else {
		Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_LOGIN_ERROR);
	}

	reload(empty($_GET['id']) ? null : '?id=' . $_GET['id']);

} elseif (isset($_POST['auth_reminder_submit'])) {
	$try = isset($_POST['auth_email']) && $_POST['auth_email']
		? BoUser::GetList(array('email' => $_POST['auth_email'], 'status_id' => 1))
		: false;

	if ($try) {
		foreach ($try as $user) {
			Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_REMIND_PWD);
			BoLog::Log(BoLog::ACT_REMIND_PWD, array('user' => $user));
			$user->RemindPassword();
		}
	} else {
		Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_REMIND_PWD_ERROR);
	}

	reload();

} elseif (isset($_GET['r']) || (isset($_GET['e']) && Session::Get()->IsLoggedIn())) {
	if (Session::Get()->IsLoggedIn()) {
		BoLog::Log(BoLog::ACT_LOGOUT, array('user' => BoUser::Auth(Session::Get()->GetUserId())));
		Session::Get()->Logout();
	}

	if (isset($_GET['r'])) {
		$try = $_GET['r'] ? BoUser::Load($_GET['r'], 'reminder_key') : false;
		if ($try && $try->ChangePassword() == 0) {
			Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_CHANGE_PWD);
			BoLog::Log(BoLog::ACT_CHANGE_PWD, array('user' => $try));

		} else {
			Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_CHANGE_PWD_ERROR);
		}

	} else {
		Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_LOGOUT);
	}

	reload();

} else {
	Session::Get()->SetParam(Session::ACT_PARAM, Session::Get()->GetParam(Session::ACT_PARAM_NEXT) ? Session::Get()->GetParam(Session::ACT_PARAM_NEXT) : Session::ACT_START);
	Session::Get()->SetParam(Session::ACT_PARAM_NEXT, Session::ACT_CONTINUE);

	/* User */
	$g_user = Session::Get()->IsLoggedIn() ? BoUser::Auth(Session::Get()->GetUserId()) : false;

	/* Section */
	$g_section = BoSection::Compute();
}

?>
