<?php

function dom_get_child($_parent_node, $_child_name) {
	return $_parent_node->getElementsByTagName($_child_name)->item(0);
}

function dom_remove($_node) {
	$_node->parentNode->removeChild($_node);
}

/**
 * @param string $_source
 * @param string $_file
 * @return DOMDocument
 */
function dom_get_obj($_source = null, $_file = null) {
// 	$result = new DOMDocument('1.0', 'utf-8');
// 	$result->resolveExternals = true;
// 	$result->substituteEntities = true;
//
// 	if ($_source) {
// 		$result->loadXML($_source, DOM_LOAD_OPTIONS);
//
// 	} else if (is_file($_file)) {
// 		$result->load($_file, DOM_LOAD_OPTIONS);
// 	}
//
// 	return $result;

    if (!empty($_source)) {
        return getXmlObject($_source);

    } else if (!empty($_file)) {
        return loadXmlObject($_file);
    }

    return false;
}

function getXmlHeader($_root)
{
    return '<?xml version="1.0" encoding="utf-8"?>' .
           "\r\n" .
           '<!DOCTYPE ' . $_root . ' SYSTEM "' . TEMPLATES . 'character_entities.dtd">' .
           "\r\n";
}

function getXmlDocument($_xml, $_root = null)
{
    $root = empty($_root) ? 'root' : $_root;
    return getXmlHeader($root) . "<$root>$_xml</$root>";
}

function getXmlDocumentForRoot($_xml, $_root)
{
    $root = empty($_root) ? 'root' : $_root;
    return getXmlHeader($root) . $_xml;
}

/**
 * @param string $_xml
 * @param string $_root
 * @return DomDocument
 */
function getXmlObject($_xml, $_root = null)
{
    return DomDocument::loadXml(getXmlDocument($_xml, $_root),
                                DOM_LOAD_OPTIONS);
}

/**
 * @param string $_xml
 * @param string $_root
 * @return DomDocument
 */
function getXmlObjectForRoot($_xml, $_root)
{
    return DomDocument::loadXml(getXmlDocumentForRoot($_xml, $_root),
                                DOM_LOAD_OPTIONS);
}

function loadXmlObject($_path)
{
    return DomDocument::load($_path, DOM_LOAD_OPTIONS);
}

function get_cdata($_text) {
	$result = str_replace('<![CDATA[', '&lt;![CDATA[', $_text);
	$result = str_replace(']]>', ']]&gt;', $result);
	return '<![CDATA[' . $result . ']]>';
}

function get_cdata_back($_text) {
	$result = str_replace('&lt;![CDATA[', '<![CDATA[', $_text);
	$result = str_replace(']]&gt;', ']]>', $result);
	return $result;
}

function normalizeXmlName($_name)
{
    return str_replace('_', '-', transformCaseToUnderline($_name));
}

function getNode($_name, $_value = null, array $_attrs = array())
{
    $nodeName = normalizeXmlName($_name);
    $xml = '<' . $nodeName;

    foreach ($_attrs as $name => $value) {
        $xml .= ' ' . normalizeXmlName($name) . '="' . $value . '"';
    }

    if (is_null($_value) || $_value == '') {
        $xml .= ' />';
    } else {
        $xml .= '>' . $_value . '</' . $nodeName . '>';
    }

    return $xml;
}

function getNoEmptyNode($_name, $_value = null, array $_attrs = array())
{
    return empty($_value) && empty($_attrs) ? '' : getNode($_name, $_value, $_attrs);
}

function getCdata($_name, $_cdata = null, array $_attrs = array())
{
    return getNode($_name,
                   empty($_cdata) ? null : '<![CDATA[' . $_cdata . ']]>',
                   $_attrs);
}

function getNoEmptyCdata($_name, $_cdata = null, array $_attrs = array())
{
    return empty($_cdata) && empty($_attrs) ? '' : getCdata($_name, $_cdata, $_attrs);
}

function getNumberNode($_name, $_number, $_attrs = null, $_decimals = null)
{
    return getNumberXml($_name, $_number, $_attrs, $_decimals);
}

function getNumberXml($_name, $_number, $_attrs = null, $_decimals = null)
{
    $value = getNumber($_number);
    $attrs = empty($_attrs) ? array() : $_attrs;
    $attrs['value'] = $value;
    $attrs['comma-value'] = str_replace('.', ',', $value);

    return getCdata($_name, format_number(abs($_number), $_decimals), $attrs);
}

function replaceEntitiesWithSymbols($_content)
{
    $matches = array();
    $content = $_content;
    preg_match_all('/&[0-9a-zA-Z]+;/', $content, $matches);

    if ($matches) {
        $xml =  '<entity>' . implode('</entity><entity>', $matches[0]) . '</entity>';
        $dom = DomDocument::loadXml(getXmlDocument($xml), DOM_LOAD_OPTIONS + LIBXML_NOERROR);
        $entities = $dom->getElementsByTagName('entity');

        for ($i = 0; $i < $entities->length; $i++) {
            $value = $entities->item($i)->nodeValue;

            if ($value) {
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    return $content;
}
