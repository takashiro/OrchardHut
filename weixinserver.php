<?php

ob_start();
require_once './core/init.inc.php';
ob_clean();

$wx = readdata('wxconnect');
$weixin = new WeixinServer($wx['token'], $wx['account']);
$request = $weixin->getRequest(true);

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
	if(isset($wx['bind_keyword']) && Autoreply::MatchKeywords($wx['bind_keyword'], $request['Content'])){
		$words = explode("\n", $wx['bind2_keyword']);
		foreach($words as &$word){
			$word = trim($word);
		}
		unset($word);

		if(isset($words[1])){
			$final = array_pop($words);
			$words = '【'.implode('】、【', $words).'】或【'.$final.'】';
		}else{
			$words = $words[0];
		}

		$weixin->replyTextMessage("若您未登录，<a href=\"{$_G['root_url']}memcp.php?action=login\">点击登录已有账号</a>，然后回复{$words}。");
	}

	if(isset($wx['bind2_keyword']) && Autoreply::MatchKeywords($wx['bind2_keyword'], $request['Content'])){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['root_url']}weixinconnect.php?action=bind&user=$user&key=$key\">点击进入商城</a>");
	}

	if(isset($wx['entershop_keyword']) && Autoreply::MatchKeywords($wx['entershop_keyword'], $request['Content'])){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['root_url']}weixinconnect.php?action=login&user=$user&key=$key\">点击进入商城</a>");
	}

	$reply = Autoreply::Find($request['Content']);
	if($reply != NULL){
		$weixin->replyTextMessage($reply);
	}
}

?>
