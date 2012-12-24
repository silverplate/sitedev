<?php

function trace_time($_function, $_label = null)
{
    global $gReceptacle;

    if (!$gReceptacle) $gReceptacle = array();

    list($msec, $sec) = explode(' ', microtime());
    $now = ((float) $msec + (float) $sec);
    $i = 0;

    while (true) {
        $func = $i == 0 ? $_function : "$_function \{$i\}";

        if (!isset($gReceptacle[$func])) {
            $open = $isGlobal = false;
            $level = 0;

            foreach ($gReceptacle as $key => $value) {
                if ($value['is_global']) {
                    $isGlobal = true;
                }

                if (is_null($value['finish'])) {
                    $level++;
                    if (!$open) $open = $key;
                }
            }

            if (!$isGlobal && $open) {
                $gReceptacle[$open]['is_global'] = true;
            }

            $gReceptacle[$func] = array(
                'start' => $now,
                'finish' => null,
                'label' => $_label,
                'is_global' => false,
                'level' => $level
            );

            break;

        } else if (!$gReceptacle[$func]['finish']) {
            $gReceptacle[$func]['finish'] = $now;

            if ($_label) {
                $gReceptacle[$func]['label'] = $_label;
            }

            break;

        } else {
            $i++;
        }
    }
}

function trace_time_get_report($_format = 'html')
{
    global $gReceptacle;

    $result = '';
    $nl = $_format != 'html' ? PHP_EOL : '<br>';
    $lv = $_format != 'html' ? "\t" : '&bull;&nbsp;';

    if ($gReceptacle) {
        $globalTime = null;

        foreach ($gReceptacle as $name => $item) {
            if ($item['is_global']) {
                $globalTime = $item['finish'] - $item['start'];
                break;
            }
        }

        foreach ($gReceptacle as $name => $item) {
            $time = $item['finish'] - $item['start'];

            if ($time > 3600) {
                $timeTaken = Ext_Number::format($time / 3600, 2) . ' hours';

            } else if ($time > 60) {
                $timeTaken = Ext_Number::format($time / 60, 2) . ' minutes';

            } else {
                $timeTaken = Ext_Number::format($time, 6) . ' seconds';
            }

            if ($item['level']) {
                for ($i = 0; $i < $item['level']; $i++) {
                    $result .= $lv;
                }
            }

            $result .= ($item['label'] ? "{$item['label']} ($name)" : $name) .
                       ': ' . $timeTaken;

            if ($globalTime && $globalTime != $time) {
                $result .= ' (' . Ext_Number::format(($time * 100) / $globalTime, 2) . '%)';
            }

            $result .= $nl;
        }
    }

    return $result;
}
