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

class Administrator extends User{
	const TABLE_NAME = 'administrator';

	const COOKIE_VAR = 'rcadmininfo';

	static public $Permissions = array();

	public function __construct($id = 0){
		parent::__construct();
		if($id = intval($id)){
			$this->fetch('*', 'id='.$id);
			$this->initPermissions();
		}
	}

	public function __destruct(){
		if(isset($this->permissions) && is_array($this->permissions)){
			$this->permissions = implode('|', array_keys($this->permissions));
		}
		if(rdate($this->logintime, 'Ymd') != rdate(TIMESTAMP, 'Ymd')){
			$this->logintime = TIMESTAMP;
		}
		parent::__destruct();
	}

	public function getLimitations($extended = true){
		if(empty($this->limitation)){
			return array();
		}
		$limitation = explode(',', $this->limitation);
		$extended && $limitation = Address::Extension($limitation);
		return $limitation;
	}

	public function toArray(){
		if($this->id > 0){
			return parent::toArray();
		}else{
			return array(
				'id' => 0,
				'account' => '',
				'pwmd5' => '',
				'nickname' => '',
				'logintime' => '',
				'realname' => '',
				'mobile' => '',
			);
		}
	}

	public function login($account = '', $pw = '', $method = 'account'){
		global $db, $tpre, $_G;
		if(!$account){//Login by Cookie
			$cookie_var = !empty($_COOKIE[static::COOKIE_VAR]) ? $_COOKIE[static::COOKIE_VAR] : '';
			if(!empty($cookie_var)){
				$cookie = $this->decodeCookie($cookie_var);

				if(!isset($cookie['id']) || !isset($cookie['loginip'])){
					return false;
				}

				$cookie = array(
					'id' => intval($cookie['id']),
				);

				$this->fetch('*', $cookie);
				$this->initPermissions();
			}
			return $this->isLoggedIn();

		}elseif($pw){
			$condition = array(
				$method => $account,
				'pwmd5' => rmd5($pw),
			);

			$this->fetch('*', $condition);
			$this->initPermissions();

			if($this->isLoggedIn()){
				$this->logged = true;
				$cookie = array('id' => $this->attr['id'], 'loginip' => self::ip());
				rsetcookie(static::COOKIE_VAR, $this->encodeCookie($cookie));
				$this->logintime = TIMESTAMP;
				return true;
			}else{
				return false;
			}
		}
	}

	public function isSuperAdmin(){
		return $this->permissions === 'all';
	}

	static public function Register($admin){
		global $db;

		@$attr = array(
			'account' => raddslashes($admin['account']),
			'pwmd5' => rmd5($admin['password']),
		);

		$table = $db->select_table('administrator');

		if($table->result_first('id', array('account' => $attr['account'])) > 0){
			return self::DUPLICATED_ACCOUNT;
		}

		$table->insert($attr);

		return $table->insert_id();
	}

	public function changePassword($old, $new, $new2 = ''){
		if(!$this->attr('id')){
			return -3;
		}elseif(rmd5($old) != $this->attr('pwmd5')){
			return -1;
		}elseif($new2 && $new != $new2){
			return -2;
		}

		$this->pwmd5 = rmd5($new);

		return true;
	}

	public function initPermissions(){
		if(isset($this->permissions) && $this->permissions !== 'all'){
			$permissions = array();
			foreach(explode('|', $this->permissions) as $perm){
				$permissions[$perm] = true;
			}
			$this->permissions = $permissions;
		}
	}

	public function hasPermission($perm){
		if($this->isSuperAdmin()){
			return true;
		}

		if(!isset(self::$Permissions[$perm])){
			return false;
		}

		$config = self::$Permissions[$perm];
		if(!empty($config['alias'])){
			return $config['alias'] === 'public' || $this->hasPermission($config['alias']);
		}

		if(!isset($this->permissions) || !is_array($this->permissions)){
			return false;
		}

		if(isset($this->permissions[$perm])){
			return true;
		}

		if(!empty($config['children']) && is_array($config['children'])){
			foreach($config['children'] as $subperm){
				if(isset($this->permissions[$subperm])){
					return true;
				}
			}
		}

		return false;
	}

	public function setPermission($perm, $value = true){
		if($this->isSuperAdmin()){
			return;
		}

		if(!is_array($perm)){
			if(isset(self::$Permissions[$perm])){
				if($value){
					$permissions = $this->permissions;
					$permissions[$perm] = true;
					$this->permissions = $permissions;
				}else{
					unset($this->permissions[$perm]);
				}
			}
		}else{
			foreach($perm as $p){
				$this->setPermission($p, $value);
			}
		}
	}

	public function clearPermission(){
		$this->permissions = array();
	}

	static public function GetAllPermissions(){
		$permissions = array();
		foreach(self::$Permissions as $perm => $permconfig){
			$permissions[] = $perm;
			if(is_array($permconfig['children'])){
				foreach($permconfig['children'] as $subperm){
					$permissions[] = $subperm;
				}
			}
		}
		return $permissions;
	}

	static public function LoadPermissions(){
		self::$Permissions = readcache('admin_permissions');
		if(self::$Permissions === null){
			self::$Permissions = array();
			if($module_dir = opendir(S_ROOT.'submodule/admin')){
				global $_G;
				while($file = readdir($module_dir)){
					if(substr_compare($file, '.inc.php', -8) == 0){
						$module_name = substr($file, 0, strlen($file) - 8);
						$class_name = $module_name.'Module';
						require_once S_ROOT.'submodule/admin/'.$file;

						if(class_exists($class_name)){
							$module = new $class_name;
							self::$Permissions[$module_name] = array(
								'alias' => $module->getAlias(),
								'children' => $module->getPermissions(),
								'parents' => $module->getRequiredPermissions(),
							);
						}
					}
				}
				closedir($module_dir);

				foreach(self::$Permissions as $module_name => &$config){
					foreach($config['parents'] as $p){
						if(isset(self::$Permissions[$p]) && !in_array($module_name, self::$Permissions[$p]['children'])){
							self::$Permissions[$p]['children'][] = $module_name;
						}
					}

					foreach($config['children'] as $c){
						if(!isset(self::$Permissions[$c])){
							self::$Permissions[$c] = array(
								'alias' => '',
								'children' => array(),
								'parents' => array($module_name),
							);
						}
					}
				}
				unset($config);
			}
			writecache('admin_permissions', self::$Permissions);
		}
	}

	static public function Delete($id, $extra = ''){
		global $db;

		if(!$id = intval($id)){
			return -1;
		}

		$table = $db->select_table('administrator');
		$table->delete('id='.$id.' AND permissions!=\'all\'', $extra);
		return $db->affected_rows;
	}

	const DUPLICATED_ACCOUNT = -1;
}

Administrator::LoadPermissions();

?>
