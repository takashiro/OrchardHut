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

/* To make use of the following codes, you have to add the rewrite rules below.
RewriteEngine On
RewriteBase /
RewriteRule ^alipay(.*)\.htm$ alipay.php?querystring=$1
*/

if(empty($_GET['querystring'])){
	function isWeixin(){
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
			return true;
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'GT-I9500') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'MQQBrowser') !== false)
			return true;
		return false;
	}

	if(empty($_GET['skipprotector']) && isWeixin()){
		if(!$_G['user']->isLoggedIn()){
			writelog('unexpected_result', '1: '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['QUERY_STRING']);
			showmsg('inaccessible_if_not_logged_in', 'memcp.php');
		}

		$_SERVER['QUERY_STRING'].= '&'.User::COOKIE_VAR.'='.urlencode($_COOKIE[User::COOKIE_VAR]);
		rheader('Location: alipay'.base64_encode($_SERVER['QUERY_STRING']).'.htm');
		exit;
	}
}else{
	$protected_url = 'alipay.php?'.base64_decode($_GET['querystring']).'&skipprotector=1';
	include view('protector');
	exit;
}

if(!$_G['user']->isLoggedIn()){
	writelog('unexpected_result', '2: '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['QUERY_STRING']);
	showmsg('inaccessible_if_not_logged_in', 'memcp.php');
}

$paymentconfig = readdata('payment');
if(empty($paymentconfig['enabled_method'][Order::PaidWithAlipay])){
	showmsg('alipay_is_disabled');
}

$_G['alipaytrade'] = array(
	'out_trade_no' => '',
	'subject' => '',
	'total_fee' => 0.00,
	'show_url' => 'order.php',
);

runhooks('alipay_started');

if(empty($_G['alipaytrade']['out_trade_no']) || empty($_G['alipaytrade']['subject']) || !is_numeric($_G['alipaytrade']['total_fee']))
	showmsg('illegal_operation');

require_once submodule('alipay', 'config');

//支付类型
$payment_type = '1';
//必填，不能修改

//服务器异步通知页面路径
$notify_url = $_G['root_url'].'api/alipay_notify.php';
//需http://格式的完整路径，不能加?id=123这类自定义参数

//页面跳转同步通知页面路径
$return_url = $_G['root_url'].'alipay_callback.php';
//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

extract($_G['alipaytrade']);
unset($_G['alipaytrade']);

//订单描述
$body = '';
//选填

//超时时间
$it_b_pay = '';
//选填

//钱包token
$extern_token = '';
//选填

//构造要请求的参数数组，无需改动
$parameter = array(
	'service' => 'alipay.wap.create.direct.pay.by.user',
	'partner' => $alipay_config['partner'],
	'seller_id' => $alipay_config['partner'],
	'payment_type' => $payment_type,
	'notify_url' => $notify_url,
	'return_url' => $return_url,
	'out_trade_no' => $out_trade_no,
	'subject' => $subject,
	'total_fee'	=> $total_fee,
	'show_url' => $show_url,
	'body' => $body,
	//'it_b_pay' => $it_b_pay,
	//'extern_token' => $extern_token,
	'_input_charset' => $alipay_config['input_charset'],
);

//建立请求
$alipaySubmit = new AlipaySubmit($alipay_config);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>支付宝手机网站支付接口 - <?php echo $_CONFIG['sitename'];?></title>
</head>
<body>
<?php echo $alipaySubmit->buildRequestForm($parameter, 'get', '确认');?>
</body>
</html>
