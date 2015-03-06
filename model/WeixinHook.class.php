<?php

class WeixinHook{

	static public function __on_order_log_added($order, $log){
		global $db, $tpre, $_G;

		if($log['operation'] == Order::StatusChanged && $log['extra'] == Order::InDeliveryPoint || $log['extra'] == Order::Delivering){
			$touser = $db->result_first("SELECT wxopenid FROM {$tpre}user WHERE id=".$order->userid);
			if($touser){
				if($log['extra'] == Order::InDeliveryPoint){
					$text = lang('weixin', 'your_order_just_arrived_in_delivery_point');
				}else{
					$text = lang('weixin', 'your_order_is_being_delivered');
				}

				if($log['operator']){
					$admin = $db->fetch_first("SELECT a.realname,a.mobile FROM {$tpre}administrator a WHERE a.id=$log[operator]");
					if($admin){
						$text.= lang('weixin', 'deliverer_is').$admin['realname'];
						if($admin['mobile']){
							$text.= '('.lang('common', 'mobile').': '.$admin['mobile'].')。';
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
						$text.= "($d[subtype])";
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
