<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

require_once '../../../core/init.inc.php';
require_once module('alipay/config');

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
		AlipayNotify::$TradeStateEnum[$_GET['trade_status']],
	);
	runhooks('alipay_callback_executed', $arguments);

}else{
	showmsg('illegal_operation');
}

?>
