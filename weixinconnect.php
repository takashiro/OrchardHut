<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

require_once './core/init.inc.php';

$actions = array('login', 'bind', 'unbind');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'login'){
	if(!$_G['user']->isLoggedIn()){
		if(empty($_GET['user']) || empty($_GET['key'])){
			showmsg('unexpected_link_with_inadequate_parameters', 'index.php');
		}

		$authkey = new Authkey($_GET['user']);
		if($authkey->isExpired()){
			showmsg('expired_wxlogin_link');
		}

		if(!$authkey->matchOnce($_GET['key'])){
			showmsg('invalid_wxlogin_link');
		}

		$user = new User;

		$open_id = $_GET['user'];
		$user->fetch('*', array('wxopenid' => $open_id));
		if($user->id <= 0){
			$user->fetch('*', array('account' => $open_id));
			if($user->id > 0){
				$user->wxopenid = $open_id;
			}
		}
		if($user->id <= 0){
			$user->account = $open_id;
			$user->pwmd5 = '';
			$user->wxopenid = $open_id;
			$user->nickname = lang('message', 'wxuser');

			$user->insert('IGNORE');
			if($db->affected_rows <= 0){
				$user = new User;
				$user->fetch('*', array('wxopenid' => $open_id));
			}
		}

		$user->force_login();
	}

	redirect('index.php');

}elseif($action == 'bind'){
	if(!$_G['user']->isLoggedIn()){
		showmsg('binding_require_user_logged_in', 'back');
	}

	if(!array_key_exists('user', $_GET) || !array_key_exists('key', $_GET)){
		showmsg('unexpected_link_with_inadequate_parameters', 'index.php');
	}

	$authkey = new Authkey($_GET['user']);
	if($authkey->isExpired()){
		showmsg('expired_wxbind_link');
	}

	if(User::Exist($_GET['user'], 'wxopenid')){
		showmsg('wxopenid_binded_to_another_account');
	}

	if($authkey->matchOnce($_GET['key'])){
		$_G['user']->wxopenid = $_GET['user'];
		showmsg('successfully_binded_wxopenid', 'index.php');
	}else{
		showmsg('invalid_wxbind_link');
	}

}else{
	if($_G['user']->isLoggedIn()){
		if($_G['user']->account == $_G['user']->wxopenid){
			showmsg('cannot_unbind_wxopenid_with_account_empty', 'memcp.php');
		}

		$_G['user']->wxopenid = NULL;
		showmsg('successfully_unbinded_wxopenid', 'refresh');
	}else{
		showmsg('binding_require_user_logged_in', 'memcp.php');
	}
}

?>
