<?php

class DBObject{
	protected $attr = array();
	protected $oattr = array();
	protected $update = array();

	const PRIMARY_KEY = 'id';

	function __destruct(){
		global $db;

		//????
		if($this->oattr){
			foreach($this->oattr as $key => $value){
				if($value != $this->attr[$key]){
					$this->update[$key] = $this->attr[$key];
				}
			}
		}

		if($this->update){
			$db->select_table(static::TABLE_NAME);
			$db->UPDATE($this->update, static::PRIMARY_KEY.'=\''.$this->attr(static::PRIMARY_KEY).'\'');
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

	public function deleteFromDB(){
		DB::select_table(static::TABLE_NAME);
		DB::DELETE('id='.$this->id);  //调用了mysql类的函数
		$this->attr = $this->oattr = array();
	}

	static public function Exist($id){
		DB::select_table(static::TABLE_NAME);
		return DB::RESULTF(static::PRIMARY_KEY, '`'.static::PRIMARY_KEY.'`=\''.$id.'\'');
	}

	static public function Count(){
		global $db;
		$db->select_table(static::TABLE_NAME);
		return $db->RESULTF('COUNT(*)');
	}
}

?>
