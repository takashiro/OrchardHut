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

abstract class DBObject{
	protected $attr = array();
	protected $oattr = array();
	protected $update = array();
	protected $table = null;

	const PRIMARY_KEY = 'id';

	static function table(){
		global $db;
		return $db->select_table(static::TABLE_NAME);
	}

	function __construct(){
		global $db;
		$this->table = self::table();
	}

	function __destruct(){
		$id = $this->attr(static::PRIMARY_KEY);
		if($id > 0){
			if($this->oattr){
				foreach($this->oattr as $key => $value){
					if($value !== $this->attr[$key]){
						$this->update[$key] = $this->attr[$key];
					}
				}
			}

			if($this->update){
				$this->table->update($this->update, static::PRIMARY_KEY.'=\''.$this->attr(static::PRIMARY_KEY).'\'');
			}
		}
	}

	function fetch($item, $condition){
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

		$this->attr = $this->oattr = $this->table->fetch_first($item, $condition);
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

	public function insert($extra = ''){
		$this->table->insert($this->attr, false, $extra);

		$this->attr(static::PRIMARY_KEY, $this->table->insert_id());
		return $this->attr(static::PRIMARY_KEY);
	}

	public function deleteFromDB(){
		$this->table->delete(array(static::PRIMARY_KEY => $this->attr(static::PRIMARY_KEY)));
		$this->attr = $this->oattr = array();
	}

	static public function Delete($id, $extra = ''){
		$id = intval($id);

		if($extra){
			$extra = ' AND ('.$extra.')';
		}

		global $db;
		$table = $db->select_table(static::TABLE_NAME);
		$table->delete(static::PRIMARY_KEY.'='.$id.$extra);
		return $table->affected_rows();
	}

	static public function Exist($id, $field = ''){
		if(!$field){
			$field = static::PRIMARY_KEY;
		}

		global $db;
		$table = $db->select_table(static::TABLE_NAME);
		return $table->result_first($field, '`'.$field.'`=\''.$id.'\' LIMIT 1');
	}

	static public function Count(){
		global $db;
		$table = $db->select_table(static::TABLE_NAME);
		return $table->result_first('COUNT(*)');
	}
}

?>
