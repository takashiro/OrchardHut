<?php

if(!defined('IN_ADMINCP')) exit('access denied');

if($_POST){
	$payment = array();
	foreach(Order::$PaymentMethod as $methodid => $name){
		$payment['enabled_method'][$methodid] = !empty($_POST['payment']['enabled_method'][$methodid]);
	}

	@$alipay = array(
		'email' => trim($_POST['alipay']['email']),
		'partner' => trim($_POST['alipay']['partner']),
		'key' => trim($_POST['alipay']['key']),
		'sign_type' => $_POST['alipay']['sign_type'],
		'input_charset' => $_POST['alipay']['input_charset'],
		'transport' => trim($_POST['alipay']['transport']),
		'private_key_path' => trim($_POST['alipay']['private_key_path']),
		'ali_public_key_path' => trim($_POST['alipay']['ali_public_key_path']),
	);

	writedata('payment', $payment);
	writedata('alipay', $alipay);
	showmsg('edit_succeed', 'refresh');
}

$payment = readdata('payment');
$alipay = readdata('alipay');

include view('payment_config');

?>
