<?php

class Session {
	const PREFIX = DB_PREFIX;
	const NAME = 'sess';
	const PATH = '/';

	const ACT_PARAM = 'action';
	const ACT_PARAM_NEXT = 'action_next';
	const ACT_START = 1;
	const ACT_LOGIN = 2;
	const ACT_LOGIN_ERROR = 3;
	const ACT_CONTINUE = 4;
	const ACT_LOGOUT = 5;
	const ACT_REMIND_PWD = 6;
	const ACT_REMIND_PWD_ERROR = 7;
	const ACT_CHANGE_PWD = 8;
	const ACT_CHANGE_PWD_ERROR = 9;

	private $IsLoggedIn;
	private $UserId;
	private $Params = array();
	private $CookieName;
	private $CookiePath;

	private static $Obj;

	public static function Get() {
		if (!isset(self::$Obj)) {
			self::$Obj = new Session;
			self::$Obj->Init();
		}

		return self::$Obj;
	}

	protected function __construct() {
		$this->SetCookiePath($this->GetCookiePath());
		$this->SetCookieName($this->GetCookieName());
	}

	protected function Init() {
		$http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$session = Db::Get()->GetEntry('
			SELECT
				is_logged_in,
				user_id
			FROM
				' . self::PREFIX . 'session
			WHERE
				' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()) . ' AND
				user_agent = ' . get_db_data(md5($http_user_agent)) . ' AND (
					ISNULL(valid_date) OR NOW() < valid_date
				) AND (
					life_span <= 0 OR DATE_ADD(creation_date, INTERVAL life_span MINUTE) < NOW()
				) AND (
					timeout <= 0 OR (NOT(ISNULL(last_impression_date)) AND DATE_ADD(last_impression_date, INTERVAL timeout MINUTE) < NOW())
				) AND (
					is_ip_match = 0 OR user_ip = ' . get_db_data($_SERVER['REMOTE_ADDR']) . '
				)
		');

		if ($session) {
			foreach (Db::Get()->GetList('SELECT name, value FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId())) as $item) {
				$this->Params[$item['name']] = unserialize($item['value']);
			}

			$this->Impress();

		} else {
			self::Destroy();
			self::Clean();

			Db::Get()->Execute('INSERT INTO ' . self::PREFIX . 'session' . Db::Get()->GetQueryFields(array(
				self::PREFIX . 'session_id' => get_db_data(self::GetId()),
				'is_ip_match' => 0,
				'is_logged_in' => 0,
				'user_id' => '\'\'',
				'user_agent' => get_db_data(md5($http_user_agent)),
				'user_ip' => get_db_data($_SERVER['REMOTE_ADDR']),
				'life_span' => 0,
				'timeout' => 0,
				'creation_date' => 'NOW()',
				'last_impression_date' => 'NOW()',
				'valid_date' => 'NULL'
			), 'insert', true));
		}

		$this->IsLoggedIn = $session && $session['is_logged_in'] == 1;
		$this->UserId = $session ? $session['user_id'] : null;
	}

	public function SetCookieName($_name) {
		$this->CookieName = $_name;
	}

	public function GetCookieName() {
		if ($this->CookieName) {
			return $this->CookieName;
		} else {
			$name = trim($this->GetCookiePath(), '/');
			$name = self::NAME . ($name ? '_' . $name : '');
			return $name;
		}
	}

	public function SetCookiePath($_path) {
		$this->CookiePath = $_path;
	}

	public function GetCookiePath() {
		if ($this->CookiePath) {
			return $this->CookiePath;
		} else {
			$url = parse_url($_SERVER['REQUEST_URI']);
			preg_match('/^(\/(admin|cms)\/)/', $url['path'], $match);
			return $match ? $match[1] : self::PATH;
		}
	}

	public static function GetId() {
		if (!isset($_COOKIE[self::Get()->GetCookieName()]) || !$_COOKIE[self::Get()->GetCookieName()]) {
			self::SetId(Db::Get()->GetUnique(self::PREFIX . 'session', self::PREFIX . 'session_id', 30));
		}

		return $_COOKIE[self::Get()->GetCookieName()];
	}

	private static function SetId($_id, $_expires = null) {
		$_COOKIE[self::Get()->GetCookieName()] = $_id;
		setcookie(self::Get()->GetCookieName(), $_id, $_expires, self::Get()->GetCookiePath());
	}

	public function IsLoggedIn() {
		return ($this->IsLoggedIn);
	}

	public function GetUserId() {
		return $this->UserId;
	}

	public function Login($_user_id, $_is_ip_match = false, $_life_span = null, $_timeout = null, $_valid_date = null) {
		// self::Clean($_user_id);

		$this->IsLoggedIn = true;
		$this->UserId = $_user_id;
		self::SetId(self::GetId(), $_valid_date ? $_valid_date : null);

		Db::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . Db::Get()->GetQueryFields(array(
			'is_ip_match' => ($_is_ip_match) ? 1 : 0,
			'is_logged_in' => 1,
			'user_id' => get_db_data($_user_id),
			'life_span' => $_life_span ? $_life_span : 0,
			'timeout' => $_timeout ? $_timeout : 0,
			'valid_date' => $_valid_date ? get_db_data(date('Y-m-d H:i:s', $_valid_date)) : 'NULL'
		), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()));
	}

	public function Logout() {
		$this->IsLoggedIn = false;
		$this->UserId = '';

		Db::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . Db::Get()->GetQueryFields(array(
			'is_logged_in' => 0,
			'user_id' => '\'\'',
			'valid_date' => 'NULL'
		), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()));
	}

	private function Impress() {
		DB::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . Db::Get()->GetQueryFields(array('last_impression_date' => 'NOW()'), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()));
	}

	private function InitParam($_name, $_value) {
		$this->Params[$_name] = unserialize($_value);
	}

	public function DeleteParam($_name) {
		Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()) . ' AND name = ' . get_db_data($_name));
		unset($this->Params[$_name]);
	}

	public function SetParam($_name, $_value) {
		self::DeleteParam($_name);

		Db::Get()->Execute('INSERT INTO ' . self::PREFIX . 'session_param' . Db::Get()->GetQueryFields(array(
			self::PREFIX . 'session_id' => self::GetId(),
			'name' => $_name,
			'value' => serialize($_value)
		), 'insert'));

		$this->Params[$_name] = $_value;
	}

	public function GetParam($_name) {
		if (!isset($this->Params[$_name])) {
			$param = Db::Get()->GetEntry('SELECT value FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()) . ' AND name = ' . get_db_data($_name));
			$this->Params[$_name] = $param ? unserialize($param['value']) : null;
		}

		return $this->Params[$_name];
	}

	private function Destroy() {
		Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()));
		Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . get_db_data(self::GetId()));
	}

	public static function Clean($_user_id = null) {
		Db::Get()->Execute('
			DELETE FROM
				' . self::PREFIX . 'session
			WHERE
				' . ($_user_id ? 'user_id = ' . get_db_data($_user_id) . ' OR ' : '') . '
				(NOT(ISNULL(valid_date)) AND valid_date < NOW()) OR
				(ISNULL(valid_date) AND DATE_ADD(last_impression_date, INTERVAL 1 DAY) < NOW()) OR
				(life_span > 0 AND DATE_ADD(creation_date, INTERVAL life_span MINUTE) < NOW()) OR
				(timeout > 0 AND (ISNULL(last_impression_date) OR DATE_ADD(last_impression_date, INTERVAL timeout MINUTE) < NOW()))
		');

		// Äëÿ MySQL > 3.23
		// Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id NOT IN (SELECT ' . self::PREFIX . 'session_id FROM ' . self::PREFIX . 'session)');

		$sessions = Db::Get()->GetList('SELECT ' . self::PREFIX . 'session_id FROM ' . self::PREFIX . 'session');
		if ($sessions) {
			Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id NOT IN (' . get_db_data($sessions) . ')');
		} else {
			Db::Get()->Execute('TRUNCATE ' . self::PREFIX . 'session_param');
		}
	}

	public function GetXml($_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : 'session';
		$result = '<' . $node_name . ' id="' . self::GetId() . '"';

		foreach (array(self::ACT_PARAM) as $item) {
			if ($this->GetParam($item)) {
				$result .= ' ' . $item . '="' . $this->GetParam($item) . '"';
			}
		}

		return $result . '>' . $_append_xml . '</' . $node_name . '>';
	}

	public function GetWorkmateXml() {
		$result = '';

		$workmates = $this->GetWorkmates();
		if ($workmates) {
			$result .= '<workmates>';
			foreach ($workmates as $item) {
				$result .= '<user><![CDATA[' . (isset($item['title']) ? $item['title'] : $item['login']) . ']]></user>';
			}
			$result .= '</workmates>';
		}

		return $result;
	}

	public function GetWorkmates() {
		if ($this->IsLoggedIn()) {
			$workmates = Db::Get()->GetList('
				SELECT
					u.title,
					u.login
				FROM
					' . self::PREFIX . 'session AS s,
					' . self::PREFIX . 'bo_user AS u
				WHERE
					DATE_ADD(s.last_impression_date, INTERVAL 15 MINUTE) > NOW() AND
					s.user_id != \'\' AND
					s.' . self::PREFIX . 'session_id != ' . get_db_data($this->GetId()) . ' AND
					s.user_id = u.' . self::PREFIX . 'bo_user_id
			');
		}

		return isset($workmates) && $workmates ? $workmates : false;
	}
}

?>
