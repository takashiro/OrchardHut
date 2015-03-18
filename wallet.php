<?php

require_once './core/init.inc.php';

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

	$db->query("UPDATE {$tpre}user SET wallet=wallet-{$order->totalprice} WHERE id={$_G['user']->id} AND wallet>={$order->totalprice}");
	if($db->affected_rows <= 0){
		showmsg('wallet_is_insufficient', 'back');
	}

	$order->paymentmethod = Order::PaidWithWallet;
	$order->alipaystate = AlipayNotify::TradeSuccess;

	showmsg('order_is_successfully_paid', 'back');
}

$limit = 10;
$offset = ($page - 1) * $limit;
$table = $db->select_table('userwalletlog');
$pagenum = $table->result_first('COUNT(*)', "uid=$_USER[id]");
$walletlog = $table->fetch_all('*', "uid=$_USER[id] ORDER BY dateline DESC LIMIT $offset,$limit");

$prepaidreward = $db->fetch_all("SELECT * FROM {$tpre}prepaidreward WHERE etime_start<=$timestamp AND etime_end>=$timestamp");
foreach($prepaidreward as &$r){
	foreach(array('minamount', 'maxamount', 'reward') as $var)
		$r[$var] = floatval($r[$var]);
}
unset($r);

include view('wallet');

?>
