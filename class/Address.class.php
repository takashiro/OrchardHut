<?php

class Address{
	static private $Format = null;
	static public function Format(){
		if(self::$Format == null){
			global $db;
			$db->select_table('addressformat');
			self::$Format = $db->MFETCH('*', '1 ORDER BY displayorder');
		}
		return self::$Format;
	}

	static private $Components = null;
	static public function Components(){
		if(self::$Components == null){
			global $db;
			$db->select_table('addresscomponent');
			self::$Components = $db->MFETCH('*', '1 ORDER BY displayorder,id');
		}
		return self::$Components;
	}
}

?>
