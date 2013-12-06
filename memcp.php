<?php

require_once './core/init.inc.php';

$actions = array('login', 'logout', 'register', 'edit');
$action = !empty($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

if($_G['user']->isLoggedIn()){
	if($action != 'logout'){
		$action = 'edit';
	}
}

if($action == 'login'){
	if($_G['user']->isLoggedIn()){
		showmsg('您已登录，不需要再次登录。', 'home.php');
	}

	if($_POST){
		$result = USER::ACTION_FAILED;

		$methods = array('account');
		$method = !empty($_POST['method']) && in_array($_POST['method'], $methods) ? $_POST['method'] : $methods[0];

		if(!empty($_POST['account']) && !empty($_POST['password'])){
			$result = $_G['user']->login($_POST['account'], $_POST['password'], $method) ? User::ACTION_SUCCEEDED : User::ACTION_FAILED;
		}

		if($result == User::ACTION_SUCCEEDED){
			if(empty($_POST['http_referer'])){
				showmsg('登录成功！', 'home.php');
			}else{
				showmsg('登录成功！', $_POST['http_referer']);
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
			$_G['user']->login($_POST['account'], $_POST['password'], 'account');
			redirect('market.php');
		}elseif($uid == User::INVALID_ACCOUNT){
			showmsg('用户名不能少于4个字，不能多于50字。', 'back');
		}elseif($uid == User::INVALID_PASSWORD){
			showmsg('密码长度不能少于6位。', 'back');
		}elseif($uid == User::DUPLICATED_ACCOUNT){
			showmsg('该用户名已经被注册，请更换一个用户名。', 'back');
		}else{
			showmsg('未知错误。', 'back');
		}
	}

	redirect('memcp.php');

}else if($action == 'edit'){
	if($_POST){
		if(isset($_POST['mobile'])){
			$mobile = trim($_POST['mobile']);
			if($mobile != '' && !User::IsMobile($mobile)){
				showmsg('请输入正确的手机号码', 'back');
			}

			$_G['user']->mobile = $mobile;
		}

		if(isset($_POST['email'])){
			$email = trim($_POST['email']);
			if($email != '' && !User::IsEmail($email)){
				showmsg('请输入正确的电子邮箱地址。', 'back');
			}

			$_G['user']->email = $email;
		}

		if(isset($_POST['nickname'])){
			$_G['user']->nickname = mb_substr($_POST['nickname'], 0, 8, 'utf8');
		}

		if($_G['user']->account == $_G['user']->qqopenid && !empty($_POST['account'])){
			$account = trim($_POST['account']);
			$duplicated = $db->result_first("SELECT id FROM {$tpre}user WHERE account='$account'");
			if($duplicated){
				showmsg('您设定的用户名已经被使用，请重新设定一个用户名。', 'back');
			}

			$_G['user']->account = $account;
		}

		if(!empty($_POST['new_password'])){
			if(empty($_POST['new_password2'])){
				showmsg('请输入两次新密码用以再次确认。', 'back');
			}

			if($_G['user']->account == $_G['user']->qqopenid){
				showmsg('设置登录密码同时需要设定登录用户名，请填写用户名。', 'back');
			}

			if($_G['user']->pwmd5){
				if(empty($_POST['old_password'])){
					showmsg('修改密码需要填写原密码。', 'back');
				}

				$result = $_G['user']->changePassword($_POST['old_password'], $_POST['new_password'], $_POST['new_password2']);
				if($result !== true){
					if($result == User::PASSWORD2_WRONG){
						showmsg('您两次输入的密码不一致，请重新输入。', 'back');
					}elseif($result == User::OLD_PASSWORD_WRONG){
						showmsg('您输入的旧密码不正确，请重新输入。', 'back');
					}
				}
			}else{
				if($_POST['new_password'] != $_POST['new_password2']){
					showmsg('您两次输入的密码不一致，请重新输入。', 'back');
				}

				$_G['user']->pwmd5 = rmd5($_POST['new_password']);
			}
		}

		showmsg('修改个人信息成功！', 'memcp.php');
	}

	include view('memcp_edit');
}

?>
