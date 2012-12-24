<?php

abstract class Core_Cms_Document extends App_ActiveRecord
{
	private static $Base;
	private $IsChildren;
	private $Controller;
	private $_template;
	private $Language;
	protected $_links = array('navigations' => null);

	const TABLE = 'fo_document';

//	protected $Files;
//	protected $Images;
	protected $files;
	protected $images;

	public function GetFilePath() {
		return rtrim(DOCUMENT_ROOT . 'f/' . ltrim($this->uri, '/'), '/') . '/';
	}

	public function UploadFile($_name, $_tmp_name) {
		if ($_name && $_tmp_name) {
			$name = Ext_File::normalizeName($_name);
			Ext_File::createDir($this->getFilePath());
			move_uploaded_file($_tmp_name, $this->GetFilePath() . $name);
			@chmod($this->GetFilePath() . $name, 0777);
		}
	}

	public function getXml($_type, $_node_name = null, $_append_xml = null, $_append_attributes = null) {
		$node_name = ($_node_name) ? $_node_name : strtolower(get_called_class());
		$result = '';

		switch ($_type) {
			case 'bo_list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->isPublished) $result .= ' is_published="true"';
				if (
				    IS_USERS &&
				    $this->authStatusId != App_Cms_User::AUTH_GROUP_ALL &&
				    App_Cms_User::GetAuthGroupTitle($this->authStatusId)
			    ) {
					$result .= ' prefix="' . Ext_String::toLower(substr(App_Cms_User::GetAuthGroupTitle($this->authStatusId), 0, 1)) . '"';
				}

				if ($_append_attributes) {
					foreach ($_append_attributes as $name => $value) {
						$result .= ' ' . $name . '="' . $value . '"';
					}
				}

				$result .= '>';

				$result .= Ext_Xml::cdata(
			        'title',
			        $this->titleCompact ? $this->titleCompact : $this->getTitle()
		        );

				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;

			case 'list':
				$result .= '<' . $node_name . ' id="' . $this->GetId() . '"';
				if ($this->isPublished) $result .= ' is_published="true"';

				if ($_append_attributes) {
					foreach ($_append_attributes as $name => $value) {
						$result .= ' ' . $name . '="' . $value . '"';
					}
				}

				$result .= '><title><![CDATA[' . $this->GetTitle() . ']]></title>';

				if ($this->titleCompact) {
					$result .= '<title_compact><![CDATA[' . $this->titleCompact . ']]></title_compact>';
				}

				$result .= $_append_xml;
				$result .= '</' . $node_name . '>';
				break;
		}

		return $result;
	}

	public function GetLang() {
		global $g_langs;

		if (is_null($this->Language)) {
			$this->Language = '';
			if (isset($g_langs) && $g_langs) {
				foreach (array_keys($g_langs) as $i) {
					$pos = strpos($this->uri, '/' . $i . '/');
					if ($pos !== false && $pos == 0) {
						$this->Language = $i;
						break;
					}
				}
			}
		}

		return $this->Language;
	}

	public function GetUrl() {
		global $g_langs;

		if ($this->GetLang()) {
			return 'http://' . $g_langs[$this->GetLang()][0] . $this->GetUri();
		} else {
			return $this->GetUri();
		}
	}

	public function GetUri() {
		if ($this->GetLang()) {
			return substr($this->uri, strlen($this->GetLang()) + 2 - 1);
		} else {
			return $this->uri;
		}
	}

	private function computeUri($_parentUri = null)
	{
		if (!is_null($_parentUri)) {
			$uri = $_parentUri;

		} else if ($this->parentId && $this->parentId !== 'NULL') {
			$parent = self::load($this->parentId);

			if ($parent) {
			    $uri = $parent->uri;
			}
		}

		if (!isset($uri)) {
		    $uri = '/';
		}

        $folder = $this->folder;

		if ($folder != '/') {
			$uri .= $folder;

			if (strpos($folder, '.') === false) {
			    $uri .= '/';
			}
		}

		$this->uri = $uri;
	}

	public static function UpdateChildrenUri($_id = null) {
		$id = '';
		$uri = '';

		if (!is_null($_id)) {
			$obj = self::load($_id);

			if ($obj) {
				$id = $_id;
				$uri = $obj->uri;

			} else {
				return false;
			}
		}

		$list = self::getList(array('parent_id' => $id));
        foreach ($list as $item) {
            $folder = $item->folder;
            if ($folder != '/' && strpos($folder, '.') === false) {
                $folder .= '/';
            }

            $item->updateAttribute('uri', $uri . $folder);
            self::updateChildrenUri($item->getId());
        }
	}

	public function create() {
		$this->ComputeUri();
		return parent::create();
	}

	public function Update() {
		$path = $this->GetFilePath();
		$this->ComputeUri();

		if ($path != $this->GetFilePath() && is_dir($path)) {
		    Ext_File::moveDir($path, $this->getFilePath());
		}

		parent::Update();
		self::UpdateChildrenUri($this->GetId());
	}

	public function delete() {
		foreach (self::getList(array('parent_id' => $this->GetId())) as $item) {
			$item->Delete();
		}

		foreach (App_Cms_Document_Data::getList(array(self::GetPri() => $this->GetId())) as $item) {
			$item->Delete();
		}

		Ext_File::deleteDir($this->getFilePath());
		parent::delete();
	}

	public function IsChildren($_except_id = null) {
		if (is_null($this->IsChildren)) {
			$list = self::getList(array('parent_id' => $this->GetId()));

			if (is_null($_except_id)) {
				$this->IsChildren = ($list);

			} elseif ($list) {
				$this->IsChildren = false;

				foreach ($list as $item) {
					if ($item->GetId() != $_except_id) {
						$this->IsChildren = true;
						break;
					}
				}

			} else {
				$this->IsChildren = false;
			}
		}

		return $this->IsChildren;
	}

	public static function GetMultiAncestors($_ids) {
		$result = array();
		foreach ($_ids as $id) {
			if (!in_array($id, $result)) {
				$result = array_merge($result, self::GetAncestors($id));
			}
		}
		return $result;
	}

	public static function GetAncestors($_id) {
		$result = array();
		$entry = App_Db::Get()->GetEntry('SELECT ' . self::GetPri() . ', parent_id FROM ' . self::GetTbl() . ' WHERE ' . self::GetPri() . ' = ' . App_Db::escape($_id));
		if ($entry) {
			$result[] = $entry[self::GetPri()];
			if ($entry['parent_id']) $result = array_merge($result, self::GetAncestors($entry['parent_id']));
		}
		return $result;
	}

	public function GetController() {
		if (is_null($this->Controller)) {
			$this->Controller = $this->foControllerId
			                  ? App_Cms_Controller::load($this->foControllerId)
			                  : false;
		}

		return $this->Controller;
	}

	public function getTemplate()
	{
		if (is_null($this->_template)) {
		    $id = $this->foTemplateId;
		    $this->_template = $id ? App_Cms_Template::getById($id) : false;
		}

		return $this->_template;
	}

	public function GetControllerFile() {
		return $this->GetController() ? $this->GetController()->GetFilename() : false;
	}

	public static function initController(App_Cms_Controller $_controller, &$_document)
	{
        require_once $_controller->getFilename();

        $class = $_controller->getClassName();

        if (class_exists($class)) {
            return new $class($_document);
        }

		return false;
	}

	public static function GetQueryConditions($_conditions = array()) {
		$self = array('table' => self::GetTbl(), 'pk' => self::GetPri());
		$self['pk_attr'] = $self['table'] . '.' . $self['pk'];

		$result = array('tables' => array($self['table']), 'row_conditions' => array());

		if (isset($_conditions['navigations'])) {
			if ($_conditions['navigations']) {
				$result['tables'][] = App_Cms_Document_ToNavigation::GetTbl();
				$result['row_conditions'][] = $self['pk_attr'] . ' = ' . App_Cms_Document_ToNavigation::GetTbl() . '.' . $self['pk'];
				$result['row_conditions'][] = App_Cms_Document_ToNavigation::GetTbl() . '.' . App_Cms_Document_Navigation::GetPri() . (is_array($_conditions['navigations']) ? ' IN (' . App_Db::escape($_conditions['navigations']) . ')' : ' = ' . App_Db::escape($_conditions['navigations']));

				if (isset($_conditions['is_published'])) {
					$result['tables'][] = App_Cms_Document_Navigation::GetTbl();
					$result['row_conditions'][] = App_Cms_Document_ToNavigation::GetTbl() . '.' . App_Cms_Document_Navigation::GetPri() . ' = ' . App_Cms_Document_Navigation::GetPri(true);
					$result['row_conditions'][] = App_Cms_Document_Navigation::GetTbl() . '.is_published = ' . App_Db::escape($_conditions['is_published']);
				}
			}

			unset($_conditions['navigations']);
		}

		if ($_conditions) {
		    $conditions = array();

		    foreach ($_conditions as $name => $value) {
		        $conditions[$self['table'] . '.' . $name] = $value;
		    }

		    $result['row_conditions'] = array_merge(
	            $result['row_conditions'],
	            App_Db::get()->getWhere($conditions)
            );
		}

		return $result;
	}

	public static function getList($_attributes = array(), $_parameters = array(), $_row_conditions = array()) {
		$conditions = self::GetQueryConditions($_attributes);

		if ($_row_conditions) {
			$conditions['row_conditions'] = array_merge(
		        $conditions['row_conditions'],
		        $_row_conditions
	        );
		}

		return parent::getList(
			get_called_class(),
			$conditions['tables'],
			self::GetBase()->getAttrNames(true),
			null,
			$_parameters,
			$conditions['row_conditions']
		);
	}

	public static function CheckUnique($_parent_id, $_folder, $_except_id = null) {
		$row_conditions = array();
		if ($_except_id) $row_conditions[] = self::GetPri() . ' != ' . App_Db::escape($_except_id);
		return !(self::getList(array('parent_id' => $_parent_id, 'folder' => $_folder), array('count' => 1), $row_conditions));
	}

	public function GetLinks($_name, $_is_published = null) {
		if (!$this->_links[$_name]) {
			$conditions = array(self::GetPri() => $this->GetId());
			if (!is_null($_is_published)) $conditions['is_published'] = $_is_published;

			switch ($_name) {
				case 'navigations':
					$this->_links[$_name] = App_Cms_Document_ToNavigation::getList($conditions);
					break;
			}
		}

		return $this->_links[$_name];
	}

	public function GetLinkIds($_name, $_is_published = null) {
		$result = array();

		switch ($_name) {
			case 'navigations':
				$keys = array(App_Cms_Document_ToNavigation::GetFirstKey(), App_Cms_Document_ToNavigation::GetSecondKey());
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
			case 'navigations':
				$class_name = 'App_Cms_Document_ToNavigation';
				$keys = array(App_Cms_Document_ToNavigation::GetFirstKey(), App_Cms_Document_ToNavigation::GetSecondKey());
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
			self::$Base->AddAttribute(self::ComputeTblName() . '_id', 'varchar', 30, true);
			self::$Base->AddForeignKey(App_Cms_Controller::GetBase());
			self::$Base->addForeignKey(App_Cms_TemplateDb::getBase());
			self::$Base->AddAttribute('parent_id', 'varchar', 30);
			self::$Base->AddAttribute('auth_status_id', 'int');
			self::$Base->AddAttribute('title', 'varchar', 255);
			self::$Base->AddAttribute('title_compact', 'varchar', 255);
			self::$Base->AddAttribute('folder', 'varchar', 255);
			self::$Base->AddAttribute('link', 'varchar', 255);
			self::$Base->AddAttribute('uri', 'varchar', 255);
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

	public static function ComputeTblName()  {
		return DB_PREFIX . self::TABLE;
	}

	public static function load($_value, $_attribute = null) {
		return parent::load(get_called_class(), $_value, $_attribute);
	}
}

?>
