<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class DeliveryStationModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('delivery');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$station_list = $db->fetch_all("SELECT * FROM {$tpre}station");

		$condition = array();

		if(isset($_GET['time_start'])){
			$time_start = rstrtotime($_GET['time_start']);
			$condition[] = 'o.dateline>='.$time_start;
			$time_start = rdate($time_start, 'Y-m-d H:i');
		}else{
			$time_start = '';
		}

		if(isset($_GET['time_end'])){
			$time_end = rstrtotime($_GET['time_end']);
			$condition[] = 'o.dateline<='.$time_end;
			$time_end = rdate($time_end, 'Y-m-d H:i');
		}else{
			$time_end = '';
		}

		$condition = $condition ? ' AND '.implode(' AND ', $condition) : '';

		foreach($station_list as &$station){
			$station_range = Address::Extension($station['orderrange']);
			$station_range = implode(',', $station_range);
			if($station_range){
				$station_comment = $db->fetch_first("SELECT AVG(level1) AS level1,AVG(level2) AS level2,AVG(level3) AS level3, COUNT(*) AS commentnum
					FROM {$tpre}ordercomment c
						LEFT JOIN {$tpre}order o ON o.id=c.orderid
					WHERE o.addressid IN ($station_range) $condition");
			}else{
				$station_comment = array(
					'level1' => null,
					'level2' => null,
					'level3' => null,
					'commentnum' => 0,
				);
			}
			$station = array_merge($station, $station_comment);
		}
		unset($station);

		include view('station_list');
	}

	public function editAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		if($_POST){
			$station = array();

			if(isset($_POST['name'])){
				$station['name'] = htmlspecialchars(trim($_POST['name']));
			}
			if(isset($_POST['address'])){
				$station['address'] = htmlspecialchars(trim($_POST['address']));
			}
			if(isset($_POST['orderrange'])){
				$station['orderrange'] = intval($_POST['orderrange']);
			}

			global $db;
			$table = $db->select_table('station');
			if($id > 0){
				$table->update($station, 'id='.$id);
				$station['id'] = $id;
			}else{
				$table->insert($station);
				$station['id'] = $table->insert_id();
			}

			if(!empty($_GET['ajax'])){
				echo json_encode($station);
				exit;
			}else{
				showmsg('edit_succeed', 'refresh');
			}

		}else{
			if($id > 0){
				global $db;
				$table = $db->select_table('station');
				$station = $table->fetch_first('*', 'id='.$id);
			}else{
				$station = array(
					'id' => 0,
					'name' => 0,
					'address' => '',
					'range' => 0,
				);
			}
		}

		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		include view('station_edit');
 	}

}
