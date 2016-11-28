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

if(!$_G['user']->isLoggedIn()){
	redirect('index.php?mod=user:login');
}

$paidstate = array(Wallet::TradeSuccess, Wallet::TradeFinished);
$paidstate = implode(',', $paidstate);
$ribbons = $db->fetch_all("SELECT r.*,o.* FROM
	{$tpre}ribbon r
		LEFT JOIN {$tpre}ribbonorder o ON o.id=r.orderid
	WHERE r.userid={$_G['user']->id}
		AND r.restnum>0
		AND o.tradestate IN ($paidstate)");

$_G['user']->formkey = 0;
$_G['user']->refreshFormKey();

include view('ribbon');
