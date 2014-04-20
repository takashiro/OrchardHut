<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$type = !empty($_GET['type']) ? $_GET['type'] : 'system';

switch($type){
case 'system':
	@$config = !empty($_POST['system']) ? $_POST['system'] : null;

	@$config = array(
		'sitename' => $config['sitename'],
		'timezone' => intval($config['timezone']),
		'timefix' => intval($config['timefix']),
		'cookiepre' => $config['cookiepre'],
		'refversion' => $config['refversion'],
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
		showmsg('successfully_updated_system_config', 'refresh');
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

case 'product':
	$db->select_table('productunit');
	$action = &$_GET['action'];
	switch($action){
	case 'edit':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$unit = array();
		if(isset($_POST['name'])){
			$unit['name'] = trim($_POST['name']);
		}
		if(isset($_POST['type'])){
			$unit['type'] = intval($_POST['type']);
			$unit['type'] = $unit['type'] == 1 ? 1 : 2;
		}
		
		if($id > 0){
			$db->UPDATE($unit, 'id='.$id);
			$unit['id'] = $id;
		}else{
			$db->INSERT($unit);
			$unit['id'] = $db->insert_id();
		}

		writecache('productunits', NULL);

		echo json_encode($unit);
		exit;
		break;
	case 'delete':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			$db->DELETE('id='.$id);
			writecache('productunits', NULL);
			
			echo $db->affected_rows();
		}else{
			echo 0;
		}
		exit;
		break;
	default:
		$product_units = $db->MFETCH('*', '1 ORDER BY type,id');
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
		showmsg('successfully_updated_qqconnect_config', 'refresh');
	}

	break;


case 'wxconnect':
	$wxconnect = readdata('wxconnect');
	foreach(array('account', 'token', 'subscribe_text', 'entershop_keyword', 'bind_keyword', 'bind2_keyword') as $var){
		isset($wxconnect[$var]) || $wxconnect[$var] = '';
		isset($_POST['wxconnect'][$var]) && $wxconnect[$var] = $_POST['wxconnect'][$var];
	}

	if($_POST){
		writedata('wxconnect', $wxconnect);
		showmsg('successfully_updated_wxconnect_config', 'refresh');
	}

	break;

case 'autoreply':
	$db->select_table('autoreply');

	$action = &$_GET['action'];
	switch($action){
	case 'edit':
		$autoreply = array();
		
		if(!empty($_POST['keyword'])){
			$autoreply['keyword'] = $_POST['keyword'];
			$autoreply['keyword'] = explode("\n", $autoreply['keyword']);
			foreach ($autoreply['keyword'] as &$word) {
				$word = trim($word);
			}
			unset($word);
			$autoreply['keyword'] = implode("\n", $autoreply['keyword']);
		}

		if(!empty($_POST['reply'])){
			$autoreply['reply'] = addslashes(htmlspecialchars_decode(stripslashes(trim($_POST['reply']))));
		}

		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			$db->UPDATE($autoreply, 'id='.$id);
			$autoreply['id'] = $id;
		}else{
			$db->INSERT($autoreply);
			$autoreply['id'] = $db->insert_id();
		}

		Autoreply::RefreshCache();

		echo json_encode($autoreply);
		exit;

	case 'delete':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			Autoreply::RefreshCache();

			$db->DELETE('id='.$id);
			echo $db->affected_rows();
		}else{
			echo 0;
		}
		exit;

	default:
		$autoreply = $db->MFETCH('*');
	}
	break;

case 'delivery':
	$action = &$_GET['action'];
	switch($action){
	case 'edit':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			$timespan = array();
			if(isset($_POST['time_from'])){
				$timespan['time_from'] = $_POST['time_from'];
			}
			if(isset($_POST['time_to'])){
				$timespan['time_to'] = $_POST['time_to'];
			}
		}else{
			$timespan = array(
				'time_from' => $_POST['time_from'],
				'time_to' => $_POST['time_to']
			);
		}

		foreach($timespan as &$time){
			@list($H, $i, $s) = explode(':', $time);
			$H = intval($H);
			$i = intval($i);
			$s = intval($s);
			$time = $H * 3600 + $i * 60 + $s;
		}
		unset($time);

		if(isset($_POST['hidden'])){
			$timespan['hidden'] = !empty($_POST['hidden']);
		}

		$db->select_table('deliverytime');
		if($id > 0){
			$db->UPDATE($timespan, 'id='.$id);
			$timespan['id'] = $id;
		}else{


			$db->INSERT($timespan);
			$timespan['id'] = $db->insert_id();
		}

		isset($timespan['time_from']) && $timespan['time_from'] = gmdate('H:i:s', $timespan['time_from']);
		isset($timespan['time_to']) && $timespan['time_to'] = gmdate('H:i:s', $timespan['time_to']);
		echo json_encode($timespan);
		exit;
	case 'delete':
		@$id = intval($_REQUEST['id']);
		if($id > 0){
			$db->select_table('deliverytime');
			$db->DELETE('id='.$id);
			echo 1;
		}
		break;
	default:
		$db->select_table('deliverytime');
		$delivery_timespans = $db->MFETCH('*');
		foreach($delivery_timespans as &$s){
			$s['time_from'] = gmdate('H:i:s', $s['time_from']);
			$s['time_to'] = gmdate('H:i:s', $s['time_to']);
		}
		unset($s);
	}
	break;
}

include view('setting_'.$type);

?>
