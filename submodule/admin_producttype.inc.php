<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];


$db->select_table('producttype');
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
		$db->UPDATE($producttype, 'id='.$id);
		$producttype['id'] = $id;
	}else{
		$db->INSERT($producttype);
		$producttype['id'] = $db->insert_id();
	}

	writecache('producttypes', NULL);

	echo json_encode($producttype);
	exit;
	break;
case 'delete':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if($id > 0){
		$db->DELETE('id='.$id);
		writecache('producttypes', NULL);

		echo $db->affected_rows();
	}else{
		echo 0;
	}
	exit;
	break;
default:
	$product_types = $db->MFETCH('*', '1 ORDER BY displayorder');
}

include view('producttype');

?>