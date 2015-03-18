<?php

class Autoreply extends DBObject{
	function __construct($id = 0){
		$id = intval($id);
		if($id > 0){
			$this->fetch('*', 'id='.$id);
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	static public function MatchKeyword($keyword, $message){
		$keyword = trim($keyword);
		$end = strlen($keyword) - 1;
		if($keyword{0} == '*'){
			if($keyword{$end} == '*'){
				$keyword = substr($keyword, 1, $end - 1);
				return stripos($message, $keyword) !== false;
			}

			$j = strlen($message) - $end;
			if($j < 0){
				return false;
			}

			for($i = 1; $i <= $end; $i++, $j++){
				if($message{$j} != $keyword{$i}){
					return false;
				}
			}

			return true;
		}

		if($keyword{$end} == '*'){
			$end--;
			if(!isset($message{$end})){
				return false;
			}

			for($i = 0, $j = 0; $i <= $end; $i++, $j++){
				if($message{$i} != $keyword{$j}){
					return false;
				}
			}

			return true;
		}

		if($keyword{0} == '~'){
			$keyword = substr($keyword, 1);
			similar_text($message, $keyword, $percent);
			return $percent > 90;
		}

		return $keyword == $message;
	}

	static function MatchKeywords($keywords, $message){
		is_array($keywords) || $keywords = explode("\n", $keywords);

		foreach($keywords as $keyword){
			if(!isset($keyword{0})){
				continue;
			}

			if(self::MatchKeyword($keyword, $message)){
				return true;
			}
		}

		return false;
	}

	static private $autoreplies = NULL;
	static function Find($message){
		if(self::$autoreplies === NULL){
			self::$autoreplies = readcache('autoreply');
			if(self::$autoreplies === NULL){
				global $db;
				$table = $db->select_table('autoreply');
				self::$autoreplies = $table->fetch_all('*');
				writecache('autoreply', self::$autoreplies);
			}
		}

		foreach(self::$autoreplies as $r){
			if(self::MatchKeywords($r['keyword'], $message)){
				return $r['reply'];
			}
		}

		return null;
	}

	static function RefreshCache(){
		writecache('autoreply', NULL);
	}
}

?>
