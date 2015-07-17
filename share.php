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

require_once './core/init.inc.php';

if(isset($_GET['uid'])){
	$uid = intval($_GET['uid']);
	$user = new User;
	$user->fetch('*', array('id' => $uid));

	if($user->exists()){
		$user = $user->toReadable();
		include view('share_qrcode');
	}
}elseif(isset($_GET['referrerid'])){
	$referrerid = intval($_GET['referrerid']);
	if($_G['user']->isLoggedIn()){
		if($_G['user']->referrerid > 0){
			$referrer = new User;
			$referrer->fetch('id,nickname,regtime', array('id' => $_G['user']->referrerid));
			showmsg(lang('message', 'your_referrer_is').$referrer->nickname, 'market.php');
		}

		if($_G['user']->id != $referrerid){
			$referrer = new User;
			$referrer->fetch('id,nickname,regtime', array('id' => $referrerid));
			if($referrer->exists() && $referrer->regtime < $_G['user']->regtime){
				$_G['user']->referrerid = $referrerid;
				showmsg(lang('message', 'your_referrer_is').$referrer->nickname, 'market.php');
			}else{
				showmsg('you_registered_earlier_than_the_referrer', 'market.php');
			}
		}
	}else{
		rsetcookie('referrerid', $referrerid);
		redirect('market.php');
	}
}

?>
