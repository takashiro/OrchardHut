<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$action = &$_GET['action'];
switch($action){
case 'cleardatacache':
	if(isset($_POST['cache']) && is_array($_POST['cache'])){
		foreach($_POST['cache'] as $cache => $checked){
			$cacheFile = S_ROOT.'data/cache/'.$cache.'.php';
			file_exists($cacheFile) && unlink($cacheFile);
		}
	}
	showmsg('cache_files_are_deleted', 'refresh');
	break;

case 'cleartemplatecache':
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
	break;

default:
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

?>
