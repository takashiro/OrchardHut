<?php

require_once './core/init.inc.php';

$actions = array('login', 'logout', 'register');
$action = !empty($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

if($action == 'login'){
	if($_G['user']->isLoggedIn()){
		redirect('home.php');
	}

	if($_POST){
		$result = USER::ACTION_FAILED;

		$methods = array('account');
		$method = !empty($_POST['method']) && in_array($_POST['method']) ? $_POST['method'] : $methods[0];

		if(!empty($_POST['account']) && !empty($_POST['password'])){
			$result = $_G['user']->login($_POST['account'], $_POST['password'], $method) ? User::ACTION_SUCCEEDED : User::ACTION_FAILED;
		}

		if($result == User::ACTION_SUCCEEDED){
			if(empty($_POST['http_referer'])){
				redirect('home.php');
			}else{
				redirect($_POST['http_referer']);
			}
		}else{
			showmsg('用户名或密码错误。', 'back');
		}
	}

	include view('login');

}elseif($action == 'logout'){
	$_G['user']->logout();
	redirect('memcp.php');

}elseif($action == 'register'){
	if($_POST){
		$uid = User::Register($_POST);
		if($uid > 0){
			$_G['user']->login($_POST['account'], $_POST['password']);
			redirect('market.php');
		}elseif($uid == User::INVALID_ACCOUNT){
			showmsg('用户名不能少于4个字，不能多于50字。', 'back');
		}elseif($uid == User::INVALID_PASSWORD){
			showmsg('密码长度不能少于6位。', 'back');
		}else{
			showmsg('未知错误。', 'back');
		}
	}

	redirect('memcp.php');
}

?>
