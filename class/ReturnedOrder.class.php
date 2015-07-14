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

class ReturnedOrder extends DBObject{

	const TABLE_NAME = 'returnedorder';

	static public $Status = array();
	const Submitted = 0;
	const Handled = 1;

	static public $DetailResult = array();
	const UnhandledDetail = 0;
	const InvalidDetail = 1;
	const FeeOnly = 2;
	const FeeAndItem = 3;

	private $details = array();

	public function __construct($id = 0){
		parent::__construct();

		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);
		}
	}

	public function insert($extra = ''){
		if(!$this->details)
			return false;

		$this->dateline = TIMESTAMP;
		$result = parent::insert($extra);
		if($result){
			global $db;
			$table = $db->select_table('returnedorderdetail');
			foreach($this->details as $d){
				$table->insert($d, 'IGNORE');
			}
		}

		return $result;
	}

	public function getDetails(){
		if($this->id > 0){
			global $db;
			$table = $db->select_table('returnedorderdetail');
			return $table->fetch_all('*', 'orderid='.$this->id);
		}else{
			return array();
		}
	}

	public function addDetail($detailid, $number){
		$d = array(
			'id' => $detailid,
			'orderid' => $this->id,
			'number' => $number,
			'state' => self::UnhandledDetail,
		);
		$this->details[] = $d;
	}

	public function toReadable(){
		$attr = parent::toReadable();
		$attr['details'] = $this->getDetails();
		return $attr;
	}

}

ReturnedOrder::$Status = array(
	ReturnedOrder::Submitted => lang('common', 'returned_order_submitted'),
	ReturnedOrder::Handled => lang('common', 'returned_order_handled'),
);


ReturnedOrder::$DetailResult = array(
	ReturnedOrder::UnhandledDetail => lang('common', 'returned_order_unhandled_detail'),
	ReturnedOrder::InvalidDetail => lang('common', 'returned_order_invalid_detail'),
	ReturnedOrder::FeeOnly => lang('common', 'returned_order_fee_only'),
	ReturnedOrder::FeeAndItem => lang('common', 'returned_order_fee_and_item'),
);

?>
