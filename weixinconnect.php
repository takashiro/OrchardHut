<?php

require_once './core/init.inc.php';

$actions = array('login', 'bind', 'unbind');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'login'){
	if(!$_G['user']->isLoggedIn()){
		if(empty($_GET['user']) || empty($_GET['key'])){
			showmsg('啊哦，我们遇到了点意外（参数不足）。', 'index.php');
		}

		$authkey = new Authkey($_GET['user']);
		if($authkey->isExpired()){
			showmsg('啊哦，我们遇到了点意外。该网页链接已失效，请回到微信重新操作。');
		}

		if(!$authkey->matchOnce($_GET['key'])){
			showmsg('啊哦，我们遇到了点意外。本网页链接无效了，请回到微信重新操作。');
		}

		$user = new User;

		$open_id = $_GET['user'];
		$user->fetchAttributesFromDB('*', array('wxopenid' => $open_id));
		if($user->id <= 0){
			$user->fetchAttributesFromDB('*', array('account' => $open_id));
			if($user->id > 0){
				$user->wxopenid = $open_id;
			}
		}
		if($user->id <= 0){
			$user->account = $open_id;
			$user->pwmd5 = '';
			$user->wxopenid = $open_id;
			$user->nickname = '微信用户';

			$user->insert();
		}

		$user->force_login();
	}

	redirect('index.php');

}elseif($action == 'bind'){
	if(!$_G['user']->isLoggedIn()){
		showmsg('请先登录，然后再进行绑定操作。', 'back');
	}
	
	if(!array_key_exists('user', $_GET) || !array_key_exists('key', $_GET)){
		showmsg('非法操作，参数不足。');
	}

	$authkey = new Authkey($_GET['user']);
	if($authkey->isExpired()){
		showmsg('该网页链接已失效，请重新进行绑定操作。');
	}

	if(User::Exist($_GET['user'], 'wxopenid')){
		showmsg('该微信账号已经绑定其他账号，请先通过微信登录然后解绑，才能重新绑定。');
	}

	if($authkey->matchOnce($_GET['key'])){
		$_G['user']->wxopenid = $_GET['user'];
		showmsg('成功绑定微信账号！', 'index.php');
	}else{
		showmsg('非法操作，本网页链接无效。');
	}

}else{
	if($_G['user']->isLoggedIn()){
		if($_G['user']->account == $_G['user']->wxopenid){
			showmsg('您的账号是通过微信登录自动注册的，需要先设定本站登录账号和密码才能解绑，否则会造成您的账号无法再次登录。', 'memcp.php');
		}

		$_G['user']->wxopenid = NULL;
		showmsg('成功解除该账号已绑定的微信账号！', 'refresh');
	}else{
		showmsg('请先登录，否则无法绑定。', 'memcp.php');
	}
}

?>
