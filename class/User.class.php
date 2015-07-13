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

class User extends DBObject{
	const TABLE_NAME = 'user';

	const COOKIE_VAR = 'rcuserinfo';

	static private $LoginMethod = array('id', 'account', 'email', 'mobile');

	public function login($account = '', $password = '', $method = 'id'){
		global $db, $tpre;

		if(!in_array($method, self::$LoginMethod)){
			$method = self::$LoginMethod[0];
		}

		if(!$account){
			if(!empty($_COOKIE[static::COOKIE_VAR])){
				$cookie = $this->decodeCookie($_COOKIE[static::COOKIE_VAR]);

				if(!is_array($cookie) || !isset($cookie['id']) || !isset($cookie['loginkey'])){
					return false;
				}

				$cookie = array(
					'id' => intval($cookie['id']),
					'loginkey' => intval($cookie['loginkey']),
				);
				$this->fetch('*', $cookie);

				if($this->isLoggedIn()){
					return true;
				}

				rsetcookie(static::COOKIE_VAR);
				return false;
			}
		}else if($password){
			$condition = array(
				$method => $account,
				'pwmd5' => rmd5($password),
			);
			$this->fetch('*', $condition);

			if($this->isLoggedIn()){
				$this->loginkey = rand(1, 0xFFFF);
				$this->logintime = TIMESTAMP;
				$cookie = array('id' => $this->id, 'loginkey' => $this->loginkey);
				rsetcookie(static::COOKIE_VAR, $this->encodeCookie($cookie));
				return true;
			}else{
				return false;
			}
		}

		return false;
	}

	public function force_login(){
		if(empty($this->id))
			return false;

		$this->loginkey = rand(1, 65535);
		$this->logintime = TIMESTAMP;
		$cookie = array('id' => $this->id, 'loginkey' => $this->loginkey);
		rsetcookie(static::COOKIE_VAR, $this->encodeCookie($cookie));
		return true;
	}

	public function logout(){
		rsetcookie(static::COOKIE_VAR);
		return true;
	}

	public function isLoggedIn(){
		return isset($this->id) && $this->id > 0;
	}

	public function changePassword($oldpw, $newpw, $newpw2 = ''){
		if($newpw2 && $newpw2 != $newpw){
			return self::PASSWORD2_WRONG;
		}

		if(rmd5($oldpw) != $this->pwmd5){
			return self::OLD_PASSWORD_WRONG;
		}

		$this->pwmd5 = rmd5($newpw);
		return true;
	}

	static public function ip(){
		$onlineip = '0.0.0.0';
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$onlineip = getenv('HTTP_CLIENT_IP');
		} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$onlineip = getenv('REMOTE_ADDR');
		} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}
		return $onlineip;
	}

	public function toReadable(){
		$attr = parent::toReadable();
		if(!empty($attr['nickname'])){
			$attr['account'] = &$attr['nickname'];
		}
		return $attr;
	}

	public static function Register($user){
		global $db;

		if(!array_key_exists('account', $user) || !array_key_exists('password', $user)){
			return 0;
		}

		$account_length = strlen($user['account']);
		if($account_length < 4 || $account_length > 15){
			return self::INVALID_ACCOUNT;
		}

		if(!preg_match('/^[0-9a-z\x{4e00}-\x{9fa5}]+$/iu', $user['account'])){
			return self::INVALID_ACCOUNT;
		}

		$password_length = strlen($user['password']);
		if($password_length < 6){
			return self::INVALID_PASSWORD;
		}

		$user = array(
			'account' => $user['account'],
			'pwmd5' => rmd5($user['password']),
			'regtime' => TIMESTAMP,
		);

		$table = $db->select_table('user');

		if($table->result_first('id', array('account' => $user['account'])) > 0){
			return self::DUPLICATED_ACCOUNT;
		}

		$table->insert($user);
		return $table->insert_id();
	}

	public function updateInfo($user){
		global $db, $tpre;

		if(isset($user['fullname'])){
			$len = strlen($user['fullname']);
			if($len < 4 || $len > 32){
				return User::INVALID_FULLNAME;
			}

			$this->fullname = $user['fullname'];
		}

		return true;
	}

	public function refreshFormKey(){
		global $db, $tpre;
		if($this->formkey == 0){
			$this->formkey = rand(1, 255);
			$db->query("UPDATE {$tpre}user SET formkey={$this->formkey} WHERE id={$this->id} AND formkey=0");
			return $db->affected_rows > 0;
		}
		return false;
	}

	public function checkFormKey($formkey){
		global $db, $tpre;
		$formkey = intval($formkey);
		$db->query("UPDATE {$tpre}user SET formkey=0 WHERE id={$this->id} AND formkey=$formkey");
		return $db->affected_rows > 0;
	}

	static public function Delete($id, $extra = ''){
		if($id = intval($id)){
			DB::select_table(static::TABLE_NAME);
			DB::DELETE('id='.$id, $extra);

			return DB::affected_rows();
		}

		return false;
	}

	static protected function decodeCookie($auth){
		return unserialize(self::authcode($auth, 'DECODE', $GLOBALS['_CONFIG']['salt']));
	}

	static protected function encodeCookie($userinfo){
		return self::authcode(serialize($userinfo), 'ENCODE', $GLOBALS['_CONFIG']['salt']);
	}

	static protected function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);

		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);

		$result = '';
		$box = range(0, 255);

		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}

		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}

		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if($operation == 'DECODE') {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}

	private static $IdNumBitWeight = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
	static public function IsIDNum($idnum){
		if(!preg_match('/[0-9]{17}[0-9X]/i', $idnum)){
			return false;
		}
		$idnum{17} == 'x' && $idnum{17} = 'X';

		//ISO 7064:1983.MOD 11-2
		$check = 0;
		for($i = 0; $i < 17; $i++){
			$check += intval($idnum{$i}) * self::$IdNumBitWeight[$i];
		}
		$check = (12 - ($check % 11)) % 11;

		if($idnum{17} == 'X'){
			if($check != 10){
				return false;
			}
		}elseif(intval($idnum{17}) != $check){
			return false;
		}

		//Birthday
		$year = intval(substr($idnum, 6, 4));
		$month = intval(substr($idnum, 10, 2));
		$day = intval(substr($idnum, 12, 2));
		if(rdate(rmktime(0, 0, 0, $month, $day, $year), 'Ymd') != substr($idnum, 6, 8)){
			return false;
		}

		return true;
	}

	static public function IsEmail($email) {
		return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
	}

	static public function IsMobile($mobile){
		if(strlen($mobile) != 11 || $mobile{0} != '1'){
			return false;
		}

		return true;
	}

	const INVALID_ACCOUNT = -1;
	const INVALID_PASSWORD = -2;
	const DUPLICATED_ACCOUNT = -3;

	const ACTION_SUCCEEDED = 1;
	const ACTION_FAILED = 0;

	const PASSWORD2_WRONG = -1;
	const OLD_PASSWORD_WRONG = -2;
}

?>
