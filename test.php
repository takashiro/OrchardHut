<?php

require_once './core/init.inc.php';

$users = $db->fetch_all("SELECT id,wxopenid FROM {$tpre}user WHERE wxopenid IS NOT NULL AND wxunionid IS NULL");

$wx = new WeixinAPI;
foreach($users as $u){
	$info = $wx->getUserInfo($u['wxopenid']);
	print_r($info);
	exit;
}

?>
