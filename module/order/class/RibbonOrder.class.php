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

class RibbonOrder extends DBObject{

	const TABLE_NAME = 'ribbonorder';

	protected $items = array();

	public function __construct(int $id = 0){
		parent::__construct();
		if($id > 0){
			$this->fetch('*', 'id='.$id);

			global $db;
			$table = $db->select_table('ribbon');
			$this->items = $table->fetch_all('*', 'orderid='.$id);
		}else{
			$this->totalprice = 0;
		}
	}

	public function add($r){
		$this->totalprice += $r['subtotal'];
		$boughtnum = $r['amount'] * $r['number'];
		$this->items[] = array(
			'orderid' => $this->id,
			'productid' => $r['productid'],
			'productname' => $r['name'],
			'productsubtype' => $r['subtype'],
			'subtotal' => $r['subtotal'],
			'boughtnum' => $boughtnum,
			'restnum' => $boughtnum,
			'amountunit' => Product::AmountUnits($r['amountunit']),
		);
	}

	public function insert($extra = ''){
		$id = parent::insert($extra);

		global $db;
		$table = $db->select_table('ribbon');
		foreach($this->items as &$r){
			$r['orderid'] = $id;
			$r['userid'] = $this->userid;
			$table->insert($r);
		}

		return $id;
	}

	const TRADE_PREFIX = 'R';

	static public function __on_trade_started(){
		if(isset($_GET['ribbonorderid']) && $id = intval($_GET['ribbonorderid'])){
			$order = new RibbonOrder;
			$order->id = $id;
			$order->fetch('*', 'id='.$id);

			global $_G;
			if($order->exists() && $order->userid == $_G['user']->id){
				$trade = &$_G['trade'];
				$trade['out_trade_no'] = self::TRADE_PREFIX.$id;
				$trade['subject'] = $_G['config']['sitename'].$trade['out_trade_no'];
				$trade['total_fee'] = $order->totalprice;
			}else{
				showmsg('order_not_exist');
			}
		}
	}

	static public function __on_trade_notified($id, $method, $trade_id, $trade_status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}

		$order = new RibbonOrder(intval(substr($id, 1)));
		if(!$order->exists()){
			writelog('trade_notify', "RIBBONORDER_NOT_EXIST\t$id\t$method\t$trade_status\t".json_encode($extra));
			exit;
		}

		if($order->tradestate == $trade_status)
			return;

		$order->paymentmethod = $method;
		$order->tradestate = $trade_status;
		$order->tradeid = $trade_id;
		if($order->tradestate == Wallet::TradeSuccess){
			$order->tradetime = TIMESTAMP;
		}
	}

	static public function __on_trade_callback_executed($id, $method, $trade_id, $trade_status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}

		$order = new RibbonOrder(intval(substr($id, 1)));
		if(!$order->exists()){
			writelog('trade_callback', "RIBBONORDER_NOT_EXIST\t$id\t$method\t$trade_status\t".json_encode($extra));
			showmsg('order_does_not_exist', 'index.php?mod=order');
		}

		$order->paymentmethod = $method;
		$order->tradestate = $trade_status;
		$order->tradeid = $trade_id;
		if($order->tradestate == Wallet::TradeSuccess){
			$order->tradetime = TIMESTAMP;
			showmsg('the_order_is_successfully_paid', 'index.php?mod=order');
		}else{
			showmsg('please_pay_for_the_order', 'index.php?mod=order');
		}
	}

}
