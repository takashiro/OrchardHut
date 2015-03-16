<?php

require_once './core/init.inc.php';
require_once submodule('alipay', 'init');

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyReturn();
if($verify_result){//验证成功
	$arguments = array(
		//商户订单号
		$_GET['out_trade_no'],
		//支付宝交易号
		$_GET['trade_no'],
		//交易状态
		$_GET['result'],
	);

	runhooks('alipay_callback_executed', $arguments);

}else{
    //验证失败
    //如要调试，请看alipay_notify.php页面的verifyReturn函数
    showmsg('illegal_operation');
}

?>
