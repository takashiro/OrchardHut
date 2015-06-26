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

if(!defined('IN_ADMINCP')) exit('access denied');

class SqlTableColumn{
	public $name;
	public $type;
	public $accept_null;
	public $default_value;
	public $extra;
}

class SqlTable{
	public $name;
	public $columns = array();
	public $engine;
	public $charset;
	public $index = array();
	public $constraint = array();

	private $is_valid = false;

	public function isValid(){
		return $this->is_valid;
	}

	public function parse($sentence){
		if(!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`(\w+)`\s+\((.*?)\)\s+ENGINE\=(MyISAM|InnoDB)\s+DEFAULT\s+CHARSET\=(\w+)/is', $sentence, $matches))
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

		return true;
	}
}

class DatabaseModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'system';
	}

	public function defaultAction(){
		$standard_tables = $this->getStandardStructure();
		$current_tables = $this->getCurrentStructure();

		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$standard_update_time = filemtime(S_ROOT.'./install/install.sql');
		include view('database');
	}

	public function getStandardStructure(){
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

	public function getCurrentStructure(){
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
		}

		$charsets = $db->fetch_all("SHOW CHARACTER SET");
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
