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

$data = isset($_GET['data']) && $_GET['data'] == 'format' ? 'format' : 'component';
$actions = array('list', 'edit', 'delete');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($data == 'format'){
	$table = $db->select_table('addressformat');

	if($action == 'edit'){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		@$format = array(
			'name' => $_POST['name'],
			'displayorder' => intval($_POST['displayorder']),
		);

		if($id > 0){
			$table->update($format, 'id='.$id);
		}else{
			$table->insert($format);
			$id = $table->insert_id();
		}

		Address::RefreshCache();

		$format['id'] = $id;
		echo json_encode($format);

	}elseif($action == 'delete'){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		if($id > 0){
			$table->delete('id='.$id);
			echo $db->affected_rows;
		}else{
			echo 0;
		}
		Address::RefreshCache();
	}else{
		$address_format = Address::Format();
		include view('address_format');
	}

}else{
	$table = $db->select_table('addresscomponent');

	if($_POST){
		if($action == 'edit'){
			$component = array();

			$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
			if($id > 0){
				if(!empty($_POST['name'])){
					$component['name'] = $_POST['name'];
				}
				if(isset($_POST['displayorder'])){
					$component['displayorder'] = intval($_POST['displayorder']);
				}

				$table->update($component, 'id='.$id);
				$component['id'] = $id;

			}else{
				@$component = array(
					'name' => $_POST['name'],
					'displayorder' => intval($_POST['displayorder']),
					'parentid' => intval($_GET['parentid']),
				);

				if($component['parentid'] > 0){
					$parent_format = $table->result_first('formatid', 'id='.$component['parentid']);
					$format = Address::Format();
					while($format && $format[0]['id'] != $parent_format){
						array_shift($format);
					}
					if(array_key_exists(1, $format)){
						$component['formatid'] = $format[1]['id'];
					}
				}else{
					$table = $db->select_table('addressformat');
					$component['formatid'] = $table->result_first('id', '1 ORDER BY displayorder LIMIT 1');
				}

				$table = $db->select_table('addresscomponent');
				$table->insert($component);
				$component['id'] = $table->insert_id();
			}

			Address::RefreshCache();
			echo json_encode($component);

		}elseif($action == 'delete'){
			$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
			$affected_rows = 0;

			if($id > 0){
				$delete_id = array($id);

				while($delete_id){
					$delete_id = implode(',', $delete_id);
					$table->delete("id IN ($delete_id)");
					$affected_rows += $db->affected_rows;

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

		exit;
	}

	$parentid = !empty($_GET['id']) ? intval($_GET['id']) : 0;

	$prev_address = array();
	$cur = $parentid;
	while($cur){
		$a = $table->fetch_first('id,name,parentid', 'id='.$cur);
		$prev_address[] = $a;
		$cur = $a['parentid'];
	}
	$prev_address = array_reverse($prev_address);

	$address_components = $table->fetch_all('*', 'parentid='.$parentid.' ORDER BY displayorder,id');

	include view('address_component');
}

?>
