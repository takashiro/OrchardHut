<?php

require_once './core/init.inc.php';
require_once S_ROOT.'controller/alipay_init.inc.php';

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyReturn();
if($verify_result){//验证成功

	//商户订单号
	$orderid = intval($_GET['out_trade_no']);

	//支付宝交易号
	$trade_no = $_GET['trade_no'];

	//交易状态
	$result = $_GET['result'];

	//以异步通知为准，此处不处理
	/*if($result == 'success'){
		$order = new Order($orderid);
		if(!$order->exists()){
			writelog('alipaycallback', array('ORDER_NOT_EXIST', $orderid, $trade_no, $result));
			showmsg('订单不存在，错误已记录。');
		}
		$order->alipaystate = Order::TradeSuccess;
		$order->alipaytradeid = $trade_no;
	}else{
		exit('unexpected result: '.$result);
	}*/

	//判断该笔订单是否在商户网站中已经做过处理
	//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
	//如果有做过处理，不执行商户的业务程序

	showmsg('成功支付订单！很快为您配送哦~', 'home.php');
}else{
    //验证失败
    //如要调试，请看alipay_notify.php页面的verifyReturn函数
    showmsg('illegal_operation');
}

?>
