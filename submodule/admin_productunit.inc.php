<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];


$table = $db->select_table('productunit');
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
		$table->update($unit, 'id='.$id);
		$unit['id'] = $id;
	}else{
		$table->insert($unit);
		$unit['id'] = $table->insert_id();
	}

	Product::RefreshCache();

	echo json_encode($unit);
	exit;
	break;
case 'delete':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if($id > 0){
		$table->delete('id='.$id);
		Product::RefreshCache();

		echo $db->affected_rows;
	}else{
		echo 0;
	}
	exit;
	break;
default:
	$product_units = $table->fetch_all('*', '1 ORDER BY type,id');
}

include view('productunit');

?>
