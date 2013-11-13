<?php

class Announcement extends DBObject{
	const TABLE_NAME = 'announcement';

	function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			parent::fetchAttributesFromDB('*', 'id='.$id);
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	function insert(){
		$this->dateline = TIMESTAMP;
		if($this->time_end < $this->time_start){
			$this->time_end = $this->time_start + 24 * 3600;
		}
		parent::insert();
	}

	static public function GetActiveAnnouncements(){
		global $db;
		$db->select_table('announcement');
		return $db->MFETCH('*', 'time_start<='.TIMESTAMP.' AND time_end>='.TIMESTAMP.' ORDER BY displayorder,time_start DESC,time_end');
	}

	function toReadable(){
		$attr = parent::toReadable();
		foreach(array('time_start', 'time_end', 'dateline') as $var){
			$attr[$var] = rdate($attr[$var]);
		}
		return $attr;
	}
}

?>
