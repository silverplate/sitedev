<?php

abstract class Core_Cms_User extends App_ActiveRecord
{
	private static $Base;
	private static $SiteUser;

	const TABLE = 'user';
	const AUTH_GROUP_GUESTS = 1;
	const AUTH_GROUP_USERS = 2;
	const AUTH_GROUP_ALL = 3; // Сумма всех констант

	public static function Get() {
		return self::$SiteUser;
	}

	public static function StartSession() {
		if (isset($_POST['auth_submit']) || isset($_POST['auth_submit_x'])) {
			$try = App_Cms_User::Auth($_POST['auth_login'], $_POST['auth_password']);
			if ($try) {
				App_Cms_Session::Get()->Login($try->GetId());
				App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_LOGIN);
			} else {
				App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_LOGIN_ERROR);
			}

			reload();

		} elseif (isset($_POST['auth_reminder_submit']) || isset($_POST['auth_reminder_submit_x'])) {
			$try = isset($_POST['auth_email']) && $_POST['auth_email']
				? App_Cms_User::GetList(array('email' => $_POST['auth_email'], 'status_id' => 1))
				: false;

			if ($try) {
				foreach ($try as $user) {
					App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_REMIND_PWD);
					$user->RemindPassword();
				}
			} else {
				App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_REMIND_PWD_ERROR);
			}

			reload();

		} elseif (isset($_GET['r']) || (isset($_GET['e']) && App_Cms_Session::Get()->IsLoggedIn())) {
			if (App_Cms_Session::Get()->IsLoggedIn()) {
				App_Cms_Session::Get()->Logout();
			}

			if (isset($_GET['r'])) {
				$try = $_GET['r'] ? App_Cms_User::Load($_GET['r'], 'reminder_key') : false;
				if ($try && $try->ChangePassword() == 0) {
					App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_CHANGE_PWD);
				} else {
					App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_CHANGE_PWD_ERROR);
				}
			} else {
				App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_LOGOUT);
			}

			reload();

		} elseif (isset($_GET['e']) && App_Cms_Session::Get()->IsLoggedIn()) {
			App_Cms_Session::Get()->Logout();
			App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_LOGOUT);
			reload();

		} else {
			App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM, App_Cms_Session::Get()->GetParam(App_Cms_Session::ACT_PARAM_NEXT) ? App_Cms_Session::Get()->GetParam(App_Cms_Session::ACT_PARAM_NEXT) : App_Cms_Session::ACT_START);
			App_Cms_Session::Get()->SetParam(App_Cms_Session::ACT_PARAM_NEXT, App_Cms_Session::ACT_CONTINUE);
			self::$SiteUser = App_Cms_Session::Get()->IsLoggedIn() ? App_Cms_User::Auth(App_Cms_Session::Get()->GetUserId()) : false;
		}
	}

	public static function GetAuthGroups () {
		return array(
			self::AUTH_GROUP_ALL => array('title' => 'Все', 'title1' => 'Всем'),
			self::AUTH_GROUP_GUESTS => array('title' => 'Неавторизованные', 'title1' => 'Неавторизованным'),
			self::AUTH_GROUP_USERS => array('title' => 'Авторизованные', 'title1' => 'Авторизованным')
		);
	}

	public static function GetAuthGroupTitle($_id, $_title = null) {
		$title = 'title' . ($_title ? '_' . $_title : '');
		$groups = self::GetAuthGroups();
		return isset($groups[$_id]) ? $groups[$_id][$title] : false;
	}

	public static function GetAuthGroup() {
		return IS_USERS ? (self::Get() ? self::AUTH_GROUP_USERS : self::AUTH_GROUP_GUESTS) : null;
	}

	public static function CheckUnique($_value, $_exclude = null) {
		return self::IsUnique(get_called_class(), self::GetTbl(), self::GetPri(), 'email', $_value, $_exclude);
	}

	public static function Auth() {
		if (func_num_args() == 1) {
			$try = App_Db::Get()->GetEntry('SELECT ' . implode(',', array_diff(self::GetBase()->GetAttributes(), array('passwd'))) . ' FROM ' . self::GetTbl() . ' WHERE ' . self::GetPri() . ' = ' . App_Db::escape(func_get_arg(0)) . ' AND status_id = 1');

		} elseif (func_num_args() == 2) {
			$try = App_Db::Get()->GetEntry('SELECT ' . implode(',', array_diff(self::GetBase()->GetAttributes(), array('passwd'))) . ' FROM ' . self::GetTbl() . ' WHERE email = ' . App_Db::escape(func_get_arg(0)) . ' AND passwd = ' . App_Db::escape(md5(func_get_arg(1))) . ' AND status_id = 1');
		}

		if (isset($try) && $try) {
			$cname = get_called_class();
			$obj = new $cname;
			$obj->DataInit($try);

			return $obj;
		}

		return false;
	}

	public function RemindPassword() {
		global $g_mail;

		if ($this->GetAttribute('email')) {
			$this->SetAttribute('reminder_key', App_Db::Get()->GetUnique(self::GetTbl(), 'reminder_key', 30));
			$this->SetAttribute('reminder_date', date('Y-m-d H:i:s'));
			$this->Update();

			return send_email($g_mail, $this->GetAttribute('email'), 'Смена пароля',
				'Для смены пароля к сайту http://' .
				$_SERVER['HTTP_HOST'] . ' загрузите страницу: http://' .
				$_SERVER['HTTP_HOST'] . '?r=' . $this->GetAttribute('reminder_key') . "\r\n\n" .
				'Если вы не просили поменять пароль, проигнорируйте это сообщение.', null, false
			);
		}
	}

	public function ChangePassword() {
		global $g_mail;

		if ($this->GetAttribute('email')) {
			if ($this->GetAttribute('status_id') == 1 && $this->GetDate('reminder_date') && mktime() - 60 * 60 * 24 < $this->GetDate('reminder_date')) {
				$password = $this->GeneratePassword();

				$this->SetPassword($password);
				$this->SetAttribute('reminder_key', '');
				$this->SetAttribute('reminder_date', '');
				$this->Update();

				return send_email($g_mail, $this->GetAttribute('email'), 'Доступ',
					'Доступ к сайту http://' . $_SERVER['HTTP_HOST'] . ".\r\n\n" .
					'Логин: ' . $this->GetAttribute('email') .
					"\r\nПароль: " . $password, null, false
				) ? 0 : 3;
			} else return 2;
		} else return 1;
	}

	public function GetTitle() {
		return $this->GetAttribute('last_name') . ' ' . $this->GetAttribute('first_name');
	}

	public function GeneratePassword() {
		return get_random_string(8);
	}

	public function SetPassword($_password) {
		$this->SetAttribute('passwd', md5($_password));
	}

	public function UpdatePassword($_password) {
		$this->UpdateAttribute('passwd', md5($_password));
	}

	public function GetXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : 'user';
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->GetAttribute('status_id') == 1) $result .= ' is_published="true"';

				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;

			case 'page_system':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->Attributes as $item) {
			$this->Attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new App_ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 30, true);
			self::$Base->AddAttribute('status_id', 'int');
			self::$Base->AddAttribute('first_name', 'varchar', 255);
			self::$Base->AddAttribute('last_name', 'varchar', 255);
			self::$Base->AddAttribute('patronymic_name', 'varchar', 255);
			self::$Base->AddAttribute('email', 'varchar', 255);
			self::$Base->AddAttribute('phone_code', 'varchar', 255);
			self::$Base->AddAttribute('phone', 'varchar', 255);
			self::$Base->AddAttribute('passwd', 'varchar', 32);
			self::$Base->AddAttribute('reminder_key', 'varchar', 30);
			self::$Base->AddAttribute('reminder_date', 'datetime');
			self::$Base->AddAttribute('creation_date', 'datetime');
		}

		return self::$Base;
	}

	public static function GetPri($_is_table = false) {
		return self::GetBase()->GetPrimary($_is_table);
	}

	public static function GetTbl() {
		return self::GetBase()->GetTable();
	}

	public static function Load($_value, $_attribute = null) {
		return parent::Load(get_called_class(), $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array(), $_row_conditions = array()) {
		return parent::GetList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters,
			$_row_conditions
		);
	}

	public static function GetCount($_attributes = array(), $_row_conditions = array()) {
		return parent::GetCount(get_called_class(), self::GetTbl(), $_attributes, $_row_conditions);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
