<?php

require_once './core/init.inc.php';
require_once './plugin/qqconnect/qqConnectAPI.php';

$actions = array('login', 'unbind');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'login'){
	$callback = !empty($_GET['callback']);

	$qc = new QC();

	if($callback){
		$access_token = $qc->qq_callback();
		$open_id = $qc->get_openid();

		$qc = new QC($access_token, $open_id);
		$user_info = $qc->get_user_info();

		if($_G['user']->isLoggedIn()){
			if($_G['user']->qqopenid){
				showmsg('please_unbind_your_qq_first', 'back');
			}

			if(User::Exist($open_id, 'qqopenid')){
				showmsg('binded_qq_cannot_be_binded_again', 'back');
			}

			$_G['user']->qqopenid = $open_id;
			$_G['user']->nickname = $user_info['nickname'];

			redirect('order.php');
		}else{
			$user = new User;

			$user->fetch('*', array('qqopenid' => $open_id));
			if($user->id <= 0){
				$user->account = $open_id;
				$user->pwmd5 = '';
				$user->qqopenid = $open_id;
				$user->nickname = $user_info['nickname'];

				$user->insert();
			}

			$user->force_login();
			showmsg('successfully_logged_in_via_qq', 'market.php');
		}
	}else{
		$qc->qq_login();
	}

}elseif($action == 'unbind'){
	if(!$_G['user']->isLoggedIn()){
		showmsg('binding_require_user_logged_in', 'memcp.php');
	}

	if(!$_G['user']->qqopenid){
		showmsg('you_have_not_bind_qq', 'back');
	}

	if($_G['user']->qqopenid == $_G['user']->account){
		showmsg('qqopenid_cannot_be_unbinded_with_empty_account', 'memcp.php');
	}

	if(empty($_GET['confirm'])){
		showmsg('confirm_to_unbind_qq', 'confirm');
	}

	$_G['user']->qqopenid = NULL;

	showmsg('successfully_unbinded_qq', 'order.php');
}

?>
