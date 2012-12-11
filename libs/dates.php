<?php

/**
 * Устаревшая реализация.
 *
 * @todo Заменить прежнюю реализацию методов по работе с датой на новую.
 */

function trace_time($_function, $_label = null) {
    global $g_receptacle;
    if (!$g_receptacle) $g_receptacle = array();

    list($msec, $sec) = explode(' ', microtime());
    $now = ((float) $msec + (float) $sec);

    $i = 0;
    while (true) {
        $function_name = ($i == 0) ? $_function : $_function . ' {' . $i . '}';
        if (!isset($g_receptacle[$function_name])) {
            $open = $is_global = false;
            $level = 0;
            foreach ($g_receptacle as $key => $value) {
                if ($value['is_global']) $is_global = true;
                if (is_null($value['finish'])) {
                    $level++;
                    if (!$open) $open = $key;
                }
            }
            if (!$is_global && $open) $g_receptacle[$open]['is_global'] = true;
            $g_receptacle[$function_name] = array('start' => $now, 'finish' => null, 'label' => $_label, 'is_global' => false, 'level' => $level);
            break;
        } elseif (!$g_receptacle[$function_name]['finish']) {
            $g_receptacle[$function_name]['finish'] = $now;
            if ($_label) $g_receptacle[$function_name]['label'] = $_label;
            break;
        } else {
            $i++;
        }
    }
}

function trace_time_get_report($_format = 'html') {
    global $g_receptacle;

    $result = '';
    $nl = ($_format == 'text') ? "\n" : '<br>';
    $lv = ($_format == 'text') ? "\t" : '&bull;&nbsp;';

    if ($g_receptacle) {
        $global_time = null;
        foreach ($g_receptacle as $name => $item) {
            if ($item['is_global']) {
                $global_time = $item['finish'] - $item['start'];
                break;
            }
        }

        foreach ($g_receptacle as $name => $item) {
            $time = $item['finish'] - $item['start'];
            if ($time > 3600) {
                $time_taken = format_number($time / 3600, 2) . ' hours';
            } elseif ($time > 60) {
                $time_taken = format_number($time / 60, 2) . ' minutes';
            } else {
                $time_taken = format_number($time, 6) .' seconds';
            }

            if ($item['level']) {
                for ($i = 0; $i < $item['level']; $i++) {
                    $result .= $lv;
                }
            }
            $result .= (($item['label']) ? "{$item['label']} ($name)" : $name) . ': ' . $time_taken;

            if ($global_time && $global_time != $time) {
                $result .= ' (' . format_number(($time * 100) / $global_time, 2) . '%)';
            }

            $result .= $nl;
        }
    }

    return $result;
}

function date_get_week_day($_number, $_language = 'ru') {
    $number = strlen($_number) == 2 && $_number{0} == '0' ? $_number{1} : $_number;

    $data = array(
        'ru' => array('Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'),
        'en' => array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
    );

    return (isset($data[$_language]) && isset($data[$_language][$number - 1]))
        ? $data[$_language][$number - 1]
        : false;
}

function date_get_month($_number, $_type = 1, $_language = 'ru') {
    $number = ($_number{0} == '0') ? $_number{1} : $_number;

    $data = array(
        'ru' => array(array('Январь', 'Января', 'Январе'), array('Февраль', 'Февраля', 'Феврале'), array('Март', 'Марта', 'Марте'), array('Апрель', 'Апреля', 'Апреле'), array('Май', 'Мая', 'Мае'), array('Июнь', 'Июня', 'Июне'), array('Июль', 'Июля', 'Июле'), array('Август', 'Августа', 'Августе'), array('Сентябрь', 'Сентября', 'Сентябре'), array('Октябрь', 'Октября', 'Октябре'), array('Ноябрь', 'Ноября', 'Ноябре'), array('Декабрь', 'Декабря', 'Декабре')),
        'en' => array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
    );

    switch ($_language) {
        case 'ru':
            return (isset($data[$_language]) && isset($data[$_language][$number - 1]) && isset($data[$_language][$number - 1][$_type - 1]))
                ? $data[$_language][$number - 1][$_type - 1]
                : false;
        case 'en':
            return (isset($data[$_language]) && isset($data[$_language][$number - 1]))
                ? $data[$_language][$number - 1]
                : false;
        default:
            return false;
    }
}

function date_get_expanded($_date, $_is_human_readable = true, $_is_year = null) {
    $date = getdate($_date);

    foreach (array('hours', 'minutes', 'seconds') as $item) {
        if (strlen($date[$item]) == 1) {
            $date[$item] = '0' . $date[$item];
        }
    }

    $h_m_s = $date['hours'] . $date['minutes'] . $date['seconds'];

    if ($_is_human_readable) {
        switch (mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year'])) {
            case tomorrow():
                $result = 'Завтра';
                break;

            case yesterday():
                $result = 'Вчера';
                break;

            case today():
                $result = 'Сегодня';
                break;

            default:
                $result = $date['mday'] . ' ' . strtolower_utf8(date_get_month($date['mon'], 2));

                if ((is_null($_is_year) && $date['year'] != date('Y')) || $_is_year === true) {
                    $result .= ' ' . $date['year'];
                }

                break;
        }

        return $result . ($h_m_s == '000000' ? '' : ", {$date['hours']}:{$date['minutes']}");

    } else {
        return $date['mday'] . ' ' . strtolower_utf8(date_get_month($date['mon'], 2)) . ($_is_year ? ' ' . $date['year'] : '') . ($h_m_s == '000000' ? '' : ", {$date['hours']}:{$date['minutes']}");
    }
}

function date_get_from_data(&$_data, $_prefix) {
    if (isset($_data[$_prefix . '_year']) && isset($_data[$_prefix . '_month']) && isset($_data[$_prefix . '_day'])) {
        if (checkdate($_data[$_prefix . '_month'], $_data[$_prefix . '_day'], $_data[$_prefix . '_year'])) {
            return mktime(0, 0, 0, $_data[$_prefix . '_month'], $_data[$_prefix . '_day'], $_data[$_prefix . '_year']);
        }
    }

    return false;
}

function today($_str_format = null) {
    return get_global_date('today', $_str_format);
}

function tomorrow($_str_format = false) {
    return get_global_date('tomorrow', $_str_format);
}

function yesterday($_str_format = false) {
    return get_global_date('yesterday', $_str_format);
}

function get_global_date($_name, $_str_format = false) {
    $var = 'g_' . $_name;
    $str_var = $var . '_str';
    global $$var, $$str_var;

    if (!isset($$var)) {
        $$var = strtotime($_name);
    }

    if ($_str_format) {
        if (!isset($$str_var)) {
            $$str_var = array();
        }

        if (!isset($$str_var[$_str_format])) {
            $$str_var[$_str_format] = date($_str_format, $$var);
        }
    }

    return $_str_format ? $$str_var[$_str_format] : $$var;
}

function format_time($_minutes, $_is_days = true, $_is_short = false) {
    $days = $_is_days ? floor($_minutes / (8 * 60)) : 0;
    $hours = floor(($_minutes - $days * 8 * 60) / 60);
    $minutes = $_minutes - $days * 8 * 60 - $hours * 60;

    if ($days || $hours || $minutes) {
        if ($_is_short) {
            return ($days ? sprintf('%02d', $days) . ':' : '') . ($hours ? sprintf('%02d', $hours) : '00') . ':' . ($minutes ? sprintf('%02d', $minutes) : '00');
        } else {
            return trim(($days ? $days . ' д ' : '') . ($hours ? $hours . ' ч ' : '') . ($minutes ? $minutes . ' м ' : ''));
        }
    } else {
        return '0';
    }
}

function get_period($_from, $_till, $_is_typo = false) {
    $spacer = $_is_typo ? '&#160;' : ' ';
    $dash = $_is_typo ? '&#151;' : '--';
    $day = date('j', $_from);
    $year = date('Y', $_from);
    $month = strtolower_utf8(date_get_month(date('m', $_from), 2));
    $till_month = strtolower_utf8(date_get_month(date('m', $_till), 2));

    if ($_from == $_till) {
        $result  = $day . $spacer;
        $result .= $month . ' ';
        $result .= $year . $spacer . 'года';

    } elseif (date('Ym', $_from) == date('Ym', $_till)) {
        $result  = $day . $dash . date('j', $_till) . $spacer;
        $result .= $month . ' ' . $year . $spacer . 'года';

    } elseif ($year == date('Y', $_till)) {
        $result  = $day . $spacer . $month . $dash;
        $result .= date('j', $_till) . $spacer;
        $result .= $till_month . ' ' . $year . $spacer . 'года';

    } else {
        $result  = $day . $spacer . $month . ' ';
        $result .= $year . $dash;
        $result .= date('j', $_till) . $spacer;
        $result .= $till_month . ' ' . date('Y', $_till);
    }
    return $result;
}
