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
		showmsg('you_have_logged_in', 'home.php');
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
				showmsg('successfully_logged_in', 'home.php');
			}else{
				showmsg('successfully_logged_in', $_POST['http_referer']);
			}
		}else{
			showmsg('invalid_account_or_password', 'back');
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
			showmsg('account_too_short_or_too_long', 'back');
		}elseif($uid == User::INVALID_PASSWORD){
			showmsg('password_too_short', 'back');
		}elseif($uid == User::DUPLICATED_ACCOUNT){
			showmsg('duplicated_account', 'back');
		}else{
			showmsg('unknown_error_period', 'back');
		}
	}

	redirect('memcp.php');

}else if($action == 'edit'){
	if($_POST){
		if(isset($_POST['mobile'])){
			$mobile = trim($_POST['mobile']);
			if($mobile != ''){
				if(!User::IsMobile($mobile)){
					showmsg('incorrect_mobile_number', 'back');
				}

				if(User::Exist($mobile, 'mobile')){
					showmsg('duplicated_mobile', 'back');
				}
			}else{
				$mobile = NULL;
			}

			$_G['user']->mobile = $mobile;
		}

		if(isset($_POST['email'])){
			$email = trim($_POST['email']);
			if($email != ''){
				if(!User::IsEmail($email)){
					showmsg('invalid_email', 'back');
				}

				if(User::Exist($email, 'email')){
					showmsg('duplicated_email', 'back');
				}
			}else{
				$email = NULL;
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
				showmsg('duplicated_account', 'back');
			}

			$_G['user']->account = $account;
		}

		if(!empty($_POST['new_password'])){
			if(empty($_POST['new_password2'])){
				showmsg('please_confim_your_password', 'back');
			}

			if($_G['user']->account == $_G['user']->qqopenid){
				showmsg('cannot_set_password_without_an_account', 'back');
			}

			if($_G['user']->pwmd5){
				if(empty($_POST['old_password'])){
					showmsg('password_modifying_require_old_password', 'back');
				}

				$result = $_G['user']->changePassword($_POST['old_password'], $_POST['new_password'], $_POST['new_password2']);
				if($result !== true){
					if($result == User::PASSWORD2_WRONG){
						showmsg('two_passwords_are_different', 'back');
					}elseif($result == User::OLD_PASSWORD_WRONG){
						showmsg('incorrect_old_password', 'back');
					}
				}
			}else{
				if($_POST['new_password'] != $_POST['new_password2']){
					showmsg('two_passwords_are_different', 'back');
				}

				$_G['user']->pwmd5 = rmd5($_POST['new_password']);
			}
		}

		showmsg('successfully_update_profile', 'memcp.php');
	}

	include view('memcp_edit');
}

?>
