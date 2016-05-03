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

class Address{
	const MAX_CASCADE_LEVEL = 8;

	static public function RefreshCache(){
		self::$AvailableComponents = self::Components();
		foreach(self::$AvailableComponents as $cid => $c){
			if($c['hidden']){
				unset(self::$AvailableComponents[$cid]);
			}else{
				$cur = $c['parentid'];
				while($cur){
					if(!isset(self::$AvailableComponents[$cur])){
						unset(self::$AvailableComponents[$cid]);
						break;
					}

					$p = self::$AvailableComponents[$cur];
					if($p['hidden']){
						unset(self::$AvailableComponents[$cid]);
						break;
					}

					$cur = $p['parentid'];
				}
			}
		}

		writecache('addresscomponent', self::$AvailableComponents);
		file_put_contents(S_ROOT.'data/js/addresscomponent.js', 'var addresscomponent = '.json_encode(self::$AvailableComponents).';');
	}

	static private $Format = null;
	static public function Format(){
		if(self::$Format === null){
			self::$Format = readdata('addressformat');
		}
		return self::$Format;
	}

	static private $Components = null;
	static public function Components(){
		if(self::$Components === null){
			global $db;
			$table = $db->select_table('addresscomponent');
			self::$Components = array();
			$components = array();
			foreach($table->fetch_all('*', '1 ORDER BY displayorder,id') as $c){
				$components[$c['id']] = $c;
			}
			foreach($components as $c){
				$parents = array();
				$cur = $c['parentid'];
				while($cur){
					array_unshift($parents, $cur);
					$cur = $components[$cur]['parentid'];
				}
				$c['parents'] = $parents;
				self::$Components[$c['id']] = $c;
			}
		}
		return self::$Components;
	}

	static private $AvailableComponents = null;
	static public function AvailableComponents(){
		if(self::$AvailableComponents === null){
			self::$AvailableComponents = readcache('addresscomponent');
			if(self::$AvailableComponents === null){
				self::RefreshCache();
			}
		}
		return self::$AvailableComponents;
	}

	static public function FindComponentById($id){
		$components = self::AvailableComponents();
		return isset($components[$id]) ? $components[$id] : array();
	}

	static public function Extension($limitation_addressids){
		is_array($limitation_addressids) || $limitation_addressids = array($limitation_addressids);

		if($limitation_addressids){
			global $db;
			$curids = $limitation_addressids;
			for($i = 0; $i < self::MAX_CASCADE_LEVEL; $i++){
				$table = $db->select_table('addresscomponent');
				$rows = $table->fetch_all('id', 'parentid IN ('.implode(',', $curids).')');
				if(!$rows){
					break;
				}
				$curids = array();
				foreach($rows as $r){
					$limitation_addressids[] = $r['id'];
					$curids[] = $r['id'];
				}
			}
			return $limitation_addressids;
		}else{
			return array();
		}
	}

	static public function FullPath($curid){
		$components = self::Components();
		$path = array();
		while($curid){
			if(empty($components[$curid])){
				break;
			}
			$c = $components[$curid];
			array_unshift($path, array('id' => $c['id'], 'name' => $c['name']));
			$curid = $c['parentid'];
		}
		return $path;
	}

	static public function FullPathString($address){
		is_array($address) || $address = self::FullPath($address);
		$str = array();
		foreach($address as $c){
			$str[] = $c['name'];
		}
		return implode(' ', $str);
	}

	static public function FullPathIds($curid){
		$components = self::Components();
		$path = array();
		while($curid){
			if(empty($components[$curid])){
				break;
			}
			$c = $components[$curid];
			$path[] = $c['id'];
			$curid = $c['parentid'];
		}
		return implode(',', $path);
	}

	static public function MinRange($field, $curid){
		$fullpath = self::FullPath($curid);
		$sql = "CASE `$field`";

		$weight = count($fullpath) - 1;
		foreach($fullpath AS $c){
			$sql.= " WHEN {$c['id']} THEN $weight";
			$weight--;
		}
		$sql.= ' ELSE '.count($fullpath);
		$sql.= ' END';
		return $sql;
	}
}
