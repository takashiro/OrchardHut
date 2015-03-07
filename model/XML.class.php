<?php

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
		$xml = preg_replace('/>\s+</s', '><', $xml);

		$dom = new DOMDocument($this->version, $this->encoding);
		$dom->loadXML($xml);

		if($tag){
			$body = $dom->getElementsByTagName($tag)->item($index);
		}else{
			$body = $dom;
		}

		$this->data = self::DOMToArray($body);
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
