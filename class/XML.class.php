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

class XML{
	private $version;
	private $encoding;
	private $data;

	function __construct($version = '1.0', $encoding = 'utf-8', $data = array()){
		$this->version = $version;
		$this->encoding = $encoding;
		$this->data = $data;
	}

	function loadXML($xml, $tag = '', $index = 0){
		if(empty($xml))
			return false;

		$xml = preg_replace('/>\s+</s', '><', $xml);

		$dom = new DOMDocument($this->version, $this->encoding);
		if(!$dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING)){
			return false;
		}

		if($tag){
			$body = $dom->getElementsByTagName($tag)->item($index);
		}else{
			$body = $dom;
		}

		$this->data = self::DOMToArray($body);
		return true;
	}

	function setVersion($version){
		$this->version = $version;
	}

	function setEncoding($encoding){
		$this->encoding = $encoding;
	}

	function setData($data){
		$this->data = $data;
	}

	public function toArray(){
		return $this->data;
	}

	public function __toString(){
		$str = '<?xml version="'.$this->version.'" encoding="'.$this->encoding.'"?>'."\r\n";
		$str.= self::CreateNode($this->data);
		return $str;
	}

	public function toString(){
		return $this->__toString();
	}

	static private function CreateNode($node, $tab = ''){
		$str = '';
		foreach($node as $key => $value){
			if(is_array($value)){
				if(self::IsAssoc($value)){
					$str.= $tab.'<'.$key.'>'."\r\n".self::CreateNode($value, $tab."\t").$tab.'</'.$key.'>'."\r\n";
				}else{
					foreach($value as $v){
						$str.= $tab.'<'.$key.'>'."\r\n".self::CreateNode($v, $tab."\t").$tab.'</'.$key.'>'."\r\n";
					}
				}
			}else{
				$str.= $tab.'<'.$key.'>'.$value.'</'.$key.'>'."\r\n";
			}
		}
		return $str;
	}

	static function IsAssoc($node){
		foreach(array_keys($node) as $key => $value){
			if($key !== $value){
				return true;
			}
		}

		return false;
	}

	static function DOMToArray($body){
		$result = array();
		$is_array = array();

		for($i = 0; $i < $body->childNodes->length; $i++){
			$node = $body->childNodes->item($i);
			$key = $node->tagName;
			$value = ($node->childNodes->length == 1 && empty($node->childNodes->item(0)->tagName)) ? $node->nodeValue :  self::DOMToArray($node);

			if(isset($result[$key])){
				if(isset($is_array[$key])){
					$result[$key][] = $value;
				}else{
					$result[$key] = array($result[$key], $value);
					$is_array[$key] = true;
				}
			}else{
				$result[$key] = $value;
			}
		}

		return $result;
	}

	static function StringToDOM($xml){
		$xml = preg_replace('/>\s+</s', '><', $xml);

		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->loadXML($xml);

		return $dom;
	}
}

?>
