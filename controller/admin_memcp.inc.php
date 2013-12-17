<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('edit', 'logout');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'edit'){
	if($_POST){
		if(isset($_POST['nickname'])){
			$_G['admin']->nickname = $_POST['nickname'];
		}

		if(!empty($_POST['password'])){
			if(empty($_POST['password2']) || $_POST['password'] != $_POST['password2']){
				showmsg('two_different_passwords', 'back');
			}

			if(!isset($_POST['old_password'])){
				showmsg('password_modifying_require_old_password', 'back');
			}

			$result = $_G['admin']->changePassword($_POST['old_password'], $_POST['password']);
			if($result === -1){
				showmsg('incorrect_old_password', 'back');
			}
		}

		showmsg('successfully_update_profile', 'refresh');
	}

	$_ADMIN = $_G['admin']->toArray();

	include view('memcp_edit');

}else{
	$_G['admin']->logout();
	redirect('admin.php');
}


?>
