<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('S_ROOT')) exit('access denied');

$action = !empty($_REQUEST['action']) ? trim($_REQUEST['action']) : ($_G['user']->isLoggedIn() ? 'home' : 'login');

switch($action){
case 'login':
	if($_G['user']->isLoggedIn()){
		showmsg('you_have_logged_in', 'index.php');
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
				showmsg('successfully_logged_in', 'index.php');
			}else{
				showmsg('successfully_logged_in', $_POST['http_referer']);
			}
		}else{
			showmsg('invalid_account_or_password', 'back');
		}
	}

	$wx = readdata('wxconnect');
	include view('login');
	break;

case 'logout':
	$_G['user']->logout();
	rsetcookie('delivering-order-number');
	rsetcookie('order-number-cache-time');
	redirect('index.php');
	break;

case 'register':
	if($_POST){
		$uid = User::Register($_POST);
		if($uid > 0){
			$_G['user']->login($_POST['account'], $_POST['password'], 'account');
			if(!empty($_COOKIE['referrerid'])){
				$referrerid = intval($_COOKIE['referrerid']);
				if(User::Exist($referrerid)){
					$_G['user']->referrerid = $referrerid;
				}
				rsetcookie('referrerid');
			}
			redirect('index.php?mod=product');
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

	redirect('index.php');
	break;

case 'edit':
	if(!$_G['user']->isLoggedIn()){
		showmsg('you_have_logged_in', 'index.php');
	}

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

		if(empty($_G['user']->account) && !empty($_POST['account'])){
			$account = trim($_POST['account']);
			if(!preg_match('/^[0-9a-z\x{4e00}-\x{9fa5}]+$/iu', $account)){
				showmsg('duplicated_account', 'back');
			}

			$length = strlen($account);
			if($length < 4 || $length > 15){
				showmsg('duplicated_account', 'back');
			}

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

			if(empty($_G['user']->account)){
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

		showmsg('successfully_update_profile', 'index.php?mod=user');
	}

	$referrer = new User;
	if($_G['user']->referrerid > 0){
		$referrer->fetch('id,nickname,account', array('id' => $_G['user']->referrerid));
	}
	$referrer = $referrer->toReadable();

	include view('edit');
	break;

default:
	$paymentconfig = readdata('payment');
	include view('home');
}

?>
