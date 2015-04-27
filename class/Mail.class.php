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

class Mail{

	const DEFAULT_FUNCTION = 1;
	const SOCKET_SMTP = 2;
	const INISET_SMTP = 3;

	static public $Config = array(
		'method' => self::SOCKET_SMTP,
		'delimiter' => "\r\n",
		'systemmail' => '',
		'systemname' => '',
		'charset' => 'utf8',

		'server' => 'ssl://smtp.exmail.qq.com',
		'port' => 465,
		'auth' => true,
		'auth_username' => '',
		'auth_password' => '',
	);

	protected $title = '';
	protected $content = '';
	protected $from = null;

	public function __construct($title, $content){
		$this->setTitle($title);
		$this->setContent($content);
	}

	public function setTitle($title){
		$this->title = $title;
	}

	public function setContent($content){
		global $_G;

		//Convert all the relative URLs to absolute URLs
		$content = preg_replace("/href\=\"(?!(http|https)\:\/\/)(.+?)\"/i", 'href="'.$_G['root_url'].'\\2"', $content);

		$content = "<html>
			<head>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".self::$Config['charset']."\">
				<title>{$this->title}</title>
			</head>
			<body>$content</body>
			</html>";

		$this->content = str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $content))));
	}

	public function getEncodedContent(){
		return chunk_split(base64_encode($this->content));
	}

	public function setFrom($from){
		$this->from = self::EncodeAddress($email_from);
	}

	public function getFrom(){
		if(empty($this->from)){
			if(empty(self::$Config['systemname'])){
				return self::$Config['systemmail'];
			}else{
				return self::$Config['systemname'].' <'.self::$Config['systemmail'].'>';
			}
		}else{
			return $this->from;
		}
	}

	public function send($email_to){
		set_time_limit(0);

		if(self::$Config['method'] == self::DEFAULT_FUNCTION){
			return $this->defaultsend($email_to);
		} elseif(self::$Config['method'] == self::SOCKET_SMTP){
			return $this->socketsmtpsend($email_to);
		}elseif(self::$Config['method'] == self::INISET_SMTP){
			return $this->inismtpsend($email_to);
		}
	}

	public function defaultsend($email_to){
		$email_to = self::EncodeAddress($email_to);
		return function_exists('mail') && @mail($email_to, self::Base64Encode($this->title), $this->getEncodedContent(), $this->getHeaders());
	}

	public function socketsmtpsend($email_to){
		$email_from = $this->getFrom();
		$email_to = self::EncodeAddress($email_to);

		if(!$fp = self::SocketOpen(self::$Config['server'], self::$Config['port'], $errno, $errstr, 30)) {
			self::WriteLog("CONNECT - Unable to connect to the SMTP server");
			return false;
		}
		stream_set_blocking($fp, true);

		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != '220') {
			self::WriteLog("CONNECT - $lastmessage");
			return false;
		}

		fputs($fp, (self::$Config['auth'] ? 'EHLO' : 'HELO')." uchome\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
			self::WriteLog("HELO/EHLO - $lastmessage");
			return false;
		}

		while(true){
			if($lastmessage{3} != '-' || empty($lastmessage)){
				break;
			}
			$lastmessage = fgets($fp, 512);
		}

		if(self::$Config['auth']) {
			fputs($fp, "AUTH LOGIN\r\n");
			$lastmessage = fgets($fp, 512);
			if(strncmp($lastmessage, '334', 3)){
				self::WriteLog("AUTH LOGIN - $lastmessage");
				return false;
			}

			fputs($fp, base64_encode(self::$Config['auth_username'])."\r\n");
			$lastmessage = fgets($fp, 512);
			if(strncmp($lastmessage, '334', 3)){
				self::WriteLog("USERNAME - $lastmessage");
				return false;
			}

			fputs($fp, base64_encode(self::$Config['auth_password'])."\r\n");
			$lastmessage = fgets($fp, 512);
			if(strncmp($lastmessage, '235', 3)){
				self::WriteLog("PASSWORD - $lastmessage");
				return false;
			}

			$email_from = self::$Config['auth_username'];
		}

		fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
		$lastmessage = fgets($fp, 512);
		if(strncmp($lastmessage, '250', 3)){
			fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
			$lastmessage = fgets($fp, 512);
			if(strncmp($lastmessage, '250', 3)){
				self::WriteLog("MAIL FROM - $lastmessage");
				return false;
			}
		}

		fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_to).">\r\n");
		$lastmessage = fgets($fp, 512);
		if(strncmp($lastmessage, '250', 3)){
			fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_to).">\r\n");
			$lastmessage = fgets($fp, 512);
			self::WriteLog("RCPT TO - $lastmessage");
			return false;
		}

		fputs($fp, "DATA\r\n");
		$lastmessage = fgets($fp, 512);
		if(strncmp($lastmessage, '354', 3)){
			self::WriteLog("DATA - $lastmessage");
			return false;
		}

		$email_content = $this->getEncodedContent();

		$headers = $this->getHeaders().'Message-ID: <'.rdate(TIMESTAMP, 'YmdHs').'.'.substr(md5($email_content.microtime()), 0, 6).rand(100000, 999999).'@'.$_SERVER['HTTP_HOST'].'>'.self::$Config['delimiter'];
		fputs($fp, "Date: ".rdate(TIMESTAMP, 'r')."\r\n");
		fputs($fp, "To: ".$email_to."\r\n");
		fputs($fp, "Subject: ".self::Base64Encode($this->title)."\r\n");
		fputs($fp, $headers);
		fputs($fp, "\r\n");
		fputs($fp, "\r\n\r\n");
		fputs($fp, $email_content);
		fputs($fp, "\r\n.\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, '250', 3)){
			self::WriteLog("END - $lastmessage");
		}
		fputs($fp, "QUIT\r\n");

		return true;
	}

	public function inismtpsend($email_to){
		ini_set('SMTP', self::$Config['server']);
		ini_set('smtp_port', self::$Config['port']);
		ini_set('sendmail_from', $this->getFrom());

		return $this->defaultsend($email_to);
	}

	protected function getHeaders(){
		return 'From: '.$this->getFrom().self::$Config['delimiter'].
			'X-Priority: 3'.self::$Config['delimiter'].
			'X-Mailer: '.$_SERVER['HTTP_HOST'].self::$Config['delimiter'].
			'MIME-Version: 1.0'.self::$Config['delimiter'].
			'Content-type: text/html; charset='.self::$Config['charset'].self::$Config['delimiter'].
			'Content-Transfer-Encoding: base64'.self::$Config['delimiter'];
	}

	static protected function EncodeAddress($address){
		if(preg_match('/^(.+?) \<(.+?)\>$/', $address, $matches)){
			return '=?'.self::$Config['charset'].'?B?'.base64_encode($matches[1]).'?= <'.$mats[2].'>';
		}else{
			return $address;
		}
	}

	static protected function SocketOpen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15){
		$fp = '';
		if(function_exists('fsockopen')){
			$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
		}elseif(function_exists('pfsockopen')){
			$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
		}elseif(function_exists('stream_socket_client')){
			$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
		}
		return $fp;
	}

	static protected function WriteLog($log){
		writelog('SMTP', '('.self::$Config['server'].':'.self::$Config['port'].') '.$log);
	}

	static protected function Base64Encode($str){
		return '=?'.self::$Config['charset'].'?B?'.base64_encode($str).'?=';
	}

	static public function LoadConfig(){
		$mailconfig = readdata('mailconfig');
		if($mailconfig && is_array($mailconfig)){
			foreach(self::$Config as $var => $oldvalue){
				if(isset($mailconfig[$var])){
					self::$Config[$var] = $mailconfig[$var];
				}
			}
		}
	}
}

Mail::LoadConfig();

?>
