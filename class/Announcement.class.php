<?php

class Announcement extends DBObject{
	const TABLE_NAME = 'announcement';

	function __construct($id = 0){
		parent::__construct();
		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);
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

	static private $PotentialAnnouncements = NULL;
	static private $ActiveAnnouncements = NULL;
	static public function GetActiveAnnouncements(){
		if(self::$ActiveAnnouncements === NULL){
			if(self::$PotentialAnnouncements === NULL){
				self::$PotentialAnnouncements = readcache('announcements');
				if(self::$PotentialAnnouncements === NULL){
					global $db;
					$table = $db->select_table('announcement');
					self::$PotentialAnnouncements = $table->fetch_all('*', 'time_end>='.TIMESTAMP.' ORDER BY displayorder,time_start DESC,time_end');
					writecache('announcements', self::$PotentialAnnouncements);
				}
			}
			self::$ActiveAnnouncements = array();
			foreach(self::$PotentialAnnouncements as &$a){
				if($a['time_start'] <= TIMESTAMP){
					self::$ActiveAnnouncements[] = &$a;
				}
			}
			unset($a);
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
