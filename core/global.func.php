<?php

//显示一个消息，并跳转到$url_forward
function showmsg($message, $url_forward = ''){
	extract($GLOBALS, EXTR_SKIP);

	if(empty($_GET['ajax'])){
		switch($url_forward){
			case 'back':
				$url_forward = 'javascript:history.back()';
			break;
			case 'refresh':
				$url_forward = $_SERVER['HTTP_REFERER'];
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

function redirect($url){
	extract($GLOBALS, EXTR_SKIP);

	$url = addslashes($url);
	include view('redirect');
	exit;
}

//设置一个cookie, $extexpiry是有效时间长度
function rsetcookie($varname, $value = '', $extexpiry = 0){
	global $_G;
	$varname = $_G['config']['cookiepre'].$varname;
	if(!$value){
		setcookie($varname, '', $_G['timestamp'] - 3600);
	}else{
		$extexpiry == -1 && $extexpiry = $_G['timestamp'] + 365 * 24 * 3600;
		setcookie($varname, $value, $extexpiry);
	}
}

//分页设置
function multi($totalnum, $limit, $curpage, $baseurl){
	$pagelimit = 9;
	
	$pagenum = ceil($totalnum / $limit);
	if($pagenum <= 1){
		return '';
	}

	$startpage = max($curpage - floor($pagelimit / 2), 1);
	$endpage = min($curpage + floor($pagelimit / 2), $pagenum);
	$pre = strstr($baseurl, '?') ? '&' : '?';
	
	$html = '<div id="multipage">';
	
	if($curpage > 1){
		$html.= '<a href="'.$baseurl.$pre.'page=1">首页</a>';  //连续定义变量
		$html.= '<a href="'.$baseurl.$pre.'page='.($curpage - 1).'">上一页</a>';
	}

	for($i = $startpage; $i < $curpage; $i++){
		$html.= '<a href="'.$baseurl.$pre.'page='.$i.'">'.$i.'</a>';
	}

	$html.= '<a href="'.$baseurl.$pre.'page='.$i.'" class="current">'.$i.'</a>';

	for($i = $i + 1; $i <= $endpage; $i++){
		$html.= '<a href="'.$baseurl.$pre.'page='.$i.'">'.$i.'</a>';
	}
	
	if($curpage<$pagenum) {
		$html.= '<a href="'.$baseurl.$pre.'page='.($curpage + 1).'">下一页</a>';
		$html.= '<a href="'.$baseurl.$pre.'page='.$pagenum.'">末页</a>';
	}
	
	$html.= '&nbsp;共'.$pagenum.'页&nbsp;转到第<input type="text" id="mul_pagenumber" />页&nbsp;';
	$html.= '<a href="javascript:changePage('."'".$baseurl.$pre.'page='."'".','.$pagenum.');">确定</a>';
	$html.= '</div>';
	
	return $html;
}


function writecache($file, $data){
	return file_put_contents(S_ROOT.'./data/cache/'.$file.'.php', '<?php return '.var_export($data, true).';?>');
}

function readcache($file){
	if(file_exists(S_ROOT.'./data/cache/'.$file.'.php')){
		return include S_ROOT.'./data/cache/'.$file.'.php';
	}else{
		return NULL;
	}
}

//递归加上转义符号，可以处理数组
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

//递归转义HTML，可以处理数组
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

//html显示模板选择
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

//日期显示
function rdate($dateline, $format = 'Y-m-d H:i:s', $lang = 'en'){
	$str = gmdate($format, $dateline + $GLOBALS['_CONFIG']['timezone'] * 3600);
	
	if($lang == 'ch' && strpos($format, 'A') !== false){
		$str = str_replace(array('AM', 'PM'), array('上午', '下午'), $str);
	}

	return $str;
}

function rmktime($hour, $minute, $second, $month, $day, $year){
	return gmmktime($hour, $minute, $second, $month, $day, $year) - $GLOBALS['_CONFIG']['timezone'] * 3600;  //gmmktime() 函数取得 GMT 日期的 UNIX 时间戳
}

function datetotimestamp($date){
	$extra = 0;
	if(strpos($date, '下午') !== false){
		$extra += 12 * 3600;
	}

	$date = explode('-', $date);  //explode() 函数把字符串分割为数组。
	if(count($date) != 3){
		return 0;
	}

	foreach($date as &$d){
		$d = intval($d);
	}
	unset($d);
	return rmktime(0, 0, 0, $date[1], $date[2], $date[0]) + $extra;
}

function rstrtotime($datetime){
	return strtotime($datetime) - TIMEZONE * 3600;
}

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

function rfilesize($size){
	if($size < 1024){
		return $size.'B';
	}elseif($size < 1024 * 1024){
		return (floor($size / 1024 * 100) / 100).'KB'; //floor() 函数向下舍入为最接近的整数。
	}else{
		return (floor($size / 1024 / 1024 * 100) / 100).'MB';
	}
}

//?????
function rheader($string, $replace = true, $http_response_code = 0) {
	$string = str_replace(array("\r", "\n"), array('', ''), $string);
	if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
		@header($string, $replace);  
		//header() 函数向客户端发送原始的 HTTP 报头;@ 用在函数前，使不显示错误信息：
	} else {
		@header($string, $replace, $http_response_code);
	}
	if(preg_match('/^\s*location:/is', $string)) {
		exit();
	}
}

//?????
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

//用配置文件中的数值进行MD5加密
function rmd5($str){
	return md5($str.$GLOBALS['_G']['config']['salt']);  //salt?????
}

//写日志
function writelog($logfile, $data){
	$logfile = S_ROOT.'./data/log/'.rdate(TIMESTAMP, 'Ymd').'_'.$logfile.'.log.do';

	$need_prefix = !file_exists($logfile); //?????

	if(is_array($data)){
		foreach($data as $k => $v){
			$data[$k] = User::ip()."\t".TIMESTAMP."\t".$v;
		}
		$data = implode("\r\n", $data); //implode() 函数把数组元素组合为一个字符串。
	}else{
		$data = User::ip()."\t".TIMESTAMP."\t".$data;
	}

	$fp = fopen($logfile, 'a');
	flock($fp, LOCK_EX); 
	// flock() 函数锁定或释放文件。要取得独占锁定（写入的程序），将 lock 设为 LOCK_EX
	if($need_prefix){
		fwrite($fp, '<?php exit;?>');
	}
	fwrite($fp, "\r\n".$data);
	fclose($fp);
}

function submitcheck($var){
	if(isset($_POST[$var]) && ($_SERVER['REQUEST_METHOD'] == 'POST' && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))){
		return true;
	}else{
		return false;
	}
}

?>
