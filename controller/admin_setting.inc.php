<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$types = array('system', 'qqconnect', 'wxconnect');
$type = !empty($_GET['type']) && in_array($_GET['type'], $types) ? $_GET['type'] : $types[0];

switch($type){
case 'system':
	@$config = $_POST['system'];

	@$config = array(
		'sitename' => $config['sitename'],
		'timezone' => intval($config['timezone']),
		'timefix' => intval($config['timefix']),
		'cookiepre' => $config['cookiepre'],
		'charset' => 'utf-8',
		'style' => $config['style'],
		'debugmode' => !empty($config['debugmode']),
		'log_request' => !empty($config['log_request']),
		'log_error' => !empty($config['log_error']),
		'refresh_template' => !empty($config['refresh_template']),
		'ticket_tips' => $config['ticket_tips'],
		'head_element' => htmlspecialchars_decode(stripslashes($config['head_element'])),
	);

	if($_POST){
		writedata('config', $config);
		showmsg('成功修改系统配置！', 'refresh');
	}

	foreach($config as $var => $v){
		isset($_CONFIG[$var]) || $_CONFIG[$var] = $v;
	}

	$_G['stylelist'] = array();
	$styledir = S_ROOT.'view/';
	$view = opendir($styledir);
	while($style = readdir($view)){
		if($style{0} == '.'){
			continue;
		}

		if($style != 'admin' && is_dir($styledir.$style)){
			$_G['stylelist'][$style] = $style;
		}
	}

	break;

case 'qqconnect':
	$qqconnect = readdata('qqconnect');
	foreach(array('appid', 'appkey', 'callback', 'scope', 'errorReport', 'storageType', 'host', 'user', 'password', 'database') as $var){
		isset($qqconnect[$var]) || $qqconnect[$var] = '';
		isset($_POST['qqconnect'][$var]) && $qqconnect[$var] = $_POST['qqconnect'][$var];
	}

	if($_POST){
		writedata('qqconnect', $qqconnect);
		showmsg('成功修改账户互联设置！', 'refresh');
	}

	break;


case 'wxconnect':
	$wxconnect = readdata('wxconnect');
	foreach(array('account', 'token', 'subscribe_text', 'help_text') as $var){
		isset($wxconnect[$var]) || $wxconnect[$var] = '';
		isset($_POST['wxconnect'][$var]) && $wxconnect[$var] = $_POST['wxconnect'][$var];
	}

	if($_POST){
		writedata('wxconnect', $wxconnect);
		showmsg('成功修改账户互联设置！', 'refresh');
	}

	break;
}

include view('setting');

?>
