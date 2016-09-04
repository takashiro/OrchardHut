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
