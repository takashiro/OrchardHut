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

class Order extends DBObject{
	const TABLE_NAME = 'order';

	//States
	public static $Status;
	const Unsorted = 0;
	const Sorted = 1;
	const Delivering = 2;
	const Received = 3;
	const Rejected = 4;
	const InDeliveryPoint = 5;
	const Canceled = 6;

	//Payment Method
	public static $PaymentMethod;
	const PaidWithCash = 0;
	const PaidOnline = 1;
	const PaidWithWallet = 2;

	//Delivery Method
	public static $DeliveryMethod;
	const HomeDelivery = 0;
	const StationDelivery = 1;

	private $detail = array();
	private $quantity_limit = array();

	public function __construct($id = 0){
		parent::__construct();

		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);

			global $db, $tpre;
			$this->detail = $db->fetch_all("SELECT * FROM {$tpre}orderdetail d WHERE orderid=$id");
		}
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function toReadable(){
		$attr = parent::toReadable();

		$attr['dateline'] = rdate($attr['dateline']);

		$attr['detail'] = $this->detail;

		$attr['deliveryaddress'] = Address::FullPathString($this->addressid);
		$attr['deliveryaddress'].= ' '.$attr['extaddress'];

		return $attr;
	}

	public function getUserOrderNum(){
		global $db, $tpre;
		$userid = $this->userid;
		$dateline = $this->dateline;
		return $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE userid=$userid AND dateline<$dateline");
	}

	public function belongToAddress($componentids){
		if(!$componentids){
			return true;
		}

		return in_array($this->addressid, Address::Extension($componentids));
	}

	public function getDetails(){
		return $this->detail;
	}

	//warning: you must call insert() after all the details has been added
	public function addDetail($d){
		global $db, $tpre;

		if($d['quantitylimit']){
			$bought = $db->result_first("SELECT amount FROM {$tpre}productquantitylimit WHERE priceid={$d['priceid']} AND userid={$this->userid}");
			$bought = intval($bought);
			$d['quantitylimit'] = intval($d['quantitylimit']);
			$d['number'] = min($d['number'], $d['quantitylimit'] - $bought);
			if($d['number'] <= 0){
				return false;
			}

			$this->quantity_limit[] = array(
				'priceid' => $d['priceid'],
				'amount' => $d['number']
			);
		}

		if($d['storageid']){
			$number = $d['amount'] * $d['number'];
			$storage = new ProductStorage;
			$storage->id = $d['storageid'];
			if(!$storage->updateNum(-$number)){
				return false;
			}
		}

		$d['subtotal'] = $d['number'] * $d['price'];
		$this->totalprice += $d['subtotal'];

		$this->detail[] = array(
			'productid' => $d['productid'],
			'storageid' => $d['storageid'],
			'productname' => $d['name'],
			'subtype' => $d['subtype'],
			'amount' => $d['amount'],
			'amountunit' => $d['amountunit'],
			'number' => $d['number'],
			'subtotal' => $d['subtotal'],
		);

		return true;
	}

	public function clearDetail(){
		$this->detail = array();
		$this->totalprice = 0;
	}

	public function insert($extra = ''){
		if(empty($this->detail)){
			return false;
		}

		global $tpre;
		$this->dateline = TIMESTAMP;

		parent::insert($extra);

		global $db;
		foreach($this->detail as &$d){
			$d['orderid'] = $this->id;
			if(is_numeric($d['amountunit'])){
				$d['amountunit'] = Product::AmountUnits($d['amountunit']);
			}
		}
		unset($d);

		$table = $db->select_table('orderdetail');
		$table->multi_insert($this->detail);

		foreach($this->quantity_limit as $ql){
			$db->query("INSERT INTO {$tpre}productquantitylimit (`priceid`,`userid`,`amount`)
				VALUES ($ql[priceid], {$this->userid}, $ql[amount])
				ON DUPLICATE KEY UPDATE `amount`=`amount`+$ql[amount]");
		}

		return true;
	}

	public function cancel(){
		global $db, $tpre;

		$table = $db->select_table('orderdetail');
		$details = $table->fetch_all('storageid,amount,number', 'orderid='.$this->id.' AND storageid IS NOT NULL');
		foreach($details as $d){
			$num = $d['amount'] * $d['number'];
			$db->query("UPDATE {$tpre}productstorage SET num=num+$num WHERE id={$d['storageid']}");
		}

		runhooks('order_canceled', array($this));
	}

	static public function Delete($orderid, $extra = ''){
		$result = parent::Delete($orderid, $extra);
		if($result){
			global $db;
			$table = $db->select_table('orderlog');
			$table->delete('orderid='.$orderid);
		}

		return $result;
	}

	//Operator Group
	const SystemOperated = 0;
	const AdministratorOperated = 1;
	const UserOperated = 2;

	//Operations
	const StatusChanged = 1;
	const DetailOutOfStock = 2;
	const PriceChanged = 3;
	const DetailInStock = 4;

	public function addLog($operator, $operation, $extra = NULL){
		$log = array(
			'orderid' => $this->id,
			'operator' => $operator->id,
			'operatorgroup' => self::SystemOperated,
			'operation' => $operation,
			'extra' => $extra,
			'dateline' => TIMESTAMP,
		);

		if($log['extra'] !== NULL){
			$log['extra'] = (string) $log['extra'];
		}

		if($operator instanceof Administrator){
			$log['operatorgroup'] = self::AdministratorOperated;
		}elseif($operator instanceof User){
			$log['operatorgroup'] = self::UserOperated;
		}

		runhooks('order_log_added', array($this, $log));

		global $db;
		$table = $db->select_table('orderlog');
		$table->insert($log, false, 'DELAYED');
		return $table->insert_id();
	}

	public function getLogs(){
		global $db, $tpre;
		$detail_in_stock = self::DetailInStock;
		$detail_out_of_stock = self::DetailOutOfStock;
		$detailop = $detail_in_stock.','.$detail_out_of_stock;

		$operatorgroup = self::AdministratorOperated;
		return $db->fetch_all("SELECT l.*,a.realname,a.mobile,d.productname,d.subtype,d.amount,d.number,d.amountunit
			FROM {$tpre}orderlog l
				LEFT JOIN {$tpre}administrator a ON l.operatorgroup=$operatorgroup AND a.id=l.operator
				LEFT JOIN {$tpre}orderdetail d ON l.operation IN ($detailop) AND d.id=l.extra
			WHERE l.orderid={$this->id}");
	}

	static protected $AlipayTradeNoPrefix = 'O';
	static public function __on_alipay_started(){
		if(isset($_GET['orderid'])){
			global $_G;
			$order = new Order($_GET['orderid']);
			if($order->exists() && $order->status != Order::Canceled && $order->userid == $_G['user']->id){
				$order->paymentmethod = Order::PaidOnline;
				//商户网站订单系统中唯一订单号，必填
				$_G['alipaytrade']['out_trade_no'] = self::$AlipayTradeNoPrefix.$order->id;

				//订单名称
				$_G['alipaytrade']['subject'] = $_G['config']['sitename'].'订单'.$order->id;

				//付款金额
				$_G['alipaytrade']['total_fee'] = $order->totalprice;
			}else{
				showmsg('order_not_exist');
			}
		}
	}

	static public function __on_alipay_notified($out_trade_no, $trade_no, $trade_status){
		$prefix_len = strlen(self::$AlipayTradeNoPrefix);
		if(strncmp($out_trade_no, self::$AlipayTradeNoPrefix, $prefix_len) == 0){
			$order = new Order(substr($out_trade_no, $prefix_len));
			if(!$order->exists()){
				writelog('alipaynotify', "ORDER_NOT_EXIST\t$out_trade_no\t$trade_no\t$trade_status");
				exit;
			}

			if(!isset(AlipayNotify::$TradeStateEnum[$trade_status])){
				writelog('alipaynotify', "UNEXPECTED_ORDER_STATE\t$out_trade_no\t$trade_no\t$trade_status");
				exit;
			}

			$order->paymentmethod = Order::PaidOnline;
			$order->alipaystate = AlipayNotify::$TradeStateEnum[$trade_status];
			$order->alipaytradeid = $trade_no;
		}
	}

	static public function __on_alipay_callback_executed($out_trade_no, $trade_no, $result){
		//以异步通知为准，此处不处理只通知
		if(strncmp($out_trade_no, self::$AlipayTradeNoPrefix, strlen(self::$AlipayTradeNoPrefix)) == 0)
			showmsg('the_order_is_successfully_paid', 'order.php');
	}
}

Order::$Status = array(
	Order::Unsorted => lang('common', 'order_unsorted'),
	Order::Sorted => lang('common', 'order_sorted'),
	Order::InDeliveryPoint => lang('common', 'order_in_delivery_point'),
	Order::Delivering => lang('common', 'order_delivering'),
	Order::Received => lang('common', 'order_received'),
	Order::Rejected => lang('common', 'order_rejected'),
	Order::Canceled => lang('common', 'order_canceled'),
);

Order::$PaymentMethod = array(
	Order::PaidWithCash => lang('common', 'order_paidwithcash'),
	Order::PaidOnline => lang('common', 'order_paidonline'),
	Order::PaidWithWallet => lang('common', 'order_paidwithwallet'),
);

Order::$DeliveryMethod = array(
	Order::HomeDelivery => lang('common', 'home_delivery'),
	Order::StationDelivery => lang('common', 'station_delivery'),
);

?>
