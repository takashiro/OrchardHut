<?php

class Authkey extends DBObject{
	const TABLE_NAME = 'authkey';
	const PRIMARY_KEY = 'user';

	public function __construct($user){
		$this->fetch('*', array('user' => $user));
	}

	public function isExpired(){
		if($this->expiry < TIMESTAMP){
			if($this->user){
				$this->deleteFromDB();
			}
			return true;
		}
		return false;
	}

	public function match($key){
		return $this->key == $key;
	}

	public function matchOnce($key){
		$result = $this->match($key);
		if($result){
			$this->deleteFromDB();
		}
		return $result;
	}

	public static function Generate($user, $expiry = NULL){
		global $db;
		$table = $db->select_table('authkey');

		$authkey = array(
			'user' => $user,
			'key' => randomstr(32),
			'expiry' => $expiry == NULL ? TIMESTAMP + 5 * 60 : $expiry,
		);

		$table->insert($authkey, true);

		return $authkey['key'];
	}
}

?>
