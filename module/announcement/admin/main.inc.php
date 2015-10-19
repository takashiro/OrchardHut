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

class AnnouncementMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		$this->listAction();
	}

	public function listAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$table = $db->select_table('announcement');
		$announcements = $table->fetch_all('*', '1 ORDER BY displayorder,time_start DESC,time_end');
		include view('list');
	}

	public function editAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		if($_POST){
			if($id > 0){
				$announcement = new Announcement($id);
				if(!empty($_POST['time_start'])){
					$announcement->time_start = rstrtotime($_POST['time_start']);
				}
				if(!empty($_POST['time_end'])){
					$announcement->time_end = rstrtotime($_POST['time_end']);
				}
				if(!empty($_POST['title'])){
					$announcement->title = $_POST['title'];
				}
				if(!empty($_POST['content'])){
					$announcement->content = htmlspecialchars_decode($_POST['content']);
				}

				if(isset($_POST['displayorder'])){
					$announcement->displayorder = intval($_POST['displayorder']);
				}

				Announcement::RefreshCache();

				if(empty($_REQUEST['ajax'])){
					showmsg('succesfully_edited_announcement', 'refresh');
				}else{
					echo json_encode($announcement->toReadable());
				}

			}else{
				if(empty($_POST['title'])){
					exit;
				}

				$announcement = new Announcement;
				$announcement->title = trim($_POST['title']);
				if(!empty($_POST['time_start'])){
					$announcement->time_start = rstrtotime($_POST['time_start']);
				}else{
					$announcement->time_start = TIMESTAMP;
				}
				if(!empty($_POST['time_end'])){
					$announcement->time_end = rstrtotime($_POST['time_end']);
				}else{
					$announcement->time_end = TIMESTAMP + 24 * 3600;
				}

				$announcement->displayorder = isset($_POST['displayorder']) ? intval($_POST['displayorder']) : 0;

				$announcement->insert();

				Announcement::RefreshCache();

				echo json_encode($announcement->toReadable());
			}
		}else{
			if($id > 0){
				$announcement = new Announcement($id);
				$announcement = $announcement->toReadable();
			}else{
				$announcement = array(
					'id' => 0,
					'title' => '',
					'content' => '',
					'time_start' => '',
					'time_end' => '',
				);
			}

			include view('edit');
		}
	}

	public function deleteAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(isset($_POST['id'])){
			$id = intval($_POST['id']);
			$table = $db->select_table('announcement');
			$table->delete('id='.$id);
			echo $db->affected_rows;
		}
	}
}

?>
