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

class ProductStorage extends DBObject{
	const TABLE_NAME = 'productstorage';

	const NormalMode = 0;
	const BookingMode = 1;

	static public $Mode = array();

	public function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);
		}
	}

	public function updateNum($addnum, $productid = NULL){
		global $db, $tpre;

		$ext_condition = '';
		if($productid !== NULL){
			$productid = intval($productid);
			$ext_condition = ' AND productid='.$productid;
		}

		$addnum = intval($addnum);
		$is_minus = $addnum < 0;
		if($is_minus){
			$addnum = -$addnum;
			$booking_mode = self::BookingMode;
			$sec_today = (TIMESTAMP + TIMEZONE * 3600) % (24 * 3600);
			$db->query("UPDATE {$tpre}productstorage SET num=num-{$addnum} WHERE id={$this->id} AND (num>=$addnum || (mode=$booking_mode AND bookingtime_start<=$sec_today AND bookingtime_end>=$sec_today)) $ext_condition");
		}else{
			$db->query("UPDATE {$tpre}productstorage SET num=num+{$addnum} WHERE id={$this->id} $ext_condition");
		}

		return $db->affected_rows > 0;
	}
}

ProductStorage::$Mode = array(
	ProductStorage::NormalMode => lang('common', 'product_storage_normal_mode'),
	ProductStorage::BookingMode => lang('common', 'product_storage_booking_mode'),
);

?>
