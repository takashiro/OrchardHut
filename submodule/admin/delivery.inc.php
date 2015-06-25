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

class DeliveryModule extends AdminControlPanelModule{
	public function editAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

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

		$table = $db->select_table('deliverytime');
		if($id > 0){
			$table->update($timespan, 'id='.$id);
			$timespan['id'] = $id;
		}else{
			$table->insert($timespan);
			$timespan['id'] = $table->insert_id();
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
	}

	public function deleteAction(){
		@$id = intval($_REQUEST['id']);
		if($id > 0){
			global $db;
			$table = $db->select_table('deliverytime');
			$table->delete('id='.$id);
			DeliveryTime::UpdateCache();
			echo 1;
		}
	}

	public function configAction(){
		$deliveryconfig = array();

		foreach(Order::$DeliveryMethod as $methodid => $name){
			foreach(array('fee', 'maxorderprice') as $var){
				if(isset($_POST['config'][$methodid][$var])){
					$deliveryconfig[$methodid][$var] = max(0, floatval($_POST['config'][$methodid][$var]));
				}
			}
		}

		writedata('deliveryconfig', $deliveryconfig);
		showmsg('successfully_updated_delivery_config');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

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

		include view('delivery');
	}
}

?>
