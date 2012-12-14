<?php

class Ext_IdentityMap
{
    /**
     * @var array[array]
     */
    static private $_identityMap = array();

    /**
     * @param string $_type
     * @param integer|string $_id
     * @return object|false
     */
    public static function get($_type, $_id)
    {
        if (
            isset(self::$_identityMap[$_type]) &&
            isset(self::$_identityMap[$_type][$_id])
        ) {
            return self::$_identityMap[$_type][$_id];

        } else {
            return false;
        }
    }

    /**
     * @param string $_type
     * @param string $_param
     * @param string $_value
     * @return object|false
     */
    static function getByParam($_type, $_param, $_value)
    {
        if (!empty(self::$_identityMap[$_type])) {
            $check = reset(self::$_identityMap[$_type]);

            if (isset($check[$_param])) {
                $value = mb_strtolower($_value);

                foreach (self::$_identityMap[$_type] as $id => $row) {
                    if (mb_strtolower($row[$_param]) == $value) {
                        return self::$_identityMap[$_type][$id];
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string $_type
     * @param integer|string $_id
     * @param object $_object
     * @return void
     */
    public static function save($_type, $_id, $_object)
    {
        if (!isset(self::$_identityMap[$_type])) {
            self::$_identityMap[$_type] = array();
        }

        self::$_identityMap[$_type][$_id] = $_object;
    }

    /**
     * @param string $_type
     * @param integer|string $_id
     * @return void
     */
    public static function delete($_type, $_id)
    {
        if (
            isset(self::$_identityMap[$_type]) &&
            isset(self::$_identityMap[$_type][$_id])
        ) {
            unset(self::$_identityMap[$_type][$_id]);
        }
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return array_keys(self::$_identityMap);
    }

    /**
     * @param string $_type
     * @return array
     */
    public static function getKeys($_type)
    {
        return isset(self::$_identityMap[$_type])
             ? array_keys(self::$_identityMap[$_type])
             : false;
    }
}
