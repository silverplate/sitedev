<?php

require_once 'prepend.php';

// $string = 'Мама мыла раму папой.';
// App_Cms_Cache_Apc::instance()->set('test', $string);
// echo App_Cms_Cache_Apc::instance()->get('test');
// App_Cms_Cache_Apc::instance()->set('db', Ext_Db::get());

d(App_Cms_Back_Section::getList());
d(App_Cms_Back_Section::getById('d55T81k398'));
d(App_Cms_Back_Section::getList());

phpinfo();
