<?php

define('FORM_NO_UPDATE', 'no_update');
define('FORM_UPDATED', 'updated');
define('FORM_ERROR', 'error');

define('FORM_GET', 'GET');
define('FORM_POST', 'POST');

class Form {
	public $NodeName;
	public $UpdateStatus;
	public $ResultMessage;
	public $Method;
	public $Groups = array();
	public $Elements = array();
	public $Buttons = array();

	public function __construct($_node_name = 'form') {
		$this->SetNodeName($_node_name);
		$this->UpdateStatus = FORM_NO_UPDATE;
		$this->Method = FORM_POST;
	}

	public function SetNodeName($_value) {
		$this->NodeName = $_value;
	}

	public function GetNodeName() {
		return $this->NodeName;
	}

	public function CreateGroup($_name, $_title) {
		$this->Groups[$_name] = new FormGroup($_name, $_title);
		$this->Groups[$_name]->SetSelected(isset($_COOKIE['form_group']) && $_COOKIE['form_group'] == $_name);

		return $this->Groups[$_name];
	}

	public function CreateElement($_name, $_type, $_label = null, $_is_required = false) {
		$class_name = 'FormEle';
		foreach (explode('_', $_type) as $word) {
			$class_name .= ucfirst($word);
		}

		if (!class_exists($class_name)) {
			$class_name= 'FormEle';
		}

		if (class_exists($class_name)) {
			$this->Elements[$_name] = new $class_name($_name, $_type, $_label, $_is_required);
			return $this->Elements[$_name];
		} else {
			return false;
		}
	}

	public function CreateButton($_label, $_name = null, $_image_url = null) {
		$name = $_name ? $_name : 'submit';
		$this->Buttons[$_name] = new FormButton($name, $_label, $_image_url);
		return $this->Buttons[$_name];
	}

	public function Load($_xml_file = null, $_xml_source = null) {
		if ($_xml_file && is_file($_xml_file)) {
			$form = loadXmlObject($_xml_file);

		} else if ($_xml_source) {
			$form = getXmlObject($_xml_source);
		}

		if (isset($form)) {
			$dom_xpath = new DOMXPath($form);

			foreach ($dom_xpath->evaluate('/node()') as $root_node) {
				if (strtoupper($root_node->getAttribute('method')) == 'GET') {
					$this->Method = FORM_GET;
				}
			}

			$groups = $dom_xpath->evaluate('group[@name and title/text()]');

			if ($groups && $groups->length > 0) {
				foreach ($groups as $group) {
					$group_name = $group->getAttribute('name');
					$label_node = dom_get_child($group, 'title');
					$this->CreateGroup($group_name, $label_node->nodeValue);

					foreach ($dom_xpath->evaluate('element', $group) as $item) {
						$this->LoadElement($item, $group_name);
					}
				}

			} else {
				foreach ($dom_xpath->evaluate('element') as $item) {
					$this->LoadElement($item);
				}
			}

			foreach ($dom_xpath->evaluate('button[label/text()]') as $item) {
				$label_node = dom_get_child($item, 'label');
				$this->CreateButton($label_node->firstChild->nodeValue, $item->getAttribute('name'), $item->getAttribute('image_url'));
			}

			return true;

		} else {
			return false;
		}
	}

	protected function LoadElement(&$_item, $_group_name = null) {
		if ($_item->hasAttribute('name') && $_item->hasAttribute('type')) {
			$label_node = dom_get_child($_item, 'label');
			$label = $label_node && $label_node->nodeValue ? $label_node->nodeValue : null;

			$element = $this->CreateElement($_item->getAttribute('name'), $_item->getAttribute('type'), $label, $_item->hasAttribute('is_required'));

			if ($element) {
				if ($_group_name) {
					$this->Groups[$_group_name]->AddElement($element);
				}

				$description_node = dom_get_child($_item, 'description');
				if ($description_node) {
					$element->SetDescription($description_node->nodeValue);
				}

				$options_node = dom_get_child($_item, 'options');
				if ($options_node) {
					foreach ($options_node->getElementsByTagName('item') as $option) {
						$element->AddOption($option->getAttribute('value'), $option->nodeValue);
					}

					if ($options_node->hasAttribute('dynamic_value_type')) {
						Form::InsertDynamicValues($element, $options_node->getAttribute('dynamic_value_type'));
					}
				}

				$value_node = dom_get_child($_item, 'value');
				if ($value_node) {
					if ($value_node->firstChild->nodeType == XML_ELEMENT_NODE || $value_node->childNodes->length > 1) {
						foreach ($value_node->childNodes as $value) {
							if ($value->nodeType == XML_ELEMENT_NODE) {
								$element->SetValue($value->nodeName, $value->nodeValue);
							}
						}

					} elseif ($value_node->firstChild->nodeValue) {
						$element->SetValue($value_node->firstChild->nodeValue);
					}

				} elseif ($_item->hasAttribute('init_value_type')) {
					switch ($_item->getAttribute('init_value_type')) {
						case 'tomorrow':
							$date = strtotime('+1 day');
							break;

						default:
						case 'now':
							$date = mktime();
							break;
					}

					switch ($element->GetType()) {
						case 'calendar':
							$element->SetValue(date('Y-m-d', $date));
							break;

						case 'date_period':
							$element->SetValue('from', date('Y-m-d', $date));
							$element->SetValue('till', date('Y-m-d', $date));
							break;

						case 'datetime_period':
							$element->SetValue('from', date('Y-m-d', $date));
							$element->SetValue('from_hours', '00');
							$element->SetValue('from_minutes', '00');
							$element->SetValue('till', date('Y-m-d', $date));
							$element->SetValue('till_hours', '00');
							$element->SetValue('till_minutes', '00');
							break;

						case 'year':
							$element->SetValue(date('Y', $date));
							break;

						case 'date':
							$element->SetValue('day', date('d', $date));
							$element->SetValue('month', date('m', $date));
							$element->SetValue('year', date('Y', $date));
							break;

						case 'datetime':
							$element->SetValue('day', date('d', $date));
							$element->SetValue('month', date('m', $date));
							$element->SetValue('year', date('Y', $date));
							$element->SetValue('hours', date('H', $date));
							$element->SetValue('minutes', date('i', $date));
							break;
					}
				}
			}
		}
	}

	public function GetXml() {
		$xml = '<'. $this->NodeName;
		if ($this->UpdateStatus) $xml .= ' status="' . $this->UpdateStatus . '"';
		$xml .= ' method="' . $this->Method . '"';
		$xml .= '>';

		if ($this->ResultMessage) {
			$xml .= '<result_message><![CDATA[' . $this->ResultMessage . ']]></result_message>';
		}

		if ($this->Groups) {
			foreach ($this->Groups as $item) $xml .= $item->GetXml();
		} else {
			foreach ($this->Elements as $item) $xml .= $item->GetXml();
		}

		foreach ($this->Buttons as $item) {
			$xml .= $item->GetXml();
		}

		return $xml . '</' . $this->NodeName . '>';
	}

	public function Execute() {
		$is_submited = false;

		foreach ($this->Buttons as $button) {
			if ($button->IsSubmited()) {
				$is_submited = true;
				break;
			}
		}

		if ($is_submited) {
			$is_error = false;

			foreach ($this->Elements as $name => $item) {
				if (
				    $item->GetType() == 'image' ||
				    $item->GetType() == 'adding_files'
				) {
					$item->GetUpdateType($_FILES);

				} else if ($this->Method == FORM_GET) {
					$item->GetUpdateType($_GET);

				} else {
					$item->GetUpdateType($_POST);
				}

				if ($item->IsUpdateError()) {
				    $is_error = true;
				}
			}

			$this->UpdateStatus = $is_error ? FORM_ERROR : FORM_UPDATED;

		} else {
			$this->UpdateStatus = FORM_NO_UPDATE;
		}
	}

	public function FillFields($_data) {
		foreach ($this->Elements as $name => $item) {
			$value = $item->ComputeValue($_data);
			if (is_array($value)) {
				foreach(array_keys($value) as $k)
				{
				  $value[$k]=strtr($value[$k], chr(11)," ");
				}
			}
			else {
				$value = strtr($value, chr(11)," ");
			}

			if ($value !== false) {
				$item->SetValue($value);
			}
		}
	}

	public function GetSqlValues() {
		$result = array();

		foreach ($this->Elements as $item) {
			if ($item->GetSqlValue()) {
				$result = array_merge($result, $item->GetSqlValue());
			}
		}

		return $result;
	}

	public function UploadImages($_upload_dir, $_file_name_type = 'real') {
		if ($_upload_dir) {
			$uploaded = array();
			$upload_dir = rtrim($_upload_dir, '/') . '/';
			foreach ($this->Elements as $item) {
				if ($item->GetType() == 'image') {
					$value = $item->GetValue();
					$is_image = (isset($_POST[$item->GetName() . '_present']) && $_POST[$item->GetName() . '_present'] && is_file($_POST[$item->GetName() . '_present']));
					$is_delete = (isset($_POST[$item->GetName() . '_delete']) && $_POST[$item->GetName() . '_delete']);
					$is_upload = ($value && isset($value['name']) && $value['name'] && isset($value['tmp_name']) && $value['tmp_name']);

					if ($is_image && ($is_delete || $is_upload)) {
						unlink($_POST[$item->GetName() . '_present']);
					}

					if ($is_upload) {
						create_directory($upload_dir, true);

						switch ($_file_name_type) {
							case 'field':
								$file_name = $item->GetName() . '.' . strtolower(get_file_extension($value['name']));
								break;

							case 'real':
							default:
								$file_name = $value['name'];
								break;
						}

						move_uploaded_file($value['tmp_name'], $upload_dir . $file_name);
						chmod($upload_dir . $file_name, 0777);
						$uploaded[$item->GetName()] = $upload_dir . $file_name;
					}

					if (is_directory_empty($upload_dir)) rmdir($upload_dir);
				}
			}
			return $uploaded;
		}

		return false;
	}

/*
	public function UploadFiles($_upload_dir) {
		if ($_upload_dir) {
			$uploaded = array();
			$upload_dir = rtrim($_upload_dir, '/') .'/';
			foreach ($this->Elements as $item) {
				if ($item->GetType() == 'adding_files') {
					$value = $item->GetValue();

					if ($value && is_array($value) && isset($value[0])) {
						foreach ($value as $file) {
							if (isset($file['name']) && $file['name'] && isset($file['tmp_name']) && $file['tmp_name']) {
								create_directory($upload_dir, true);
								$file_name = translit($file['name']);

								move_uploaded_file($file['tmp_name'], $upload_dir . $file_name);
								chmod($upload_dir . $file_name, 0777);
								$uploaded[$item->GetName()] = $upload_dir . $file_name;
							}
						}
					}
					if (is_directory_empty($upload_dir)) rmdir($upload_dir);
				}
			}
			return $uploaded;
		}

		return false;
	}
*/

	public static function InsertDynamicValues(&$_element, $_type) {
		switch ($_type) {
/*
			case 'auth_groups':
				$list = AuthGroup::GetList(array('status_id' => '1'));
				break;
*/
		}

		if (isset($list) && is_array($list)) {
			foreach ($list as $item) {
				if (is_object($item)) {
					$_element->AddOption($item->GetId(), $item->GetTitle());
				} else {
					$_element->AddOption($item, $item);
				}
			}
		}
	}
}

?>
