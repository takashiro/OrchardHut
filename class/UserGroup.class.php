
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

class UserGroup extends DBObject{
	const TABLE_NAME = 'usergroup';

	public static $Type = array();
	const NormalType = 0;
	const SpecialType = 1;

	public function __construct($id = 0){
		parent::__construct();
		if($id = intval($id)){
			$this->fetch('*', 'id='.$id);
		}
	}

	public static function RefreshCache(){
		writecache('usergroup_normal', null);
	}

	public static $Names = null;
	public static function Names(){
		if(self::$Names === null){
			self::$Names = readcache('usergroup_name');
			if(self::$Names === null){
				global $db;
				$table = $db->select_table('usergroup');
				self::$Names = array();
				$groups = $table->fetch_all('id,name');
				foreach($groups as $g){
					self::$Names[$g['id']] = $g['name'];
				}
				writecache('usergroup_name', self::$Names);
			}
		}
		return self::$Names;
	}

	public static function Name($id){
		$names = self::Names();
		return isset($names[$id]) ? $names[$id] : '';
	}

	public static $NormalList = null;
	public static function NormalList(){
		if(self::$NormalList === null){
			self::$NormalList = readcache('usergroup_normal');
			if(self::$NormalList === null){
				global $db;
				$table = $db->select_table('usergroup');
				self::$NormalList = $table->fetch_all('*', 'type='.self::NormalType.' ORDER BY minordernum,maxordernum');
				writecache('usergroup_normal', self::$NormalList);
			}
		}
		return self::$NormalList;
	}

	public static function ByOrderNum($attr, $ordernum){
		$usergroups = self::NormalList();
		foreach($usergroups as $u){
			if($u['minordernum'] <= $ordernum && $ordernum <= $u['maxordernum']){
				return $u[$attr];
			}
		}
		return null;
	}

	public static function RefreshUser($userid, $ordernum = null){
		global $db, $tpre;
		$received = Order::Received;
		$ordernum === null && $ordernum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE userid={$userid} AND status=$received");
		$groupid = self::ByOrderNum('id', $ordernum);
		$db->query("UPDATE LOW_PRIORITY {$tpre}user SET groupid=$groupid WHERE id=$userid");
	}

	static public function __on_order_log_added($order, $log){
		global $db, $tpre, $_G;

		if($log['operation'] == Order::StatusChanged && $log['extra'] == Order::Received)
			UserGroup::RefreshUser($order->userid);
	}
}

UserGroup::$Type = array(
	lang('common', 'usergroup_normal'),
	lang('common', 'usergroup_special'),
);

?>
