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

class WeixinHook{

	static public function __on_order_log_added($order, $log){
		global $db, $tpre, $_G;

		if($log['operation'] == Order::StatusChanged && $log['extra'] == Order::InDeliveryStation || $log['extra'] == Order::Delivering){
			$touser = $db->result_first("SELECT wxopenid FROM {$tpre}user WHERE id=".$order->userid);
			if($touser){
				if($log['extra'] == Order::InDeliveryStation){
					$text = lang('weixin', 'your_order_just_arrived_in_delivery_station');
					if($order->deliverymethod == Order::HomeDelivery){
						$text.= lang('weixin', 'please_wait_for_the_deliverer');
					}else{
						$text.= lang('weixin', 'please_fetch_your_package');
					}
				}else{
					$text = lang('weixin', 'your_order_is_being_delivered');
				}

				if($log['operator']){
					$admin = $db->fetch_first("SELECT a.realname,a.mobile FROM {$tpre}administrator a WHERE a.id={$log['operator']}");
					if($admin){
						$text.= "\n";
						$text.= lang('weixin', 'deliverer_is').$admin['realname'];
						if($admin['mobile']){
							$text.= '('.lang('common', 'mobile').': '.$admin['mobile'].')';
						}
					}
				}

				global $_G;
				$text.= "\n订单：";

				foreach($order->getDetails() as $d){
					$text.= "\n";
					if($d['state'] == '1'){
						$text.= '[缺货]';
					}
					$text.= $d['productname'];
					if(!empty($d['subtype'])){
						$text.= "({$d['subtype']})";
					}
					$text.= ($d['amount'] * $d['number']).$d['amountunit'];
				}

				$wx = new WeixinAPI;
				$wx->sendTextMessage($touser, $text);
			}
		}
	}
}

?>
