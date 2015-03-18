<?php

class Wallet{

	static protected $AlipayTradeNoPrefix = 'W';
	static public function __on_alipay_started(){
		if(isset($_GET['recharge'])){
			global $_G, $db;

			$log = array(
				'uid' => $_G['user']->id,
				'dateline' => TIMESTAMP,
				'cost' => floatval($_GET['recharge']),
			);

			$log['cost'] = round($log['cost'] * 100) / 100;

			if($log['cost'] > 0){
				$table = $db->select_table('userwalletlog');
				$table->insert($log);
				$id = $table->insert_id();

				//商户网站订单系统中唯一订单号，必填
				$_G['alipaytrade']['out_trade_no'] = self::$AlipayTradeNoPrefix.$id;

				//订单名称
				$_G['alipaytrade']['subject'] = $_G['config']['sitename'].'充值'.$id;

				//付款金额
				$_G['alipaytrade']['total_fee'] = $log['cost'];
			}else{
				showmsg('the_number_you_must_be_kidding_me', 'back');
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
			$table = $db->select_table('userwalletlog');
			$table->update($log, array('id' => $id));

			if($log['alipaystate'] == AlipayNotify::TradeSuccess || $log['alipaystate'] == AlipayNotify::TradeFinished){
				global $tpre;
				$db->query("UPDATE {$tpre}userwalletlog SET recharged=1 WHERE id='$id'");
				if($db->affected_rows > 0){
					$log = $db->fetch_first("SELECT uid,cost FROM {$tpre}userwalletlog WHERE id='$id'");
					$fee = $log['cost'];
					$timestamp = TIMESTAMP;
					$extrafee = $db->result_first("SELECT reward
						FROM {$tpre}prepaidreward
						WHERE minamount<=$fee AND maxamount>=$fee
							AND etime_start<=$timestamp AND etime_end>=$timestamp
						ORDER BY reward DESC
						LIMIT 1");
					$fee += $extrafee;

					$db->query("UPDATE {$tpre}userwalletlog SET delta=$fee WHERE id='$id'");
					$db->query("UPDATE {$tpre}user SET wallet=wallet+$fee WHERE id=$log[uid]");
				}
			}
		}
	}

	static public function __on_alipay_callback_executed($out_trade_no, $trade_no, $result){
		global $_G;

		//以异步通知为准，此处不处理只通知
		if(strncmp($out_trade_no, self::$AlipayTradeNoPrefix, strlen(self::$AlipayTradeNoPrefix)) == 0)
			showmsg('wallet_is_successfully_recharged', 'wallet.php');
	}
}

?>
