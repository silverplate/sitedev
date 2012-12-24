<?php

abstract class Core_Cms_Bo_Section extends App_ActiveRecord
{
	private static $Base;
	protected $_links = array('users' => null);
	const TABLE = 'bo_section';

	public function getName()
	{
	    return Ext_File::normalizeName($this->getTitle());
	}

	public static function CheckUnique($_value, $_exclude = null) {
		return self::IsUnique(get_called_class(), self::GetTbl(), self::GetPri(), 'uri', $_value, $_exclude);
	}

	public static function compute() {
		global $g_section_start_url;

		$url = parse_url($_SERVER['REQUEST_URI']);
		$path = explode('/', trim(str_replace($g_section_start_url, '', $url['path']), '/'));

		$entry = App_Db::Get()->GetEntry('SELECT ' . implode(',', self::GetBase()->getAttrNames()) . ' FROM ' . self::GetTbl() . ' WHERE uri = ' . App_Db::escape($path[0]));
		if ($entry) {
			$class = get_called_class();
			$obj = new $class;
			$obj->fillWithData($entry);
			return $obj;
		}

		return false;
	}

	public function GetUri() {
		return '/cms/' . $this->uri . '/';
	}

	public function getXml($_type, $_node_name = null, $_append_xml = null, $_append_attributes = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(get_called_class());
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->isPublished) $result .= ' is_published="true"';

				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;

			case 'bo_navigation':
				$result .= '<' . $node_name;

				$attributes = array_merge(array('id' => $this->GetId(), 'uri' => $this->GetUri()));

				if ($_append_attributes) $attributes = array_merge($attributes, $_append_attributes);

				foreach ($attributes as $name => $value) {
					if ($value) {
						$result .= ' ' . $name . '="' . $value . '"';
					}
				}
				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';
				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function GetLinks($_name, $_is_published = null) {
		if (!$this->_links[$_name]) {
			$conditions = array(self::GetPri() => $this->GetId());
			if (!is_null($_is_published)) $conditions['is_published'] = $_is_published;

			switch ($_name) {
				case 'users':
					$this->_links[$_name] = App_Cms_Bo_UserToSection::getList($conditions);
					break;
			}
		}

		return $this->_links[$_name];
	}

	public function GetLinkIds($_name, $_is_published = null) {
		$result = array();

		switch ($_name) {
			case 'users':
				$keys = array(App_Cms_Bo_UserToSection::GetFirstKey(), App_Cms_Bo_UserToSection::GetSecondKey());
				break;
		}

		$key = self::GetPri() == $keys[0] ? $keys[1] : $keys[0];
		$links = $this->GetLinks($_name, $_is_published);

		if ($links) {
			foreach ($links as $item) {
				if ($item->$key) {
					$result[] = $item->$key;
				}
			}
		}

		return $result;
	}

	public function SetLinks($_name, $_value = null) {
		$this->_links[$_name] = array();

		switch ($_name) {
			case 'users':
				$class_name = 'App_Cms_Bo_UserToSection';
				$keys = array(App_Cms_Bo_UserToSection::GetFirstKey(), App_Cms_Bo_UserToSection::GetSecondKey());
				break;
		}

		if (is_array($_value)) {
			$key = $this->GetPri() == $keys[0] ? $keys[1] : $keys[0];

			foreach ($_value as $id => $item) {
				$obj = new $class_name;
				$obj->setAttrValue($this->getPri(), $this->getId());

				if (is_array($item)) {
					$obj->$key = $id;

					foreach ($item as $attribute => $value) {
						$obj->$attribute = $value;
					}

				} else {
				    $obj->$key = $item;
				}

				$this->_links[$_name][] = $obj;
			}
		}
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->_attributes as $item) {
			$this->_attributes[$item->GetName()] = clone($item);
		}
	}

	public static function GetBase() {
		if (!isset(self::$Base)) {
			self::$Base = new App_ActiveRecord(self::ComputeTblName());
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 10, true);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('uri', 'varchar', 255);
			self::$Base->AddAttribute('description', 'text');
			self::$Base->AddAttribute('sort_order', 'int');
			self::$Base->AddAttribute('is_published', 'boolean');
		}

		return self::$Base;
	}

	public static function GetPri($_is_table = false) {
		return self::GetBase()->GetPrimary($_is_table);
	}

	public static function GetTbl() {
		return self::GetBase()->GetTable();
	}

	public static function load($_value, $_attribute = null) {
		return parent::load(get_called_class(), $_value, $_attribute);
	}

	public static function getList($_attributes = array(), $_parameters = array()) {
		return parent::getList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->getAttrNames(),
			$_attributes,
			$_parameters
		);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
