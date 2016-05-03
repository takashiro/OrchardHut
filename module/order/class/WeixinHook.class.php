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

class WeixinHook{

	static public function __on_order_log_added($order, $log){
		global $db, $tpre, $_G;

		if($log['operation'] != Order::StatusChanged)
			return;

		if($log['extra'] == Order::InDeliveryStation || $log['extra'] == Order::Delivering){
			$touser = $db->result_first("SELECT wxopenid FROM {$tpre}user WHERE id=".$order->userid);
			if(!$touser)
				return;

			if($log['extra'] == Order::InDeliveryStation){
				$text = lang('message', 'your_order_just_arrived_in_delivery_station');

				$fullpath = Address::FullPathIds($order->addressid);
				if($fullpath){
					$min_range = Address::MinRange('orderrange', $order->addressid);
					$station = $db->fetch_first("SELECT id,address FROM {$tpre}station WHERE orderrange IN ($fullpath) ORDER BY $min_range LIMIT 1");
					if(!empty($station['address'])){
						$text.= lang('message', 'the_station_is_located_in').$station['address'].lang('common', 'period');
					}
				}

				if($order->deliverymethod == Order::HomeDelivery){
					$text.= lang('message', 'please_wait_for_the_deliverer');
				}else{
					$text.= lang('message', 'please_fetch_your_package');
				}
			}else{
				$text = lang('message', 'your_order_is_being_delivered');
			}

			if($log['operator']){
				$admin = $db->fetch_first("SELECT a.realname,a.mobile FROM {$tpre}administrator a WHERE a.id={$log['operator']}");
				if($admin){
					$text.= "\n";
					$text.= lang('message', 'deliverer_is').$admin['realname'];
					if($admin['mobile']){
						$text.= '('.lang('common', 'mobile').': '.$admin['mobile'].')';
					}
				}
			}

			global $_G;

			$text.= "\n".lang('common', 'order_id').':'.$order->id;

			$text.= "\n".lang('common', 'order_detail').':';

			foreach($order->getDetails() as $d){
				$text.= "\n";
				if($d['state'] == '1'){
					$text.= '['.lang('common', 'out_of_stock').']';
				}
				$text.= $d['productname'];
				if(!empty($d['subtype'])){
					$text.= "({$d['subtype']})";
				}
				$text.= ($d['amount'] * $d['number']).$d['amountunit'];
			}

			$wx = new WeixinAPI;
			$wx->sendTextMessage($touser, $text);

		}elseif($log['extra'] == Order::Received){
			$touser = $db->result_first("SELECT wxopenid FROM {$tpre}user WHERE id=".$order->userid);
			if(!$touser)
				return;

			$link = $_G['site_url'].'index.php?mod=order&action=comment&orderid='.$order->id;
			$text = '<a href="'.$link.'">'.lang('message', 'thanks_for_supporting_please_rank').'</a>';

			$wx = new WeixinAPI;
			$wx->sendTextMessage($touser, $text);
		}
	}
}
