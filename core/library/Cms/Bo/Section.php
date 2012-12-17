<?php

abstract class Core_Cms_Bo_Section extends App_ActiveRecord
{
	private static $Base;
	protected $Links = array('users' => null);
	const TABLE = 'bo_section';

	public function GetName() {
		return strtolower(str_replace(' ', '_', translit($this->GetTitle())));
	}

	public static function CheckUnique($_value, $_exclude = null) {
		return self::IsUnique(get_called_class(), self::GetTbl(), self::GetPri(), 'uri', $_value, $_exclude);
	}

	public static function compute() {
		global $g_section_start_url;

		$url = parse_url($_SERVER['REQUEST_URI']);
		$path = explode('/', trim(str_replace($g_section_start_url, '', $url['path']), '/'));

		$entry = App_Db::Get()->GetEntry('SELECT ' . implode(',', self::GetBase()->GetAttributes()) . ' FROM ' . self::GetTbl() . ' WHERE uri = ' . App_Db::escape($path[0]));
		if ($entry) {
			$class = get_called_class();
			$obj = new $class;
			$obj->DataInit($entry);
			return $obj;
		}

		return false;
	}

	public function GetUri() {
		return '/cms/' . $this->GetAttribute('uri') . '/';
	}

	public function GetXml($_type, $_node_name = null, $_append_xml = null, $_append_attributes = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(get_called_class());
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->GetAttribute('is_published') == 1) $result .= ' is_published="true"';

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
		if (!$this->Links[$_name]) {
			$conditions = array(self::GetPri() => $this->GetId());
			if (!is_null($_is_published)) $conditions['is_published'] = $_is_published;

			switch ($_name) {
				case 'users':
					$this->Links[$_name] = App_Cms_Bo_UserToSection::GetList($conditions);
					break;
			}
		}

		return $this->Links[$_name];
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
				if ($item->GetAttribute($key)) {
					array_push($result, $item->GetAttribute($key));
				}
			}
		}

		return $result;
	}

	public function SetLinks($_name, $_value = null) {
		$this->Links[$_name] = array();

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
				$obj->SetAttribute($this->GetPri(), $this->GetId());

				if (is_array($item)) {
					$obj->SetAttribute($key, $id);
					foreach ($item as $attribute => $value) {
						$obj->SetAttribute($attribute, $value);
					}

				} else {
					$obj->SetAttribute($key, $item);
				}

				array_push($this->Links[$_name], $obj);
			}
		}
	}

	public function __construct() {
		parent::__construct(self::GetTbl());
		foreach (self::GetBase()->Attributes as $item) {
			$this->Attributes[$item->GetName()] = clone($item);
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

	public static function Load($_value, $_attribute = null) {
		return parent::Load(get_called_class(), $_value, $_attribute);
	}

	public static function GetList($_attributes = array(), $_parameters = array()) {
		return parent::GetList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters
		);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
