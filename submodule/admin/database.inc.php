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

if(!defined('IN_ADMINCP')) exit('access denied');

class SqlTableColumn{
	public $name;
	public $type;
	public $accept_null;
	public $default_value;
	public $extra;

	public function toSql(){
		if($this->accept_null){
			if($this->default_value == 'NULL'){
				$ext_sql = 'DEFAULT NULL';
			}else{
				$ext_sql = 'NULL DEFAULT \''.$this->default_value.'\'';
			}
		}else{
			$ext_sql = 'NOT NULL';
			if($this->default_value){
				$ext_sql.= ' DEFAULT \''.$this->default_value.'\'';
			}
		}

		return "`{$this->name}` {$this->type} $ext_sql {$this->extra}";
	}
}

class SqlTable{
	public $name;
	public $columns = array();
	public $engine;
	public $charset;
	public $primary_key = array();
	public $unique_keys = array();
	public $indexes = array();
	public $constraints = array();

	private $is_valid = false;

	public function isValid(){
		return $this->is_valid;
	}

	public function parse($sentence){
		if(!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`(\w+)`\s+(\(.*?\))\s+ENGINE\=(MyISAM|InnoDB)\s+DEFAULT\s+CHARSET\=(\w+)/is', $sentence, $matches))
			return;

		if(!$this->parseColumns($matches[2]))
			return;

		$this->name = $matches[1];
		$this->engine = $matches[3];
		$this->charset = $matches[4];
		$this->is_valid = true;
	}

	private function parseColumns($columns){
		preg_match_all('/`(\w+)`\s+(\w+(?:\(\d+(?:,\d+)*\))?(?:\s+(?:unsigned|unsigned))?)(?:\s+((?:NOT\s+)?NULL))?(?:\s+DEFAULT\s+(\'.*?\'|NULL|CURRENT_TIMESTAMP))?(?:\s+(AUTO_INCREMENT))?\s*[,)]/i', $columns, $matches);

		$column_num = count($matches[0]);
		for($i = 0; $i < $column_num; $i++){
			$c = new SqlTableColumn;
			$c->name = $matches[1][$i];
			$c->type = $matches[2][$i];
			$c->accept_null = strcasecmp($matches[3][$i], 'NULL') == 0;
			$c->default_value = trim($matches[4][$i], '\' ');
			if(strcasecmp($c->default_value, 'NULL') == 0){
				$c->accept_null = true;
			}
			$c->extra = strtolower($matches[5][$i]);

			$this->columns[$c->name] = $c;
		}

		preg_match_all('/PRIMARY\s+KEY\s*\(\s*(`\w+`(?:\s*,\s*`\w+`)*)\s*\)\s*[,)]/i', $columns, $matches);
		if(!empty($matches[1])){
			$this->primary_key = explode(',', $matches[1][0]);
			foreach($this->primary_key as &$field){
				$field = trim($field, '` ');
			}
			unset($field);
		}

		preg_match_all('/((?:UNIQUE\s+)?KEY)\s+`(\w+)`\s*\(\s*(`\w+`(?:\s*,\s*`\w+`)*)\s*\)\s*[,)]/i', $columns, $matches);
		$key_num = count($matches[0]);
		for($i = 0; $i < $key_num; $i++){
			$type = $matches[1][$i];
			$name = $matches[2][$i];
			$fields = explode(',', $matches[3][$i]);
			foreach($fields as &$field){
				$field = trim($field, '` ');
			}
			unset($field);

			if(strcasecmp($type,'KEY') == 0){
				$this->indexes[$name] = $fields;
			}else{
				$this->unique_keys[$name] = $fields;
			}
		}

		return true;
	}

	public function toSql(){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$this->name.'` (';

		$columns = array();
		foreach($this->columns as $c){
			$columns[] = $c->toSql();
		}

		if($this->primary_key){
			$columns[] = 'PRIMARY KEY (`'.implode('`,`', $this->primary_key).'`)';
		}

		foreach($this->unique_keys as $name => $keys){
			$columns[] = 'UNIQUE KEY `'.$name.'` (`'.implode('`,`', $keys).'`)';
		}

		foreach($this->indexes as $name => $keys){
			$columns[] = 'KEY `'.$name.'` (`'.implode('`,`', $keys).'`)';
		}

		$sql.= implode(',', $columns);
		$sql.= ') ENGINE='.$this->engine.' DEFAULT CHARSET='.$this->charset;

		return $sql;
	}
}

class DatabaseModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'system';
	}

	public function defaultAction(){
		$standard_tables = self::GetStandardStructure();
		$current_tables = self::GetCurrentStructure();
		$overhead_tables = self::GetAllTableOverhead();

		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$standard_update_time = filemtime(S_ROOT.'./install/install.sql');

		$expiry = TIMESTAMP - Authkey::$ExpiryTime;
		$expired_authkey_num = $db->result_first("SELECT COUNT(*) FROM {$tpre}authkey WHERE `expiry`<$expiry");

		include view('database');
	}

	public function clearExpiredAuthkeyAction(){
		global $db, $tpre;
		$expiry = TIMESTAMP - Authkey::$ExpiryTime;
		$db->query("DELETE FROM {$tpre}authkey WHERE `expiry`<$expiry");
		showmsg('sucessfully_cleared_authkeys', 'refresh');
	}

	public function optimizeTableAction(){
		if(!empty($_POST['tables']) && is_array($_POST['tables'])){
			global $db;
			foreach($_POST['tables'] as $table_name => $on){
				$table_name = raddslashes($table_name);
				$db->query("OPTIMIZE TABLE `$table_name`");
			}
		}
		showmsg('successfully_optimized_tables', 'refresh');
	}

	public function dropTableAction(){
		self::CheckTargetTable($s, $t);

		if($s === null && $t !== null){
			global $db;
			$db->query("DROP TABLE `{$s->name}`");
			showmsg('successfully_dropped_table', 'refresh');
		}else{
			showmsg('failed_to_drop_table', 'back');
		}
	}

	public function alterTableAction(){
		self::CheckTargetTable($s, $t);

		if($s !== null && $t !== null){
			global $db;
			$db->query("ALTER TABLE `{$s->name}` ENGINE={$s->engine} DEFAULT CHARSET={$s->charset}");
			showmsg('successfully_altered_table', 'refresh');
		}else{
			showmsg('failed_to_alter_table', 'back');
		}
	}

	public function createTableAction(){
		self::CheckTargetTable($s, $t);

		if($s !== null && $t === null){
			global $db;
			$db->query($s->toSql());
			showmsg('successfully_added_table', 'refresh');
		}else{
			showmsg('failed_to_add_table', 'back');
		}
	}

	private static function CheckTargetTable(&$s, &$t){
		if(!isset($_GET['table']))
			showmsg('illegal_operation', 'back');

		$table = trim($_GET['table']);

		$standard_tables = self::GetStandardStructure();
		$current_tables = self::GetCurrentStructure();

		if(isset($standard_tables[$table])){
			$s = $standard_tables[$table];
		}else{
			$s = null;
		}

		if(isset($current_tables[$table])){
			$t = $current_tables[$table];
		}else{
			$t = null;
		}
	}

	public function dropColumnAction(){
		self::CheckTargetColumn($table, $column, $s, $t);

		if($t !== null && $s === null){
			global $db;
			$db->query("ALTER TABLE `$table` DROP COLUMN `$column`");
			showmsg('successfully_dropped_column', 'refresh');
		}else{
			showmsg('failed_to_drop_column', 'back');
		}
	}

	public function alterColumnAction(){
		self::CheckTargetColumn($table, $column, $s, $t);

		if($t !== null && $s !== null && $t != $s){
			$subsql = $s->toSql();
			global $db;
			$db->query("ALTER TABLE `$table` CHANGE `$column` $subsql");
			showmsg('successfully_altered_column', 'refresh');
		}else{
			showmsg('failed_to_alter_column', 'back');
		}
	}

	public function addColumnAction(){
		self::CheckTargetColumn($table, $column, $s, $t);

		if($t === null && $s !== null){
			$subsql = $s->toSql();
			global $db;
			$db->query("ALTER TABLE `$table` ADD $subsql");
			showmsg('successfully_added_column', 'refresh');
		}else{
			showmsg('failed_to_add_column', 'back');
		}
	}

	public function changePrimaryKeyAction(){
		self::CheckTargetTable($s, $t);

		global $db, $tpre;
		if($t->primary_key){
			$db->query("ALTER TABLE `{$t->name}` DROP PRIMARY KEY");
		}

		$primary_key = implode('`,`', $s->primary_key);
		$db->query("ALTER TABLE `{$t->name}` ADD PRIMARY KEY (`$primary_key`)");
		showmsg('successfully_changed_primary_key', 'refresh');
	}

	public function changeKeyAction(){
		self::CheckTargetKey($table, $key, $keytype, $s, $t);

		global $db;
		if($t){
			$db->query("ALTER TABLE `$table` DROP INDEX `$key`");
		}

		if($s){
			$columns = implode('`,`', $s);
			$db->query("ALTER TABLE `$table` ADD $keytype `$key` (`$columns`)");
		}

		showmsg('successfully_changed_key', 'refresh');
	}

	private static function CheckTargetColumn(&$table, &$column, &$s, &$t){
		self::CheckTargetTable($s, $t);
		if($s === null || $t === null){
			showmsg('illegal_operation', 'back');
		}

		if(empty($_GET['column'])){
			showmsg('illegal_operation', 'back');
		}

		$column = trim($_GET['column']);
		$table = $s->name;

		if(isset($s->columns[$column])){
			$s = $s->columns[$column];
		}else{
			$s = null;
		}

		if(isset($t->columns[$column])){
			$t = $t->columns[$column];
		}else{
			$t = null;
		}
	}

	private static function CheckTargetKey(&$table, &$key, &$keytype, &$s, &$t){
		self::CheckTargetTable($s, $t);
		if($s === null || $t === null){
			showmsg('illegal_operation', 'back');
		}

		if(empty($_GET['key']) || empty($_GET['keytype']))
			showmsg('illegal_operation', 'back');
		$keytype = trim($_GET['keytype']);
		$key = trim($_GET['key']);
		$table = $s->name;

		if($keytype == 'index'){
			$keytype = 'INDEX';
			$s = isset($s->indexes[$key]) ? $s->indexes[$key] : null;
			$t = isset($t->indexes[$key]) ? $t->indexes[$key] : null;
		}elseif($keytype == 'unique'){
			$keytype = 'UNIQUE';
			$s = isset($s->unique_keys[$key]) ? $s->unique_keys[$key] : null;
			$t = isset($t->unique_keys[$key]) ? $t->unique_keys[$key] : null;
		}else{
			showmsg('illegal_operation', 'back');
		}
	}

	public static function GetAllTableOverhead(){
		global $db, $_CONFIG;
		return $db->fetch_all("SELECT table_name,engine,data_free FROM `information_schema`.`tables` WHERE table_schema='{$_CONFIG['db']['name']}' AND data_free>0");
	}

	public static function GetStandardStructure(){
		$sql = file_get_contents(S_ROOT.'./install/install.sql');
		$sql = explode(';', $sql);

		$standard_tables = array();

		foreach($sql as $sentence){
			$t = new SqlTable;
			$t->parse($sentence);
			if($t->isValid()){
				$standard_tables[$t->name] = $t;
			}
			unset($t);
		}

		return $standard_tables;
	}

	public static function GetCurrentStructure(){
		$current_tables = array();

		global $db, $tpre;
		$query = $db->query("SHOW TABLES");
		while($table = $query->fetch_array()){
			$table_name = $table[0];

			$t = new SqlTable;
			$current_tables[$table_name] = $t;

			$t->name = $table_name;

			$config = $db->fetch_first("SHOW TABLE STATUS WHERE name='{$table_name}'");
			$t->engine = $config['Engine'];
			$t->charset = $config['Collation'];

			$columns = $db->fetch_all("SHOW COLUMNS FROM `{$table_name}`");

			foreach($columns as $column){
				$c = new SqlTableColumn;
				$c->name = $column['Field'];
				$c->type = $column['Type'];
				$c->accept_null = $column['Null'] != 'NO';
				$c->default_value = $column['Default'];
				$c->extra = $column['Extra'];

				if($c->accept_null && $c->default_value === null){
					$c->default_value = 'NULL';
				}

				$t->columns[$c->name] = $c;
			}

			$indexes = $db->fetch_all("SHOW INDEX FROM `{$table_name}`");
			foreach($indexes as $index){
				if($index['Key_name'] == 'PRIMARY'){
					$t->primary_key[] = $index['Column_name'];
				}else{
					if($index['Non_unique']){
						$t->indexes[$index['Key_name']][] = $index['Column_name'];
					}else{
						$t->unique_keys[$index['Key_name']][] = $index['Column_name'];
					}
				}
			}
		}

		$charsets = $db->fetch_all('SHOW CHARACTER SET');
		$collation_to_charset = array();
		foreach($charsets as $c){
			$collation_to_charset[$c['Default collation']] = $c['Charset'];
		}

		foreach($current_tables as $t){
			if(isset($collation_to_charset[$t->charset])){
				$t->charset = $collation_to_charset[$t->charset];
			}
		}

		return $current_tables;
	}

}

?>
