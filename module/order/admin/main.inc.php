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

if(!defined('IN_ADMINCP')) exit('access denied');

function num2bit(int $num){
	$arr = array();
	$i = 0;
	while($num){
		if(($num & 1) == 1){
			$arr[$i] = true;
		}
		$num >>= 1;
		$i++;
	}
	return $arr;
}

function bit2num(array $arr){
	$num = 0;
	foreach($arr as $key => $value){
		$num |= pow(2, $key);
	}
	return $num;
}

class OrderMainModule extends AdminControlPanelModule{

	public function getPermissions(){
		return array(
			'order_sort',
			'order_sort_w',
			'order_deliver',
			'order_deliver_w',
			'order_to_station',
		);
	}

	public function defaultAction(){
		$this->listAction();
	}

	public function listAction($action = 'list'){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$available_status = array();
		if($_G['admin']->hasPermission('order_sort')){
			$available_status[Order::Unsorted] = false;
			$available_status[Order::Sorted] = false;
		}
		if($_G['admin']->hasPermission('order_to_station')){
			$available_status[Order::Sorted] = false;
			$available_status[Order::ToDeliveryStation] = false;
		}
		if($_G['admin']->hasPermission('order_deliver')){
			$available_status[Order::ToDeliveryStation] = false;
			$available_status[Order::InDeliveryStation] = false;
			$available_status[Order::Delivering] = false;
			$available_status[Order::Received] = false;
			$available_status[Order::Rejected] = false;
		}
		$available_status[Order::Canceled] = false;

		//显示（或导出Excel表格）订单列表
		if($action == 'list'){
			//保存查询条件，数组中的每个元素用AND连接构成一个WHERE子句
			$condition = array();

			//$display_status数组的键名即订单的状态，$display_status[X]为true则显示状态为X的订单
			if(!empty($_POST['display_status']) && is_array($_POST['display_status'])){
				$display_status = $_POST['display_status'];
			}elseif(isset($_GET['display_status'])){
				if(is_array($_GET['display_status'])){
					$display_status = array();
					foreach($_GET['display_status'] as $status => $on){
						$display_status[intval($status)] = true;
					}
				}else{
					$display_status = num2bit(intval($_GET['display_status']));
				}
			}else{
				$display_status = array();
				foreach(Order::$Status as $statusid => $value){
					$display_status[$statusid] = true;
				}
				unset($display_status[Order::Canceled]);
			}

			$deliverymethod = -1;

			//过滤掉无权限查看的订单
			foreach($display_status as $statusid => $value){
				if(!isset($available_status[$statusid])){
					unset($display_status[$statusid]);
				}else{
					$available_status[$statusid] = true;
				}
			}

			//按订单号查询
			if(!empty($_REQUEST['orderid'])){
				$condition[] = 'o.id='.intval($_REQUEST['orderid']);

				$time_start = '';
				$time_end = '';
				$available_status;
				foreach($available_status as &$checked){
					$checked = true;
				}
				unset($checked);
				$display_status = $available_status;

				$_REQUEST['tradestate'] = 0;
				$_REQUEST['time_start'] = $_REQUEST['time_end'] = '';
				unset($_REQUEST['deliverymethod']);
			}

			//根据付款状态查询订单
			if(!isset($_REQUEST['tradestate'])){
				$tradestate = Wallet::TradeSuccess;
				$condition[] = 'o.tradestate='.$tradestate;
			}else{
				$tradestate = intval($_REQUEST['tradestate']);

				//@todo: resolve the hack
				if($tradestate > 0){
					if($tradestate != 1){
						$condition[] = 'o.tradestate='.$tradestate;
					}else{
						$condition[] = 'o.tradestate IN (0,1)';
					}
				}
			}

			//下单起始时间
			if(isset($_REQUEST['time_start'])){
				$time_start = empty($_REQUEST['time_start']) ? '' : rstrtotime($_REQUEST['time_start']);
			}else{
				$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd'), rdate(TIMESTAMP, 'Y'));

				//根据截单时间调整时分秒
				$deliverytimes = DeliveryTime::FetchAllEffective();
				if($deliverytimes){
					usort($deliverytimes, function($t1, $t2){
						return $t1['deadline'] > $t2['deadline'];
					});

					$i = 0;
					$max = count($deliverytimes);
					$time1 = $time_start + $deliverytimes[$i]['deadline'];
					if($time1 < TIMESTAMP){
						while($time1 < TIMESTAMP){
							$i++;
							$time2 = $time1;
							$time1 = $time_start + $deliverytimes[$i % $max]['deadline'] + 24 * 3600 * floor($i / $max);
						}
						$time_start = $time2;
						$time_end = $time1;
					}else{
						while($time1 >= TIMESTAMP){
							$time2 = $time1;
							$time1 = $time_start + $deliverytimes[$max - 1 - (-$i % $max)]['deadline'] - 24 * 3600 * floor(-$i / $max);
							$i--;
						}
						$time_start = $time1;
						$time_end = $time2;
					}
				}
			}

			//下单截止时间
			if(isset($_REQUEST['time_end'])){
				$time_end = empty($_REQUEST['time_end']) ? '' : rstrtotime($_REQUEST['time_end']);
			}elseif(empty($time_end)){
				$time_end = $time_start + 1 * 24 * 3600;
			}

			$timefield = $tradestate >= Wallet::TradeSuccess ? 'tradetime' : 'dateline';
			if($time_start !== ''){
				$condition[] = 'o.'.$timefield.'>='.$time_start;
				$time_start = rdate($time_start, 'Y-m-d H:i');
			}

			if($time_end !== ''){
				$condition[] = 'o.'.$timefield.'<='.$time_end;
				$time_end = rdate($time_end, 'Y-m-d H:i');
			}

			//送货方式
			if(isset($_REQUEST['deliverymethod'])){
				$deliverymethod = intval($_REQUEST['deliverymethod']);
				if(isset(Order::$DeliveryMethod[$deliverymethod])){
					$condition[] = 'o.deliverymethod='.$deliverymethod;
				}else{
					$deliverymethod = -1;
				}
			}

			$status = array_keys($display_status);
			if($status){
				$condition[] = 'o.status IN ('.implode(',', $status).')';
			}else{
				$condition[] = '0';
			}

			//过滤掉送货地点不在当前管理员的管辖范围内的订单
			$limitation_addressids = $_G['admin']->getLimitations();
			if($limitation_addressids){
				$condition[] = 'o.addressid IN ('.implode(',', $limitation_addressids).')';
			}

			//过滤掉受产品分类限制的订单
			if(!empty($_G['admin']->producttypes)){
				$producttypes = explode(',', $_G['admin']->producttypes);
				foreach($producttypes as &$producttype){
					$producttype = intval($producttype);
				}
				unset($producttype);
				$producttypes = implode(',', $producttypes);

				if($producttypes){
					$condition[] = "EXISTS (SELECT *
						FROM {$tpre}orderdetail td
							LEFT JOIN {$tpre}product tp ON tp.id=td.productid
						WHERE td.orderid=o.id AND tp.type IN ($producttypes))";
				}else{
					$condition[] = '0';
				}
			}

			//根据送货地址查询订单
			$delivery_address = array();
			if(!empty($_REQUEST['delivery_address']) && is_array($_REQUEST['delivery_address'])){
				$dacondition = array();
				foreach($_REQUEST['delivery_address'] as $addressid){
					$addressid = intval($addressid);
					if($addressid > 0){
						$delivery_address[] = $addressid;
						$address_range = Address::Extension($addressid);
						if($address_range){
							$dacondition[] = 'o.addressid IN ('.implode(',', $address_range).')';
						}
					}
				}
				if($dacondition){
					$dacondition = implode(' OR ', $dacondition);
					$condition[] = '('.$dacondition.')';
				}
			}

			//根据用户ID查询订单
			if(!empty($_REQUEST['userid'])){
				$userid = intval($_REQUEST['userid']);
				$condition[] = 'o.userid='.$userid;
			}else{
				$userid = '';
			}

			//根据收件人姓名查询订单
			if(!empty($_REQUEST['addressee'])){
				$addressee = trim($_REQUEST['addressee']);
				$condition[] = 'o.addressee LIKE \'%'.$addressee.'%\'';
			}else{
				$addressee = '';
			}

			//根据手机号查询订单
			if(!empty($_REQUEST['mobile'])){
				$mobile = trim($_REQUEST['mobile']);
				$condition[] = 'o.mobile=\''.$mobile.'\'';
			}else{
				$mobile = '';
			}

			//限定状态改变的时间
			if(!empty($_REQUEST['statusid']) && !empty($_REQUEST['statustime'])){
				$statusid = intval($_REQUEST['statusid']);
				$statustime = intval($_REQUEST['statustime']);
				if($statusid > 0){
					$status_changed = Order::StatusChanged;
					$condition[] = "EXISTS (SELECT * FROM {$tpre}orderlog l WHERE l.orderid=o.id AND l.operation=$status_changed AND l.dateline>$statustime)";
				}
			}

			//连接成WHERE子句
			$condition = implode(' AND ', $condition);

			//处理统计信息
			$stat = array(
				'statonly' => !empty($_REQUEST['stat']['statonly']),		//仅显示统计信息
				'totalprice' => !empty($_REQUEST['stat']['totalprice']),	//计算总价格
				'item' => !empty($_REQUEST['stat']['item']),				//根据商品分类统计
			);

			//判断显示格式，若为csv则导出Excel表格
			$template_formats = array('html', 'csv', 'ticket', 'barcode', 'json');
			$template_format = &$_REQUEST['format'];
			if(empty($template_format) || !in_array($template_format, $template_formats)){
				$template_format = $template_formats[0];
			}

			//从数据库中查询订单，实现分页
			$pagenum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order o WHERE $condition");
			if(!$stat['statonly']){
				$limit_subsql = '';
				if($template_format == 'html' || $template_format == 'json'){
					$limit = 20;
					$offset = ($page - 1) * $limit;
					$limit_subsql = "LIMIT $offset,$limit";
				}

				$received_status = Order::Received;
				$orders = $db->fetch_all("SELECT o.*,u.nickname,u.account,
						(SELECT COUNT(*) FROM {$tpre}order WHERE userid=o.userid AND status=$received_status) AS ordernum
					FROM {$tpre}order o
						LEFT JOIN {$tpre}user u ON u.id=o.userid
					WHERE $condition
					ORDER BY o.status,o.tradetime
					$limit_subsql");
			}else{
				$orders = array();
			}

			//计算统计信息
			if($template_format == 'html'){
				$statdata = array();
				if($stat['totalprice']){
					$statdata['totalprice'] = $db->result_first("SELECT SUM(totalprice) FROM {$tpre}order o WHERE $condition");
				}else{
					$statdata['totalprice'] = 0.00;
				}

				if($stat['item']){
					$statdata['item'] = $db->fetch_all("SELECT d.productname,d.subtype,d.amountunit,SUM(d.amount*d.number) AS num,SUM(d.subtotal) AS totalprice
						FROM {$tpre}orderdetail d
							LEFT JOIN {$tpre}order o ON d.orderid=o.id
						WHERE $condition
						GROUP BY d.productname,d.subtype,d.amountunit");
				}else{
					$statdata['item'] = array();
				}
			}

			//查询各个订单的详细内容（每种商品的购买数量、单项价格等）
			if($orders){
				$orderids = array();
				foreach($orders as &$o){
					$orderids[] = $o['id'];
				}
				unset($o);

				$orderids = implode(',', $orderids);

				//取得所有订单的物品列表
				$order_details = array();
				$query = $db->query("SELECT d.id,d.productname,d.subtype,d.amount,d.amountunit,d.number,d.orderid,d.state,d.subtotal
					FROM {$tpre}orderdetail d
					WHERE d.orderid IN ($orderids)");
				while($d = $query->fetch_assoc()){
					$order_details[$d['orderid']][] = $d;
				}

				foreach($orders as &$o){
					$o['detail'] = !empty($order_details[$o['id']]) ? $order_details[$o['id']] : array();
					is_array($o['detail']) || $o['detail'] = array();
					$o['address'] = Address::FullPath($o['addressid']);
				}
				unset($o, $order_details);
			}

		//高级查找
		}else{
			$display_status = Order::$Status;
			unset($display_status[Order::Canceled]);

			$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));
			//根据截单时间调整时分秒
			$deliverytimes = DeliveryTime::FetchAllEffective();
			usort($deliverytimes, function($t1, $t2){
				return $t1['deadline'] > $t2['deadline'];
			});
			$dt = current($deliverytimes);
			$time_start += $dt['deadline'];
			unset($deliverytimes, $dt);

			$time_end = $time_start + 24 * 3600;
		}

		$address_format = Address::Format();
		$address_components = Address::AvailableComponents();

		$restricted = $_G['admin']->getLimitations();
		if($restricted){
			foreach($address_components as $cid => $c){
				if(!in_array($cid, $restricted)){
					unset($address_components[$cid]);
				}
			}
		}

		$delivery_methods = Order::$DeliveryMethod;
		$delivery_methods[-1] = '';

		if($action == 'list'){
			if($template_format == 'html'){
				$query_string = array();
				if($display_status){
					$query_string['display_status'] = bit2num($display_status);
				}

				$vars = array(
					'delivery_address',
					'time_start', 'time_end',
					'stat',
					'mobile', 'addressee',
					'userid',
					'tradestate',
				);
				foreach($vars as $var){
					if(isset($$var)){
						$query_string[$var] = $$var;
					}
				}
				if($deliverymethod != -1){
					$query_string['deliverymethod'] = $deliverymethod;
				}

				$query_string = http_build_query($query_string);

				include view('list');
			}else{
				if($template_format == 'ticket' || $template_format == 'barcode'){
					$ticketconfig = readdata('ticket');
					foreach($orders as &$o){
						$o['deliveryaddress'] = Address::FullPathString($o['addressid']).' '.$o['extaddress'];
						$o['dateline'] = rdate($o['dateline']);
						$o['tradetime'] = rdate($o['tradetime']);
					}
					unset($o);
				}
				include view('list_'.$template_format);
			}
		}else{
			include view('search');
		}
	}

	public function mark_unsortedAction(){
		global $_G;

		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if($_G['admin']->isSuperAdmin() && $order->status != Order::Unsorted){
				$order->status = Order::Unsorted;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Unsorted);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_sortedAction(){
		global $_G;

		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::Unsorted || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Sorted;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Sorted);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_deliveringAction(){
		global $_G;

		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::ToDeliveryStation || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Delivering;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Delivering);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_todpAction(){
		global $_G;
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_to_station')) exit('permission denied');

		$order = new Order($_GET['orderid']);
		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::Sorted || $_G['admin']->isSuperAdmin()){
				$order->status = Order::ToDeliveryStation;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::ToDeliveryStation);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_indpAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(!$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::ToDeliveryStation || $_G['admin']->isSuperAdmin()){
				$order->status = Order::InDeliveryStation;
				if(!empty($_GET['customlabel'])){
					$order->customlabel = trim($_GET['customlabel']);
				}
				$order->addLog($_G['admin'], Order::StatusChanged, Order::InDeliveryStation);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_receivedAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if(($order->status == Order::Delivering || $order->status == Order::InDeliveryStation) || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Received;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Received);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function mark_rejectedAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
				exit('permission denied');
			}

			if(($order->status == Order::Delivering || $order->status == Order::InDeliveryStation) || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Rejected;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Rejected);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function ticketAction($ticket_type = 'ticket'){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			$order = new Order($orderid);

			if($order->id <= 0){
				exit('the order has been canceled');
			}

			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('access denied');
			}

			$ordernum = $order->getUserOrderNum();
			$order = $order->toReadable();
			$order['ordernum'] = &$ordernum;

			$ticketconfig = readdata('ticket');

			include view('list_'.$ticket_type);
		}
	}

	public function barcodeAction(){
		$this->ticketAction('barcode');
	}

	public function cancelAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			if(empty($_GET['confirm'])){
				showmsg('confirm_to_cancel_order', 'confirm');
			}

			$new_status = Order::Canceled;
			$db->query("UPDATE {$tpre}order SET status=$new_status WHERE id=$orderid");
			if($db->affected_rows > 0){
				$order = new Order($orderid);
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Canceled);
				$order->cancel();
			}
			empty($_COOKIE['http_referer']) || redirect($_COOKIE['http_referer']);
		}
	}

	public function detail_outofstockAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(!$_G['admin']->hasPermission('order_sort_w')){
			exit('access denied');
		}

		$detailid = isset($_GET['detailid']) ? intval($_GET['detailid']) : 0;
		if($detailid <= 0){
			exit('access denied');
		}

		$state = !empty($_REQUEST['state']) ? 1 : 0;
		$order_unsorted = Order::Unsorted;
		$db->query("UPDATE {$tpre}orderdetail d
			SET d.state=$state
			WHERE d.id=$detailid AND (SELECT o.status FROM {$tpre}order o WHERE o.id=d.orderid)=$order_unsorted");

		$result = array();
		if($db->affected_rows){
			$orderid = $db->result_first("SELECT orderid FROM {$tpre}orderdetail WHERE id=$detailid");
			$db->query("UPDATE {$tpre}order o SET o.totalprice=(SELECT SUM(d.subtotal) FROM {$tpre}orderdetail d WHERE d.orderid=o.id AND d.state=0) WHERE o.id=$orderid");
			$result['totalprice'] = $db->result_first("SELECT totalprice FROM {$tpre}order WHERE id=$orderid");

			$order = new Order($orderid);
			$order->addLog($_G['admin'], $state ? Order::DetailOutOfStock : Order::DetailInStock, $detailid);
		}

		echo json_encode($result);
	}

	public function updateTradeStateAction(){
		if(empty($_GET['orderid']))
			exit('access denied');

		$orderid = intval($_GET['orderid']);
		if($orderid <= 0)
			exit('invalid order id');

		$alipay = new Alipay;
		$result = $alipay->queryOrder('O'.$orderid, false);
		if(isset($result['alipay_trade_query_response'])){
			$response = $result['alipay_trade_query_response'];
			if(isset($response['trade_status'])){
				runhooks('trade_notified', array(
					$response['out_trade_no'],
					Wallet::ViaAlipay,
					$response['trade_no'],
					Alipay::$TradeStateEnum[$response['trade_status']],
					$response,
				));
			}

			showmsg('successfully_updated_order_trade_state', 'refresh');
		}

		showmsg('order_not_exist_failed_to_update_order_trade_state', 'back');
	}

}
