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

class Autoreply extends DBObject{

	function __construct($id = 0){
		parent::__construct();
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
