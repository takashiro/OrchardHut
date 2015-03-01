<?php

class WeixinAPI extends CUrl{

	protected $appId;
	protected $appSecret;

	protected $accessToken;
	protected $accessTokenExpireTime;

	protected $error;

	public function __construct($appId = NULL, $appSecret = NULL){
		parent::__construct();
		$this->setServer('https://api.weixin.qq.com/cgi-bin/');

		$this->restoreConfig();
		if($appId && $appSecret){
			$this->appId = $appId;
			$this->appSecret = $appSecret;
		}
	}

	public function __destruct(){
		$this->saveConfig();
	}

	public function hasError(){
		return isset($this->error['errcode']) && $this->error['errcode'] != 0;
	}

	public function getError(){
		return $this->error;
	}

	public function getErrorMessage(){
		return isset($this->error['errmsg']) ? $this->error['errmsg'] : '';
	}

	public function request($url, $data = NULL){
		$result = json_decode(parent::request($url, $data), true);
		isset($result['errcode']) && $this->error = $result;
		return $result;
	}

	public function restoreConfig(){
		$result = readdata('weixinapi');
		if($result){
			if(isset($result['accessToken'])){
				$this->accessToken = $result['accessToken'];
			}

			if(isset($result['accessTokenExpireTime'])){
				$this->accessTokenExpireTime = $result['accessTokenExpireTime'];
			}

			if(isset($result['appId'])){
				$this->appId = $result['appId'];
			}

			if(isset($result['appSecret'])){
				$this->appSecret = $result['appSecret'];
			}
		}
	}

	public function saveConfig(){
		$result = array();
		$result['appId'] = $this->appId;
		$result['appSecret'] = $this->appSecret;
		$result['accessToken'] = $this->accessToken;
		$result['accessTokenExpireTime'] = $this->accessTokenExpireTime;
		writedata('weixinapi', $result);
	}

	public function getAccessToken($force_refresh = false){
		if($force_refresh || $this->accessTokenExpireTime < TIMESTAMP){
			$result = $this->request('token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecret);
			if(isset($result['access_token'])){
				$this->accessToken = $result['access_token'];
				$this->accessTokenExpireTime = $result['expires_in'];
			}else{
				$this->accessToken = '';
				$this->accessTokenExpireTime = 0;
			}
		}

		return $this->accessToken;
	}

	public function getMenu(){
		$access_token = $this->getAccessToken();
		if(!$access_token){
			return NULL;
		}

		$result = $this->request('menu/get?access_token='.$access_token);
		return $this->hasError() ? NULL : $result['menu'];
	}

	public function setMenu($menu){
		$access_token = $this->getAccessToken();
		if(!$access_token){
			return false;
		}

		if(empty($menu)){
			$this->request('menu/delete?access_token='.$access_token);
		}else{
			is_string($menu) || $menu = self::json_encode($menu);
			$this->request('menu/create?access_token='.$access_token, $menu);
		}

		return !$this->hasError();
	}

	public function sendTextMessage($toUser, $text){
		$access_token = $this->getAccessToken();
		if(!$access_token){
			return false;
		}

		$message = array(
			'touser' => $toUser,
			'msgtype' => 'text',
			'text' => array('content' => $text),
		);
		$message = self::json_encode($message);

		$result = $this->request('message/custom/send?access_token='.$access_token, $message);
		return !$this->hasError();
	}

	static public function json_encode($data){
		if(PHP_VERSION >= '5.4'){
			return json_encode($data, JSON_UNESCAPED_UNICODE);
		}

		if(!is_array($data)){
			if(is_numeric($data)){
				return $data;
			}elseif(is_string($data)){
				return '"'.addslashes($data).'"';
			}else{
				return $data ? 'true' : 'false';
			}
		}

		$is_assoc = false;
		$max = count($data);
		for($i = 0; $i < $max; $i++){
			if(!isset($data[$i])){
				$is_assoc = true;
				break;
			}
		}

		if($is_assoc){
			$result = '{';
			foreach($data as $key => $value){
				$result.= self::json_encode($key).':'.self::json_encode($value).',';
			}
			$result{strlen($result) - 1} = '}';
		}else{
			$result = '[';
			foreach ($data as $value) {
				$result.= self::json_encode($value).',';
			}
			$result{strlen($result) - 1} = ']';
		}
		return $result;
	}
}

?>
