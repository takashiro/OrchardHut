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

class CacheModule extends AdminControlPanelModule{

	public function getAlias(){
		return 'system';
	}

	public function clearDataCacheAction(){
		if(isset($_POST['cache']) && is_array($_POST['cache'])){
			foreach($_POST['cache'] as $cache => $checked){
				$cacheFile = S_ROOT.'data/cache/'.$cache.'.php';
				file_exists($cacheFile) && unlink($cacheFile);
			}
		}
		showmsg('cache_files_are_deleted', 'refresh');
	}

	public function clearTemplateCacheAction(){
		$templateFiles = scandir(S_ROOT.'data/template/');
		if(isset($_POST['template']) && is_array($_POST['template'])){
			foreach($templateFiles as $templateFile){
				if(substr_compare($templateFile, '.tpl.php', -8) === 0){
					foreach($_POST['template'] as $prefix => $checked){
						if(strncmp($templateFile, $prefix, strlen($prefix)) === 0){
							unlink(S_ROOT.'data/template/'.$templateFile);
						}
					}
				}

			}
		}
		showmsg('cache_files_are_deleted', 'refresh');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$cacheList = scandir(S_ROOT.'data/cache/');
		foreach($cacheList as $i => &$cache){
			if(substr_compare($cache, '.php', -4) !== 0){
				unset($cacheList[$i]);
			}else{
				$cache = substr($cache, 0, strlen($cache) - 4);
			}
		}
		unset($cache);
		$cacheList = array_values($cacheList);

		$templateList = array();
		$templateFiles = scandir(S_ROOT.'data/template/');
		foreach($templateFiles as $file){
			if(substr_compare($file, '.tpl.php', -8) === 0){
				$name = explode('_', $file);
				$templateList[$name[0].'_'.$name[1]] = true;
			}
		}
		$templateList = array_keys($templateList);

		include view('cache');
	}
}

?>
