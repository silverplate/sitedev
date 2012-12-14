<?php

class Ext_Xml
{
    public static function normalize($_name)
    {
        return Ext_String::dash($_name);
    }

    public static function node($_name, $_value = null, array $_attrs = null)
    {
        $name = self::normalize($_name);
        $xml = '<' . $name;

        if ($_attrs) {
            foreach ($_attrs as $key => $value) {
                $xml .= ' ' . self::normalize($key) . '="' . $value . '"';
            }
        }

        if (empty($_value)) {
            $xml .= ' />';

        } else if (is_array($_value)) {
            $xml .= '>' . implode('', self::removeControlCharacters($_value)) . "</$name>";

        } else {
            $xml .= '>' . self::removeControlCharacters($_value) . "</$name>";
        }

        return $xml;
    }

    public static function notEmptyNode($_name, $_value = null, array $_attrs = null)
    {
        return empty($_value) && empty($_attrs)
             ? ''
             : self::node($_name, $_value, $_attrs);
    }

    public static function cdata($_name, $_cdata = null, array $_attrs = null)
    {
        return self::node(
            $_name,
            is_null($_cdata) || $_cdata == '' ? null : "<![CDATA[$_cdata]]>",
            $_attrs
        );
    }

    public static function notEmptyCdata($_name, $_value = null, array $_attrs = null)
    {
        return empty($_value) && empty($_attrs)
             ? ''
             : self::cdata($_name, $_value, $_attrs);
    }

    public static function number($_name, $_number)
    {
        return self::cdata($_name,
                           Ext_Number::format(abs($_number)),
                           array('value' => $_number));
    }

    /**
     * @param string|array $_container
     * @param string|array $_xml
     */
    public static function append(&$_container, $_xml)
    {
        if ($_xml) {
            if (is_array($_container)) {
                if (is_array($_xml)) $_container = array_merge($_container, $_xml);
                else                 $_container[] = $_xml;

            } else {
                $_container .= is_array($_xml) ? implode($_xml) : $_xml;
            }
        }
    }

    /**
     * Удаление неотображаемых символов (ASCII control characters),
     * используемых в MS Word, которые ломают XML.
     *
     * @link http://www.danshort.com/ASCIImap/indexhex.htm
     * @param string $_source
     * @return string
     */
    public static function removeControlCharacters($_source)
    {
        // Кроме x09
        return preg_replace("/[\x{7F}\x{00}-\x{08}\x{0A}-\x{1F}]/", '', $_source);
    }

    /**
     * @param string $_xml
     * @return string
     */
    public static function format($_xml)
    {
        return Ext_Dom::getInnerXml(Ext_Dom::get($_xml)->documentElement, true);
    }
}
