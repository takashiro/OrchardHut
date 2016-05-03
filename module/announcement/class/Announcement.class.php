<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

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

	function insert($extra = ''){
		$this->dateline = TIMESTAMP;
		if($this->time_end < $this->time_start){
			$this->time_end = $this->time_start + 24 * 3600;
		}
		return parent::insert($extra);
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
