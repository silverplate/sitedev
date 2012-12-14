<?php

abstract class Core_ActiveRecord_Attribute
{
    private $Name;
    private $Type;
    private $Length;
    private $Value;
    private $IsPrimary;
    private $IsUnique;

    public function __construct($_name, $_type, $_length = null, $_is_primary = null, $_is_unique = null, $_value = null) {
        $this->Name = $_name;
        $this->SetType($_type);
        $this->Length = $_length;
        $this->IsPrimary = ($_is_primary);
        $this->IsUnique = ($_is_unique);

        if ($_value) {
            $this->SetValue($_value);
        }
    }

    public function SetType($_type) {
        switch ($_type) {
            case 'int':
            case 'integer':
                $this->Type = 'integer';
                break;
            case 'float':
                $this->Type = 'float';
                break;
            case 'bool':
            case 'boolean':
                $this->Type = 'boolean';
                break;
            case 'char':
            case 'varchar':
                $this->Type = 'varchar';
                break;
            default:
                $this->Type = $_type;
        }
    }

    public function GetType() {
        return $this->Type;
    }

    public function GetLength() {
        return $this->Length;
    }

    public function SetValue($_value) {
        if ($_value === 'NULL') {
            $this->Value = $_value;
            return;
        }

        switch ($this->Type) {
            case 'int':
            case 'integer':
                $this->Value = (int) $_value;
                break;

            case 'float':
                $this->Value = (float) str_replace(',', '.', $_value);
                break;

            case 'bool':
            case 'boolean':
                $this->Value = ($_value) ? 1 : 0;
                break;

            case 'char':
            case 'varchar':
            case 'string':
            default:
                $this->Value = $_value;
                break;
        }
    }

    public function GetValue($_is_escape = true) {
        if ($this->Value == 'NULL') {
            return $this->Value;

        } else if (
            $this->Value == '' &&
            (in_array($this->Type, array('date', 'datetime')))
        ) {
            return 'NULL';

        } else if ($this->Value == '' && $_is_escape) {
            return '\'\'';

        } else {
            switch ($this->Type) {
                case 'int':
                case 'integer':
                case 'bool':
                case 'boolean':
                    return $this->Value;

                case 'float':
                    return str_replace(',', '.', $this->Value);

                case 'char':
                case 'varchar':
                case 'string':
                default:
                    return ($_is_escape) ? Db::escape($this->Value) : $this->Value;
            }
        }
    }

    public function IsValue() {
        return !((string) $this->Value == '');
    }

    public function GetName() {
        return $this->Name;
    }

    public function IsPrimary() {
        return ($this->IsPrimary);
    }

    public function IsUnique() {
        return ($this->IsUnique);
    }
}
