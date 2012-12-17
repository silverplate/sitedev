<?php

class Ext_Dom
{
    /**
     * @param DOMNode $_parent
     * @param string $_name
     * @return DOMNode
     */
    public static function getChildByName($_parent, $_name)
    {
        return $_parent->getElementsByTagName($_name)->item(0);
    }

    /**
     * @param DOMNode $_node
     */
    public static function remove($_node)
    {
        return $_node->parentNode->removeChild($_node);
    }

    /**
     * @param string $_xml
     * @param string $_rootNode
     * @return DOMDocument
     */
    public static function get($_xml, $_rootNode = 'root')
    {
        return DOMApp_Cms_Document::loadXML(Ext_Xml::node($_rootNode, $_xml));
    }

    /**
     * @param DOMDocument|DOMNode $_source
     * @param boolean $_doFormat
     * @throws Exception
     * @return string
     */
    public static function getXml($_source, $_doFormat = false)
    {
        $source = $_source;
        $source->formatOtput = $_doFormat;

        if ($source instanceof DOMDocument) {
            return $source->saveXML();

        } else if ($source instanceof DOMNode) {
            return $source->ownerDocument->saveXML($source);
        }

        throw new Exception('Incompatible source type. DOMDocument or DOMNode is expected.');
    }

    /**
     * @param DOMDocument|DOMNode $_source
     * @param boolean $_doFormat
     * @throws Exception
     * @return string
     */
    public static function getInnerXml($_source, $_doFormat = false)
    {
        if ($_source instanceof DOMDocument) {
            return self::getXml($_source->documentElement, $_doFormat);

        } else if ($_source instanceof DOMNode) {
            $xml = array();

            foreach ($_source->childNodes as $child) {
                $xml[] = self::getXml($child, $_doFormat);
            }

            return implode($_doFormat ? PHP_EOL : '', $xml);
        }

        throw new Exception('Incompatible source type. DOMDocument or DOMNode is expected.');
    }
}
