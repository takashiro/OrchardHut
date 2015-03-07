<?php

/*
	Load the language pack (./view/$_G[style]/$type.lang.php) and translate $from into the local language
*/
function lang($type, $from = NULL){
	$style = $GLOBALS['_G']['style'];
	$file = './view/'.$style.'/'.$type.'.lang.php';

	if(file_exists($file)){
		$lang = include $file;
	}else{
		$style = 'default';
		$file = './view/'.$style.'/'.$type.'.lang.php';
		$lang = file_exists($file) ? include $file : array();
	}

	if($style != 'default'){
		$default_lang = include './view/default/'.$type.'.lang.php';;
		foreach($default_lang as $key => $value){
			if(!isset($lang[$key])){
				$lang[$key] = $value;
			}
		}
	}

	if($from === NULL){
		return $lang;
	}elseif(isset($lang[$from])){
		return $lang[$from];
	}else{
		trigger_error('undefined message in language pack: '.$from, E_USER_ERROR);
		return $from;
	}
}

/*
	short for "show message". It displays a prompt and exit the script, and then redirect to $url_forward in a few seconds.
*/
function showmsg($message, $url_forward = ''){
	extract($GLOBALS, EXTR_SKIP);

	static $lang = NULL;
	$lang == NULL && $lang = lang('message');

	if(isset($lang[$message])){
		$message = $lang[$message];
	}

	if(empty($_GET['ajax'])){
		switch($url_forward){
			case 'back':
				$url_forward = 'javascript:history.back()';
			break;
			case 'refresh':
				$url_forward = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			break;
			case 'login':
				$url_forward = 'login.php';
			break;
			case 'confirm':
				if($_POST){
					$confirm_key = randomstr(8);
					rsetcookie('postdata_'.$confirm_key, serialize($_POST));
				}else{
					$confirm_key = '';
				}
				rsetcookie('http_referer', $_SERVER['HTTP_REFERER']);
			break;
		}

		include view('show_message');
	}else{
		echo json_encode(array('message' => $message, 'url_forward' => $url_forward));
	}
	exit();
}

/*
	Redirect to $url using JavaScript. JavaScript redirect forces some browsers to refresh page caches.
*/
function redirect($url){
	extract($GLOBALS, EXTR_SKIP);

	$url = addslashes($url);
	include view('redirect');
	exit;
}

/*
	Set or unset a new cookie variable.
	$extexpiry represents how long the new variable will exist. A year by default.
*/
function rsetcookie($varname, $value = '', $extexpiry = -1){
	global $_G;
	$varname = $_G['config']['cookiepre'].$varname;
	if(!$value){
		setcookie($varname, '', $_G['timestamp'] - 3600);
	}else{
		$extexpiry == -1 && $extexpiry = $_G['timestamp'] + 365 * 24 * 3600;
		setcookie($varname, $value, $extexpiry);
	}
}

/*
	Write the variable $data into the file ./data/cache/$file.php
*/
function writecache($file, $data){
	return file_put_contents(S_ROOT.'./data/cache/'.$file.'.php', '<?php return '.var_export($data, true).';?>');
}

/*
	Read from the cache file ./data/cache/$file.php. It returns NULL when the file doesn't exist.
*/
function readcache($file){
	if(file_exists(S_ROOT.'./data/cache/'.$file.'.php')){
		return include S_ROOT.'./data/cache/'.$file.'.php';
	}else{
		return NULL;
	}
}

/*
	Write the variable $data into the file ./data/$file.php
	Files here can't be deleted, unlike writecache().
	$data may be stored here only and its deletion causes possible problems.
*/
function writedata($file, $data){
	return file_put_contents(S_ROOT.'./data/'.$file.'.inc.php', '<?php return '.var_export($data, true).';?>');
}

/*
	Read from the cache file ./data/$file.php. It returns NULL when the file doesn't exist.
	Files here can't be deleted, unlike readcache().
	$data may be stored here only and its deletion causes possible problems.
*/
function readdata($file){
	$file = S_ROOT.'./data/'.$file.'.inc.php';
	if(file_exists($file)){
		return include $file;
	}else{
		return NULL;
	}
}

function raddslashes($str){
	if(is_array($str)){
		foreach($str as $key => $val){
			$str[$key] = raddslashes($val);
		}
	}else{
		$str = addslashes($str);
	}
	return $str;
}

function rhtmlspecialchars($str){
	if(is_array($str)){
		foreach($str as $key => $val){
			$str[$key] = rhtmlspecialchars($val);
		}
	}else{
		$str = htmlspecialchars($str);
	}
	return $str;
}

/*
	Include a template file from ./view/$_G[style]/$tpl.htm
	The file will be parsed and cached into ./data/template/
	We defined several statements used in template files like {if} {elseif} {/if} which
	can be quoted with <!-- and --> as HTML comments. Template files must be parsed into PHP scripts before they run.
*/
function view($tpl){
	global $_G;
	$htmpath = S_ROOT.'./view/'.$_G['style'].'/'.$tpl.'.htm';
	if(!file_exists($htmpath)){
		$htmpath = S_ROOT.'./view/default/'.$tpl.'.htm';
	}
	$tplpath = S_ROOT.'./data/template/'.$_G['style'].'_'.$tpl.'.tpl.php';
	if(!file_exists($tplpath) || (!empty($_G['config']['refresh_template']) && filemtime($htmpath) > filemtime($tplpath))){
		file_put_contents($tplpath, Template::parse_template($htmpath));
	}
	return $tplpath;
}

function rdate($dateline, $format = 'Y-m-d H:i:s'){
	return gmdate($format, $dateline + TIMEZONE * 3600);
}

function rmktime($hour, $minute, $second, $month, $day, $year){
	return gmmktime($hour, $minute, $second, $month, $day, $year) - TIMEZONE * 3600;
}

/*
	Convert $date into a UNIX timestamp
*/
function datetotimestamp($date){
	$date = explode('-', $date);
	if(count($date) != 3){
		return 0;
	}

	foreach($date as &$d){
		$d = intval($d);
	}
	unset($d);
	return rmktime(0, 0, 0, $date[1], $date[2], $date[0]);
}

/*
	Convert a time string Y-m-d H:i:s into a UNIX timestamp
*/
function rstrtotime($datetime){
	$datetime = explode(' ', $datetime);
	$date = explode('-', $datetime[0]);
	isset($date[1]) || $date[1] = 1;
	isset($date[2]) || $date[2] = 1;

	foreach($date as &$d){
		$d = intval($d);
	}

	if(isset($datetime[1])){
		$time = explode(':', $datetime[1]);
		isset($time[1]) || $time[1] = 0;
		isset($time[2]) || $time[2] = 0;

		foreach($time as &$t){
			$t = intval($t);
		}
	}else{
		$time = array(0, 0, 0);
	}

	return rmktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
}

/*
	Generate a random string of $length characters which are nothing but letters or numbers.
*/
function randomstr($length, $numeric = 0) {
	PHP_VERSION < '4.2.0' ? mt_srand((double) microtime() * 1000000) : mt_srand();
	$seed = base_convert(md5(print_r($_SERVER, 1).microtime()), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function rheader($string, $replace = true, $http_response_code = 0) {
	$string = str_replace(array("\r", "\n"), array('', ''), $string);
	if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
		@header($string, $replace);
	} else {
		@header($string, $replace, $http_response_code);
	}
	if(preg_match('/^\s*location:/is', $string)) {
		exit();
	}
}

function riconv($in, $out, $str){
	if(is_array($str)){
		foreach($str as $k => $v){
			$str[$k] = riconv($in, $out, $v);
		}
		return $str;
	}else{
		return iconv($in, $out, $str);
	}
}

function rmd5($str){
	return md5($str.$GLOBALS['_G']['config']['salt']);
}

function writelog($logfile, $data){
	$logfile = S_ROOT.'./data/log/'.rdate(TIMESTAMP, 'Ymd').'_'.$logfile.'.log.php';

	$need_prefix = !file_exists($logfile);

	if(is_array($data)){
		foreach($data as $k => $v){
			$data[$k] = User::ip()."\t".TIMESTAMP."\t".$v;
		}
		$data = implode("\r\n", $data);
	}else{
		$data = User::ip()."\t".TIMESTAMP."\t".$data;
	}

	$fp = fopen($logfile, 'a');
	flock($fp, LOCK_EX);
	if($need_prefix){
		fwrite($fp, '<?php exit;?>');
	}
	fwrite($fp, "\r\n".$data);
	fclose($fp);
}

?>
