<?php

class Database extends mysqli{
	protected $table_prefix;
	protected $query_num = 0;
	protected $table_objects = array();

	function query_num(){
		return $this->query_num;
	}

	function query($sql, $result_mode = MYSQLI_STORE_RESULT){
		$query = parent::query($sql, $result_mode);
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
