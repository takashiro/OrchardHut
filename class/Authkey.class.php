<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

class Authkey extends DBObject{
	const TABLE_NAME = 'authkey';
	const PRIMARY_KEY = 'user';

	public static $ExpiryTime;

	public function __construct($user){
		parent::__construct();
		$this->fetch('*', array('user' => $user));
	}

	public function isExpired(){
		if($this->expiry < TIMESTAMP){
			if($this->user){
				$this->deleteFromDB();
			}
			return true;
		}
		return false;
	}

	public function match($key){
		return $this->key == $key;
	}

	public function matchOnce($key){
		$result = $this->match($key);
		if($result){
			$this->deleteFromDB();
		}
		return $result;
	}

	public static function Generate($user, $expiry = NULL){
		global $db;
		$table = $db->select_table('authkey');

		$authkey = array(
			'user' => $user,
			'key' => randomstr(32),
			'expiry' => $expiry == NULL ? TIMESTAMP + self::$ExpiryTime : $expiry,
		);

		$table->insert($authkey, true);

		return $authkey['key'];
	}
}

Authkey::$ExpiryTime = 5 * 60;

?>
