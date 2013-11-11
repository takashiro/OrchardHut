<?php

class Order extends DBObject{
	const TABLE_NAME = 'order';

	private $detail = array();
	private $address_components = array();

	public function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			parent::fetchAttributesFromDB('*', 'id='.$id);

			global $db;
			$db->select_table('orderdetail');
			$this->detail = $db->MFETCH('*', 'orderid='.$id);
			$db->select_table('orderaddresscomponent');
			$this->address_components = $db->MFETCH('*', 'orderid='.$id);
		}
	}

	public function addDetail($d){
		$this->detail[] = $d;
	}

	public function clearDetail(){
		$this->detail = array();
	}

	public function addAddressComponent($c){
		$this->address_components[] = $c;
	}

	public function insert(){
		$this->dateline = TIMESTAMP;

		parent::insert();

		global $db;
		$db->select_table('orderdetail');
		foreach($this->detail as &$d){
			$d['orderid'] = $this->id;
		}
		unset($d);
		$db->INSERTS($this->detail);

		$db->select_table('orderaddresscomponent');
		foreach($this->address_components as &$c){
			$c['orderid'] = $this->id;
		}
		unset($c);
		$db->INSERTS($this->address_components);
	}

	public function __destruct(){
		parent::__destruct();
	}
}

?>
