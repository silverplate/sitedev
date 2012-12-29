<?php

abstract class Core_Cms_Session
{
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

    private $_isLoggedIn;
    private $_userId;
    private $_params = array();
    private $_cookieName;
    private $_cookiePath;
    private static $_obj;

    /**
     * @return App_Cms_Session
     */
    public static function get()
    {
        if (!isset(self::$_obj)) {
            $class = get_called_class();

            self::$_obj = new $class;
            self::$_obj->_init();
        }

        return self::$_obj;
    }

    protected function __construct()
    {
        $this->setCookiePath($this->getCookiePath());
        $this->setCookieName($this->getCookieName());
    }

    protected function _init()
    {
        $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $session = App_Db::Get()->GetEntry('
            SELECT
                is_logged_in,
                user_id
            FROM
                ' . self::PREFIX . 'session
            WHERE
                ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()) . ' AND
                user_agent = ' . App_Db::escape(md5($http_user_agent)) . ' AND (
                    ISNULL(valid_date) OR NOW() < valid_date
                ) AND (
                    life_span <= 0 OR DATE_ADD(creation_date, INTERVAL life_span MINUTE) < NOW()
                ) AND (
                    timeout <= 0 OR (NOT(ISNULL(last_impression_date)) AND DATE_ADD(last_impression_date, INTERVAL timeout MINUTE) < NOW())
                ) AND (
                    is_ip_match = 0 OR user_ip = ' . App_Db::escape($_SERVER['REMOTE_ADDR']) . '
                )
        ');

        if ($session) {
            foreach (App_Db::Get()->GetList('SELECT name, value FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId())) as $item) {
                $this->_params[$item['name']] = unserialize($item['value']);
            }

            $this->impress();

        } else {
            self::_destroy();
            self::clean();

            App_Db::Get()->Execute('INSERT INTO ' . self::PREFIX . 'session' . App_Db::Get()->GetQueryFields(array(
                self::PREFIX . 'session_id' => App_Db::escape(self::getId()),
                'is_ip_match' => 0,
                'is_logged_in' => 0,
                'user_id' => '\'\'',
                'user_agent' => App_Db::escape(md5($http_user_agent)),
                'user_ip' => App_Db::escape($_SERVER['REMOTE_ADDR']),
                'life_span' => 0,
                'timeout' => 0,
                'creation_date' => 'NOW()',
                'last_impression_date' => 'NOW()',
                'valid_date' => 'NULL'
            ), 'insert', true));
        }

        $this->_isLoggedIn = $session && $session['is_logged_in'] == 1;
        $this->_userId = $session ? $session['user_id'] : null;
    }

    public function setCookieName($_name)
    {
        $this->_cookieName = $_name;
    }

    public function getCookieName()
    {
        if ($this->_cookieName) {
            return $this->_cookieName;
        } else {
            $name = trim($this->getCookiePath(), '/');
            $name = self::NAME . ($name ? '_' . $name : '');
            return $name;
        }
    }

    public function setCookiePath($_path)
    {
        $this->_cookiePath = $_path;
    }

    public function getCookiePath()
    {
        if ($this->_cookiePath) {
            return $this->_cookiePath;
        } else {
            $url = parse_url($_SERVER['REQUEST_URI']);
            preg_match('/^(\/(admin|cms)\/)/', $url['path'], $match);
            return $match ? $match[1] : self::PATH;
        }
    }

    public static function getId()
    {
        if (!isset($_COOKIE[self::get()->getCookieName()]) || !$_COOKIE[self::get()->getCookieName()]) {
            self::_setId(App_Db::Get()->GetUnique(self::PREFIX . 'session', self::PREFIX . 'session_id', 30));
        }

        return $_COOKIE[self::get()->getCookieName()];
    }

    private static function _setId($_id, $_expires = null)
    {
        $_COOKIE[self::get()->getCookieName()] = $_id;
        setcookie(self::get()->getCookieName(), $_id, $_expires, self::get()->GetCookiePath());
    }

    public function isLoggedIn()
    {
        return (boolean) $this->_isLoggedIn;
    }

    public function getUserId()
    {
        return $this->_userId;
    }

    public function login($_userId,
                          $_isIpMatch = false,
                          $_lifeSpan = null,
                          $_timeout = null,
                          $_validDate = null)
    {
        $this->_isLoggedIn = true;
        $this->_userId = $_userId;
        self::_setId(self::getId(), $_validDate ? $_validDate : null);

        App_Db::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . App_Db::Get()->GetQueryFields(array(
            'is_ip_match' => ($_isIpMatch) ? 1 : 0,
            'is_logged_in' => 1,
            'user_id' => App_Db::escape($_userId),
            'life_span' => $_lifeSpan ? $_lifeSpan : 0,
            'timeout' => $_timeout ? $_timeout : 0,
            'valid_date' => $_validDate ? App_Db::escape(date('Y-m-d H:i:s', $_validDate)) : 'NULL'
        ), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()));
    }

    public function logout()
    {
        $this->_isLoggedIn = false;
        $this->_userId = '';

        App_Db::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . App_Db::Get()->GetQueryFields(array(
            'is_logged_in' => 0,
            'user_id' => '\'\'',
            'valid_date' => 'NULL'
        ), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()));
    }

    private function impress()
    {
        App_Db::Get()->Execute('UPDATE ' . self::PREFIX . 'session' . App_Db::Get()->GetQueryFields(array('last_impression_date' => 'NOW()'), 'update', true) . 'WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()));
    }

    private function _initParam($_name, $_value)
    {
        $this->_params[$_name] = unserialize($_value);
    }

    public function deleteParam($_name)
    {
        App_Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()) . ' AND name = ' . App_Db::escape($_name));
        unset($this->_params[$_name]);
    }

    public function setParam($_name, $_value)
    {
        self::deleteParam($_name);

        App_Db::Get()->Execute('INSERT INTO ' . self::PREFIX . 'session_param' . App_Db::Get()->GetQueryFields(array(
            self::PREFIX . 'session_id' => self::getId(),
            'name' => $_name,
            'value' => serialize($_value)
        ), 'insert'));

        $this->_params[$_name] = $_value;
    }

    public function getParam($_name)
    {
        if (!isset($this->_params[$_name])) {
            $param = App_Db::Get()->GetEntry('SELECT value FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()) . ' AND name = ' . App_Db::escape($_name));
            $this->_params[$_name] = $param ? unserialize($param['value']) : null;
        }

        return $this->_params[$_name];
    }

    private function _destroy()
    {
        App_Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()));
        App_Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id = ' . App_Db::escape(self::getId()));
    }

    public static function clean($_userId = null)
    {
        App_Db::Get()->Execute('
            DELETE FROM
                ' . self::PREFIX . 'session
            WHERE
                ' . ($_userId ? 'user_id = ' . App_Db::escape($_userId) . ' OR ' : '') . '
                (NOT(ISNULL(valid_date)) AND valid_date < NOW()) OR
                (ISNULL(valid_date) AND DATE_ADD(last_impression_date, INTERVAL 1 DAY) < NOW()) OR
                (life_span > 0 AND DATE_ADD(creation_date, INTERVAL life_span MINUTE) < NOW()) OR
                (timeout > 0 AND (ISNULL(last_impression_date) OR DATE_ADD(last_impression_date, INTERVAL timeout MINUTE) < NOW()))
        ');

        App_Db::Get()->Execute('DELETE FROM ' . self::PREFIX . 'session_param WHERE ' . self::PREFIX . 'session_id NOT IN (SELECT ' . self::PREFIX . 'session_id FROM ' . self::PREFIX . 'session)');
    }

    public function getXml($_node = null, $_xml = null)
    {
        $node_name = ($_node) ? $_node : 'session';
        $result = '<' . $node_name . ' id="' . self::getId() . '"';

        foreach (array(self::ACT_PARAM) as $item) {
            if ($this->getParam($item)) {
                $result .= ' ' . $item . '="' . $this->getParam($item) . '"';
            }
        }

        return $result . '>' . $_xml . '</' . $node_name . '>';
    }

    public function getWorkmateXml()
    {
        $result = '';

        $workmates = $this->getWorkmates();
        if ($workmates) {
            $result .= '<workmates>';
            foreach ($workmates as $item) {
                $result .= '<user><![CDATA[' . (isset($item['title']) ? $item['title'] : $item['login']) . ']]></user>';
            }
            $result .= '</workmates>';
        }

        return $result;
    }

    public function getWorkmates()
    {
        if ($this->isLoggedIn()) {
            $workmates = App_Db::Get()->GetList('
                SELECT
                    u.title,
                    u.login
                FROM
                    ' . self::PREFIX . 'session AS s,
                    ' . self::PREFIX . 'back_user AS u
                WHERE
                    DATE_ADD(s.last_impression_date, INTERVAL 15 MINUTE) > NOW() AND
                    s.user_id != \'\' AND
                    s.' . self::PREFIX . 'session_id != ' . App_Db::escape($this->getId()) . ' AND
                    s.user_id = u.' . self::PREFIX . 'back_user_id
            ');
        }

        return isset($workmates) && $workmates ? $workmates : false;
    }
}
