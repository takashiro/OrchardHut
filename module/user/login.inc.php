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
