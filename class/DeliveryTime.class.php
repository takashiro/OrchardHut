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

/*
	DeliveryTime here means the time when orders reach customers (misused perhaps =.=)
	It's frequently read (without joint tables) but seldom modified. So we declare a class
	to cache its data intoa single file, and thus reduce MySQL queries.
*/

class DeliveryTime{
	public static function FetchAll(){
		$timespans = readcache('deliverytime');
		if(!$timespans){
			global $db;
			$table = $db->select_table('deliverytime');
			$timespans = $table->fetch_all('*');
			writecache('deliverytime', $timespans);
		}

		return $timespans;
	}

	public static function FetchAllEffective(){
		$timespans = array();

		foreach(self::FetchAll() as $span){
			if(empty($span['hidden']) && $span['effective_time'] <= TIMESTAMP && TIMESTAMP <= $span['expiry_time']){
				$timespans[] = $span;
			}
		}

		return $timespans;
	}

	public static function UpdateCache(){
		writecache('deliverytime', false);
	}

	public static function SortByTimeFrom(&$timespans){
		usort($timespans, 'DeliveryTime::__sort_by_time_from');
	}

	private static function __sort_by_time_from($s1, $s2){
		return $s1['time_from'] > $s2['time_from'];
	}
}

?>
