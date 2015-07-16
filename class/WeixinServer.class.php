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

class WeixinServer{
	private $token = '';
	private $client_openid = '';
	private $server_id = '';
	private $app_id = '';
	private $aes_key = '';

	const RAW_MESSAGE = 0;
	const AES_MESSAGE = 1;
	private $encoding_mode = self::RAW_MESSAGE;

	function __construct($app_id, $token, $server_id){
		$this->app_id = $app_id;
		$this->token = $token;
		$this->server_id = $server_id;
	}

	function setAesKey($key){
		$this->aes_key = base64_decode($key.'=');
	}

	function setEncodingMode($mode){
		$this->encoding_mode = $mode;
	}

	function isValidRequest(){
		if(!array_key_exists('timestamp', $_GET) || !array_key_exists('nonce', $_GET) || !array_key_exists('signature', $_GET)){
			return false;
		}

		$tmp_str = self::ParseSignature(array($this->token, $_GET['timestamp'], $_GET['nonce']));
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
		$request->loadXML($input, 'xml');
		$request = $request->toArray();

		if($this->encoding_mode == self::AES_MESSAGE){
			if(!isset($request['Encrypt'])){
				return false;
			}

			$request = $this->decryptRequest($request['Encrypt']);
		}

		$this->client_openid = isset($request['FromUserName']) ? $request['FromUserName'] : null;
		return $request;
	}

	function replyTextMessage($content, $to_user = NULL){
		$to_user == NULL && $to_user = $this->client_openid;
		$from_user = $this->server_id;

		$xml = '<xml>'.
				'<ToUserName><![CDATA['.$to_user.']]></ToUserName>'.
				'<FromUserName><![CDATA['.$from_user.']]></FromUserName>'.
				'<CreateTime>'.TIMESTAMP.'</CreateTime>'.
				'<MsgType><![CDATA[text]]></MsgType>'.
				'<Content><![CDATA['.$content.']]></Content>'.
			'</xml>';

		if($this->encoding_mode == self::AES_MESSAGE){
			$xml = $this->encryptReply($xml);
		}

		exit($xml);
	}

	private function decryptRequest($encrypted){
		//验证安全签名
		$signature = self::ParseSignature(array($this->token, $_GET['timestamp'], $_GET['nonce'], $encrypted));
		if(!isset($_GET['msg_signature']) || $signature != $_GET['msg_signature']){
			return false;
		}

		$result = $this->decryptText($encrypted);
		if(!$result){
			return false;
		}

		$xml = new XML;
		$xml->loadXML($result, 'xml');
		return $xml->toArray();
	}

	private function encryptReply($reply_xml){
		//加密
		$encrypt = $this->encryptText($reply_xml);
		if(!$encrypt){
			return false;
		}

		//生成安全签名
		$nonce = randomstr(16);
		$signature = self::ParseSignature(array($this->token, TIMESTAMP, $nonce, $encrypt));

		//生成发送的xml
		return '<xml>'.
			'<Encrypt><![CDATA['.$encrypt.']]></Encrypt>'.
			'<MsgSignature><![CDATA['.$signature.']]></MsgSignature>'.
			'<TimeStamp>'.TIMESTAMP.'</TimeStamp>'.
			'<Nonce><![CDATA['.$nonce.']]></Nonce>'.
		'</xml>';
	}

	private static $PKCS7_BLOCK_SIZE = 32;
	private static function PKCS7Encode($text){
		$text_length = strlen($text);

		//计算需要填充的位数
		$amount_to_pad = self::$PKCS7_BLOCK_SIZE - ($text_length % self::$PKCS7_BLOCK_SIZE);
		if($amount_to_pad == 0){
			$amount_to_pad = self::$PKCS7_BLOCK_SIZE;
		}

		//获得补位所用的字符
		$pad_chr = chr($amount_to_pad);
		$tmp = '';
		for($index = 0; $index < $amount_to_pad; $index++){
			$tmp .= $pad_chr;
		}

		return $text.$tmp;
	}

	private static function PKCS7Decode($text){
		$pad = ord(substr($text, -1));
		if ($pad < 1 || $pad > 32) {
			$pad = 0;
		}
		return substr($text, 0, (strlen($text) - $pad));
	}

	private function encryptText($text){
		try{
			//获得16位随机字符串，填充到明文之前
			$random = randomstr(16);
			$text = $random . pack('N', strlen($text)) . $text . $this->app_id;

			// 网络字节序
			$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
			$iv = substr($this->aes_key, 0, 16);

			//使用自定义的填充方式对明文进行补位填充
			$text = self::PKCS7Encode($text);
			mcrypt_generic_init($module, $this->aes_key, $iv);

			//加密
			$encrypted = mcrypt_generic($module, $text);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);

			return base64_encode($encrypted);
		}catch(Exception $e){
			return '';
		}
	}

	private function decryptText($encrypted){
		try{
			//使用BASE64对需要解密的字符串进行解码
			$ciphertext_dec = base64_decode($encrypted);
			$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
			$iv = substr($this->aes_key, 0, 16);
			mcrypt_generic_init($module, $this->aes_key, $iv);

			//解密
			$decrypted = mdecrypt_generic($module, $ciphertext_dec);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);
		}catch(Exception $e){
			return '';
		}

		try{
			//去除补位字符
			$result = self::PKCS7Decode($decrypted);
			//去除16位随机字符串,网络字节序和AppId
			if (strlen($result) < 16)
				return '';
			$content = substr($result, 16, strlen($result));
			$len_list = unpack('N', substr($content, 0, 4));
			$xml_len = $len_list[1];
			$xml_content = substr($content, 4, $xml_len);
			$from_appid = substr($content, $xml_len + 4);
		}catch(Exception $e){
			return '';
		}
		if($from_appid != $this->app_id)
			return '';
		return $xml_content;
	}

	private static function ParseSignature($array){
		sort($array, SORT_STRING);
		$str = implode($array);
		return sha1($str);
	}
}

?>
