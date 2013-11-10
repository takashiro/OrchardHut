<?php

class Product extends DBObject{
	CONST TABLE_NAME = 'product';

	public function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			parent::fetchAttributesFromDB('*', 'id='.$id);
		}
	}

	function uploadImage($var, $attr = ''){
		if(!$attr){
			$attr = $var;
		}

		if($this->id && !empty($_FILES[$var]) && $_FILES[$var]['error'] == 0){
			$file_info = pathinfo($_FILES[$var]['name']);
			$ext = &$file_info['extension'];
			switch($ext){
				case 'jpg':case 'jpeg':
					$image = imagecreatefromjpeg($_FILES[$var]['tmp_name']);
					break;
				case 'bmp':
					$image = imagecreatefromwbmp($_FILES[$var]['tmp_name']);
					break;
				case 'gif':
					$image = imagecreatefromgif($_FILES[$var]['tmp_name']);
					break;
				case 'png':
					break;
				default:
					return false;
			}

			$dest_path = S_ROOT.'./data/attachment/product_'.$this->id.'_'.$attr.'.png';

			if($ext != 'png'){
				imagepng($image, $dest_path);
			}else{
				move_uploaded_file($_FILES[$var]['tmp_name'], $dest_path);
			}

			$this->$attr = 1;

			return true;
		}

		return false;
	}

	function hasImage($attr){
		return $this->$attr;
	}

	function getImage($attr){
		if(!empty($this->$attr)){
			return './data/attachment/product_'.$this->id.'_'.$attr.'.png';
		}else{
			return './view/default/image/product_unknown_icon.png';
		}
	}

	public function getPrices($to_readable = false){
		if($this->id > 0){
			global $db;
			$db->select_table('productprice');
			$prices = $db->MFETCH('*', 'productid='.$this->id);
			if($to_readable){
				foreach($prices as &$p){
					$p['priceunit'] = self::PriceUnits($p['priceunit']);
					$p['amountunit'] = self::AmountUnits($p['amountunit']);
				}
			}
			return $prices;
		}else{
			return array();
		}
	}

	public function editPrice($price){
		@$id = intval($price['id']);

		global $db;
		$db->select_table('productprice');
		if($id > 0){
			$update = array();

			if(isset($price['subtype'])){
				$update['subtype'] = $price['subtype'];
			}

			if(isset($price['price'])){
				$update['price'] = floatval($price['price']);
			}

			if(isset($price['priceunit'])){
				$update['priceunit'] = intval($price['priceunit']);
			}

			if(isset($price['amount'])){
				$update['amount'] = floatval($price['amount']);
			}

			if(isset($price['amountunit'])){
				$update['amountunit'] = intval($price['amountunit']);
			}

			$db->UPDATE($update, array('id' => $id, 'productid' => $this->id));
			return $update;
		}else{
			@$price = array(
				'productid' => $this->id,
				'subtype' => $price['subtype'],
				'price' => floatval($price['price']),
				'priceunit' => intval($price['priceunit']),
				'amount' => floatval($price['amount']),
				'amountunit' => intval($price['amountunit']),
			);
			$db->INSERT($price);
			$price['id'] = $db->insert_id();
			return $price;
		}
	}

	public function deletePrice($id){
		$id = intval($id);
		global $db;
		$db->select_table('productprice');
		$db->DELETE(array('id' => $id, 'productid' => $this->id));
		return $db->affected_rows();
	}

	public function toArray(){
		if($this->id > 0){
			$attr = parent::toArray();
			$attr['photo'] = $this->getImage('photo');
			$attr['icon'] = $this->getImage('icon');
			return $attr;
		}else{
			return array(
				'id' => 0,
				'name' => '',
				'type' => 0,
				'introduction' => '',
				'prices' => array(),
			);
		}
	}

	static private $Types = array('单卖', '套餐');
	static public function Types($typeid = -1){
		if($typeid == -1 || !array_key_exists($typeid, self::$Types)){
			return self::$Types;
		}else{
			return self::$Types[$typeid];
		}
	}

	static private function Units($type){
		global $db;
		$db->select_table('productunit');
		$units = array();
		foreach($db->MFETCH('id,name', 'type='.intval($type)) as $u){
			$units[$u['id']] = $u['name'];
		}
		return $units;
	}

	static private $AmountUnits = null;
	static public function AmountUnits($id = -1){
		if(self::$AmountUnits == null){
			self::$AmountUnits = self::Units(2);
		}
		if($id == -1 || !array_key_exists($id, self::$AmountUnits)){
			return self::$AmountUnits;
		}else{
			return self::$AmountUnits[$id];
		}
	}

	static private $PriceUnits = null;
	static public function PriceUnits($id = -1){
		if(self::$PriceUnits == null){
			self::$PriceUnits = self::Units(1);
		}
		if($id == -1 || !array_key_exists($id, self::$PriceUnits)){
			return self::$PriceUnits;
		}else{
			return self::$PriceUnits[$id];
		}
	}
}

?>
