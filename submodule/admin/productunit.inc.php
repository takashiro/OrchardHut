<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

if($_G['admincp']['mode'] == 'permission'){
	return array();
}

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
