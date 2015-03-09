<?php

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

	//Alipay State
	public static $AlipayState;
	public static $AlipayStateEnum;
	const WaitBuyerPay = 1;		//交易创建，等待买家付款。
	const TradeClosed = 2;		//在指定时间段内未支付时关闭的交易；在交易完成全额退款成功时关闭的交易。
	const TradeSuccess = 3;		//交易成功，且可对该交易做操作，如：多级分润、退款等。
	const TradePending = 4;		//等待卖家收款（买家付款后，如果卖家账号被冻结）。
	const TradeFinished = 5;	//交易成功且结束，即不可再做任何操作

	//Payment Method
	public static $PaymentMethod;
	const PaidWithCash = 0;
	const PaidOnline = 1;

	private $detail = array();
	private $address_components = array();
	private $quantity_limit = array();

	public function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			parent::fetchAttributesFromDB('*', 'id='.$id);

			global $db, $tpre;
			$this->detail = $db->fetch_all("SELECT * FROM {$tpre}orderdetail d WHERE orderid=$id");
			$this->address_components = $db->fetch_all("SELECT c.*,g.name
				FROM {$tpre}orderaddresscomponent c
					LEFT JOIN {$tpre}addresscomponent g ON g.id=c.componentid
					LEFT JOIN {$tpre}addressformat f ON f.id=c.formatid
				WHERE c.orderid=$id ORDER BY f.displayorder");
		}
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function toReadable(){
		$attr = parent::toReadable();

		$attr['dateline'] = rdate($attr['dateline']);

		$attr['detail'] = $this->detail;

		$attr['deliveryaddress'] = '';
		foreach($this->address_components as $c){
			$attr['deliveryaddress'].= $c['name'].' ';
		}
		$attr['deliveryaddress'].= $attr['extaddress'];

		$attr['priceunit'] = Product::PriceUnits($attr['priceunit']);

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

		global $db;
		$db->select_table('orderaddresscomponent');

		if(is_array($componentids)){
			$condition = 'orderid='.$this->id.' AND componentid IN ('.implode(',', $componentids).')';
		}else{
			$condition = array('orderid' => $this->id, 'componentid' => intval($componentids));
		}

		return $this->id == $db->RESULTF('orderid', $condition);
	}

	public function getDetails(){
		return $this->detail;
	}

	//warning: you must call insert() after all the details has been added
	public function addDetail($d){
		global $db, $tpre;

		if($d['quantitylimit']){
			$bought = $db->result_first("SELECT amount FROM {$tpre}productquantitylimit WHERE priceid=$d[priceid] AND userid={$this->userid}");
			$bought = intval($bought);
			$d['quantitylimit'] = intval($d['quantitylimit']);
			$d['number'] = $d['quantitylimit'] - $bought;
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
			$db->query("UPDATE {$tpre}productstorage SET num=num-$number WHERE id=$d[storageid] AND num>=$number");
			if($db->affected_rows() <= 0){
				return false;
			}
		}

		$this->totalprice += $d['number'] * $d['price'];

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

	public function addAddressComponent($c){
		$this->address_components[] = $c;
	}

	public function getAddressComponents(){
		return $this->address_components;
	}

	public function insert(){
		if(empty($this->detail)){
			return false;
		}

		global $tpre;
		$this->dateline = TIMESTAMP;

		parent::insert();

		global $db;
		foreach($this->detail as &$d){
			$d['orderid'] = $this->id;
			if(is_numeric($d['amountunit'])){
				$d['amountunit'] = Product::AmountUnits($d['amountunit']);
			}
		}
		unset($d);

		$db->select_table('orderdetail');
		$db->INSERTS($this->detail);

		foreach($this->address_components as &$c){
			$c['orderid'] = $this->id;
		}
		unset($c);

		$db->select_table('orderaddresscomponent');
		$db->INSERTS($this->address_components);

		foreach($this->quantity_limit as $ql){
			$db->query("INSERT INTO {$tpre}productquantitylimit (`priceid`,`userid`,`amount`)
				VALUES ($ql[priceid], {$this->userid}, $ql[amount])
				ON DUPLICATE KEY UPDATE `amount`=`amount`+$ql[amount]");
		}

		return true;
	}

	static public function Delete($orderid, $extra = ''){
		$result = parent::Delete($orderid, $extra);
		if($result){
			global $db, $tpre;

			$db->select_table('orderdetail');
			$details = $db->MFETCH('storageid,amount,number', 'orderid='.$orderid.' AND storageid IS NOT NULL');
			foreach($details as $d){
				$num = $d['amount'] * $d['number'];
				$db->query("UPDATE {$tpre}productstorage SET num=num+$num WHERE id=$d[storageid]");
			}
			$db->DELETE('orderid='.$orderid);

			$db->select_table('orderaddresscomponent');
			$db->DELETE('orderid='.$orderid);

			$db->select_table('orderlog');
			$db->DELETE('orderid='.$orderid);
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

	public function addLog(&$operator, $operation, $extra = NULL){
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
		$db->select_table('orderlog');
		$db->INSERT($log, false, 'DELAYED');
		return $db->insert_id();
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
}

Order::$Status = array(
	Order::Unsorted => lang('common', 'order_unsorted'),
	Order::Sorted => lang('common', 'order_sorted'),
	Order::Delivering => lang('common', 'order_delivering'),
	Order::InDeliveryPoint => lang('common', 'order_in_delivery_point'),
	Order::Received => lang('common', 'order_received'),
	Order::Rejected => lang('common', 'order_rejected'),
);

Order::$AlipayState = array(
	Order::WaitBuyerPay => lang('common', 'order_waitbuyerpay'),
	Order::TradeClosed => lang('common', 'order_tradeclosed'),
	Order::TradeSuccess => lang('common', 'order_tradesuccess'),
	Order::TradePending => lang('common', 'order_tradepending'),
	Order::TradeFinished => lang('common', 'order_tradefinished'),
);

Order::$AlipayStateEnum = array(
	'WAIT_BUYER_PAY' => Order::WaitBuyerPay,
	'TRADE_CLOSED' => Order::TradeClosed,
	'TRADE_SUCCESS' => Order::TradeSuccess,
	'TRADE_PENDING' => Order::TradePending,
	'TRADE_FINISHED' => Order::TradeFinished,
);

Order::$PaymentMethod = array(
	Order::PaidWithCash => lang('common', 'order_paidwithcash'),
	Order::PaidOnline => lang('common', 'order_paidonline'),
);

?>
