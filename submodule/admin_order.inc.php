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

if(!defined('IN_ADMINCP')) exit('access denied');

$action = &$_REQUEST['action'];
empty($action) && $action = 'list';

switch($action){
	case 'list':case 'search':
		//显示（或导出Excel表格）订单列表
		if($action == 'list'){
			//保存查询条件，数组中的每个元素用AND连接构成一个WHERE子句
			$condition = array();

			//$display_status数组的键名即订单的状态，$display_status[X]为true则显示状态为X的订单
			if(!empty($_POST['display_status']) && is_array($_POST['display_status'])){
				$display_status = $_POST['display_status'];
			}elseif(isset($_GET['display_status'])){
				$display_status = array();
				foreach(explode(',', $_GET['display_status']) as $status){
					$display_status[$status] = true;
				}
			}else{
				$display_status = array();
				foreach(Order::$Status as $statusid => $value){
					$display_status[$statusid] = true;
				}
			}

			//判断当前管理员的权限，过滤掉无权限查看的订单
			if(!$_G['admin']->hasPermission('order_sort')){
				unset($display_status[Order::Unsorted]);
			}

			if(!$_G['admin']->hasPermission('order_deliver')){
				unset($display_status[Order::Sorted], $display_status[Order::Delivering]);
			}

			//输入了订单号，直接按订单号查询，忽略管理员权限外的其他条件
			if(!empty($_REQUEST['orderid'])){
				$condition[] = 'o.id='.intval($_REQUEST['orderid']);
				$display_status[Order::Received] = $display_status[Order::Rejected] = true;

				$time_start = '';
				$time_end = '';

			//没有订单号，所有条件均要考虑
			}else{
				//下单起始时间
				if(isset($_REQUEST['time_start'])){
					$time_start = empty($_REQUEST['time_start']) ? '' : rstrtotime($_REQUEST['time_start']);
				}else{
					$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));

					//根据截单时间调整时分秒
					$deliverytimes = DeliveryTime::FetchAllEffective();
					usort($deliverytimes, function($t1, $t2){
						return $t1['deadline'] > $t2['deadline'];
					});
					$dt = current($deliverytimes);
					$time_start += $dt['deadline'];
					unset($deliverytimes, $dt);
				}
				//下单截止时间
				if(isset($_REQUEST['time_end'])){
					$time_end = empty($_REQUEST['time_end']) ? '' : rstrtotime($_REQUEST['time_end']);
				}else{
					$time_end = $time_start + 1 * 24 * 3600;
				}

				if($time_start !== ''){
					$condition[] = 'o.dateline>='.$time_start;
					$time_start = rdate($time_start, 'Y-m-d H:i');
				}

				if($time_end !== ''){
					$condition[] = 'o.dateline<='.$time_end;
					$time_end = rdate($time_end, 'Y-m-d H:i');
				}
			}
			$display_status = array_keys($display_status);
			$condition[] = 'o.status IN ('.implode(',', $display_status).')';

			//过滤掉送货地点不在当前管理员的管辖范围内的订单
			$limitation_addressids = $_G['admin']->getLimitations();
			if($limitation_addressids){
				$condition[] = 'o.addressid IN ('.implode(',', $limitation_addressids).')';
			}

			//根据送货地址查询订单
			$delivery_address = array();
			if(!empty($_REQUEST['delivery_address']) && is_array($_REQUEST['delivery_address'])){
				$dacondition = array();
				foreach($_REQUEST['delivery_address'] as $addressid){
					$addressid = intval($addressid);
					$delivery_address[] = $addressid;
					$address_range = Address::Extension($addressid);
					$dacondition[] = 'o.addressid IN ('.implode(',', $address_range).')';
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

			//查询某个管理员管辖范围内的订单
			if(!empty($_REQUEST['administrator'])){
				$administrator = trim($_REQUEST['administrator']);
				$limitation = $db->result_first("SELECT limitation FROM {$tpre}administrator WHERE account='$administrator'");
				if($limitation){
					$limitation = explode(',', $limitation);
					$limitation = Address::Extension($limitation);
					$condition[] = 'o.addressid IN ('.implode(',', $limitation).')';
				}
			}else{
				$administrator = '';
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
			$template_formats = array('html', 'csv', 'print', 'barcode');
			$template_format = &$_REQUEST['format'];
			if(empty($template_format) || !in_array($template_format, $template_formats)){
				$template_format = $template_formats[0];
			}

			//从数据库中查询订单，实现分页
			$pagenum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order o WHERE $condition");
			if(!$stat['statonly']){
				$limit_subsql = '';
				if($template_format == 'html'){
					$limit = 20;
					$offset = ($page - 1) * $limit;
					$limit_subsql = "LIMIT $offset,$limit";
				}

				$orders = $db->fetch_all("SELECT o.*,
						(SELECT COUNT(*) FROM {$tpre}order WHERE userid=o.userid AND dateline<o.dateline) AS ordernum
					FROM {$tpre}order o
					WHERE $condition
					ORDER BY o.status,o.dtime_from,o.dateline
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
		}else{
			$display_status = array_keys(Order::$Status);
			$time_start = rmktime(17, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));
			$time_end = $time_start + 24 * 3600;
		}

		$address_format = Address::Format();
		$address_components = Address::Components();

		$restricted = $_G['admin']->getLimitations();
		if($restricted){
			foreach($address_components as $cid => $c){
				if(!in_array($cid, $restricted)){
					unset($address_components[$cid]);
				}
			}
		}

		$available_status = Order::$Status;
		if(!$_G['admin']->hasPermission('order_sort')){
			unset($available_status[Order::Unsorted]);
		}
		if(!$_G['admin']->hasPermission('order_deliver')){
			unset($available_status[Order::Sorted], $available_status[Order::Delivering]);
		}

		foreach($available_status as &$checked){
			$checked = false;
		}
		unset($checked);

		foreach($display_status as $status){
			$available_status[$status] = true;
		}

		if($action == 'list'){
			if($template_format == 'html'){
				include view('order_list');
			}else{
				if($template_format == 'print' || $template_format == 'barcode'){
					$ticketconfig = readdata('ticket');
					foreach($orders as &$o){
						$o['deliveryaddress'] = Address::FullPathString($o['addressid']).' '.$o['extaddress'];
					}
				}
				include view('order_'.$template_format);
			}
		}else{
			include view('order_search');
		}
	break;

	case 'mark_unsorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if($_G['admin']->isSuperAdmin() && $order->status != Order::Unsorted){
				$order->status = Order::Unsorted;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Unsorted);
			}
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_sorted':
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

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_delivering':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::InDeliveryPoint || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Delivering;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Delivering);
			}
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_indp':
		if(empty($_GET['orderid'])){
			include view('order_markindp');
			exit;
		}

		if(!$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::Sorted || $_G['admin']->isSuperAdmin()){
				$order->status = Order::InDeliveryPoint;
				if(!empty($_GET['customlabel'])){
					$order->customlabel = trim($_GET['customlabel']);
				}
				$order->addLog($_G['admin'], Order::StatusChanged, Order::InDeliveryPoint);
			}
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_received':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if(($order->status == Order::Delivering || $order->status == Order::InDeliveryPoint) || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Received;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Received);
			}
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_rejected':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
				exit('permission denied');
			}

			if(($order->status == Order::Delivering || $order->status == Order::InDeliveryPoint) || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Rejected;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Rejected);
			}
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'print':case 'barcode':
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

			include view('order_'.$action);
		}
	break;

	case 'cancel':
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
	break;

	case 'detail_outofstock':
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

			$order = new Order;
			$order->id = $orderid;
			$order->addLog($_G['admin'], $state ? Order::DetailOutOfStock : Order::DetailInStock, $detailid);
		}

		echo json_encode($result);
	break;

	case 'changestate':
		include view('order_changestate');
		break;

	default:
		showmsg('illegal_operation');
}

?>
