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
				showmsg('请先解除已经绑定的QQ。', 'back');
			}

			if(User::Exist($open_id, 'qqopenid')){
				showmsg('该QQ已经绑定其他账号，您不能再次绑定。您需要通过该QQ登录本站解除绑定，才能绑定新账号。', 'back');
			}

			$_G['user']->qqopenid = $open_id;
			$_G['user']->nickname = $user_info['nickname'];

			redirect('home.php');
		}else{
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
		}
	}else{
		$qc->qq_login();
	}

}elseif($action == 'unbind'){
	if(!$_G['user']->isLoggedIn()){
		showmsg('请先登录。', 'memcp.php');
	}

	if(!$_G['user']->qqopenid){
		showmsg('您未绑定QQ账号。', 'back');
	}

	if($_G['user']->qqopenid == $_G['user']->account){
		showmsg('您的账号是通过QQ登录自动注册的，需要先设定本站登录账号和密码才能解绑，否则会造成您的账号无法再次登录。', 'memcp.php');
	}

	if(empty($_GET['confirm'])){
		showmsg('您确定要解除与QQ的绑定吗？', 'confirm');
	}

	$_G['user']->qqopenid = '';
	showmsg('成功解除与QQ的绑定。', 'home.php');
}

?>
