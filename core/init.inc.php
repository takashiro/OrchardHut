<?php

//初始化一个自定义的全局变量，用于存储用户信息，缓存信息等等
$_G = array();
$_G['starttime'] = microtime(true); 

define('S_ROOT', dirname(dirname(__FILE__)).'/');
define('S_VERSION', '1.0');
error_reporting(0);
set_time_limit(0);

//类自动加载
class SimpleClassLoader{
	static public function Load($classname){
		$file_path = S_ROOT.'./model/'.$classname.'.class.php';
		require_once $file_path;
	}
}
spl_autoload_register('SimpleClassLoader::Load');

require_once './core/global.func.php';

$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

//程序配置及关键信息
$_G['config'] = (include './data/config.inc.php') + (include './data/stconfig.inc.php');
$_G['config']['db'] = include './data/dbconfig.inc.php';
$_CONFIG = &$_G['config'];

$_G['root_url'] = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/');
$_G['style'] = $_G['config']['style'];
empty($_G['style']) && $_G['style'] = 'default';

//数据库配置
$_G['db'] = new Mysql;

$db = &$_G['db'];
$tpre = &$_CONFIG['db']['tpre'];
$db->set_tablepre($tpre);
$db->connect($_CONFIG['db']);

//时间戳
@date_default_timezone_set('Etc/GMT +'.intval($_CONFIG['timezone']));

$_G['timestamp'] = time() + intval($_CONFIG['timefix']);
define('TIMESTAMP', $_G['timestamp']);
define('TIMEZONE', $_CONFIG['timezone']);

//Handle Request
if(!empty($_GET['confirm'])){
	$_SERVER['HTTP_REFERER'] = $_COOKIE['http_referer'];
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
		if(substr($k, 0, $cookiepre_length) == $_CONFIG['cookiepre']){
			$cookie[substr($k, $cookiepre_length)] = $v;
		}
	}
	$_COOKIE = $cookie;
	unset($cookie);
}

//转义处理
foreach(array('_POST', '_GET', '_COOKIE') as $request){
	${$request} = rhtmlspecialchars(raddslashes(${$request}));
}

//常用变量处理
$page = isset($_GET['page']) ? max(1, intval($_REQUEST['page'])) : 1;
$pagenum = 0;

$_G['user'] = new User;
$_G['user']->login();
$_USER = $_G['user']->toArray();

//Debug模式
if(!empty($_CONFIG['debugmode'])){
	error_reporting(E_ALL | E_STRICT ^ E_NOTICE);
}

//错误日志
if(!empty($_CONFIG['log_error'])){
	function custom_error_log($errno, $errstr, $errfile, $errline){
		global $PHP_SELF, $_G;
		writelog('error', $errno."\t".$errstr."\t".$errfile."\t".$errline."\t".$_G['user']->id."\t".$PHP_SELF."\t".json_encode($_POST)."\t".json_encode($_GET));
		return false;
	}
	set_error_handler('custom_error_log', E_ALL | E_STRICT);
}

if(!defined('IN_ADMINCP')){
	//用户访问日志
	if(!empty($_CONFIG['log_request'])){
		function custom_request_log(){
			global $_G;
			writelog('request', $_G['request_log']."\t".(microtime(true) - $_G['starttime'])."\t".$_G['db']->querynum);
		}
		register_shutdown_function('custom_request_log');

		$_G['request_log'] = $_G['user']->id."\t".$PHP_SELF."\t".json_encode($_POST)."\t".json_encode($_GET);
	}
}

?>
