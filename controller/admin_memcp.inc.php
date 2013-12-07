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
				showmsg('您两次输入的新密码不一致，请重新输入。', 'back');
			}

			if(!isset($_POST['old_password'])){
				showmsg('请输入旧密码，否则无法修改密码。', 'back');
			}

			$result = $_G['admin']->changePassword($_POST['old_password'], $_POST['password']);
			if($result === -1){
				showmsg('您输入的旧密码不正确，请重新输入。', 'back');
			}

			$account = $_G['admin']->account;
			$_G['admin']->logout();
			$_G['admin']->login($account, $_POST['password']);
		}

		showmsg('成功修改个人信息！', 'refresh');
	}

	include view('memcp_edit');

}else{
	$_G['admin']->logout();
	redirect('admin.php');
}


?>
