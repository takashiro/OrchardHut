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

class Database extends mysqli{
	protected $table_prefix;
	protected $query_num = 0;
	protected $table_objects = array();

	function query_num(){
		return $this->query_num;
	}

	function query($sql, $result_mode = MYSQLI_STORE_RESULT){
		$query = parent::query($sql, $result_mode);
		if (!$query){
			trigger_error($this->error, E_USER_ERROR);
		}
		$this->query_num++;
		return $query;
	}

	function set_table_prefix($prefix){
		$this->table_prefix = $prefix;
	}

	function select_table($table_name){
		if(empty($this->table_objects[$table_name])){
			$table = new DatabaseTable($this->table_prefix.$table_name, $this);
			$this->table_objects[$table_name] = $table;
			return $table;
		}else{
			return $this->table_objects[$table_name];
		}
	}

	function fetch_first($sql){
		$query = $this->query($sql);
		return $query->fetch_assoc();
	}

	function fetch_all($sql){
		$query = $this->query($sql);
		return $query->fetch_all(MYSQLI_ASSOC);
	}

	function fetch_row($sql){
		$query = $this->query($sql);
		return $query->fetch_row();
	}

	function result_first($sql){
		$row = $this->fetch_row($sql);
		return $row[0];
	}
}

?>
