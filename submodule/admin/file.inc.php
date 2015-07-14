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

if(!defined('IN_ADMINCP')) exit('access denied');

class FileModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'system';
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$lost_files = array();
		$modified_files = array();

		$fp = fopen('data/sha1.inc.php', 'r');
		while($data = fscanf($fp, '%d %s %d %s')){
			if(empty($data[1]) || empty($data[3]))
				continue;

			$standard_sha1 = $data[1];
			$filename = $data[3];

			if(file_exists($filename)){
				$current_sha1 = self::FileSHA1($filename);
				if($standard_sha1 != $current_sha1){
					$modified_files[] = array(
						'file_name' => $filename,
						'modified_time' => filemtime($filename),
					);
				}
			}else{
				$lost_files[] = $filename;
			}
		}
		fclose($fp);

		$standard_update_time = filemtime('data/sha1.inc.php');

		$writable_directories = array(
			'./data/',
			'./data/attachment/',
			'./data/cache/',
			'./data/error/',
			'./data/js/',
			'./data/log/',
			'./data/template/',
		);

		$directory_results = array();
		foreach($writable_directories as $dir){
			$directory_results[] = array(
				'path' => $dir,
				'is_writable' => is_writable(S_ROOT.$dir),
			);
		}

		include view('file');
	}

	static private $BinaryExtensions = array('.jpg', '.png');
	static private function IsBinary($filename){
		foreach(self::$BinaryExtensions as $ext){
			if(substr_compare($filename, $ext, -strlen($ext)) == 0){
				return true;
			}
		}
		return false;
	}

	static private function FileSHA1($filename){
		$data = file_get_contents(S_ROOT.$filename);
		if(!self::IsBinary($filename)){
			$data = str_replace("\r\n", "\n", $data);
		}
		return sha1('blob '.strlen($data).chr(0).$data);
	}
}

?>
