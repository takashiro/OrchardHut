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

class DatabaseTable{

	protected $table_name;
	protected $db;

	function __construct($table_name, $db){
		$this->table_name = $table_name;
		$this->db = $db;
	}

	function name(){
		return $this->table_name;
	}

	function insert_id(){
		return $this->db->insert_id;
	}

	function affected_rows(){
		return $this->db->affected_rows;
	}

	function select($fields, $condition = '1'){
		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		is_array($fields) && $fields = '`'.implode('`,`', $fields).'`';
		return $this->db->query("SELECT $fields FROM `{$this->table_name}` WHERE $condition");
	}

	function fetch_first($fields, $condition = '1'){
		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		is_array($fields) && $fields = '`'.implode('`,`', $fields).'`';
		$query = $this->db->query("SELECT $fields FROM `{$this->table_name}` WHERE $condition");
		return $query->fetch_assoc();
	}

	function fetch_all($fields, $condition = '1'){
		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		is_array($fields) && $fields = '`'.implode('`,`', $fields).'`';
		$query = $this->db->query("SELECT $fields FROM `{$this->table_name}` WHERE $condition");
		return $query->fetch_all(MYSQLI_ASSOC);
	}

	function result_first($field, $condition = '1'){
		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		$query = $this->db->query("SELECT $field FROM `{$this->table_name}` WHERE $condition");
		return $query->fetch_row()[0];
	}

	function update($node, $condition = '1', $priority = ''){
		if(empty($node)){
			return false;
		}

		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		$sql = array();
		foreach($node as $k => $v){
			if($v !== NULL){
				$v = raddslashes($v);
				$sql[] = "`$k`='$v'";
			}else{
				$sql[] = "`$k`=NULL";
			}
		}
		$sql = implode(',', $sql);
		$priority = '' ? '' : 'LOW_PRIORITY';
		return $this->db->query("UPDATE $priority `{$this->table_name}` SET $sql WHERE $condition");
	}

	function insert($node, $replace = false, $extra = ''){
		$action = $replace ? 'REPLACE' : 'INSERT';
		$fields = implode('`,`',array_keys($node));

		$values = array_values($node);
		$values = raddslashes($values);
		foreach($values as &$value){
			if($value !== NULL){
				$value = '\''.$value.'\'';
			}else{
				$value = 'NULL';
			}
		}
		unset($value);
		$values = implode(',', $values);

		return $this->db->query("$action $extra INTO `{$this->table_name}` (`$fields`) VALUES ($values)");
	}

	function multi_insert($nodes, $replace = false, $extra = ''){
		if(!$nodes || !is_array($nodes)){
			return false;
		}

		$action = $replace ? 'REPLACE' : 'INSERT';

		$nodes = array_values($nodes);
		$nodes = raddslashes($nodes);

		$fields = array_keys($nodes[0]);

		$values = array();
		foreach($nodes as $n){
			$v = array();
			foreach($fields as $f){
				$v[] = $n[$f];
			}
			$values[] = '\''.implode('\',\'', $v).'\'';
		}

		$values = '('.implode('),(', $values).')';
		$fields = implode('`,`', $fields);
		return $this->db->query("$action $extra INTO `{$this->table_name}` (`$fields`) VALUES $values");
	}

	function delete($condition, $priority = 'LOW_PRIORITY'){
		if(is_array($condition)){
			$condition = self::array_to_condition($condition);
		}

		if($condition == '1'){
			return $this->db->query("TRUNCATE `{$this->table_name}`");
		}else{
			return $this->db->query("DELETE $priority FROM `{$this->table_name}` WHERE $condition");
		}
	}

	static protected function array_to_condition($conditions = array()){
		if(!$conditions){
			return '1';
		}

		$sql = array();
		foreach($conditions as $field => $value){
			$value = raddslashes($value);
			$sql[] = "`{$field}`='$value'";
		}
		$sql = implode(' AND ', $sql);

		return $sql;
	}
}

?>
