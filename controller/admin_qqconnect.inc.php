<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$qqconnect = readdata('qqconnect');
foreach(array('appid', 'appkey', 'callback', 'scope', 'errorReport', 'storageType', 'host', 'user', 'password', 'database') as $var){
	isset($qqconnect[$var]) || $qqconnect[$var] = '';
	isset($_POST['qqconnect'][$var]) && $qqconnect[$var] = $_POST['qqconnect'][$var];
}

if($_POST){
	writedata('qqconnect', $qqconnect);
	showmsg('successfully_updated_qqconnect_config', 'refresh');
}

include view('qqconnect');

?>
