<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$table = $db->select_table('prepaidreward');

$action = &$_GET['action'];
switch($action){
case 'edit':
	$prepaidreward = array();

	foreach(array('minamount', 'maxamount', 'reward') as $var){
		if(isset($_POST[$var])){
			$prepaidreward[$var] = floatval($_POST[$var]);
		}
	}

	foreach(array('etime_start', 'etime_end') as $var){
		isset($_POST[$var]) && $prepaidreward[$var] = rstrtotime($_POST[$var]);
	}

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if($id > 0){
		$table->update($prepaidreward, 'id='.$id);
		echo $db->affected_rows;
	}else{
		$table->insert($prepaidreward);

		$prepaidreward['id'] = $table->insert_id();
		foreach(array('etime_start', 'etime_end') as $var){
			isset($prepaidreward[$var]) && $prepaidreward[$var] = rdate($prepaidreward[$var]);
		}

		echo json_encode($prepaidreward);
	}
	exit;

case 'delete':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if($id > 0){
		$table->delete('id='.$id);
		echo $db->affected_rows;
	}else{
		echo 0;
	}
	exit;

default:
	$prepaidrewards = $table->fetch_all('*');
	foreach($prepaidrewards as &$prepaidreward){
		foreach(array('etime_start', 'etime_end') as $var){
			$prepaidreward[$var] = rdate($prepaidreward[$var]);
		}
	}
	unset($prepaidreward);

	include view('prepaidreward');
}

?>
