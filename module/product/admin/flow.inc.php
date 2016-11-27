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

class ProductFlowModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('product');
	}

	public function editAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$flow = array();
		if(isset($_POST['name'])){
			$flow['name'] = trim($_POST['name']);
		}
		if(isset($_POST['displayorder'])){
			$flow['displayorder'] = intval($_POST['displayorder']);
		}

		if(isset($_POST['hidden'])){
			$flow['hidden'] = !empty($_POST['hidden']) ? 1 : 0;
		}

		global $db;
		$table = $db->select_table('productflow');
		if($id > 0){
			$table->update($flow, 'id='.$id);
			$flow['id'] = $id;
		}else{
			$table->insert($flow);
			$flow['id'] = $table->insert_id();
		}

		Product::RefreshCache();

		echo json_encode($flow);
		exit;
	}

	public function deleteAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			global $db;
			$table = $db->select_table('productflow');
			$table->delete('id='.$id);
			Product::RefreshCache();

			echo $db->affected_rows;
		}else{
			echo 0;
		}
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('productflow');
		$flows = $table->fetch_all('*', '1 ORDER BY displayorder');

		include view('flow');
	}
}

