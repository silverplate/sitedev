<?php

function d($_var)
{
    return debug($_var);
}

function debug($_var)
{
    if (PHP_SAPI == 'cli') {
        var_dump($_var);

    } else {
        echo '<pre>';
        if (is_string($_var))   echo htmlspecialchars($_var);
        else                    var_dump($_var);
    }

	die();
}

function format_number($_number, $_decimals = 2) {
	return (float) $_number == (int) $_number
		? number_format($_number, null, null, ' ')
		: number_format($_number, $_decimals, ',', ' ');
}

function format_phone($_phone = null, $_code = null, $_internal = null, $_is_html = false) {
	$result = '';

	if ($_phone || $_code || $_internal) {
		$phone_f = '';
		$phone = str_replace(array(' ', '-'), '', $_phone);

		if (is_number($phone)) {
			while ($phone) {
				if (strlen_utf8($phone) > 2 * 2) {
					$piece = substr($phone, strlen_utf8($phone) - 2);
					$phone = substr($phone, 0, strlen_utf8($phone) - 2);
				} else {
					$piece = $phone;
					$phone = '';
				}

				if ($phone_f) $piece .= '-';
				$phone_f = $piece . $phone_f;
			}

		} else {
			$phone_f = $_phone;
		}

		$code_f = str_replace(array('(', ')'), '', $_code);
		if ($code_f) $code_f = '(' . $code_f . ')';

		$internal_f = $_internal ? '#' . $_internal : '';
		$result = $internal_f;

		if ($phone_f) {
			$result = ($_is_html ? '<span class="nowrap">' : '') . ($code_f ? $code_f . ' ' : '') . $phone_f . ($_is_html ? '</span>' : '') . ' ' . $result;
		}
	}

	return $result;
}

function get_random_string($_length) {
	$letters = 'abcdefghijklmnopqrstuvwxyz';
	$numbers = '0123456789';
	$symbol = '';
	$result = '';

	for ($i = 0; $i < $_length; $i++) {
		if (0 == rand(0, 3)) {
			$symbol = $letters{rand(0, strlen($letters) - 1)};
			if (0 == rand(0, 3)) $symbol = strtoupper($symbol);
		} else {
			$symbol = $numbers{rand(0, strlen($numbers) - 1)};
		}
		$result .= $symbol;
	}
	return $result;
}

function get_random_string_optimized($_length) {
	$consonant = 'bcdfghjklmnpqrstvwxz';
	$vowel = 'aeiouy';
	$result = '';
	$pairs = ($_length == 2) ? 1 : floor($_length / 2) - 1;

	for ($i = 0; $i < $pairs; $i++) {
		$result .= $consonant[rand(0, strlen($consonant) - 1)];
		$result .= $vowel[rand(0, strlen($vowel) - 1)];
	}
	if ($_length % 2 != 0) $result .= $consonant[rand(0, strlen($consonant) - 1)];
	if ($_length > 2) $result .= rand(0, 9) . rand(0, 9);

	return $result;
}

function translit($_string) {
	$rus = array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'cz', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'i', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya');
	$result = '';
	$string = $_string;

	for ($i = 0; $i < strlen_utf8($string); $i++) {
		$char = substr_utf8($string, $i, 1);

		if (isset($rus[$char])) {
			$result .= $rus[$char];

		} elseif (isset($rus[strtolower_utf8($char)])) {
			$result .= ucfirst($rus[strtolower_utf8($char)]);

		} else {
			$result .= $char;
		}
	}

	return $result;
}

function is_email($_email) {
	return (preg_match('/^[0-9a-zA-Z_][0-9a-zA-Z_.-]*[0-9a-zA-Z_-]@([0-9a-zA-Z][0-9a-zA-Z-]*\.)+[a-zA-Z]{2,4}$/', $_email));
}

function detect_cyr_charset($_str) {
	$charsets = array('k' => 0, 'w' => 0, 'd' => 0, 'i' => 0, 'm' => 0);

	for ($i = 0, $length = strlen_utf8($_str); $i < $length; $i++) {
		$char = ord($_str[$i]);
		//non-russian characters
		if ($char < 128 || $char > 256) continue;

		//CP866
		if (($char > 159 && $char < 176) || ($char > 223 && $char < 242)) $charsets['d'] += 3;
		if ($char > 127 && $char < 160) $charsets['d'] += 1;

		//KOI8-R
		if ($char > 191 && $char < 223) $charsets['k'] += 3;
		if ($char > 222 && $char < 256) $charsets['k'] += 1;

		//WIN-1251
		if ($char > 223 && $char < 256) $charsets['w'] += 3;
		if ($char > 191 && $char < 224) $charsets['w'] += 1;

		//MAC
		if ($char > 221 && $char < 255) $charsets['m'] += 3;
		if ($char > 127 && $char < 160) $charsets['m'] += 1;

		//ISO-8859-5
		if ($char > 207 && $char < 240) $charsets['i'] += 3;
		if ($char > 175 && $char < 208) $charsets['i'] += 1;
	}

	arsort($charsets);
	return key($charsets);
}

function is_number($_try) {
	return preg_match('/^\-?[1-9][0-9]*$/', $_try);
}

function cut_string($_string, $_length) {
	$result = '';

	if (strlen_utf8($_string) > $_length) {
		$length = 0;

		foreach (explode(' ', $_string) as $item) {
			$length += strlen_utf8($item);
			if ($length >= $_length) break;
			else $result .= ($result == '' ? '' : ' ') . $item;
		}

		$result .= '&hellip;';

	} else {
		$result = $_string;
	}

	return $result;
}

function list_to_array($_list) {
	$result = array();

	if ($_list) {
		$list = str_replace(array("\r\n", "\n", ','), ';', $_list);
		$list = preg_replace("/;+/", ';', $list);

		foreach (explode(';', $list) as $item) {
			if (trim($item)) array_push($result, trim($item));
		}
	}

	return $result;
}

function encode($_string) {
	return @iconv('windows-1251', 'utf-8', $_string);
}

function decode($_string) {
	return @iconv('utf-8', 'windows-1251', $_string);
}

function decode_array(&$_item) {
	$_item = decode($_item);
}

function is_mbstring_overload() {
	global $g_is_mbstring_overload;

	if (is_null($g_is_mbstring_overload)) {
		$g_is_mbstring_overload =
			function_exists('mb_substr') &&
			(int) ini_get('mbstring.func_overload') > 1;
	}

	return $g_is_mbstring_overload;
}

function strtolower_utf8($_string) {
	return is_mbstring_overload()
		? strtolower($_string)
		: encode(strtolower(decode($_string)));
}

function strtoupper_utf8($_string) {
	return is_mbstring_overload()
		? strtoupper($_string)
		: encode(strtoupper(decode($_string)));
}

function substr_utf8($_string, $_offset, $_count) {
	return is_mbstring_overload()
		? substr($_string, $_offset, $_count)
		: encode(substr(decode($_string), $_offset, $_count));
}

function strlen_utf8($_string) {
	return is_mbstring_overload()
		? strlen($_string)
		: strlen(decode($_string));
}

//function int_to_roman($int) {
//	if (!is_number($int)) return false;
//
//	$int = abs($int);
//	$thousands = (int) ($int / 1000);
//	$int -= $thousands * 1000;
//	$result = str_repeat('M', $thousands);
//
//	$table = array(
//		900	=> 'CM',
//		500	=> 'D',
//		400	=> 'CD',
//		100	=> 'C',
//		90	=> 'XC',
//		50	=> 'L',
//		40	=> 'XL',
//		10	=> 'X',
//		9	=> 'IX',
//		5	=> 'V',
//		4	=> 'IV',
//		1	=> 'I'
//	);
//
//	while ($int) {
//		foreach ($table as $part => $fragment) {
//			if ($part <= $int) break;
//		}
//
//		$amount = (int) ($int / $part);
//		$int -= $part * $amount;
//		$result .= str_repeat($fragment, $amount);
//	}
//
//	return $result;
//}

function transformCaseToUnderline($_value)
{
    $result = '';
    for ($i = 0; $i < strlen($_value); $i++) {
        if (
            $i != 0 &&
            !in_array($_value{$i}, array('_', '-', ':')) &&
            !in_array($_value{$i - 1}, array('_', '-')) &&
            $_value{$i} == strtoupper($_value{$i})
        ) {
            $result .= '_';
        }
        $result .= strtolower($_value{$i});
    }
    return $result;
}
