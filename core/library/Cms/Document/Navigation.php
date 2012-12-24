<?php

abstract class Core_Cms_Document_Navigation extends App_ActiveRecord
{
	private static $Base;
	private static $NavItems = array();
	protected $_links = array('documents' => null);

	const TABLE = 'fo_navigation';

	public static function GetTypes() {
		return array(
			'list' => array('title' => 'Список'),
			'tree' => array('title' => 'Дерево')
		);
	}

    public static function getRowDocuments($_name)
    {
        global $g_langs;

		$list = App_Db::get()->getList('
			SELECT
			    d.' . App_Cms_Document::getPri() . ' AS id,
				d.*
			FROM
				' . App_Cms_Document_Navigation::getTbl() . ' AS n,
				' . App_Cms_Document::getTbl() . ' AS d,
				' . App_Cms_Document_ToNavigation::getTbl() . ' AS l
			WHERE
			    n.is_published = 1 AND
				n.name = ' . App_Db::escape($_name) . ' AND
				n.' . App_Cms_Document_Navigation::getPri() . ' = l.' . App_Cms_Document_Navigation::getPri() . ' AND
				l.' . App_Cms_Document::getPri() . ' = d.' . App_Cms_Document::getPri() . ' AND
				d.is_published = 1' .
				(is_null(App_Cms_User::getAuthGroup()) ? '' : ' AND (d.auth_status_id = 0 OR d.auth_status_id & ' . App_Cms_User::getAuthGroup() . ')') . '
			ORDER BY
				d.sort_order
		');

		if ($list && !empty($g_langs)) {
			for ($i = 0; $i < count($list); $i++) {
				foreach (array_keys($g_langs) as $j) {
					$pos = strpos($list[$i]['uri'], '/' . $j . '/');
					if (0 === $pos) {
						$list[$i]['lang'] = $j;

						if (
							'host' == SITE_LANG_TYPE ||
							0 != strpos($list[$i]['uri'], '/' . $j . '/')
						) {
							$list[$i]['uri'] = substr($list[$i]['uri'], strlen($j) + 2 - 1);
						}

						break;
					}
				}
			}
		}

        return $list;
    }

    public static function getDocuments($_name)
    {
        $documents = array();
        $data = self::getRowDocuments($_name);

        foreach ($data as $row) {
            $document = new App_Cms_Document();
            $document->dataInit($row);
            $documents[$document->getId()] = $document;
        }

        return $documents;
    }

	public static function getNavigationXml($_name, $_type) {
		self::$NavItems = self::getRowDocuments($_name);

		$result = $_type == 'tree'
		        ? self::GetNavigationXmlTree()
		        : self::GetNavigationXmlList();

        return $result ? Ext_Xml::node($_name, $result) : false;
	}

	public function GetNavigationXmlTree($_parent_id = '') {
		$result = '';
		$keys = array_keys(self::$NavItems);
		foreach ($keys as $key) {
			if (isset(self::$NavItems[$key])) {
				$item = self::$NavItems[$key];
				if ($item['parent_id'] == $_parent_id) {
					unset(self::$NavItems[$key]);
					$result .= '<item uri="' . $item['uri'] . '" link="' . ($item['link'] ? $item['link'] : $item['uri']) . '"';
					if (isset($item['lang'])) $result .= ' xml:lang="' . $item['lang'] . '"';
					$result .= '><title><![CDATA[' . ($item['title_compact'] ? $item['title_compact'] : $item['title']) . ']]></title>';
					$result .= self::GetNavigationXmlTree($item['id']);
					$result .= '</item>';
				}
			}
		}
		return $result;
	}

	public function GetNavigationXmlList() {
		$result = '';

		foreach (self::$NavItems as $item) {
			$result .= '<item uri="' . $item['uri'] . '" link="' . ($item['link'] ? $item['link'] : $item['uri']) . '"';
			if (isset($item['lang'])) $result .= ' xml:lang="' . $item['lang'] . '"';
			$result .= '><title><![CDATA[' . ($item['title_compact'] ? $item['title_compact'] : $item['title']) . ']]></title>';
			$result .= '</item>';
		}

		return $result;
	}

	public function getXml($_type, $_node_name = null, $_append_xml = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(get_called_class());
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() .'"';
				if ($this->getAttribute('is_published') == 1) $result .= ' is_published="true"';

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
				case 'documents':
					$this->_links[$_name] = App_Cms_Document_ToNavigation::getList($conditions);
					break;
			}
		}

		return $this->_links[$_name];
	}

	public function GetLinkIds($_name, $_is_published = null) {
		$result = array();

		switch ($_name) {
			case 'documents':
				$keys = array(App_Cms_Document_ToNavigation::GetFirstKey(), App_Cms_Document_ToNavigation::GetSecondKey());
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
		$this->_links[$_name] = array();

		switch ($_name) {
			case 'documents':
				$class_name = 'DocumentToNavigation';
				$keys = array(App_Cms_Document_ToNavigation::GetFirstKey(), App_Cms_Document_ToNavigation::GetSecondKey());
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

				array_push($this->_links[$_name], $obj);
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
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 30, true);
			self::$Base->AddAttribute('name', 'varchar', 255);
			self::$Base->AddAttribute('type', 'varchar', 255);
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('is_published', 'boolean');
			self::$Base->AddAttribute('sort_order', 'int');
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

	public static function getList($_attributes = array(), $_parameters = array(), $_rowConditions = array()) {
		return parent::getList(
			get_called_class(),
			self::GetTbl(),
			self::GetBase()->GetAttributes(),
			$_attributes,
			$_parameters,
			$_rowConditions
		);
	}

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}
}

?>
