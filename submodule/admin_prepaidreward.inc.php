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
