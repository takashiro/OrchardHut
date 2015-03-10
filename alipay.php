<?php

require_once './core/init.inc.php';

$_G['alipaytrade'] = array(
	'out_trade_no' => '',
	'subject' => '',
	'total_fee' => 0.00,
);

runhooks('alipay_started');

if(empty($_G['alipaytrade']['out_trade_no']) || empty($_G['alipaytrade']['subject']) || !is_numeric($_G['alipaytrade']['total_fee']))
	showmsg('illegal_operation');

require_once S_ROOT.'controller/alipay_init.inc.php';

/**************************调用授权接口alipay.wap.trade.create.direct获取授权码token**************************/

//返回格式
$format = 'xml';
//必填，不需要修改

//返回格式
$v = '2.0';
//必填，不需要修改

//请求号
$req_id = date('Ymdhis');
//必填，须保证每次请求都是唯一

//**req_data详细信息**

//服务器异步通知页面路径
$notify_url = $_G['root_url'].'api/alipay_notify.php';
//需http://格式的完整路径，不允许加?id=123这类自定义参数

//页面跳转同步通知页面路径
$call_back_url = $_G['root_url'].'alipay_callback.php';
//需http://格式的完整路径，不允许加?id=123这类自定义参数

//操作中断返回地址
$merchant_url = $_G['root_url'].'order.php';
//用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数

//卖家支付宝帐户
$seller_email = $alipay_config['email'];
//必填

extract($_G['alipaytrade']);
unset($_G['alipaytrade']);

//请求业务参数详细
$req_data = '<direct_trade_create_req>'
	.'<notify_url>' . $notify_url . '</notify_url>'
	.'<call_back_url>' . $call_back_url . '</call_back_url>'
	.'<seller_account_name>' . $seller_email . '</seller_account_name>'
	.'<out_trade_no>' . $out_trade_no . '</out_trade_no>'
	.'<subject>' . $subject . '</subject>'
	.'<total_fee>' . $total_fee . '</total_fee>'
	.'<merchant_url>' . $merchant_url . '</merchant_url>'
	.'</direct_trade_create_req>';
//必填

/************************************************************/

//构造要请求的参数数组，无需改动
$para_token = array(
	'service' => 'alipay.wap.trade.create.direct',
	'partner' => trim($alipay_config['partner']),
	'sec_id' => trim($alipay_config['sign_type']),
	'format' => $format,
	'v'	=> $v,
	'req_id' => $req_id,
	'req_data' => $req_data,
	'_input_charset' => trim(strtolower($alipay_config['input_charset'])),
);

//建立请求
$alipaySubmit = new AlipaySubmit($alipay_config);
$html_text = $alipaySubmit->buildRequestHttp($para_token);

//URLDECODE返回的信息
$html_text = urldecode($html_text);

//解析远程模拟提交后返回的信息
$para_html_text = $alipaySubmit->parseResponse($html_text);

//获取request_token
$request_token = $para_html_text['request_token'];


/**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

//业务详细
$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
//必填

//构造要请求的参数数组，无需改动
$parameter = array(
	'service' => 'alipay.wap.auth.authAndExecute',
	'partner' => trim($alipay_config['partner']),
	'sec_id' => trim($alipay_config['sign_type']),
	'format' => $format,
	'v'	=> $v,
	'req_id' => $req_id,
	'req_data' => $req_data,
	'_input_charset' => trim(strtolower($alipay_config['input_charset']))
);

//建立请求
$alipaySubmit = new AlipaySubmit($alipay_config);
$html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>支付宝即时到账交易接口</title>
<style type="text/css">
body{display: none;}
</style>
</head>
<body>
<?php echo $html_text;?>
</body>
</html>
