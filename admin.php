<?php

define('IN_ADMINCP', true);//CPanel modules admin_* can't be executed without the contant
require_once './core/init.inc.php';

//Administrator automatically logs in via cookie records
$_G['admin'] = new Administrator;
$_G['admin']->login();

//Handle user login or logout requests
if(!$_G['admin']->isLoggedIn()){
	if($_POST && !empty($_POST['account']) && !empty($_POST['password'])){
		$result = $_G['admin']->login($_POST['account'], $_POST['password']);

		if($result){
			redirect('admin.php');
		}else{
			showmsg('invalid_account_or_password', 'back');
		}
	}

	include view('login');
	exit;
}

$_ADMIN = $_G['admin']->toReadable();

//Include the requested module
$public_mod = array('home', 'memcp');
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : 'home';
$module = './controller/admin_'.$mod.'.inc.php';
$mod_url = 'admin.php?mod='.$mod;
if(file_exists($module)){
	if(!in_array($mod, $public_mod) && !$_G['admin']->hasPermission($mod)){
		showmsg('no_permission', 'back');
	}

	include $module;
}else{
	$mod_url = 'admin.php?mod=home';
	include './controller/admin_home.inc.php';
}

?>
