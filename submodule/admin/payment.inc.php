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

if(!defined('IN_ADMINCP')) exit('access denied');

if($_G['admincp']['mode'] == 'permission'){
	return array();
}

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
