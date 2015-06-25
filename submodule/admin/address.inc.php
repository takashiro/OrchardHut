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

class AddressModule extends AdminControlPanelModule{
	public function defaultAction(){
		$this->listAction();
	}

	public function formatAction(){
		if($_POST){
			$addressformat = array();
			for($i = 1; $i <= Address::MAX_CASCADE_LEVEL; $i++){
				if(!empty($_POST['addressformat'][$i])){
					$addressformat[$i] = trim($_POST['addressformat'][$i]);
				}else{
					break;
				}
			}
			writedata('addressformat', $addressformat);
			showmsg('edit_succeed', 'refresh');
		}
	}

	public function editAction(){
		if(empty($_POST))
			return;

		global $db, $tpre;

		$component = array();

		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			if(!empty($_POST['name'])){
				$component['name'] = $_POST['name'];
			}
			if(isset($_POST['displayorder'])){
				$component['displayorder'] = intval($_POST['displayorder']);
			}

			$parentname = '';
			if(isset($_POST['parentid'])){
				$component['parentid'] = intval($_POST['parentid']);
				$parentname = $db->result_first("SELECT name FROM {$tpre}addresscomponent WHERE id={$component['parentid']}");
				if(!$parentname){
					unset($component['parentid']);
				}
			}

			$table = $db->select_table('addresscomponent');
			$table->update($component, 'id='.$id);
			$component['id'] = $id;
			if($parentname){
				$component['parentname'] = $parentname;
			}

		}else{
			@$component = array(
				'name' => $_POST['name'],
				'displayorder' => intval($_POST['displayorder']),
				'parentid' => intval($_GET['parentid']),
			);

			$table = $db->select_table('addresscomponent');
			$table->insert($component);
			$component['id'] = $table->insert_id();
		}

		Address::RefreshCache();
		echo json_encode($component);
	}

	public function deleteAction(){
		if(empty($_POST))
			return;

		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		$affected_rows = 0;

		if($id > 0){
			$delete_id = array($id);

			while($delete_id){
				$delete_id = implode(',', $delete_id);

				global $db;
				$table = $db->select_table('addresscomponent');
				$table->update(array('hidden' => 1), "id IN ($delete_id)");
				$affected_rows += $db->affected_rows;

				$db->query("DELETE FROM {$tpre}deliveryaddress WHERE addressid IN ($delete_id)");

				$nodes = $table->fetch_all('id', "parentid IN ($delete_id)");
				$delete_id = array();
				foreach($nodes as $n){
					$delete_id[] = $n['id'];
				}
			}
		}

		Address::RefreshCache();
		echo $affected_rows;
	}

	public function listAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$parentid = !empty($_GET['id']) ? intval($_GET['id']) : 0;

		$prev_address = array();
		$cur = $parentid;
		$table = $db->select_table('addresscomponent');
		while($cur){
			$a = $table->fetch_first('id,name,parentid', 'id='.$cur);
			$prev_address[] = $a;
			$cur = $a['parentid'];
		}
		$prev_address = array_reverse($prev_address);

		$address_components = $db->fetch_all("SELECT o.*,p.name AS parentname
			FROM {$tpre}addresscomponent o
				LEFT JOIN {$tpre}addresscomponent p ON p.id=o.parentid
			WHERE o.parentid=$parentid AND o.hidden=0
			ORDER BY o.displayorder,o.id");

		$addressformat = readdata('addressformat');

		include view('address_component');
	}
}

?>