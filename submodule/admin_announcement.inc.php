<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

switch($action){
	case 'list':
		$table = $db->select_table('announcement');
		$announcements = $table->fetch_all('*', '1 ORDER BY displayorder,time_start DESC,time_end');
		include view('announcement_list');
	break;

	case 'edit':
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

			include view('announcement_edit');
		}
	break;

	case 'delete':
		if(isset($_POST['id'])){
			$id = intval($_POST['id']);
			$table = $db->select_table('announcement');
			$table->delete('id='.$id);
			echo $db->affected_rows;
		}
	break;
}

?>
