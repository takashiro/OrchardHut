<?php

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

		if(empty($GLOBALS['HTTP_RAW_POST_DATA'])){
			return false;
		}

		$request = new XML;
		$request->loadXML($GLOBALS['HTTP_RAW_POST_DATA']);
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
