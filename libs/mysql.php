<?php

class DbMysql {
	/**
	 * Connection to a MySQL server.
	 *
	 * @var resource
	 */
	public $Connection;

	/**
	 * Sets logs file.
	 *
	 * @var string
	 */
	protected $LogFilePath = 'mysql.log';

	/**
	 * Sets logs on or off.
	 *
	 * @var bool
	 */
	public $IsLog = false;

	/**
	 * Database user.
	 *
	 * @var string
	 */
	protected $User = '';

	/**
	 * Database user password.
	 *
	 * @var string
	 */
	protected $Password = '';

	/**
	 * Database host.
	 *
	 * @var string
	 */
	protected $Host = '';

	/**
	 * Database port.
	 *
	 * @var string
	 */
	protected $Port = '';

	/**
	 * Database name.
	 *
	 * @var string
	 */
	protected $Database = '';

	/**
	 * Connects to database with passed connection string.
	 *
	 * @param string $_connection_string
	 * @return bool
	 */
	public function __construct($_connection_string) {
		$connection_string = '';

		for ($i = strlen($_connection_string) - 1; $i >= 0; $i--) {
			$append = $_connection_string{$i} == '@' && strpos($_connection_string, '@', $i + 1) !== false
				? '~Я~'
				: $_connection_string{$i};

			$connection_string = $append . $connection_string;
		}

		$params = parse_url($connection_string);

		foreach (array('user', 'pass', 'host', 'port', 'path') as $item) {
			if (isset($params[$item])) {
				$params[$item] = str_replace('~Я~', '@', $params[$item]);
			} else {
				$params[$item] = '';
			}
		}

		$this->User = $params['user'];
		$this->Password = $params['pass'];
		$this->Host = $params['host'];
		$this->Port = $params['port'];
		$this->Database = trim($params['path'], '/');

		$this->Connection = mysql_connect(rtrim($this->Host . ':' . $this->Port, ':'), $this->User, $this->Password, true) or $this->Error(mysql_error());
		mysql_select_db($this->Database) or $this->Error(mysql_error());
	}

	/**
	 * Executes a query.
	 *
	 * @param string $_query
	 * @return resource
	 */
	public function Execute($_query) {
		if ($this->IsLog) {
			list($msec, $sec) = explode(' ', microtime());
			$start = ((float) $msec + (float) $sec);
		}

		$result = mysql_query($_query, $this->Connection) or $this->Error(mysql_error() . '. ' . $_query);

		if ($this->IsLog) {
			list($msec, $sec) = explode(' ', microtime());
			$finish = ((float) $msec + (float) $sec);
			$this->Log($_query, $finish - $start);
		}

		return $result;
	}

	/**
	 * Executes multiple queries.
	 *
	 * @param string $_query
	 * @return array
	 */
	public function MultiExecute($_query) {
		$result = array();
		foreach (explode(';', trim($_query, ';')) as $query) {
			if ('' != $query) {
				array_push($result, $this->Execute($query));
			}
		}

		return $result;
	}

	/**
	 * Executes a query and return associated array of the first entry.
	 *
	 * @param string $_query
	 * @return array
	 */
	public function GetEntry($_query) {
		$result = $this->Execute($_query);
		return (mysql_num_rows($result) > 0) ? mysql_fetch_array($result, MYSQL_ASSOC) : false;
	}

	/**
	 * Returns an array of result rows.
	 *
	 * @param string $query
	 * @param string $options auto, one, few
	 * @return array
	 */
	public function GetList($_query, $_options = 'auto') {
		$list = array();
		$result = $this->Execute($_query);
		$type = ($_options == 'one' || ($_options == 'auto' && mysql_num_fields($result) == 1)) ? 'one' : 'few';

		if ($type == 'one') {
			while ($row = mysql_fetch_row($result)) array_push($list, $row[0]);
		} else {
			while ($row = mysql_fetch_assoc($result)) array_push($list, $row);
		}

		return $list;
	}

	/**
	 * Gets last inserted ID.
	 *
	 * @return int
	 */
	public function GetLastInsertedId() {
		return mysql_insert_id($this->Connection);
	}

	public function Escape($_data, $_quote = '\'') {
		return is_array($_data)
			? $this->EscapeList($_data, $_quote)
			: $this->EscapeValue($_data, $_quote);
	}

	/**
	 * Prepares data to be posted into a query.
	 *
	 * @param string $_value
	 * @param string $_quote
	 * @return string
	 */
	public function EscapeValue($_value, $_quote = '\'') {
		return
			preg_match('/^[0-9]+\.[0-9]+$/', $_value) || preg_match('/^[1-9][0-9]*$/', $_value)
			? $_value
			: $_quote . mysql_real_escape_string($_value, $this->Connection) . $_quote;
	}

	/**
	 * Prepares data to be posted into a query.
	 *
	 * @param array $_values
	 * @param string $_quote
	 * @return string
	 */
	public function EscapeList($_values, $_quote = '\'') {
		$result = array();
		for ($i = 0; $i < count($_values); $i++) {
			array_push($result, $this->Escape($_values[$i], $_quote));
		}
		return implode(', ', $result);
	}

	/**
	 * Returns list of fields for inserting into a query.
	 *
	 * @param array $_fields
	 * @param string $_type
	 * @param bool $_is_escaped
	 * @return string
	 */
	public function GetQueryFields($_fields, $_type, $_is_escaped = false) {
		return $_is_escaped
			? $this->getCustomQueryFields($_type, array(), $_fields)
			: $this->getCustomQueryFields($_type, $_fields, array());
	}

	/**
	 * Returns list of fields for inserting into a query.
	 *
	 * @param string $type
	 * @param array $parse
	 * @param array $leave
	 * @return string
	 */
	public function getCustomQueryFields($type, array $parse = array(), array $leave = array()) {
		foreach ($parse as $name => $value) {
			$parse[$name] = $this->Escape($value);
		}

		$fields = array_merge($parse, $leave);

		if ('insert' == $type) {
			return
				' (' . implode(', ', array_keys($fields)) . ') ' .
				'VALUES (' . implode(', ', array_values($fields)) . ')';

		} elseif ('update' == $type) {
			$result = '';

			foreach ($fields as $field => $value) {
				$result .=
					('' == $result ? '' : ', ') .
					$field . ' = ' . $value;
			}

			return ' SET ' . $result . ' ';
		}

		return false;
	}

	/**
	 * Returns next auto increment int value.
	 *
	 * @param string $_table
	 * @param string $_field
	 * @param string $_condition
	 * @return int
	 */
	public function GetNextNumber($_table, $_field, $_condition = '') {
		$result = $this->GetEntry('SELECT ' . $_field . ' AS max FROM ' . $_table . (($_condition) ? ' WHERE ' . $_condition : '') . ' ORDER BY ' . $_field . ' DESC LIMIT 1');
		return (int) $result['max'] + 1;
	}

	/**
	 * Returns unique string for field in table.
	 *
	 * @param string $_table
	 * @param string $_field
	 * @param int $_length
	 * @return string
	 */
	public function GetUnique($_table, $_field = null, $_length = 30) {
		$field = is_null($_field) ? $_table . '_id' : $_field;
		while (true) {
			$unique = $this->GetRandomString($_length);
			if (!$this->GetEntry('SELECT ' . $field . ' FROM ' . $_table . ' WHERE ' . $field . ' = ' . $this->Escape($unique))) {
				break;
			}
		}
		return $unique;
	}

	/**
	 * Returns random string.
	 *
	 * @param int $_length
	 * @return string
	 */
	private function GetRandomString($_length) {
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$numbers = '0123456789';
		$symbol = '';
		$result = '';

		for ($i = 0; $i < $_length; $i++) {
			if (0 == rand(0, 3)) {
				$symbol = $letters{rand(0, strlen($letters) - 1)};
				if (0 == rand(0, 3)) $symbol = strtoupper($symbol);
			} else {
				$symbol = $numbers{rand(0, strlen($numbers) - 1)};
			}
			$result .= $symbol;
		}
		return $result;
	}

	/**
	 * Appends query with additional info to log file.
	 *
	 * @param string $_value
	 */
	public function Log($_value, $_time = null) {
		if ($this->IsLog) {
			$fp = fopen(rtrim(dirname(__FILE__), '/') . '/' . $this->LogFilePath, 'a');
			$log = array(date('Y-m-d H:i:s'), $_time ? format_number($_time, 4) : '', preg_replace('/\s+/', ' ', trim($_value)));

			foreach (array('REQUEST_URI', 'REMOTE_ADDR', 'HTTP_USER_AGENT') as $i) {
				if (isset($_SERVER[$i]) && $_SERVER[$i]) array_push($log, $_SERVER[$i]);
			}

			fwrite($fp, implode("\t", $log) . "\n");
			fclose($fp);
		}
	}

	/**
	 * Defines how errors output. First, try to output through framework function.
	 *
	 * @param string $_error
	 */
	protected function Error($_error) {
		die($_error);
	}

	/**
	 * Returns database user.
	 *
	 * @return string
	 */
	public function GetUser() {
		return $this->User;
	}

	/**
	 * Returns database password.
	 *
	 * @return string
	 */
	public function GetPassword() {
		return $this->Password;
	}

	/**
	 * Returns database host.
	 *
	 * @return string
	 */
	public function GetHost() {
		return $this->Host;
	}

	/**
	 * Returns database port.
	 *
	 * @return string
	 */
	public function GetPort() {
		return $this->Port;
	}

	/**
	 * Returns database name.
	 *
	 * @return string
	 */
	public function GetDatabase() {
		return $this->Database;
	}

	/**
	 * Destructor.
	 *
	 */
	public function __destruct() {
		if (!empty($this->Connection)) {
			mysql_close($this->Connection);
		}
		$this->Log('Mysql descructor.');
	}
}

?>
