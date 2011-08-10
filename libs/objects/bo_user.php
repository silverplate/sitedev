<?php

class BoUser extends ActiveRecord {
	private static $Base;
	protected $Links = array('sections' => null);
	const TABLE = 'bo_user';

	private $Files;
	protected $Images;

	const FOLDER = 'f/bo_user/';

	public function GetFileFolder() {
		return DOCUMENT_ROOT . self::FOLDER . $this->GetId() . '/';
	}

	public function GetImageFolder() {
		return $this->GetFileFolder();
	}

	public function UploadFile($_name, $_tmp_name) {
		if ($_name && $_tmp_name) {
			$name = translit($_name);
			create_directory($this->GetFileFolder(), true);
			move_uploaded_file($_tmp_name, $this->GetFileFolder() . $name);
			chmod($this->GetFileFolder() . $name, 0777);
		}
	}

	public function GetFiles() {
		if (is_null($this->Files)) {
			$this->Files = array();
			if ($this->GetFileFolder() && is_dir($this->GetFileFolder())) {
				$dir_handle = opendir($this->GetFileFolder());
				while ($item = readdir($dir_handle)) {
					if ($item != '.' && $item != '..' && is_file($this->GetFileFolder() . $item)) {
						$file = new File($this->GetFileFolder() . $item, DOCUMENT_ROOT, '/');
						$this->Files[strtolower($file->GetFileName())] = $file;
					}
				}
				closedir($dir_handle);
			}
		}
		return $this->Files;
	}

	public function GetImages() {
		if (is_null($this->Images)) {
			$this->Images = array();

			if ($this->GetFiles()) {
				foreach ($this->GetFiles() as $file) {
					if (Image::IsImageExtension($file->GetExtension())) {
						$image = new Image($file->GetPath(), $file->GetPathStartsWith(), $file->GetUriStartsWith());
						$this->Images[strtolower($image->GetFileName())] = $image;
					}
				}
			}

		}
		return $this->Images;
	}

	public static function CheckUnique($_value, $_exclude = null) {
		return self::IsUnique(__CLASS__, self::GetTbl(), self::GetPri(), 'login', $_value, $_exclude);
	}

	public static function Auth() {
		if (func_num_args() == 1) {
			$try = Db::Get()->GetEntry('SELECT ' . implode(',', array_diff(self::GetBase()->GetAttributes(), array('passwd'))) . ' FROM ' . self::GetTbl() . ' WHERE ' . self::GetPri() . ' = ' . get_db_data(func_get_arg(0)) . ' AND status_id = 1');

		} elseif (func_num_args() == 2) {
			$try = Db::Get()->GetEntry('SELECT ' . implode(',', array_diff(self::GetBase()->GetAttributes(), array('passwd'))) . ' FROM ' . self::GetTbl() . ' WHERE login = ' . get_db_data(func_get_arg(0)) . ' AND passwd = ' . get_db_data(md5(func_get_arg(1))) . ' AND status_id = 1');
		}

		if (isset($try) && $try && (!$try['ip_restriction'] || in_array($_SERVER['REMOTE_ADDR'], list_to_array($try['ip_restriction'])))) {
			$cname = __CLASS__;
			$obj = new $cname;
			$obj->DataInit($try);

			return $obj;
		}

		return false;
	}

	public function GetSections($_is_published = true) {
		return BoSection::GetList(array('is_published' => 1, BoSection::GetPri() => $this->GetLinkIds('sections', $_is_published)));
	}

	public function GetSectionGroups($_is_published = true) {
		$result = array();

		$sections_list = BoSection::GetList(array('is_published' => 1, BoSection::GetPri() => $this->GetLinkIds('sections', $_is_published)));
		if($sections_list) {
			$section_group_ids = array();
			foreach ($sections_list AS $section) {
				array_push($section_group_ids, $section->GetAttribute(BoSectionGroup::GetPri()));
			}

			if($section_group_ids) {
				return BoSectionGroup::GetList(array(BoSectionGroup::GetPri() => $section_group_ids, 'is_published' => 1));
			}
		}

		return null;
	}

	public function IsSection($_id) {
		return in_array($_id, $this->GetLinkIds('sections'));
	}

	public function RemindPassword() {
		global $g_section_start_url, $g_bo_mail;

		if ($this->GetAttribute('email')) {
			$this->SetAttribute('reminder_key', Db::Get()->GetUnique(self::GetTbl(), 'reminder_key', 30));
			$this->SetAttribute('reminder_date', date('Y-m-d H:i:s'));
			$this->Update();

			return send_email($g_bo_mail, $this->GetAttribute('email'), 'Смена пароля',
				'Для смены пароля к системе управления сайта http://' .
				$_SERVER['HTTP_HOST'] . $g_section_start_url . ' загрузите страницу: http://' .
				$_SERVER['HTTP_HOST'] . $g_section_start_url . '?r=' . $this->GetAttribute('reminder_key') . "\r\n\n" .
				'Если вы не просили поменять пароль, проигнорируйте это сообщение.'
			);
		}
	}

	public function ChangePassword() {
		global $g_section_start_url, $g_bo_mail;

		if ($this->GetAttribute('email')) {
			if ($this->GetAttribute('status_id') == 1 && $this->GetDate('reminder_date') && mktime() - 60 * 60 * 24 < $this->GetDate('reminder_date')) {
				$password = $this->GeneratePassword();

				$this->SetPassword($password);
				$this->SetAttribute('reminder_key', '');
				$this->SetAttribute('reminder_date', '');
				$this->Update();

				$ip_restriction = '';
				$ips = list_to_array($this->GetAttribute('ip_restriction'));
				if ($ips) {
					$ip_restriction = "\r\nРазрешённы" . (count($ips) > 1 ? 'е IP-адреса' : 'й IP-адрес') . ': ' . implode(', ', $ips);
				}

				return send_email($g_bo_mail, $this->GetAttribute('email'), 'Доступ',
					"Доступ к системе управления сайта http://{$_SERVER['HTTP_HOST']}{$g_section_start_url}.\r\n\n" .
					'Логин: ' . $this->GetAttribute('login') .
					"\r\nПароль: " . $password .
					$ip_restriction
				) ? 0 : 3;
			} else return 2;
		} else return 1;
	}

	public function GetTitle() {
		return $this->GetAttribute('title') ? $this->GetAttribute('title') : $this->GetAttribute('login');
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

	public static function GetQueryConditions($_conditions = array()) {
		$self = array('table' => self::GetTbl(), 'pk' => self::GetPri());
		$self['pk_attr'] = $self['table'] . '.' . $self['pk'];

		$result = array('tables' => array($self['table']), 'row_conditions' => array());

		if (isset($_conditions['theme_id'])) {
			if ($_conditions['theme_id']) {
				array_push($result['tables'], ThemeToUser::GetTbl());
				array_push($result['row_conditions'], $self['pk_attr'] . ' = ' . ThemeToUser::GetTbl() . '.' . $self['pk'] . ' AND ' . ThemeToUser::GetTbl() . '.' . Theme::GetPri() . ' IN (' . get_db_data($_conditions['theme_id']) . ')');
			}
			unset($_conditions['theme_id']);
		}

		foreach ($_conditions as $attribute => $value) {
			array_push($result['row_conditions'], $self['table'] . '.' . $attribute . (is_array($value) ? ' IN (' . Db::Get()->EscapeList($value) . ')' : ' = ' . get_db_data($value)));
		}
		return $result;
	}

	public static function GetList($_conditions = array(), $_parameters = array(), $_row_conditions = array()) {
		$conditions = self::GetQueryConditions($_conditions);

		$parameters = $_parameters;
		if (!isset($parameters['sort_order'])) {
			$parameters['sort_order'] = 'title';
		}

		$row_conditions = $conditions['row_conditions'];

		if ($_row_conditions) {
			$row_conditions = array_merge($row_conditions, $_row_conditions);
		}

		return parent::GetList(__CLASS__, $conditions['tables'], self::GetBase()->GetAttributes(true), null, $parameters, $row_conditions);
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

			case 'bo_user':
				$result .= '<' . $node_name . '>';
				$result .= '<title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;

		}

		return $result;
	}

	public function GetLinks($_name, $_is_published = null) {
		if (!$this->Links[$_name]) {
			$conditions = array(self::GetPri() => $this->GetId());
			if (!is_null($_is_published)) $conditions['is_published'] = $_is_published;

			switch ($_name) {
				case 'sections':
					$this->Links[$_name] = BoUserToSection::GetList($conditions);
					break;
			}
		}

		return $this->Links[$_name];
	}

	public function GetLinkIds($_name, $_is_published = null) {
		$result = array();

		switch ($_name) {
			case 'sections':
				$keys = array(BoUserToSection::GetFirstKey(), BoUserToSection::GetSecondKey());
				break;
		}

		$key = self::GetPri() == $keys[0] ? $keys[1] : $keys[0];
		$links = $this->GetLinks($_name, $_is_published);

		if ($links) {
			foreach ($links as $item) {
				if ($item->GetAttribute($key)) {
					array_push($result, $item->GetAttribute($key));
				}
			}
		}

		return $result;
	}

	public function SetLinks($_name, $_value = null) {
		$this->Links[$_name] = array();

		switch ($_name) {
			case 'sections':
				$class_name = 'BoUserToSection';
				$keys = array(BoUserToSection::GetFirstKey(), BoUserToSection::GetSecondKey());
				break;
		}

		if (is_array($_value)) {
			$key = $this->GetPri() == $keys[0] ? $keys[1] : $keys[0];

			foreach ($_value as $id => $item) {
				$obj = new $class_name;
				$obj->SetAttribute($this->GetPri(), $this->GetId());

				if (is_array($item)) {
					$obj->SetAttribute($key, $id);
					foreach ($item as $attribute => $value) {
						$obj->SetAttribute($attribute, $value);
					}

				} else {
					$obj->SetAttribute($key, $item);
				}

				array_push($this->Links[$_name], $obj);
			}
		}
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->Attributes as $item) {
			$this->Attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 10, true);
			self::$Base->AddAttribute('status_id', 'int', 11);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('login', 'varchar', 30);
			self::$Base->AddAttribute('passwd', 'varchar', 32);
			self::$Base->AddAttribute('email', 'varchar', 255);
			self::$Base->AddAttribute('ip_restriction', 'text');
			self::$Base->AddAttribute('reminder_key', 'varchar', 30);
			self::$Base->AddAttribute('reminder_date', 'datetime');
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
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	/*public static function GetList($_attributes = array(), $_parameters = array()) {
		return parent::GetList(
			__CLASS__,
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters
		);
	}*/

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
