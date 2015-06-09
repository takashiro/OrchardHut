<?php

if(!defined('IN_ADMINCP')) exit('access denied');

rheader('Cache-Control: no-cache, must-revalidate');
rheader('Content-Type: application/octet-stream');
rheader('Content-Disposition: attachment; filename="'.$_CONFIG['sitename'].'用户列表('.rdate(TIMESTAMP, 'Y-m-d His').').csv"');

$address_format = Address::Format();

//UTF-8 BOM
echo chr(0xEF), chr(0xBB), chr(0xBF);

//输出表头
echo 'UID,用户名,昵称,注册时间,订单数量,钱包余额,手机号';
foreach($address_format as $format){
	if(empty($format))
		break;
	echo ',', $format;
}
echo "\n";

foreach($user_list as $u){
	echo $u['id'], ',';
	if(strlen($u['account']) <= 15){
		echo $u['account'];
	}
	echo ',', $u['nickname'], ',', rdate($u['regtime']), ',', $u['ordernum'], ',', $u['wallet'], ',', $u['mobile'];

	$address_path = Address::FullPath($u['addressid']);
	$maxi = count($address_format);
	for($i = 0; $i < $maxi; $i++){
		echo ',', isset($address_path[$i]['name']) ? $address_path[$i]['name'] : '';
	}
	echo "\n";
}

?>
