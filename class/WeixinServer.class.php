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

class WeixinServer{
	private $token = '';
	private $client_openid = '';
	private $server_id = '';

	function __construct($token, $server_id){
		$this->token = $token;
		$this->server_id = $server_id;
	}

	function isValidRequest(){
		if(!array_key_exists('timestamp', $_GET) || !array_key_exists('nonce', $_GET) || !array_key_exists('signature', $_GET)){
			return false;
		}

		$tmp_arr = array($this->token, $_GET['timestamp'], $_GET['nonce']);
		sort($tmp_arr, SORT_STRING);
		$tmp_str = implode($tmp_arr);
		$tmp_str = sha1($tmp_str);

		return $tmp_str == $_GET['signature'];
	}

	function getRequest($skip_validation = false){
		if(!$skip_validation && !$this->isValidRequest()){
			return false;
		}

		$input = file_get_contents('php://input');
		if(empty($input)){
			return false;
		}

		$request = new XML;
		$request->loadXML($input);
		$request = $request->toArray();
		$request = $request['xml'];
		$this->client_openid = $request['FromUserName'];
		return $request;
	}

	function replyTextMessage($content, $to_user = NULL){
		$to_user == NULL && $to_user = $this->client_openid;
		$from_user = $this->server_id;

		$xml = "<xml>
			<ToUserName><![CDATA[$to_user]]></ToUserName>
			<FromUserName><![CDATA[$from_user]]></FromUserName>
			<CreateTime>".TIMESTAMP."</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[$content]]></Content>
		</xml>";

		exit($xml);
	}
}

?>
