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

//初始化一个自定义的全局变量，用于存储用户信息，缓存信息等等
$_G = array();
$_G['starttime'] = microtime(true);

define('S_ROOT', dirname(dirname(__FILE__)).'/');
define('S_VERSION', '2.4');
error_reporting(0);
set_time_limit(0);

ob_start();

//类自动加载
spl_autoload_register(function($classname){
	require_once S_ROOT.'./class/'.$classname.'.class.php';
});

require_once S_ROOT.'./core/global.func.php';

$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

//程序配置及关键信息
$_G['config'] = (include S_ROOT.'./data/config.inc.php') + (include S_ROOT.'./data/stconfig.inc.php');
$_G['config']['db'] = include S_ROOT.'./data/dbconfig.inc.php';
$_CONFIG = &$_G['config'];

$_G['root_url'] = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/');
$_G['style'] = $_G['config']['style'];
empty($_G['style']) && $_G['style'] = 'default';

//数据库配置
$_G['db'] = new Database($_CONFIG['db']['host'], $_CONFIG['db']['user'], $_CONFIG['db']['pw'], $_CONFIG['db']['name']);

$db = &$_G['db'];
$tpre = &$_CONFIG['db']['tpre'];
$db->set_table_prefix($tpre);
$db->set_charset($_CONFIG['db']['charset']);

//时间戳
@date_default_timezone_set('Etc/GMT +'.intval($_CONFIG['timezone']));

$_G['timestamp'] = time() + intval($_CONFIG['timefix']);
define('TIMESTAMP', $_G['timestamp']);
define('TIMEZONE', $_CONFIG['timezone']);
$timestamp = TIMESTAMP;

//Handle Request
if(!empty($_GET['confirm'])){
	isset($_COOKIE['http_referer']) && $_SERVER['HTTP_REFERER'] = $_COOKIE['http_referer'];
	rsetcookie('http_referer');
	if(!empty($_GET['confirm_key'])){
		$_POST = unserialize($_COOKIE['postdata_'.$_GET['confirm_key']]);
		rsetcookie('postdata_'.$_GET['confirm_key']);
	}
}

if(!empty($_CONFIG['cookiepre'])){
	$cookie = array();
	$cookiepre_length = strlen($_CONFIG['cookiepre']);
	foreach($_COOKIE as $k => $v){
		if(strncmp($k, $_CONFIG['cookiepre'], $cookiepre_length) === 0){
			$cookie[substr($k, $cookiepre_length)] = $v;
		}
	}

	$session_name = session_name();
	array_key_exists($session_name, $_COOKIE) && $cookie[$session_name] = $_COOKIE[$session_name];

	$_COOKIE = $cookie;
	unset($cookie);
}

//常用变量处理
$page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
$pagenum = 0;

$_G['user'] = new User;
$_G['user']->login();
$_USER = $_G['user']->toReadable();

//Debug模式
if(!empty($_CONFIG['debugmode'])){
	error_reporting(E_ALL);
}

//错误日志
if(!empty($_CONFIG['log_error'])){
	set_error_handler(function($errorLevel, $errorMessage, $errorFile, $errorLine){
		return include submodule('core', 'handleerror');
	}, E_ALL);

	register_shutdown_function(function(){
		$error = error_get_last();
		if($error && $error['type'] == E_ERROR || $error['type'] == E_USER_ERROR){
			$errorLevel = $error['type'];
			$errorMessage = $error['message'];
			$errorFile = $error['file'];
			$errorLine = $error['line'];
			return include submodule('core', 'handleerror');
		}
	});
}

if(!defined('IN_ADMINCP')){
	//用户访问日志
	if(!empty($_CONFIG['log_request'])){
		register_shutdown_function(function(){
			global $_G;
			writelog('request', $_G['request_log']."\t".(microtime(true) - $_G['starttime'])."\t".$_G['db']->query_num());
		});

		$_G['request_log'] = $_G['user']->id."\t".$PHP_SELF."\t".json_encode($_POST)."\t".json_encode($_GET);
	}
}

?>
