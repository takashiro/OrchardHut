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
	if(isset($wx['bind_keyword']) && Autoreply::MatchKeywords($wx['bind_keyword'], $request['Content'])){
		$words = explode("\n", $wx['bind2_keyword']);
		foreach($words as &$word){
			$word = trim($word);
		}
		unset($word);

		if(isset($words[1])){
			$final = array_pop($words);
			$words = '【'.implode('】、【', $words).'】'.lang('message', 'or').'【'.$final.'】';
		}else{
			$words = $words[0];
		}

		$weixin->replyTextMessage(lang('message', 'if_not_logged_in_comma')."<a href=\"{$_G['root_url']}memcp.php?action=login\">".lang('message', 'click_here_and_login')."</a>，".lang('message', 'and_reply').$words);
	}

	if(isset($wx['bind2_keyword']) && Autoreply::MatchKeywords($wx['bind2_keyword'], $request['Content'])){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['root_url']}weixinconnect.php?action=bind&user=$user&key=$key\">".lang('message', 'click_and_enter_shop').'</a>');
	}

	if(isset($wx['entershop_keyword']) && Autoreply::MatchKeywords($wx['entershop_keyword'], $request['Content'])){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['root_url']}weixinconnect.php?action=login&user=$user&key=$key\">".lang('message', 'click_and_enter_shop').'</a>');
	}

	$reply = Autoreply::Find($request['Content']);
	if($reply != NULL){
		$weixin->replyTextMessage($reply);
	}
}

?>
