<?php

ob_start();
require_once './core/init.inc.php';
ob_clean();

$weixin = new WeixinServer('scufruit', 'scufruit');
$request = $weixin->getRequest();

if(!$request){
	if($weixin->isValidRequest()){
		exit($_GET['echostr']);
	}
	exit('access denied');
}

$help_text = '新鲜水果随手订！ 这位客官，我是来自四川大学的主页君果果^_^ 感谢您关注微果园mo-鼓掌
1.回复【商城】即可进入商城购物啦
2.若您没有账号，直接进入商城，系统会为您自动分配账号的
3.若您已有账号，回复【绑定】，然后根据提示绑定已有账号，以后就可以畅通无阻啦
4.下单后果果会尽快安排挑果师为您尽可能的挑选仓库中最好的水果，打包发货哦。如果是首次下单，请先注册哦，并填写您的地址（如：0舍0单元101）以及联系方式，以后就不需要啦，果果会记住你的mo-坏笑
5.我们的配送时间为每天的19:00-23:00，记得到时取货哦。';

if($request['MsgType'] == 'event'){
	if($request['Event'] == 'subscribe'){
		$weixin->replyTextMessage($help_text);
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
		$weixin->replyTextMessage($help_text);
	}
}

?>
