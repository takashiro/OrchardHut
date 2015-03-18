<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];


$table = $db->select_table('producttype');
$action = &$_GET['action'];
switch($action){
case 'edit':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$producttype = array();
	if(isset($_POST['name'])){
		$producttype['name'] = trim($_POST['name']);
	}
	if(isset($_POST['displayorder'])){
		$producttype['displayorder'] = intval($_POST['displayorder']);
	}

	if($id > 0){
		$table->update($producttype, 'id='.$id);
		$producttype['id'] = $id;
	}else{
		$table->insert($producttype);
		$producttype['id'] = $table->insert_id();
	}

	Product::RefreshCache();

	echo json_encode($producttype);
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
	$product_types = $table->fetch_all('*', '1 ORDER BY displayorder');
}

include view('producttype');

?>
