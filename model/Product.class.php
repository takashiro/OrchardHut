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
			if($attr == 'icon'){
				if($ext == 'png'){
					$image = imagecreatefrompng($dest_path);
				}
				$rgb = imagecolorsforindex($image, imagecolorat($image, 0, 0));
				$this->icon_background = ($rgb['red'] << 16) + ($rgb['green'] << 8) + $rgb['blue'];
			}

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

	public function getFilteredPrices(){
		if($this->id <= 0){
			return array();
		}

		global $db, $tpre;
		$productid = $this->id;
		$now = TIMESTAMP;

		$prices = array();

		$query = $db->query("SELECT c.*,p.*,c.id AS is_countdown
			FROM {$tpre}productprice p
				LEFT JOIN {$tpre}productcountdown c ON c.id=p.id
			WHERE p.productid=$productid AND (c.id IS NULL OR (c.start_time<=$now AND c.end_time>=$now))
			ORDER BY p.displayorder");
		while($p = $db->fetch_array($query)){
			$prices[$p['id']] = $p;
		}

		foreach($prices as &$p){
			$p['priceunit'] = self::PriceUnits($p['priceunit']);
			$p['amountunit'] = self::AmountUnits($p['amountunit']);
			$p['price'] = floatval($p['price']);
			$p['amount'] = floatval($p['amount']);
			unset($prices[$p['masked_priceid']]);
		}
		unset($p);

		return $prices;
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
					$p['priceunit'] = self::PriceUnits($p['priceunit']);
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

			if(isset($price['displayorder'])){
				$update['displayorder'] = intval($price['displayorder']);
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
				'displayorder' => intval($price['displayorder']),
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
					$p['priceunit'] = self::PriceUnits($p['priceunit']);
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
		@$id = intval($countdown['id']);

		$price = $this->editPrice($countdown);

		global $db;
		$db->select_table('productcountdown');
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

			$db->UPDATE($update, array('id' => $id));
			return array_merge($price, $update);
		}else if(!empty($price['id'])){
			@$countdown = array(
				'id' => $price['id'],
				'masked_priceid' => intval($countdown['masked_priceid']),
				'start_time' => rstrtotime($countdown['start_time']),
				'end_time' => rstrtotime($countdown['end_time']),
			);
			$db->INSERT($countdown);

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
		$db->select_table('productcountdown');
		$db->DELETE(array('id' => $id));
		return $db->affected_rows();
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
				'type' => 0,
				'introduction' => '',
				'text_color' => '#000',
				'background_color' => '#FFF',
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
		if($id == -1){
			return self::$AmountUnits;
		}elseif(!isset(self::$AmountUnits[$id])){
			return '';
		}else{
			return self::$AmountUnits[$id];
		}
	}

	static private $PriceUnits = null;
	static public function PriceUnits($id = -1){
		if(self::$PriceUnits == null){
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
}

?>
