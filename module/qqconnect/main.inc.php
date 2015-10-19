<?php

/***********************************************************************
Elf Web App Framework
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

require_once MOD_ROOT.'3rdparty/qqConnectAPI.php';

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

			redirect('index.php');
		}else{
			$user = new User;

			$user->fetch('*', array('qqopenid' => $open_id));
			if($user->id <= 0){
				$user->account = null;
				$user->pwmd5 = '';
				$user->qqopenid = $open_id;
				$user->nickname = $user_info['nickname'];
				$user->regtime = TIMESTAMP;
				if(!empty($_COOKIE['referrerid'])){
					$referrerid = intval($_COOKIE['referrerid']);
					if(User::Exist($referrerid)){
						$user->referrerid = intval($_COOKIE['referrerid']);
					}
					rsetcookie('referrerid');
				}

				$user->insert('IGNORE');
				if($db->affected_rows <= 0){
					$user = new User;
					$user->fetch('*', array('qqopenid' => $open_id));
				}
			}

			$user->force_login();

			showmsg('successfully_logged_in_via_qq', './?mod=product');
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

	if(empty($_G['user']->account)){
		showmsg('qqopenid_cannot_be_unbinded_with_empty_account', 'memcp.php');
	}

	if(empty($_GET['confirm'])){
		showmsg('confirm_to_unbind_qq', 'confirm');
	}

	$_G['user']->qqopenid = NULL;

	showmsg('successfully_unbinded_qq', './?mod=order');
}

?>
