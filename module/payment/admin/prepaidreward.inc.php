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

class PaymentPrepaidRewardModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('payment');
	}

	public function editAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('prepaidreward');
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
	}

	public function deleteAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			global $db;
			$table = $db->select_table('prepaidreward');
			$table->delete('id='.$id);
			echo $db->affected_rows;
		}else{
			echo 0;
		}
		exit;
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('prepaidreward');
		$prepaidrewards = $table->fetch_all('*');
		foreach($prepaidrewards as &$prepaidreward){
			foreach(array('etime_start', 'etime_end') as $var){
				$prepaidreward[$var] = rdate($prepaidreward[$var]);
			}
		}
		unset($prepaidreward);

		include view('prepaidreward');
	}

}

?>
