<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23

 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */

require_once '../core/init.inc.php';
require_once S_ROOT.'controller/alipay_init.inc.php';

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result){//验证成功
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

	//解析notify_data
	//注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
	$doc = new DOMDocument();
	if($alipay_config['sign_type'] == '0001'){
		$doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
	}elseif($alipay_config['sign_type'] == 'MD5'){
		$doc->loadXML($_POST['notify_data']);
	}else{
		exit('fail');
	}

	if(!empty($doc->getElementsByTagName('notify')->item(0)->nodeValue)){
		$alipaytrade = array(
			//商户订单号
			$doc->getElementsByTagName('out_trade_no')->item(0)->nodeValue,
			//支付宝交易号
			$doc->getElementsByTagName('trade_no')->item(0)->nodeValue,
			//交易状态
			$doc->getElementsByTagName('trade_status')->item(0)->nodeValue,
		);

		runhooks('alipay_notified', $alipaytrade);

		exit('success');
	}

}else{
    //验证失败
    exit('fail');
}

?>
