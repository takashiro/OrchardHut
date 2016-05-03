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

class PromotionHook{

	static public function __on_order_log_added($order, $log){
		global $db, $tpre, $_G;

		if($log['operation'] == Order::StatusChanged && $log['extra'] == Order::Received){
			$config = readdata('promotion');
			if(empty($config['orderrewardratio']))
				return;

			if(empty($config['enabled_method'][$order->paymentmethod]))
				return;

			$referrer = $db->fetch_first("SELECT r.id
				FROM {$tpre}order o
					LEFT JOIN {$tpre}user u ON u.id=o.userid
					LEFT JOIN {$tpre}user r ON r.id=u.referrerid
				WHERE o.id={$order->id}");
			if(!$referrer || empty($referrer['id']))
				return;

			$referrer_order = $db->result_first("SELECT id FROM {$tpre}order WHERE userid={$referrer['id']} LIMIT 1");
			if(empty($referrer_order))
				return;

			$duplicated_order = $db->result_first("SELECT id FROM {$tpre}order WHERE userid={$referrer['id']} AND mobile='{$order->mobile}' LIMIT 1");
			if(!empty($duplicated_order))
				return;

			$reward = ($order->totalprice - $order->deliveryfee) * intval($config['orderrewardratio']) / 100;
			if($reward <= 0)
				return;

			$db->query("UPDATE {$tpre}user SET wallet=wallet+{$reward} WHERE id={$referrer['id']}");
			if ($db->affected_rows > 0){
				$log = array(
					'uid' => $referrer['id'],
					'dateline' => TIMESTAMP,
					'type' => Wallet::OrderRewardLog,
					'delta' => $reward,
					'orderid' => $order->id,
				);
				$table = $db->select_table('userwalletlog');
				$table->insert($log);
			}
		}
	}

}
