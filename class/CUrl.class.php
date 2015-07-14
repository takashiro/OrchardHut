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

class CUrl{
	private $ch;
	private $retry = 3;

	protected $server = '202.115.47.141';
	protected $in_charset = 'UTF-8';
	protected $out_charset = 'UTF-8';

	function __construct(){
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; InfoPath.1; CIBA)');

		$cookie_file = S_ROOT.'data/cookie/'.User::ip();
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
	}

	function __destruct(){
		curl_close($this->ch);
	}

	protected function curl_setopt($option, $value){
		curl_setopt($this->ch, $option, $value);
	}

	public function setServer($host){
		$this->server = $host;
	}

	public function request($url, $data = NULL){
		curl_setopt($this->ch, CURLOPT_URL, $this->server.$url);
		if($data){
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		}else{
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}
		$result = curl_exec($this->ch);

		if(!$result && $this->retry){
			$time = 0;
			while($time < $this->retry){
				$result = curl_exec($this->ch);
				if($result){
					break;
				}
				$time++;
			}
		}

		return $this->in_charset == $this->out_charset ? $result : iconv($this->in_charset, $this->out_charset, $result);
	}

}

?>
