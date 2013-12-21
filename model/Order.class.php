<?php

class Order extends DBObject{
	const TABLE_NAME = 'order';

	public static $Status;
	const Unsorted = 0;
	const Delivering = 1;
	const Received = 2;
	const Rejected = 3;

	private $detail = array();
	private $address_components = array();

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

	public function addDetail($d){
		if($d['storageid']){
			global $db, $tpre;
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
			'productname' => $d['productname'],
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
		}

		return $result;
	}
}

Order::$Status = array(
	lang('common', 'order_unsorted'),
	lang('common', 'order_delivering'),
	lang('common', 'order_received'),
	lang('common', 'order_rejected')
);

?>
