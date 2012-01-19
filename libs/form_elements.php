<?php

class FormEle implements FormEleInterface {
	protected $Name;
	protected $Type;
	protected $Label;
	protected $Description;
	protected $Value;
	protected $IsRequired;
	protected $Options = array();
	protected $OptionGroups = array();

	protected $AdditionalXml;
	protected $DbValueType;
	protected $UpdateType;
	protected $ErrorValue;
	protected $_errorMessage;

	public function __construct($_name, $_type, $_label = null, $_is_required = false) {
		$this->Name = $_name;
		$this->Type = $_type;
		$this->Label = $_label;
		$this->SetRequired($_is_required);
		$this->UpdateType = FIELD_NO_UPDATE;
	}

	public function SetName($_value) {
		$this->Name = $_value;
	}

	public function GetName() {
		return $this->Name;
	}

	public function GetType() {
		return $this->Type;
	}

	public function SetRequired($_is_required) {
		$this->IsRequired = ($_is_required);
	}

	public function GetValue() {
		return $this->Value;
	}

	public function SetValue() {
		if (func_num_args() == 1) {
			$this->Value = get_cdata_back(func_get_arg(0));
		} elseif (func_num_args() == 2) {
			if (!is_array($this->Value)) $this->Value = array();
			$this->Value[func_get_arg(0)] = get_cdata_back(func_get_arg(1));
		} else {
			$this->Value = '';
		}
	}

	public function SetErrorValue() {
		if (func_num_args() == 1) {
			$this->ErrorValue = func_get_arg(0);
		} elseif (func_num_args() == 2) {
			if (!is_array($this->ErrorValue)) $this->ErrorValue = array();
			$this->ErrorValue[func_get_arg(0)] = func_get_arg(1);
		}
	}

	public function AddOptionGroup($_label) {
		array_push($this->OptionGroups, $_label);
		return count($this->OptionGroups) - 1;
	}

	public function AddOption($_value, $_label, $_group = null) {
		if (is_null($_group)) {
			array_push($this->Options, array('value' => $_value, 'label' => $_label));
		} else {
			if (!isset($this->Options[$_group])) {
				$this->Options[$_group] = array();
			}
			array_push($this->Options[$_group], array('value' => $_value, 'label' => $_label));
		}
	}

	public function RemoveOption($_value) {
		$options = array();
		for ($i = 0; $i < count($this->Options); $i++) {
			if ($this->Options[$i]['value'] != $_value) {
				array_push($options, $this->Options[$i]);
			}
		}
		$this->Options = $options;
	}

	public function GetXml() {
		$xml = '<element name="' . $this->Name . '" type="' . $this->Type . '" update_type="'. $this->UpdateType . '"';

		if ($this->IsRequired) {
			$xml .= ' is_required="true"';
		}

		$xml .= '>';

		if ($this->Label) {
			$xml .= '<label>' . get_cdata($this->Label) . '</label>';
		}

		if ($this->Value != '') {
			$xml .= '<value>';
			if (is_array($this->Value)) {
				foreach ($this->Value as $key => $value) {
				    if ($value != 'NULL') {
                        $xml .= preg_match('/^[a-z_]+$/', $key)
                            ? '<' . $key . '>' . get_cdata($value) . '</' . $key . '>'
                            : '<item key="' . $key . '">' . get_cdata($value) . '</item>';
                    }
				}
			} else {
				$xml .= get_cdata($this->Value);
			}
			$xml .= '</value>';
		}

// 		if ('' != $this->GetDescription() || 'adding_files' == $this->GetType()) {
		if ($this->getDescription() != '') {
			$xml .= '<description><![CDATA[';

// 			if ('' != $this->GetDescription()) {
				$xml .= $this->getDescription();
// 			}
//
// 			if ('adding_files' == $this->GetType()) {
// 				if ('' != $this->GetDescription()) {
// 					$xml .= ' ';
// 				}
//
// 				$xml .=
// 					'Суммарный размер за&nbsp;один раз загружаемых файлов не&nbsp;должен превышать ' .
// 					get_max_upload_size() .
// 					'&nbsp;МБ.';
// 			}

			$xml .= ']]></description>';
		}

		if ($this->Options) {
			$xml .= '<options>';

			if ($this->OptionGroups && isset($this->Options[0]) && is_array($this->Options[0])) {
				foreach ($this->OptionGroups as $group_id => $group_title) {
					if (isset($this->Options[$group_id]) && $this->Options[$group_id]) {
						$xml .= '<group><title><![CDATA[' . $group_title . ']]></title>';
						foreach ($this->Options[$group_id] as $option) {
							$xml .= '<item value="' . $option['value'] . '"><![CDATA[' . $option['label'] . ']]></item>';
						}
						$xml .= '</group>';
					}
				}
			} else {
				foreach ($this->Options as $option) {
					$xml .= '<item value="' . $option['value'] . '"><![CDATA[' . $option['label'] . ']]></item>';
				}
			}

			$xml .= '</options>';
		}

		if ($this->ErrorValue) {
			$xml .= '<error><value>';
			if (is_array($this->ErrorValue)) {
				foreach ($this->ErrorValue as $key => $value) {
					$element = is_numeric($key) ? 'item' : $key;
					$xml .= "<{$element}><![CDATA[{$value}]]></{$element}>";
				}
			} else {
				$xml .= '<![CDATA[' . $this->ErrorValue . ']]>';
			}
			$xml .= '</value></error>';
		}

        if ($this->_errorMessage) {
            $xml .= '<error-message><![CDATA[' .
                    $this->_errorMessage .
                    ']]></error-message>';
        }

		if ($this->GetAdditionalXml()) {
			$xml .= '<additional>' . $this->GetAdditionalXml() . '</additional>';
		}

		return $xml . '</element>';
	}

	public function getDescription()
	{
		return $this->Description;
	}

	public function setDescription($_value)
	{
		$this->Description = $_value;
	}

    public function addDescription($_value)
    {
        $value = $this->getDescription();
        if ($value) $value .= ' ';
        $value .= $_value;

        $this->setDescription($value);
    }

	public function IsUpdateSuccess() {
		return in_array($this->UpdateType, array(FIELD_NO_UPDATE, FIELD_SUCCESS));
	}

	public function IsUpdateError() {
		return !$this->IsUpdateSuccess();
	}

	public function GetUpdateType($_data) {
		$value = $this->ComputeValue($_data);

		if ($value === false) {
			$this->UpdateType = $this->CheckValue();
		} else {
			$this->UpdateType = $this->CheckValue($value);

			if ($this->IsUpdateSuccess()) $this->SetValue($value);
			else $this->SetErrorValue($value);
		}

		return $this->UpdateType;
	}

	public function SetUpdateType($_type) {
		$this->UpdateType = $_type;
	}

	public function getErrorMessage()
	{
	    return $this->_errorMessage;
	}

	public function setErrorMessage($_message)
	{
	    $this->_errorMessage = $_message;
	}

	public function GetAdditionalXml() {
		return $this->AdditionalXml;
	}

	public function SetAdditionalXml($_value) {
		$this->AdditionalXml = $_value;
	}

	public function AddAdditionalXml($_value) {
		$this->AdditionalXml .= $_value;
	}

	public function ComputeValue($_data) {
		if (isset($_data[$this->Name])) {
			return $_data[$this->Name];
		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			return array ($this->Name => $this->GetValue());
		} else {
			return false;
		}
	}
}

class FormEleName extends FormEle {
	public function ComputeValue($_data) {
		$value = array();

		if (isset($_data['first_name']) || isset($_data['last_name'])) {
			if (isset($_data['first_name'])) {
				$value['first_name'] = $_data['first_name'];
			}

			if (isset($_data['last_name'])) {
				$value['last_name'] = $_data['last_name'];
			}

			if (isset($_data['patronymic_name'])) {
				$value['patronymic_name'] = $_data['patronymic_name'];
			}

			return $value;

		} elseif (isset($_data[$this->Name . '_first_name']) || isset($_data[$this->Name . '_last_name'])) {
			if (isset($_data[$this->Name . '_first_name'])) {
				$value['first_name'] = $_data[$this->Name . '_first_name'];
			}

			if (isset($_data[$this->Name . '_last_name'])) {
				$value['last_name'] = $_data[$this->Name . '_last_name'];
			}

			if (isset($_data[$this->Name . '_patronymic_name'])) {
				$value['patronymic_name'] = $_data[$this->Name . '_patronymic_name'];
			}

			return $value;

		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && !(isset($_value['first_name']) && $_value['first_name'] && isset($_value['last_name']) && $_value['last_name'])) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!isset($_value['first_name']) && !isset($_value['last_name'])) {
			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$result = array();
			$value = $this->GetValue();

			if (isset($value['first_name'])) {
				$result['first_name'] = $value['first_name'];
			}

			if (isset($value['last_name'])) {
				$result['last_name'] = $value['last_name'];
			}

			if (isset($value['patronymic_name'])) {
				$result['patronymic_name'] = $value['patronymic_name'];
			}

			return $result;
		}

		return false;
	}
}

class FormEleEmail extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && !preg_match('/^[0-9a-zA-Z_][0-9a-zA-Z_.-]*[0-9a-zA-Z_-]@([0-9a-zA-Z][0-9a-zA-Z-]*\.)+[a-zA-Z]{2,4}$/', $_value)) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleBoolean extends FormEle {
	public function ComputeValue($_data) {
		return (isset($_data[$this->Name]) && $_data[$this->Name]) ? 1 : 0;
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value != '1')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			return array($this->Name => ($this->GetValue()) ? '1' : '0');
		}
		return false;
	}
}

class FormEleFolder extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && !preg_match('/^[a-zA-Z_0-9\-]+$/', $_value) && $_value != '/') {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleFilename extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && !preg_match('/^[a-zA-Z_0-9\-\.]+$/', $_value) && $_value != '/') {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleUri extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && (!preg_match('/^[a-zA-Z_0-9\-\/]+$/', $_value) || strpos($_value, '//') !== false)) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleDate extends FormEle {
	public function ComputeValue($_data) {
		$value = array();

		if (!empty($_data[$this->Name]) && $_data[$this->Name] != '0000-00-00') {
			$date = strtotime($_data[$this->Name]);
			$value = array('day' => date('d', $date), 'month' => date('m', $date), 'year' => date('Y', $date));

			return $value;

		} elseif (isset($_data[$this->Name . '_day']) || isset($_data[$this->Name . '_month']) || isset($_data[$this->Name . '_year'])) {
			foreach (array('year', 'month', 'day') as $i) {
				if (isset($_data[$this->Name . '_' . $i])) {
					$value[$i] = $_data[$this->Name . '_' . $i];
				}
			}

			return $value;

		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		$is_value = true;

		foreach (array('year', 'month', 'day') as $i) {
			if (!(isset($_value[$i]) && $_value[$i])) {
				$is_value = false;
				break;
			}
		}

		if ($this->IsRequired && !$is_value) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!$is_value) {
			return FIELD_NO_UPDATE;

		} elseif (!checkdate((int) $_value['month'], (int) $_value['day'], (int) $_value['year'])) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
			if ($value) {
				$result = array($this->Name => '');

				foreach (array('year', 'month', 'day') as $i) {
					$result[$this->Name] .= ($result[$this->Name] ? '-' : '') . $value[$i];
				}

				return $result;
			}
		}
		return false;
	}
}

class FormEleDateTime extends FormEle {
	public function ComputeValue($_data) {
		$value = array();

		if (
		    !empty($_data[$this->Name]) &&
		    $_data[$this->Name] != '0000-00-00 00:00:00'
		) {
            $date = is_numeric($_data[$this->Name])
                  ? $_data[$this->Name]
                  : strtotime($_data[$this->Name]);

			$value = array('day' => date('d', $date),
			               'month' => date('m', $date),
			               'year' => date('Y', $date),
			               'hours' => date('H', $date),
			               'minutes' => date('i', $date));

			return $value;

		} else if (
		    isset($_data[$this->Name . '_day']) ||
		    isset($_data[$this->Name . '_month']) ||
		    isset($_data[$this->Name . '_year']) ||
		    isset($_data[$this->Name . '_hours']) ||
		    isset($_data[$this->Name . '_minutes'])
		) {
			foreach (array('year', 'month', 'day', 'hours', 'minutes') as $i) {
				if (isset($_data[$this->Name . '_' . $i])) {
					$value[$i] = $_data[$this->Name . '_' . $i];
				}
			}

			return $value;

		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		$is_value = true;

// 		foreach (array('year', 'month', 'day', 'hours', 'minutes') as $i) {
		foreach (array('year', 'month', 'day') as $i) {
            if (empty($_value[$i])) {
				$is_value = false;
				break;
			}
		}

		if ($this->IsRequired && !$is_value) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!$is_value) {
			return FIELD_NO_UPDATE;

		} elseif (!checkdate((int) $_value['month'], (int) $_value['day'], (int) $_value['year']) || (int) $_value['hours'] > 23 || (int) $_value['minutes'] > 59) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
			if ($value) {
				$result = array($this->Name => '');

				foreach (array('year', 'month', 'day') as $i) {
					$result[$this->Name] .= ($result[$this->Name] ? '-' : '') . $value[$i];
				}

				foreach (array('hours', 'minutes') as $i) {
					$result[$this->Name] .= ($i == 'hours' ? ' ' : ':') . $value[$i];
				}

				return $result;
			}
		}
		return false;
	}
}

class FormEleDatePeriod extends FormEle {
	public function ComputeValue($_data) {
	    $value = array('from' => null, 'till' => null);

        if (
            !empty($_data[$this->Name . '_from']) &&
            $_data[$this->Name . '_from'] != '0000-00-00'
        ) {
            $value['from'] = $_data[$this->Name . '_from'];
        }

        if (
            !empty($_data[$this->Name . '_till']) &&
            $_data[$this->Name . '_till'] != '0000-00-00'
        ) {
            $value['till'] = $_data[$this->Name . '_till'];
        }

        return count($value) > 0 ? $value : false;
	}

	public function CheckValue($_value = null) {
		if (
		    $this->IsRequired &&
		    (empty($_value['from']) || empty($_value['till']))
		) {
			return FIELD_ERROR_REQUIRED;

// 		} else if (
// 		    !key_exists('from', $_value) &&
// 		    !key_exists('till', $_value)
// 		) {
// 			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();

			return array(
			    $this->Name . '_from' => empty($value['from']) ? 'NULL' : $value['from'],
			    $this->Name . '_till' => empty($value['till']) ? 'NULL' : $value['till']
			);
		}

		return false;
	}
}

class FormEleDatetimePeriod extends FormEle {
	public function __construct($_name, $_type, $_label = null, $_is_required = false) {
		parent::__construct($_name, $_type, $_label, $_is_required);

		$hours_xml = '<hours>';
		for ($i = 0; $i < 24; $i++) {
			$value = sprintf('%02d', $i);
			$hours_xml .= '<item value="' . $value . '">' . $i . '</item>';
		}
		$hours_xml .= '</hours>';

		$minutes_xml = '<minutes>';
		for ($i = 0; $i < 60; $i = $i + 10) {
			$value = sprintf('%02d', $i);
			$minutes_xml .= '<item value="' . $value . '">' . $i . '</item>';
		}
		$minutes_xml .= '</minutes>';

		$this->AddAdditionalXml($hours_xml . $minutes_xml);
	}

	public function ComputeValue($_data) {
        $result = array('from' => null,
                        'from_hours' => null,
                        'from_minutes' => null,
                        'till' => null,
                        'till_hours' => null,
                        'till_minutes' => null);
		if (
            !empty($_data[$this->Name . '_from']) &&
		    $_data[$this->Name . '_from'] != '0000-00-00'
		) {
            $from = is_numeric($_data[$this->Name . '_from'])
                  ? $_data[$this->Name . '_from']
                  : strtotime($_data[$this->Name . '_from']);

            $result['from'] = date('Y-m-d', $from);
            $result['from_hours'] = date('H', $from);
            $result['from_minutes'] = date('i', $from);
		}

		if (
            !empty($_data[$this->Name . '_till']) &&
		    $_data[$this->Name . '_till'] != '0000-00-00'
		) {
            $till = is_numeric($_data[$this->Name . '_till'])
                  ? $_data[$this->Name . '_till']
                  : strtotime($_data[$this->Name . '_till']);

            $result['till'] = date('Y-m-d', $till);
            $result['till_hours'] = date('H', $till);
            $result['till_minutes'] = date('i', $till);
		}

        foreach (
            array('from_hours', 'from_minutes', 'till_hours', 'till_minutes')
            as $i
        ) {
            $name = $this->Name . '_' . $i;

            if (isset($_data[$name]) && (int) $_data[$name]) {
                $result[$i] = sprintf('%02d', $_data[$name]);
            }
        }

        return $result;
	}

	public function CheckValue($_value = null) {
	    if ($_value) {
            $is_value = true;
//             foreach (array('from', 'from_hours', 'from_minutes', 'till', 'till_hours', 'till_minutes') as $i) {
            foreach (array('from', 'till') as $i) {
                if (empty($_value[$i])) {
                    $is_value = false;
                    break;
                }
            }

	    } else {
	        $is_value = false;
	    }

		if ($this->IsRequired && !$is_value) {
			return FIELD_ERROR_REQUIRED;

// 		} elseif (!$is_value) {
// 			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();

            return array(
                $this->Name . '_from' => empty($value['from']) ? 'NULL' : "{$value['from']} {$value['from_hours']}:{$value['from_minutes']}:00",
                $this->Name . '_till' => empty($value['till']) ? 'NULL' : "{$value['till']} {$value['till_hours']}:{$value['till_minutes']}:00"
            );
		}

		return false;
	}
}

class FormEleImage extends FormEle {
	public function SetImage($_path) {
		$this->SetValue($this->ComputeValue($_path));
	}

	public function ComputeValue($_data) {
		$value = array();

		if (is_array($_data) && isset($_data[$this->Name]) && $_data[$this->Name] && is_array($_data[$this->Name]) && $_data[$this->Name]['name']) {
			$value = array(
				'name' => $_data[$this->Name]['name'],
				'tmp_name' => $_data[$this->Name]['tmp_name']
			);

		} elseif ((is_array($_data) && isset($_data[$this->Name]) && is_string($_data[$this->Name]) && $_data[$this->Name] && is_file($_data[$this->Name])) || (is_string($_data) && $_data && is_file($_data))) {
			$filepath = is_array($_data) ? $_data[$this->Name] : $_data;
			$size = getimagesize($filepath);
			if ($size) {
				$value = array(
					'path' => $filepath,
					'url' => str_replace(DOCUMENT_ROOT, '/', $filepath),
					'width' => $size[0],
					'height' => $size[1],
					'type' => $size[2],
					'size' => format_number(filesize($filepath) / 1024, 2)
				);
			}
		}

		return $value;
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && (!$_value || !isset($_value['name']) || !isset($_value['tmp_name']) || !$_value['name'] || !$_value['tmp_name'])) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!($_value && isset($_value['name']) && isset($_value['tmp_name']) && $_value['name'] && $_value['tmp_name'])) {
			return FIELD_NO_UPDATE;

		} elseif (!in_array(strtolower(get_file_extension($_value['name'])), array('jpg', 'gif', 'png')) || !getimagesize($_value['tmp_name'])) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
			if ($value) {
				if (is_array($value) && isset($value['name']) && $value['name']) {
					return array($this->Name => $value['name']);

				} elseif (is_file($value)) {
					return array($this->Name => basename($value));
				}
			}
		}

		return false;
	}
}

class FormEleAddingFiles extends FormEle {
	public function ComputeValue($_data) {
		$value = array();
		$data = is_array($_data) && isset($_data[$this->Name]) ? $_data[$this->Name] : $_data;

		if (is_array($data)) {
			if (isset($data['name']) && isset($data['tmp_name'])) {
				if (is_array($data['name']) && is_array($data['tmp_name'])) {
					for ($i = 0; $i < count($data['name']); $i++) {
						if (isset($data['name'][$i]) && $data['name'][$i] && isset($data['tmp_name'][$i]) && $data['tmp_name'][$i]) {
							array_push($value, array(
								'name' => $data['name'][$i],
								'tmp_name' => $data['tmp_name'][$i]
							));
						}
					}

				} elseif ($data['name'] && $data['tmp_name']) {
					array_push($value, array(
						'name' => $data['name'],
						'tmp_name' => $data['tmp_name']
					));
				}
			}

		} elseif ($data && is_file($data)) {
			$value = array(
				'path' => $data,
				'url' => str_replace(DOCUMENT_ROOT, '/', $data),
				'size' => format_number(filesize($data) / 1024, 2)
			);
		}

		return $value;
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && !$_value) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!$_value) {
			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
			if ($value) {
				if (is_array($value)) {
					if (isset($value['path'])) {
						return array($this->Name => translit(basename($value['path'])));

					} else {
						$result = array();
						foreach ($value as $file) {
							if (isset($file['name'])) {
								array_push($result, translit($file['name']));
							}
						}
						if ($result) {
							return array($this->Name => implode(', ', $result));
						}
					}

				} elseif ($value) {
					return array($this->Name => $value);
				}
			}
		}

		return false;
	}
}

class FormEleYear extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && ((int) $_value < 1901 || (int) $_value > 2155)) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleInteger extends FormEle {
	public function CheckValue($_value = null) {
		if ($this->IsRequired && (is_null($_value) || $_value == '')) {
			return FIELD_ERROR_REQUIRED;

		} elseif (is_null($_value)) {
			return FIELD_NO_UPDATE;

		} elseif ($_value != '' && !preg_match('/^\-?[0-9]+$/', $_value)) {
			return FIELD_ERROR_SPELLING;

		} else {
			return FIELD_SUCCESS;
		}
	}
}

class FormEleMultiple extends FormEle {
	public function ComputeValue($_data) {
		if (isset($_data[$this->Name])) {
			if (is_array($_data[$this->Name])) {
				$value = array();
				foreach ($_data[$this->Name] as $item) array_push($value, $item);
				return $value;
			} else {
				return array($_data[$this->Name]);
			}
		} else {
			return array();
		}
	}

	public function CheckValue($_value = null) {
		return $this->IsRequired && (!$_value || !is_array($_value))
			? FIELD_ERROR_REQUIRED
			: FIELD_SUCCESS;
	}

	public function GetSqlValue() {
		return $this->UpdateType == FIELD_SUCCESS
			? array($this->Name => implode(', ', $this->GetValue()))
			: false;
	}
}

class FormElePhone extends FormEle {
	public function ComputeValue($_data) {
		$value = array();

		if (isset($_data[$this->Name])) {
			$value['number'] = $_data[$this->Name];

			if (isset($_data[$this->Name . '_code'])) {
				$value['code'] = $_data[$this->Name . '_code'];
			}

			return $value;

		} elseif (isset($_data[$this->Name . '_number'])) {
			$value['number'] = $_data[$this->Name . '_number'];

			if (isset($_data[$this->Name . '_code'])) {
				$value['code'] = $_data[$this->Name . '_code'];
			}

			return $value;

		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && !(isset($_value['number']) && $_value['number'])) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!isset($_value['number'])) {
			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$result = array();
			$value = $this->GetValue();

			if (isset($value['number'])) {
				$result[$this->Name] = $value['number'];
			}

			if (isset($value['code'])) {
				$result[$this->Name . '_code'] = $value['code'];
			}

			return $result;
		}

		return false;
	}
}

class FormElePassword extends FormEle {
	public function ComputeValue($_data) {
		$value = array();

		if (isset($_data[$this->Name]) && $_data[$this->Name] != '') {
			$value['password'] = $_data[$this->Name];

			if (isset($_data[$this->Name . '_check'])) {
				$value['check'] = $_data[$this->Name . '_check'];
			}

			return $value;

		} else {
			return false;
		}
	}

	public function CheckValue($_value = null) {
		if ($this->IsRequired && (!(isset($_value['password']) && $_value['password']) || !(isset($_value['check']) && $_value['check']))) {
			return FIELD_ERROR_REQUIRED;

		} elseif (!isset($_value['password']) && !isset($_value['check'])) {
			return FIELD_NO_UPDATE;

		} elseif (isset($_value['password']) && isset($_value['check']) && $_value['password'] == $_value['check']) {
			return FIELD_SUCCESS;

		} else {
			return FIELD_ERROR_SPELLING;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
			if ($value) {
				return array($this->Name => $value['password']);
			}
		}

		return false;
	}
}

class FormEleCalendarDatetime extends FormEle {
	public function __construct($_name, $_type, $_label = null, $_is_required = false) {
		parent::__construct($_name, $_type, $_label, $_is_required);

		$hours_xml  = '<hours><item value="00">0</item>';
		for ($i = 6; $i < 24; $i++) {
			$value = sprintf('%02d', $i);
			$hours_xml .= '<item value="' . $value . '">' . $i . '</item>';
		}
		$hours_xml .= '</hours>';

		$minutes_xml = '<minutes>';
		for ($i = 0; $i < 60; $i = $i + 10) {
			$value = sprintf('%02d', $i);
			$minutes_xml .= '<item value="' . $value . '">' . $i . '</item>';
		}
		$minutes_xml .= '</minutes>';

		$this->AddAdditionalXml($hours_xml . $minutes_xml);
	}

	public function ComputeValue($_data) {
        $result = array('date' => null,
                        'hours' => null,
                        'minutes' => null);
		if (
		    !empty($_data[$this->Name]) &&
		    $_data[$this->Name] != '0000-00-00 00:00:00'
		) {
            $date = is_numeric($_data[$this->Name])
                  ? $_data[$this->Name]
                  : strtotime($_data[$this->Name]);

			$result = array(
				'date' => date('Y-m-d', $date),
				'hours' => date('H', $date),
				'minutes' => date('i', $date)
			);

			foreach (array('hours', 'minutes') as $i) {
				$name = $this->Name . '_' . $i;
				if (isset($_data[$name]) && (int) $_data[$name]) {
					$result[$i] = sprintf('%02d', $_data[$name]);
				}
			}
		}

		return $result;
	}

	public function CheckValue($_value = null) {
	    if ($_value) {
		    $is_value = true;

// 		    foreach (array('date', 'hours', 'minutes') as $i) {
		    foreach (array('date') as $i) {
                if (empty($_value[$i])) {
				    $is_value = false;
    				break;
	    		}
		    }

		} else {
    		$is_value = false;
		}

		if ($this->IsRequired && !$is_value) {
			return FIELD_ERROR_REQUIRED;

// 		} else if (!$is_value) {
// 			return FIELD_NO_UPDATE;

		} else {
			return FIELD_SUCCESS;
		}
	}

	public function GetSqlValue() {
		if ($this->UpdateType == FIELD_SUCCESS) {
			$value = $this->GetValue();
            return array($this->Name => empty($value['date'])
                                      ? 'NULL'
                                      : "{$value['date']} {$value['hours']}:{$value['minutes']}:00");
		}

		return false;
	}
}


class FormEleLogin extends FormEleFilename {}
class FormEleMultipleTree extends FormEleMultiple {}
class FormEleChooser extends FormEle {}
class FormEleSingleTree extends FormEleChooser {}
class FormEleText extends FormEle {}
class FormEleShortText extends FormEleText {}
class FormEleLargeText extends FormEleText {}


interface FormEleInterface {
	public function ComputeValue($_data);
	public function CheckValue($_value = null);
	public function GetSqlValue();
}


define('FIELD_NO_UPDATE', 'no_update');
define('FIELD_SUCCESS', 'success');
define('FIELD_ERROR', 'error');
define('FIELD_ERROR_SPELLING', 'error_spelling');
define('FIELD_ERROR_REQUIRED', 'error_required');
define('FIELD_ERROR_EXIST', 'error_exist');


class FormButton {
	protected $Name;
	protected $Label;
	protected $ImageUrl;

	public function __construct($_name, $_label, $_image_url = null) {
		$this->Name = $_name;
		$this->Label = $_label;
		$this->ImageUrl = $_image_url;
	}

	public function GetXml() {
		$xml = '<button name="' . $this->Name . '"';

		if ($this->IsSubmited()) {
			$xml .= ' is_submited="true"';
		}

		if ($this->ImageUrl) {
			$xml .= ' image_url="' . $this->ImageUrl . '"';
		}

		$xml .= '><label><![CDATA[' . $this->Label . ']]></label></button>';
		return $xml;
	}

	public function IsSubmited() {
		global $_POST;

		return (isset($_POST[$this->Name]) || (isset($_POST[$this->Name . '_x']) && isset($_POST[$this->Name . '_y'])));
	}
}


class FormGroup {
	private $Name;
	private $Title;
	private $Elements = array();
	private $IsSelected;
	private $AdditionalXml;

	public function __construct($_name, $_title, $_is_selected = false) {
		$this->Name = $_name;
		$this->Title = $_title;
		$this->SetSelected($_is_selected);
	}

	public function SetSelected($_is_selected) {
		$this->IsSelected = ($_is_selected);
	}

	public function GetElements() {
		return $this->Elements;
	}

	public function SetElements(&$_elements) {
		$this->Elements = $_elements;
	}

	public function AddElement(&$_element) {
		array_push($this->Elements, $_element);
	}

	public function SetAdditionalXml($_value) {
		$this->AdditionalXml = $_value;
	}

	public function AddAdditionalXml($_value) {
		$this->AdditionalXml .= $_value;
	}

	public function GetXml() {
		$result = '<group';
		if ($this->Name) $result .= ' name="' . $this->Name . '"';
		if ($this->IsSelected) $result .= ' is_selected="true"';
		$result .= '>';
		if ($this->Title) $result .= '<title><![CDATA[' . $this->Title . ']]></title>';
		if ($this->AdditionalXml) $result .= '<additional>' . $this->AdditionalXml . '</additional>';
		foreach ($this->Elements as $i) $result .= $i->GetXml();
		$result .= '</group>';
		return $result;
	}
}

?>
