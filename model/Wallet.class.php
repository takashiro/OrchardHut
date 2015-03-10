<?php

class Wallet{

	static protected $AlipayTradeNoPrefix = 'W';
	static public function __on_alipay_started(){
		if(isset($_GET['recharge'])){
			global $_G, $db;

			$log = array(
				'uid' => $_G['user']->id,
				'dateline' => TIMESTAMP,
				'delta' => floatval($_GET['recharge']),
			);

			if($log['delta'] != 0){
				$db->select_table('userwalletlog');
				$db->INSERT($log);
				$id = $db->insert_id();

				//商户网站订单系统中唯一订单号，必填
				$_G['alipaytrade']['out_trade_no'] = self::$AlipayTradeNoPrefix.$id;

				//订单名称
				$_G['alipaytrade']['subject'] = $_G['config']['sitename'].'充值'.$id;

				//付款金额
				$_G['alipaytrade']['total_fee'] = $log['delta'];
			}else{
				showmsg('illegal_operation');
			}
		}
	}

	static public function __on_alipay_notified($out_trade_no, $trade_no, $trade_status){
		$prefix_len = strlen(self::$AlipayTradeNoPrefix);
		if(strncmp($out_trade_no, self::$AlipayTradeNoPrefix, $prefix_len) == 0){
			global $db;
			$id = substr($out_trade_no, $prefix_len);

			$log = array(
				'alipaytradeid' => $trade_no,
				'alipaystate' => AlipayNotify::$TradeStateEnum[$trade_status],
			);
			$db->select_table('userwalletlog');
			$db->UPDATE($log, array('id' => $id));
		}
	}

	static public function __on_alipay_callback_executed($out_trade_no, $trade_no, $result){
		global $_G;

		//以异步通知为准，此处不处理
		/*if($result == 'success'){
			$order = new Order($orderid);
			if(!$order->exists()){
				writelog('alipaycallback', array('ORDER_NOT_EXIST', $orderid, $trade_no, $result));
				showmsg('订单不存在，错误已记录。');
			}
			$order->alipaystate = AlipayNotify::TradeSuccess;
			$order->alipaytradeid = $trade_no;
		}else{
			exit('unexpected result: '.$result);
		}*/

		if(strncmp($out_trade_no, self::$AlipayTradeNoPrefix, strlen(self::$AlipayTradeNoPrefix)) == 0)
			showmsg('成功充值！钱包又鼓起来啦~', 'wallet.php');
	}
}

?>
