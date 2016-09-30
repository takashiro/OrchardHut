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

class Product extends DBObject{
	CONST TABLE_NAME = 'product';

	public function __construct($id = 0){
		parent::__construct();

		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);
		}
	}

	public function getFilteredPrices(){
		if($this->id <= 0){
			return array();
		}

		global $db, $tpre;
		$productid = $this->id;
		$now = TIMESTAMP;

		$prices = array();

		$check_booking_mode = ProductStorage::IsBookingMode() ? ' OR s.mode='.ProductStorage::BookingMode : '';
		$query = $db->query("SELECT c.*,p.*,c.id AS is_countdown
			FROM {$tpre}productprice p
				LEFT JOIN {$tpre}productcountdown c ON c.id=p.id
				LEFT JOIN {$tpre}productstorage s ON s.id=p.storageid AND s.productid=p.productid
			WHERE p.productid=$productid
				AND (p.storageid IS NULL OR s.num>=p.amount $check_booking_mode)
				AND (c.id IS NULL OR (c.start_time<=$now AND c.end_time>=$now))
			ORDER BY p.displayorder");
		while($p = $query->fetch_assoc()){
			$prices[$p['id']] = $p;
		}

		foreach($prices as &$p){
			$p['amountunit'] = self::AmountUnits($p['amountunit']);
			$p['price'] = floatval($p['price']);
			$p['amount'] = floatval($p['amount']);
			unset($prices[$p['masked_priceid']]);
		}
		unset($p);

		return $prices;
	}

	static public function FetchFilteredPrices(&$oproducts){
		if(empty($oproducts)){
			return;
		}

		global $db, $tpre;
		$now = TIMESTAMP;

		$products = array();
		$productids = array();
		foreach($oproducts as &$p){
			$productids[] = $p['id'];

			$p['rule'] = array();
			$products[$p['id']] = &$p;
		}
		unset($p);
		$productids = implode(',', $productids);

		$prices = array();

		$check_booking_mode = ProductStorage::IsBookingMode() ? ' OR s.mode='.ProductStorage::BookingMode : '';
		$query = $db->query("SELECT c.*,p.*,c.id AS is_countdown
			FROM {$tpre}productprice p
				LEFT JOIN {$tpre}productcountdown c ON c.id=p.id
				LEFT JOIN {$tpre}productstorage s ON s.id=p.storageid AND s.productid=p.productid
			WHERE p.productid IN ($productids)
				AND (p.storageid IS NULL OR s.num>=p.amount $check_booking_mode)
				AND (c.id IS NULL OR (c.start_time<=$now AND c.end_time>=$now))
			ORDER BY p.displayorder");
		while($p = $query->fetch_assoc()){
			$prices[$p['id']] = $p;
		}

		foreach($prices as &$p){
			$p['amountunit'] = self::AmountUnits($p['amountunit']);
			$p['price'] = floatval($p['price']);
			$p['amount'] = floatval($p['amount']);
			unset($prices[$p['masked_priceid']]);

			$products[$p['productid']]['rule'][] = &$p;
		}
		unset($p);
	}

	public function getPrices($to_readable = false){
		if($this->id > 0){
			global $db, $tpre;
			$productid = $this->id;
			$prices = $db->fetch_all("SELECT p.*
				FROM {$tpre}productprice p
					LEFT JOIN {$tpre}productcountdown c ON c.id=p.id
				WHERE p.productid=$productid AND c.id IS NULL
				ORDER BY p.displayorder");
			if($to_readable){
				foreach($prices as &$p){
					$p['amountunit'] = self::AmountUnits($p['amountunit']);
					$p['price'] = floatval($p['price']);
					$p['amount'] = floatval($p['amount']);
				}
			}
			return $prices;
		}else{
			return array();
		}
	}

	public function editPrice($price){
		$id = isset($price['id']) ? intval($price['id']) : 0;

		$update = array();

		if(isset($price['subtype'])){
			$update['subtype'] = $price['subtype'];
		}

		if(isset($price['briefintro'])){
			$update['briefintro'] = $price['briefintro'];
		}

		if(isset($price['price'])){
			$update['price'] = floatval($price['price']);
		}

		if(isset($price['amount'])){
			$update['amount'] = floatval($price['amount']);
		}

		if(isset($price['amountunit'])){
			$update['amountunit'] = intval($price['amountunit']);
		}

		if(isset($price['storageid'])){
			$update['storageid'] = intval($price['storageid']);
			$update['storageid'] <= 0 && $update['storageid'] = NULL;
		}

		if(isset($price['quantitylimit'])){
			$update['quantitylimit'] = intval($price['quantitylimit']);
		}

		if(isset($price['displayorder'])){
			$update['displayorder'] = intval($price['displayorder']);
		}

		global $db;
		$table = $db->select_table('productprice');
		if($id > 0){
			$table->update($update, array('id' => $id, 'productid' => $this->id));
		}else{
			$update['productid'] = $this->id;

			$table->insert($update);
			$update['id'] = $table->insert_id();
		}

		return $update;
	}

	public function deletePrice($id){
		$id = intval($id);
		global $db;
		$table = $db->select_table('productprice');
		$table->delete(array('id' => $id, 'productid' => $this->id));
		$table = $db->select_table('productquantitylimit');
		$table->delete(array('priceid' => $id));
		return $db->affected_rows;
	}

	public function getCountdowns($to_readable = false){
		if($this->id > 0){
			global $db, $tpre;
			$productid = $this->id;
			$prices = $db->fetch_all("SELECT p.*,c.*
				FROM {$tpre}productprice p
					LEFT JOIN {$tpre}productcountdown c ON c.id=p.id
				WHERE p.productid=$productid AND c.id IS NOT NULL
				ORDER BY p.displayorder");
			if($to_readable){
				foreach($prices as &$p){
					$p['amountunit'] = self::AmountUnits($p['amountunit']);
					$p['price'] = floatval($p['price']);
					$p['amount'] = floatval($p['amount']);
				}
			}
			return $prices;
		}else{
			return array();
		}
	}

	public function editCountdown($countdown){
		$id = isset($countdown['id']) ? intval($countdown['id']) : 0;

		$price = $this->editPrice($countdown);

		global $db;
		$table = $db->select_table('productcountdown');
		if($id > 0){
			$update = array();

			if(isset($countdown['masked_priceid'])){
				$update['masked_priceid'] = intval($countdown['masked_priceid']);
			}

			if(isset($countdown['start_time'])){
				$update['start_time'] = rstrtotime($countdown['start_time']);
			}

			if(isset($countdown['end_time'])){
				$update['end_time'] = rstrtotime($countdown['end_time']);
			}

			$table->update($update, array('id' => $id));

			if(isset($update['start_time'])){
				$update['start_time'] = rdate($update['start_time']);
			}
			if(isset($update['end_time'])){
				$update['end_time'] = rdate($update['end_time']);
			}

			return array_merge($price, $update);
		}else if(!empty($price['id'])){
			$countdown = array(
				'id' => $price['id'],
				'masked_priceid' => isset($countdown['masked_priceid']) ? intval($countdown['masked_priceid']) : 0,
				'start_time' => isset($countdown['start_time']) ? rstrtotime($countdown['start_time']) : 0,
				'end_time' => isset($countdown['end_time']) ? rstrtotime($countdown['end_time']) : 0,
			);
			$table->insert($countdown);

			$countdown['start_time'] = rdate($countdown['start_time']);
			$countdown['end_time'] = rdate($countdown['end_time']);

			return array_merge($price, $countdown);
		}

		return $price;
	}

	public function deleteCountdown($id){
		$this->deletePrice($id);

		$id = intval($id);
		global $db;
		$table = $db->select_table('productcountdown');
		$table->delete(array('id' => $id));
		return $db->affected_rows;
	}

	static public function Storages($storageids, $simple = true){
		global $db;
		$table = $db->select_table('productstorage');

		$condition = array();

		if(ProductStorage::IsBookingMode()){
			$condition[] = 'mode!='.ProductStorage::BookingMode;
		}

		if($storageids){
			$condition[] = 'id IN ('.implode(',', $storageids).')';
		}

		if($condition){
			$condition = implode(' AND ', $condition);
		}else{
			$condition = '1';
		}

		if(!$simple){
			return $table->fetch_all('*', $condition);
		}

		$storages = array();
		foreach($table->fetch_all('id,num', $condition) as $s){
			$storages[$s['id']] = intval($s['num']);
		}
		return $storages;
	}

	static public function QuantityLimits($priceids){
		global $_G, $db, $tpre;
		$limit = array();
		if($_G['user']->isLoggedIn()){
			$query = $db->query("SELECT priceid,amount FROM {$tpre}productquantitylimit WHERE userid={$_G['user']->id}");
			while($l = $query->fetch_assoc()){
				$limit[intval($l['priceid'])] = intval($l['amount']);
			}
		}
		return $limit;
	}

	public function getStorages(){
		if($this->id <= 0){
			return array();
		}

		global $db;
		$table = $db->select_table('productstorage');
		return $table->fetch_all('*', 'productid='.$this->id);
	}

	public function editStorage($storage){
		if($this->id <= 0){
			return false;
		}

		$id = isset($storage['id']) ? intval($storage['id']) : 0;

		$attr = array();
		if(isset($storage['remark'])){
			$attr['remark'] = trim($storage['remark']);
		}

		if(isset($storage['mode'])){
			$attr['mode'] = intval($storage['mode']);
		}

		global $db, $tpre;
		$table = $db->select_table('productstorage');

		if($id > 0){
			$productid = $this->id;
			$table->update($attr, array('id' => $id, 'productid' => $productid));
		}else{
			$attr['productid'] = $this->id;

			$table->insert($attr);
			$attr['id'] = $table->insert_id();
		}

		return $attr;
	}

	public function deleteStorage($id){
		$id = intval($id);
		global $db;
		$table = $db->select_table('productstorage');
		$table->delete(array('id' => $id));
		$result = $db->affected_rows;
		if($result > 0){
			$table = $db->select_table('productprice');
			$table->update(array('storageid' => NULL), array('productid' => $this->id, 'storageid' => $id));
		}
	}

	public function toArray(){
		if($this->id > 0){
			$attr = parent::toArray();
			$attr['photo'] = $this->getImage('photo');
			$attr['icon'] = $this->getImage('icon');
			$attr['text_color'] = isset($attr['text_color']) ? dechex($attr['text_color']) : 0x000000;
			$attr['background_color'] = isset($attr['background_color']) ? dechex($attr['background_color']) : 0xFFFFFF;
			$attr['icon_background'] = isset($attr['icon_background']) ? dechex($attr['icon_background']) : 0xC8E0AA;
			return $attr;
		}else{
			return array(
				'id' => 0,
				'name' => '',
				'type' => 1,
				'introduction' => '',
				'text_color' => '#000',
				'background_color' => '#FFF',
				'icon_background' => '#FFF',
				'prices' => array(),
			);
		}
	}

	static public function Delete($id, $extra = ''){
		parent::Delete($id, $extra);

		global $db, $tpre;
		$id = intval($id);
		$condition = 'productid='.$id;

		$db->query("DELETE FROM {$tpre}productcountdown WHERE id IN (SELECT id FROM {$tpre}productprice WHERE productid=$id)");

		$table = $db->select_table('productprice');
		$table->delete($condition);
		$table = $db->select_table('productstorage');
		$table->delete($condition);
	}

	static private $Types = NULL;
	static public function Types($typeid = -1){
		if(self::$Types === NULL){
			self::$Types = readcache('producttypes');
			if(self::$Types === NULL){
				global $db;
				$table = $db->select_table('producttype');
				$types = $table->fetch_all('*', 'hidden=0 ORDER BY displayorder');
				self::$Types = array();
				foreach($types as $type){
					self::$Types[$type['id']] = $type['name'];
				}

				writecache('producttypes', self::$Types);
			}
		}

		if($typeid == -1){
			return self::$Types;
		}else{
			return isset(self::$Types[$typeid]) ? self::$Types[$typeid] : '';
		}
	}

	static public function AvailableTypes(){
		global $_G;
		if(!isset($_G['admin']) || !($_G['admin'] instanceof Administrator))
			return array();

		$types = Product::Types();
		if($_G['admin']->producttypes){
			$typeids = explode(',', $_G['admin']->producttypes);
			foreach($types as $typeid => $typename){
				if(!in_array($typeid, $typeids))
					unset($types[$typeid]);
			}
		}
		return $types;
	}

	static public function RefreshCache(){
		writecache('productunits', NULL);
		writecache('producttypes', NULL);
	}

	static private function Units($type){
		$units = readcache('productunits');

		if($units === NULL){
			global $db;
			$table = $db->select_table('productunit');
			$units = array();
			foreach($table->fetch_all('id,name,type', 'hidden=0') as $u){
				$units[$u['type']][$u['id']] = $u['name'];
			}

			writecache('productunits', $units);
		}

		return isset($units[$type]) ? $units[$type] : NULL;
	}

	static private $AmountUnits = null;
	static public function AmountUnits($id = -1){
		if(self::$AmountUnits === null){
			self::$AmountUnits = self::Units(2);
		}

		if($id == -1){
			return self::$AmountUnits;
		}elseif(!isset(self::$AmountUnits[$id])){
			return '';
		}else{
			return self::$AmountUnits[$id];
		}
	}

	static public $PriceUnit;
	static private $PriceUnits = null;
	static public function PriceUnits($id = -1){
		if(self::$PriceUnits === null){
			self::$PriceUnits = self::Units(1);
		}
		if($id == -1){
			return self::$PriceUnits;
		}elseif(!isset(self::$PriceUnits[$id])){
			return '';
		}else{
			return self::$PriceUnits[$id];
		}
	}

	static public function PriceLimits($priceids){
		$result = array();
		if($priceids){
			global $db;
			$table = $db->select_table('productpricelimit');
			$limits = $table->fetch_all('priceid,usergroupid', 'priceid IN ('.implode(',', $priceids).')');
			foreach($limits as $l){
				isset($result[$l['priceid']]) || $result[$l['priceid']] = array();
				$result[$l['priceid']][] = $l['usergroupid'];
			}
		}
		return $result;
	}
}

$priceunits = Product::PriceUnits();
Product::$PriceUnit = is_array($priceunits) ? current($priceunits) : '';
