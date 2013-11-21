<?php

require_once './core/init.inc.php';
require_once './plugin/qqconnect/qqConnectAPI.php';

$actions = array('login', 'bind', 'unbind');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'login'){
	if($_G['user']->isLoggedIn()){
		redirect('home.php');
	}

	$callback = !empty($_GET['callback']);

	$qc = new QC();

	if($callback){
		$access_token = $qc->qq_callback();
		$open_id = $qc->get_openid();
		$user_info = $qc->get_user_info();

		$user = new User;

		$user->fetchAttributesFromDB('*', array('qqopenid' => $open_id));
		if($user->id <= 0){
			$user->account = $open_id;
			$user->pwmd5 = '';
			$user->qqopenid = $open_id;
			$user->nickname = $user_info['nickname'];

			$user->insert();
		}

		$user->force_login();
		redirect('market.php');
	}else{
		$qc->qq_login();
	}
}

?>
