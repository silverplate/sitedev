<?php

class Db {
	private static $Db;

	public static function Get() {
		if (!isset(self::$Db)) {
			require_once(LIBRARIES . 'mysql.php');
			$obj = new DbMysql(DB_CONNECTION_STRING);
			$obj->IsLog = false;
			self::$Db = $obj;
		}
		return self::$Db;
	}
}

function get_db_data($_data) {
	return Db::Get()->Escape($_data);
}

function dump() {
	return exec('/usr/local/mysql/bin/mysqldump -u' . Db::Get()->GetUser() . ' -p' . Db::Get()->GetPassword() . ' --compact --add-drop-table --extended-insert ' . Db::Get()->GetDatabase() . ' > ' . SETS . 'dump.sql');
}

?>
