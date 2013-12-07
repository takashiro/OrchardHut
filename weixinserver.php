<?php

ob_start();
require_once './core/init.inc.php';
ob_clean();

$wx = readdata('wxconnect');
$weixin = new WeixinServer($wx['token'], $wx['account']);
$request = $weixin->getRequest();

if(!$request){
	if($weixin->isValidRequest()){
		exit($_GET['echostr']);
	}
	exit('access denied');
}

if($request['MsgType'] == 'event'){
	if($request['Event'] == 'subscribe'){
		$weixin->replyTextMessage($wx['subscribe_text']);
	}
}elseif($request['MsgType'] == 'text'){
	$keywords = array('绑定', 'bd', 'bangding', 'bind');
	$bind = false;
	foreach($keywords as $keyword){
		if($request['Content'] == $keyword){
			$bind = true;
			break;
		}
	}

	if($bind){
		$weixin->replyTextMessage("若您未登录，<a href=\"http://ts19920424.gotoip3.com/memcp.php?action=login\">点击登录已有账号</a>，然后回复【已登录】或【ydl】。");
	}

	$keywords = array('已登录', 'ydl', 'yidenglu', 'loggedin');
	$loggedin = false;
	foreach($keywords as $keyword){
		if($request['Content'] == $keyword){
			$loggedin = true;
			break;
		}
	}

	if($loggedin){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"http://ts19920424.gotoip3.com/weixinconnect.php?action=bind&user=$user&key=$key\">点击进入商城</a>");
	}

	$keywords = array('购买', '商城', 'gm', 'sc', 'goumai', 'shangcheng');
	$enter_shop = false;
	foreach($keywords as $keyword){
		if($request['Content'] == $keyword){
			$enter_shop = true;
			break;
		}
	}

	if($enter_shop){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"http://ts19920424.gotoip3.com/weixinconnect.php?action=login&user=$user&key=$key\">点击进入商城</a>");
	}


	$keywords = array('帮助', 'bz', '/?', 'help', 'bangzhu');
	$help = false;
	foreach($keywords as $keyword){
		if($request['Content'] == $keyword){
			$help = true;
			break;
		}
	}

	if($help){
		$weixin->replyTextMessage($wx['help_text']);
	}
}

?>
