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
