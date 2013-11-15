<?php

class Administrator extends User{
	const TABLE_NAME = 'administrator';

	const AUTH_FIELD = 'account';
	const COOKIE_VAR = 'rcadmininfo';

	static private $Permission = array(  
		'all' => 0,
		'order' => 0x1,
		'order_sort' => 0x2,
		'order_deliver' => 0x4,
		'admin' => 0x8,
		'market' => 0x10,
		'announcement' => 0x20,
	);

	public function __construct($id = 0){
		if($id = intval($id)){
			DBObject::fetchAttributesFromDB('*', 'id='.$id);
		}
	}
	
	public function __destruct(){
		DBObject::__destruct();
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
			);
		}
	}

	public function login($account = '', $pw = '', $method = 'account'){
		global $db, $tpre, $_G;
		if(!$account){//Login by Cookie
			$cookie_var = !empty($_COOKIE[static::COOKIE_VAR]) ? $_COOKIE[static::COOKIE_VAR] : (!empty($_POST[static::COOKIE_VAR]) ? $_POST[static::COOKIE_VAR] : '');
			if(!empty($cookie_var)){
				$cookie = $this->decodeCookie($cookie_var);
				if(!array_key_exists('id', $cookie) || !array_key_exists('loginip', $cookie)){
					return false;
				}

				if($cookie['loginip'] != self::ip()){
					return false;
				}

				$cookie = array(
					'id' => intval($cookie['id']),
				);

				DBObject::fetchAttributesFromDB('*', $cookie);
				return $this->isLoggedIn();
			}
		}elseif($pw){
			$condition = array(
				'account' => $account,
				'pwmd5' => rmd5($pw),
			);

			DBObject::fetchAttributesFromDB('*', $condition);
		}

		if($this->id <= 0){
			$this->logout();
			return false;
		}else{
			$this->logged = true;
			$cookie = array('id' => $this->attr['id'], 'loginip' => self::ip());
			rsetcookie(static::COOKIE_VAR, $this->encodeCookie($cookie));

			return true;
		}
	}

	public function isAdministrator(){
		return true;
	}

	public function isSuperAdmin(){
		return $this->permission == -1;
	}
	
	static public function Register($admin){
		global $db;

		$attr = array(
			'account' => raddslashes($admin['account']),
			'pwmd5' => rmd5($admin['password']),
		);
		
		$db->select_table('administrator');
		$db->INSERT($attr);
		
		return $db->insert_id();
	}
	
	public function changePassword($old, $new, $new2 = ''){
		if(!$this->attr('id')){
			return -3;
		}elseif(rmd5($old) != $this->attr('pwmd5')){
			return -1;
		}elseif($new != $new2){
			return -2;
		}
		
		$this->attr('pwmd5', rmd5($new));
		rsetcookie(static::COOKIE_VAR);

		return true;
	}
	
	public function hasPermission($permission){
		if(strpos($permission, '|') !== false){
			$permissions = explode('|', $permission);
			foreach($permissions as $perm){
				if($this->hasPermission($perm)){
					return true;
				}
			}
			return false;
		}

		if(isset(self::$Permission[$permission])){
			return ($this->attr('permission') & self::$Permission[$permission]) == self::$Permission[$permission];
		}else{
			return false;
		}
	}

	public function setPermission($permission, $value = true){
		if($this->isSuperAdmin()){
			return false;
		}

		if(!is_array($permission)){
			if(isset(self::$Permission[$permission])){
				if($value){
					$this->attr['permission'] |= self::$Permission[$permission];
				}else{
					$this->attr['permission'] &= ~self::$Permission[$permission];
				}
				return true;
			}else{
				return false;
			}
		}else{
			$this->attr('permission', 0);
			foreach(self::$Permission as $p => $c){
				if($permission[$p]){
					$this->attr['permission'] |= $c;
				}
			}
			return true;
		}
	}

	public function clearPermission(){
		$this->permission &= 0x0;
	}

	static public function GetAllPermissionNames(){
		return array_keys(self::$Permission);
	}

	static public function ToPermissionList($permission){
		$admin = new Admin;
		$admin->permission = $permission;

		$all_permissions = self::GetAllPermissionNames();
		$permission = array();

		foreach($all_permissions as $perm){
			if($admin->hasPermission($perm)){
				$permission[] = $perm;
			}
		}

		return $permission;
	}
	
	static public function Delete($id){
		global $db;
		
		if(!$id = intval($id)){
			return -1;
		}
		
		$db->select_table('administrator');
		$db->DELETE('id='.$id.' AND permission!=-1');
		return $db->affected_rows();
	}
}

?>