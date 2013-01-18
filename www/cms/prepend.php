<?php

implementFile(__FILE__, 1);

function implementFile($_localFilePath, $_depthInsideDocRoot)
{
    $path = explode(DIRECTORY_SEPARATOR, $_localFilePath);
    $pos = count($path) - $_depthInsideDocRoot - 2;
    array_splice($path, $pos, 1, array('core', $path[$pos]));

    require_once implode(DIRECTORY_SEPARATOR, $path);
}
