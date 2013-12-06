<?php

class DBObject{
	protected $attr = array();
	protected $oattr = array();
	protected $update = array();

	const PRIMARY_KEY = 'id';

	function __destruct(){
		global $db;

		$db->select_table(static::TABLE_NAME);

		$id = $this->attr(static::PRIMARY_KEY);
		if($id > 0){
			if($this->oattr){
				foreach($this->oattr as $key => $value){
					if($value != $this->attr[$key]){
						$this->update[$key] = $this->attr[$key];
					}
				}
			}

			if($this->update){
				$db->UPDATE($this->update, static::PRIMARY_KEY.'=\''.$this->attr(static::PRIMARY_KEY).'\'');
			}
		}
	}

	function fetchAttributesFromDB($item, $condition){
		global $db;
		if(is_array($item)){
			$item = implode(',', $item);
		}
		if(is_array($condition)){
			$c = array();
			foreach($condition as $attr => $value){
				$c[] = '`'.$attr.'`=\''.$value.'\'';
			}
			$condition = implode(' AND ', $c);
		}

		$db->select_table(static::TABLE_NAME);
		$this->attr = $this->oattr = $db->FETCH($item, $condition);
	}

	public function exists(){
		return !empty($this->attr[static::PRIMARY_KEY]);
	}

	public function toArray(){
		return $this->attr;
	}

	public function toReadable(){
		return $this->attr;
	}

	public function __get($attr){
		return isset($this->attr[$attr]) ? $this->attr[$attr] : null;
	}

	public function __set($attr, $value){
		return $this->attr[$attr] = $value;
	}

	public function __isset($attr){
		return isset($this->attr[$attr]);
	}

	public function attr($attr, $value = null){
		if($value === null){
			return isset($this->attr[$attr]) ? $this->attr[$attr] : null;
		}else{
			$this->attr[$attr] = $value;
		}
	}

	public function update($attr, $value){
		$this->update[$attr] = $value;
	}

	public function insert(){
		global $db;
		$db->select_table(static::TABLE_NAME);
		$db->INSERT($this->attr);

		$this->attr(static::PRIMARY_KEY, $db->insert_id());
		return $this->attr(static::PRIMARY_KEY);
	}

	public function deleteFromDB(){
		global $db;
		$db->select_table(static::TABLE_NAME);
		$db->DELETE(array(static::PRIMARY_KEY => $this->attr(static::PRIMARY_KEY)));
		$this->attr = $this->oattr = array();
	}

	static public function Delete($id){
		global $db;
		$id = intval($id);
		$db->select_table(static::TABLE_NAME);
		$db->DELETE(static::PRIMARY_KEY.'='.$id);
	}

	static public function Exist($id, $field = ''){
		global $db;

		if(!$field){
			$field = static::PRIMARY_KEY;
		}

		$db->select_table(static::TABLE_NAME);
		return $db->RESULTF($field, '`'.$field.'`=\''.$id.'\' LIMIT 1');
	}

	static public function Count(){
		global $db;
		$db->select_table(static::TABLE_NAME);
		return $db->RESULTF('COUNT(*)');
	}
}

?>
