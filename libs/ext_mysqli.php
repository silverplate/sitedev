<?php

class Ext_Mysqli extends Mysqli
{
    /**
     * Logs file.
     *
     * @var string
     */
    protected $_logFilePath = 'mysqli.log';

    /**
     * Logs on or off.
     *
     * @var boolean
     */
    public $isLog = false;

    /**
     * Database user.
     *
     * @var string
     */
    protected $_user;

    /**
     * Database user password.
     *
     * @var string
     */
    protected $_password;

    /**
     * Database host.
     *
     * @var string
     */
    protected $_host;

    /**
     * Database port.
     *
     * @var string
     */
    protected $_port;

    /**
     * Database name.
     *
     * @var string
     */
    protected $_database;

    /**
     * Connects to database with passed connection string.
     *
     * @param string $_connectionString
     */
    public function __construct($_connectionString)
    {
        $connectionString = '';

        for ($i = strlen($_connectionString) - 1; $i >= 0; $i--) {
            $append = $_connectionString{$i} == '@' &&
                      strpos($_connectionString, '@', $i + 1) !== false
                    ? '~Я~'
                    : $_connectionString{$i};

            $connectionString = $append . $connectionString;
        }

        $params = parse_url($connectionString);

        foreach (array('user', 'pass', 'host', 'port', 'path') as $item) {
            if (isset($params[$item])) {
                $params[$item] = str_replace('~Я~', '@', $params[$item]);
            } else {
                $params[$item] = '';
            }
        }

        $this->_user     = $params['user'];
        $this->_password = $params['pass'];
        $this->_host     = $params['host'];
        $this->_port     = (int) $params['port'];
        $this->_database = trim($params['path'], '/');

        @parent::__construct($this->_host,
                             $this->_user,
                             $this->_password,
                             $this->_database,
                             $this->_port);

        $this->_throwError();

        if (!empty($params['query'])) {
            $query = array();

            foreach (explode('&', $params['query']) as $item) {
                list($name, $value) = explode('=', $item);

                if ($name && $value) {
                    $query[$name] = $value;
                }
            }

            if (!empty($query['set-names'])) {
                $this->execute('SET NAMES ' . $query['set-names']);
            }
        }
    }

    /**
     * Executes a query.
     *
     * @param string $_query
     * @return mysqli_result
     */
    public function execute($_query)
    {
        if ($this->isLog) {
            list($msec, $sec) = explode(' ', microtime());
            $start = (float) $msec + (float) $sec;
        }

        $result = $this->query($_query);
        $this->_throwError();

        if ($this->isLog) {
            list($msec, $sec) = explode(' ', microtime());
            $finish = (float) $msec + (float) $sec;
            $this->log($_query, $finish - $start);
        }

        return $result;
    }

    /**
     * Executes multiple queries.
     *
     * @param string $_query
     * @return mysqli_result
     */
    public function multiExecute($_query)
    {
        if ($this->multi_query($_query)) {
            $result = array();

            do {
                $item = $this->store_result();

                if ($item) {
                    $row = $item->fetch_row();

                    while ($row) {
                        $result[] = $row;
                        $row = $item->fetch_row();
                    }

                    $item->free();
                }

            } while ($this->next_result());

            return $result;
        }

        return false;
    }

    /**
     * Executes a query and return associated array of the first entry.
     *
     * @param string $_query
     * @return array
     */
    public function getEntry($_query)
    {
        $result = $this->execute($_query);
        return $result && $result->num_rows > 0 ? $result->fetch_assoc() : false;
    }

    /**
     * Returns an array of result rows.
     *
     * @param string $_query
     * @param string $_options auto, one, few
     * @return array
     */
    public function getList($_query, $_options = 'auto')
    {
        $list = array();
        $result = $this->execute($_query);

        if ($result && $result->num_rows > 0) {
            $type = $_options;

            if ($type != 'one' && $type != 'few') {
                $type = $this->field_count == 1 ? 'one' : 'few';
            }

            if ($type == 'one') {
                $row = $result->fetch_row();

                while ($row) {
                    $list[] = $row[0];
                    $row = $result->fetch_row();
                }

            } else {
                $row = $result->fetch_assoc();

                while ($row) {
                    $list[] = $row;
                    $row = $result->fetch_assoc();
                }
            }
        }

        return $list;
    }

    /**
     * Gets last inserted ID.
     *
     * @return integer
     */
    public function getLastInsertedId()
    {
        return $this->insert_id;
    }

    /**
     * @param string|integer|array $_data
     * @param string $_quote
     * @return string
     */
    public function escape($_data, $_quote = null)
    {
        return is_array($_data)
             ? $this->escapeList($_data, $_quote)
             : $this->escapeValue($_data, $_quote);
    }

    /**
     * Prepares data to be posted into a query.
     *
     * @param string $_value
     * @param string $_quote
     * @return string
     */
    public function escapeValue($_value, $_quote = null)
    {
        $quote = empty($_quote) ? '\'' : $_quote;
        return Ext_Number::isNumber($_value)
             ? $_value
             : $quote . $this->real_escape_string($_value) . $quote;
    }

    /**
     * Prepares data to be posted into a query.
     *
     * @param array $_values
     * @param string $_quote
     * @return string
     */
    public function escapeList($_values, $_quote = null)
    {
        $result = array();

        for ($i = 0; $i < count($_values); $i++) {
            $result[] = $this->escape($_values[$i], $_quote);
        }

        return implode(', ', $result);
    }

    /**
     * Returns list of fields for inserting into a query.
     *
     * @param array $_fields
     * @param string $_type
     * @param boolean $_isEscaped
     * @return string
     */
    public function getQueryFields($_fields, $_type, $_isEscaped = false)
    {
        return $_isEscaped
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
    public function getCustomQueryFields($type,
                                         array $parse = array(),
                                         array $leave = array())
    {
        foreach ($parse as $name => $value) {
            $parse[$name] = $this->escape($value);
        }

        $fields = array_merge($parse, $leave);

        if ('insert' == $type) {
            return ' (' . implode(', ', array_keys($fields)) . ') ' .
                   'VALUES (' . implode(', ', array_values($fields)) . ')';

        } else if ('update' == $type) {
            $result = '';

            foreach ($fields as $field => $value) {
                $result .= ('' == $result ? '' : ', ') . $field . ' = ' . $value;
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
     * @return integer
     */
    public function getNextNumber($_table, $_field, $_condition = '')
    {
        $result = $this->getEntry(
            'SELECT ' . $_field . ' AS max' .
            ' FROM ' . $_table .
            ($_condition ? ' WHERE ' . $_condition : '') .
            ' ORDER BY ' . $_field . ' DESC' .
            ' LIMIT 1'
        );

        return (int) $result['max'] + 1;
    }

    /**
     * Returns unique string for field in table.
     *
     * @param string $_table
     * @param string $_field
     * @param integer $_length
     * @return string
     */
    public function getUnique($_table, $_field = null, $_length = 30)
    {
        $field = is_null($_field) ? $_table . '_id' : $_field;

        while (true) {
            $unique = Ext_String::getRandom($_length);

            if (!$this->getEntry('SELECT ' . $field .
                                 ' FROM ' . $_table .
                                 ' WHERE ' . $field . ' = ' . $this->escape($unique))
            ) {
                break;
            }
        }

        return $unique;
    }

    /**
     * Appends query with additional info to log file.
     *
     * @param string $_value
     * @param float $_time
     */
    public function log($_value, $_time = null)
    {
        if ($this->isLog) {
            $fp = fopen(rtrim(dirname(__FILE__), '/') . '/' . $this->_logFilePath, 'a');
            $log = array(date('Y-m-d H:i:s'),
                         $_time ? number_format($_time, 4) : '',
                         preg_replace('/\s+/', ' ', trim($_value)));

            foreach (array('REQUEST_URI', 'REMOTE_ADDR', 'HTTP_USER_AGENT') as $i) {
                if (!empty($_SERVER[$i])) {
                    $log[] = $_SERVER[$i];
                }
            }

            fwrite($fp, implode("\t", $log) . PHP_EOL);
            fclose($fp);
        }
    }

    /**
     * Defines how errors output. First, try to output through framework function.
     *
     * @param string $_error
     */
    protected function _error($_error)
    {
        die($_error);
    }

    protected function _throwError()
    {
        if ($this->connect_error) {
            $this->_error(
                "MySQL connection error ({$this->connect_errno}): {$this->connect_error}"
            );
        }

        if (mysqli_connect_error()) {
            $this->_error('Connect error (' . mysqli_connect_errno() . '): ' .
                          mysqli_connect_error());
        }

        if ($this->error) {
            $this->_error("MySQL error ({$this->errno}): {$this->error}");
        }
    }

    /**
     * Returns database user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns database password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Returns database host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Returns database port.
     *
     * @return string
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Returns database name.
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if ($this->host_info) {
            $this->close();
        }

        $this->log('Mysqli descructor.');
    }
}
