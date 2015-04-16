<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

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
			$id = raddslashes($id);

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
					$db->query("UPDATE {$tpre}user SET wallet=wallet+$fee WHERE id={$log['uid']}");

					runhooks('user_wallet_changed', array($log['uid'], $log['cost']));
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
