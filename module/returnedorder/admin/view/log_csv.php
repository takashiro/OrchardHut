<?php

if(!defined('IN_ADMINCP')) exit('access denied');

rheader('Cache-Control: no-cache, must-revalidate');
rheader('Content-Type: application/octet-stream');
rheader('Content-Disposition: attachment; filename="'.$_CONFIG['sitename'].'退单('.rdate(TIMESTAMP, 'Y-m-d His').').csv"');

//UTF-8 BOM
echo chr(0xEF), chr(0xBB), chr(0xBF);

echo '订单号,地址,收件人,收件人手机号,退单时间,付款方式,付款状态,退回物品,退单原因,退款,回复', "\r\n";

foreach($returned_orders as $o){
	$rowspan = count($o['details']) + 1;

	echo $o['id'];
	echo ',"', Address::FullPathString($o['addressid']), ' ', $o['extaddress'], '"';
	echo ',', $o['addressee'];
	echo ',', $o['mobile'];
	echo ',', rdate($o['dateline']);
	echo ',', Wallet::$PaymentMethod[$o['paymentmethod']];
	echo ',';
	if($o['tradestate']){
		echo Wallet::$TradeState[$o['tradestate']];
	}elseif($o['paymentmethod'] != Wallet::ViaCash){
		echo '(等待付款)';
	}

	echo ',"';
	foreach($o['details'] as $d){
		echo $d['productname'];
		if($d['subtype']){
			echo '(', $d['subtype'], ')';
		}
		echo ' ', $d['number'], '/', $d['boughtnum'], $d['amountunit'];
		echo ' ', sprintf('%.2f', $d['subtotal'] * $d['number'] / $d['boughtnum']), '/', $d['subtotal'], Product::$PriceUnit;
		echo ' ', ReturnedOrder::$DetailResult[$d['state']];
		echo "\r\n";
	}
	echo '"';

	echo ',"', addslashes(trim($o['reason'])), '"';
	echo ',', $o['returnedfee'];
	echo ',"', addslashes(trim($o['adminreply'])), '"';
	echo "\r\n";
}

?>
