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

class WeixinAutoreplyModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('weixin');
	}

	public function defaultAction(){
		$this->listAction();
	}

	public function editAction(){
		$autoreply = array();

		if(!empty($_POST['keyword'])){
			$autoreply['keyword'] = $_POST['keyword'];
			$autoreply['keyword'] = explode("\n", $autoreply['keyword']);
			foreach ($autoreply['keyword'] as &$word) {
				$word = trim($word);
			}
			unset($word);
			$autoreply['keyword'] = implode("\n", $autoreply['keyword']);
		}

		if(!empty($_POST['reply'])){
			$autoreply['reply'] = trim($_POST['reply']);
		}

		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		global $db;
		$table = $db->select_table('autoreply');
		if($id > 0){
			$table->update($autoreply, 'id='.$id);
			$autoreply['id'] = $id;
		}else{
			$table->insert($autoreply);
			$autoreply['id'] = $table->insert_id();
		}

		Autoreply::RefreshCache();

		echo json_encode($autoreply);
		exit;
	}

	public function deleteAction(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if($id > 0){
			Autoreply::RefreshCache();

			global $db;
			$table = $db->select_table('autoreply');
			$table->delete('id='.$id);
			echo $db->affected_rows;
		}else{
			echo 0;
		}
		exit;
	}

	public function listAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('autoreply');
		$autoreply = $table->fetch_all('*');
		include view('autoreply');
	}

}

?>
