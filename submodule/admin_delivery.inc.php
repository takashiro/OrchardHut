<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$action = &$_GET['action'];
switch($action){
case 'edit':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	$timespan = array();
	if(isset($_POST['time_from'])){
		$timespan['time_from'] = $_POST['time_from'];
	}
	if(isset($_POST['time_to'])){
		$timespan['time_to'] = $_POST['time_to'];
	}
	if(isset($_POST['deadline'])){
		$timespan['deadline'] = $_POST['deadline'];
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
		$timespan['hidden'] = !empty($_POST['hidden']) ? 1 : 0;
	}

	if(isset($_POST['effective_time'])){
		$timespan['effective_time'] = rstrtotime($_POST['effective_time']);
	}

	if(isset($_POST['expiry_time'])){
		$timespan['expiry_time'] = rstrtotime($_POST['expiry_time']);
	}

	$db->select_table('deliverytime');
	if($id > 0){
		$db->UPDATE($timespan, 'id='.$id);
		$timespan['id'] = $id;
	}else{
		$db->INSERT($timespan);
		$timespan['id'] = $db->insert_id();
	}

	foreach(array('deadline', 'time_from', 'time_to') as $var){
		isset($timespan[$var]) && $timespan[$var] = floor($timespan[$var] / 3600).gmdate(':i:s', $timespan[$var]);
	}

	foreach(array('effective_time', 'expiry_time') as $var){
		isset($timespan[$var]) && $timespan[$var] = rdate($timespan[$var]);
	}

	DeliveryTime::UpdateCache();
	echo json_encode($timespan);
	exit;
case 'delete':
	@$id = intval($_REQUEST['id']);
	if($id > 0){
		$db->select_table('deliverytime');
		$db->DELETE('id='.$id);
		DeliveryTime::UpdateCache();
		echo 1;
	}
	break;
case 'config':
	$deliveryconfig = array();

	foreach(Order::$DeliveryMethod as $methodid => $name){
		if(isset($_POST['fee'][$methodid])){
			$deliveryconfig['fee'][$methodid] = max(0, floatval($_POST['fee'][$methodid]));
		}
	}

	writedata('deliveryconfig', $deliveryconfig);
	showmsg('successfully_updated_delivery_config');

default:
	$deliveryconfig = readdata('deliveryconfig');

	$delivery_timespans = DeliveryTime::FetchAll();
	foreach($delivery_timespans as &$s){
		foreach(array('deadline', 'time_from', 'time_to') as $var){
			$s[$var] = floor($s[$var] / 3600).gmdate(':i:s', $s[$var]);
		}

		foreach(array('effective_time', 'expiry_time') as $var){
			$s[$var] = rdate($s[$var]);
		}
	}
	unset($s);
}

include view('delivery');

?>
