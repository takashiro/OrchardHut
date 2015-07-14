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

if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$paymentconfig = readdata('payment');
if(isset($_GET['orderid'])){
	if(empty($paymentconfig['enabled_method'][Order::PaidWithWallet])){
		showmsg('wallet_payment_is_disabled');
	}

	$order = new Order($_GET['orderid']);
	if(!$order->exists() || $order->status == Order::Canceled){
		showmsg('order_not_exist', 'back');
	}

	if(!empty($order->alipaystate)){
		showmsg('your_alipay_wallet_is_processing_the_order', 'back');
	}

	if($_G['user']->wallet < $order->totalprice){
		showmsg('wallet_is_insufficient', 'back');
	}

	$wallet = new Wallet($_G['user']);
	if(!$wallet->pay($order)){
		showmsg('wallet_is_insufficient', 'back');
	}

	showmsg('order_is_successfully_paid', 'back');
}

$limit = 10;
$offset = ($page - 1) * $limit;
$table = $db->select_table('userwalletlog');
$pagenum = $table->result_first('COUNT(*)', "uid={$_USER['id']}");
$walletlog = $table->fetch_all('*', "uid={$_USER['id']} ORDER BY dateline DESC LIMIT $offset,$limit");

$prepaidreward = $db->fetch_all("SELECT * FROM {$tpre}prepaidreward WHERE etime_start<=$timestamp AND etime_end>=$timestamp");
foreach($prepaidreward as &$r){
	foreach(array('minamount', 'maxamount', 'reward') as $var)
		$r[$var] = floatval($r[$var]);
}
unset($r);

include view('wallet');

?>
