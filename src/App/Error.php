<?php

namespace App;

class Error extends \Core\Error
{
    public function showUserfriendlyMessage()
    {
        $file = TEMPLATES . '500.html';

        if (is_file($file)) readfile($file);
        else                return parent::showUserfriendlyMessage();
    }
}
