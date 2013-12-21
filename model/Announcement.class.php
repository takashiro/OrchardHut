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

	static private $ActiveAnnouncements = NULL;
	static public function GetActiveAnnouncements(){
		if(self::$ActiveAnnouncements === NULL){
			self::$ActiveAnnouncements = readcache('announcements');
			if(self::$ActiveAnnouncements === NULL){
				global $db;
				$db->select_table('announcement');
				self::$ActiveAnnouncements = $db->MFETCH('*', 'time_start<='.TIMESTAMP.' AND time_end>='.TIMESTAMP.' ORDER BY displayorder,time_start DESC,time_end');
				writecache('announcements', self::$ActiveAnnouncements);
			}
		}

		return self::$ActiveAnnouncements;
	}

	static public function RefreshCache(){
		writecache('announcements', NULL);
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
