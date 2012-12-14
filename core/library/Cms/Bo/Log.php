<?php

abstract class Core_Cms_Bo_Log extends App_ActiveRecord
{
	private static $Base;
	const TABLE = 'bo_log';

	const ACT_LOGIN = 1;
	const ACT_LOGOUT = 2;
	const ACT_CREATE = 3;
	const ACT_MODIFY = 4;
	const ACT_DELETE = 5;
	const ACT_REMIND_PWD = 6;
	const ACT_CHANGE_PWD = 7;

	public static function LogModule($_action_id, $_entry_id, $_description = null) {
		return self::Log($_action_id, array('entry_id' => $_entry_id, 'description' => $_description));
	}

	public static function Log($_action_id, $_params = array()) {
		global $g_user, $g_section;

		$params = array(
			'user_ip' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'request_uri' => $_SERVER['REQUEST_URI'],
			'request_get' => serialize($_GET),
			'request_post' => serialize($_POST),
			'cookies' => serialize($_SERVER['HTTP_COOKIE']),
			'script_name' => $_SERVER['SCRIPT_NAME'],
			'action_id' => $_action_id,
			'entry_id' => isset($_params['entry_id']) ? $_params['entry_id'] : '',
			'description' => isset($_params['description']) ? $_params['description'] : ''
		);

		if (isset($_params['section'])) {
			$params[BoSection::GetPri()] = $_params['section']->GetId();
			$params['section_name'] = $_params['section']->GetTitle();

		} elseif ($g_section) {
			$params[BoSection::GetPri()] = $g_section->GetId();
			$params['section_name'] = $g_section->GetTitle();

		} elseif (isset($_params['section_id']) && isset($_params['section_name'])) {
			$params[BoSection::GetPri()] = $_params['section_id'];
			$params['section_name'] = $_params['section_name'];

		} else {
			$section = BoSection::Compute();
			if ($section) {
				$params[BoSection::GetPri()] = $section->GetId();
				$params['section_name'] = $section->GetTitle();
			}
		}

		if (isset($_params['user'])) {
			$params[BoUser::GetPri()] = $_params['user']->GetId();
			$params['user_name'] = $_params['user']->GetTitle();

		} elseif ($g_user) {
			$params[BoUser::GetPri()] = $g_user->GetId();
			$params['user_name'] = $g_user->GetTitle();

		} elseif (isset($_params['user_id']) && isset($_params['user_name'])) {
			$params[BoUser::GetPri()] = $_params['user_id'];
			$params['user_name'] = $_params['user_name'];
		}

		$class_name = __CLASS__;
		$obj = new $class_name;
		$obj->DataInit($params);
		$obj->Create();

		return $obj;
	}

	public static function GetActions() {
		return array(
			BoLog::ACT_LOGIN => 'Авторизация',
			BoLog::ACT_LOGOUT => 'Окончание работы',
			BoLog::ACT_CREATE => 'Создание',
			BoLog::ACT_MODIFY => 'Изменение',
			BoLog::ACT_DELETE => 'Удаление',
			BoLog::ACT_REMIND_PWD => 'Напоминание пароля',
			BoLog::ACT_CHANGE_PWD => 'Смена пароля'
		);
	}

	public function GetXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : __CLASS__;
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$append_attributes = array('date' => date('d.m.y H:i:s', $this->GetDate('creation_date')));
				foreach (array('entry_id', 'user_ip', 'script_name', 'action_id') as $item) {
					if ($this->GetAttribute($item)) {
						$append_attributes[$item] = $this->GetAttribute($item);
					}
				}

				$append_xml = $_append_xml;
				foreach (array('user_agent', 'description') as $item) {
					if ($this->GetAttribute($item)) {
						$append_xml .= '<' . $item . '><![CDATA[' . $this->GetAttribute($item) . ']]></' . $item . '>';
					}
				}

				$result = parent::GetXml(null, $node_name, $append_xml, $append_attributes);
				break;
		}

		return $result;
	}

	public static function GetQueryConditions($_conditions = array()) {
		$self = array('table' => self::GetTbl(), 'pk' => self::GetPri());
		$self['pk_attr'] = $self['table'] . '.' . $self['pk'];

		$result = array('tables' => array($self['table']), 'row_conditions' => array());

		if (isset($_conditions['from_date'])) {
			if ($_conditions['from_date']) {
				array_push($result['row_conditions'], $self['table'] . '.creation_date >= ' . Db::escape(date('Y-m-d 00:00:00', $_conditions['from_date'])));
			}
			unset($_conditions['from_date']);
		}

		if (isset($_conditions['till_date'])) {
			if ($_conditions['till_date']) {
				array_push($result['row_conditions'], $self['table'] . '.creation_date <= ' . Db::escape(date('Y-m-d 23:59:59', $_conditions['till_date'])));
			}
			unset($_conditions['till_date']);
		}

		if ($_conditions) {
			foreach ($_conditions as $attribute => $value) {
				array_push($result['row_conditions'], $self['table'] . '.' . $attribute . (is_array($value) ? ' IN (' . Db::Get()->EscapeList($value) . ')' : ' = ' . Db::escape($value)));
			}
		}

		return $result;
	}

	public static function GetList($_conditions = array(), $_parameters = array(), $_row_conditions = array()) {
		$conditions = self::GetQueryConditions($_conditions);

		$parameters = $_parameters;
		if (!isset($parameters['sort_order'])) {
			$parameters['sort_order'] = 'creation_date DESC';
		}

		$row_conditions = $conditions['row_conditions'];
		if ($_row_conditions) {
			$row_conditions = array_merge($row_conditions, $_row_conditions);
		}

		return parent::GetList(__CLASS__, $conditions['tables'], self::GetBase()->GetAttributes(true), null, $parameters, $row_conditions);
	}


	public static function GetCount($_conditions = array(), $_row_conditions = array()) {
		$conditions = self::GetQueryConditions($_conditions);

		$row_conditions = $conditions['row_conditions'];
		if ($_row_conditions) {
			$row_conditions = array_merge($row_conditions, $_row_conditions);
		}

		return parent::GetCount(__CLASS__, $conditions['tables'], null, $row_conditions);
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
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'int', 10, true);
			self::$Base->AddForeignKey(BoUser::GetBase());
			self::$Base->AddForeignKey(BoSection::GetBase());
			self::$Base->AddAttribute('section_name', 'varchar', 255);
			self::$Base->AddAttribute('user_name', 'varchar', 255);
			self::$Base->AddAttribute('user_ip', 'varchar', 15);
			self::$Base->AddAttribute('user_agent', 'varchar', 255);
			self::$Base->AddAttribute('request_uri', 'text');
			self::$Base->AddAttribute('request_get', 'text');
			self::$Base->AddAttribute('request_post', 'text');
			self::$Base->AddAttribute('cookies', 'text');
			self::$Base->AddAttribute('script_name', 'varchar', 255);
			self::$Base->AddAttribute('action_id', 'int');
			self::$Base->AddAttribute('entry_id', 'varchar', 30);
			self::$Base->AddAttribute('description', 'text');
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
		return parent::Load(__CLASS__, $_value, $_attribute);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
