<?php

class Address{
	static public function RefreshCache(){
		writecache('addressformat', NULL);
		writecache('addresscomponent', NULL);
	}

	static private $Format = null;
	static public function Format(){
		if(self::$Format === null){
			self::$Format = readcache('addressformat');
			if(self::$Format === null){
				global $db;
				$table = $db->select_table('addressformat');
				self::$Format = $table->fetch_all('*', '1 ORDER BY displayorder');
				writecache('addressformat', self::$Format);
			}
		}
		return self::$Format;
	}

	static private $Components = null;
	static public function Components(){
		if(self::$Components === null){
			self::$Components = readcache('addresscomponent');
			if(self::$Components === null){
				global $db;
				$table = $db->select_table('addresscomponent');
				self::$Components = $table->fetch_all('*', '1 ORDER BY displayorder,id');
				writecache('addresscomponent', self::$Components);
			}
		}
		return self::$Components;
	}
}

?>
