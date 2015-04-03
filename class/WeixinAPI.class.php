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
		$result = readcache('wxconnect');
		if($result){
			if(isset($result['accessToken'])){
				$this->accessToken = $result['accessToken'];
			}

			if(isset($result['accessTokenExpireTime'])){
				$this->accessTokenExpireTime = $result['accessTokenExpireTime'];
			}
		}

		$result = readdata('wxconnect');
		if(isset($result['app_id'])){
			$this->appId = $result['app_id'];
		}

		if(isset($result['app_secret'])){
			$this->appSecret = $result['app_secret'];
		}
	}

	public function saveConfig(){
		$result = array();
		$result['accessToken'] = $this->accessToken;
		$result['accessTokenExpireTime'] = $this->accessTokenExpireTime;
		writecache('wxconnect', $result);
	}

	public function getAccessToken($force_refresh = false){
		if($force_refresh || $this->accessTokenExpireTime < TIMESTAMP){
			$result = $this->request('token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecret);
			if(isset($result['access_token'])){
				$this->accessToken = $result['access_token'];
				$this->accessTokenExpireTime = $result['expires_in'] + TIMESTAMP;
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
			is_string($menu) || $menu = json_encode($menu, JSON_UNESCAPED_UNICODE);
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
		$message = json_encode($message, JSON_UNESCAPED_UNICODE);

		$result = $this->request('message/custom/send?access_token='.$access_token, $message);
		return !$this->hasError();
	}
}

?>
