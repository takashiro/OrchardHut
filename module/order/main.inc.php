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

if(!defined('S_ROOT')) exit('access denied');

if(!$_G['user']->isLoggedIn()){
	redirect('./?mod=user');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action){
case 'delete':
	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid <= 0){
		showmsg('order_not_exist', 'back');
	}

	$order = new Order($orderid);
	if(!$order->exists()){
		showmsg('order_not_exist', 'back');
	}

	if(empty($_GET['confirm'])){
		if($order->paymentmethod == Wallet::ViaAlipay && $order->tradestate != Order::TradeSuccess){
			showmsg('alipay_not_updated_confirm_to_cancel_order', 'confirm');
		}else{
			showmsg('confirm_to_cancel_order', 'confirm');
		}
	}

	$new_status = Order::Canceled;
	$db->query("UPDATE {$tpre}order SET status=$new_status WHERE id=$orderid AND status=0");
	if($db->affected_rows > 0){
		$order->addLog($_G['user'], Order::StatusChanged, Order::Canceled);
		$order->cancel();
		showmsg('successfully_canceled_order', './?mod=order');
	}

	showmsg('order_not_exist', 'back');
	break;

case 'mark_received':
	if(empty($_GET['confirm'])){
		showmsg('confirm_to_mark_order_as_received', 'confirm');
	}

	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid > 0){
		$old_status = array(Order::Sorted, Order::ToDeliveryStation, Order::Delivering, Order::InDeliveryStation);
		$old_status = implode(',', $old_status);
		$new_status = Order::Received;
		$db->query("UPDATE {$tpre}order SET status=$new_status WHERE id=$orderid AND userid={$_USER['id']} AND status IN ($old_status)");
		if($db->affected_rows > 0){
			$order = new Order($orderid);
			$order->addLog($_G['user'], Order::StatusChanged, Order::Received);

			rsetcookie('order-number-cache-time', 0);
			showmsg('successfully_received', './?mod=order');
		}
	}

	showmsg('order_not_exist', 'back');
	break;

case 'view':
	if(empty($_GET['orderid'])) exit('access denied');
	$orderid = intval($_GET['orderid']);
	$order = new Order($orderid);
	if($order->id <= 0 || $order->userid != $_G['user']->id){
		showmsg('order_not_exist', 'refresh');
	}

	$orderlog = $order->getLogs();
	$order = $order->toReadable();

	include view('detail');
	break;

case 'return':
	if(empty($_GET['orderid'])) exit('access denied');
	$orderid = intval($_GET['orderid']);
	$order = new Order($orderid);
	if(!$order->exists() || $order->userid != $_G['user']->id || $order->status == Order::Unsorted || $order->status == Order::Canceled){
		showmsg('order_not_exist', 'refresh');
	}

	if($_POST){
		if(!isset($_POST['detail']) || !is_array($_POST['detail'])) exit('invalid');

		$valid_details = array();
		foreach($order->getDetails() as $d){
			$valid_details[$d['id']] = $d;
		}

		$returned_order = new ReturnedOrder;
		$returned_order->id = $order->id;
		$returned_order->dateline = TIMESTAMP;
		$returned_order->reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';
		if(!$returned_order->reason && !empty($_POST['otherreason'])){
			$returned_order->reason = htmlspecialchars($_POST['otherreason']);
		}
		$returned_order->state = ReturnedOrder::Submitted;
		$returned_order->returnedfee = 0;

		foreach($_POST['detail'] as $detailid => $number){
			$detailid = intval($detailid);
			$number = intval($number);
			if($detailid > 0 && $number > 0 && isset($valid_details[$detailid])){
				$d = $valid_details[$detailid];
				$max_number = $d['amount'] * $d['number'];
				$max_number < $number && $number = $max_number;
				$returned_order->addDetail($detailid, $number);
			}
		}

		if($returned_order->insert('IGNORE')){
			showmsg('returned_order_has_been_submitted', 'refresh');
		}else{
			showmsg('failed_to_submit_returned_order', 'back');
		}
	}

	$returned_order = new ReturnedOrder($order->id);

	$order = $order->toReadable();
	if($returned_order->exists()){
		$returned_order = $returned_order->toReadable();

		$details = array();
		foreach($returned_order['details'] as $d){
			$details[$d['id']] = $d;
		}

		foreach($order['detail'] as $d){
			if(!isset($details[$d['id']]))
				continue;

			foreach($d as $k => $v){
				isset($details[$d['id']][$k]) || $details[$d['id']][$k] = $v;
			}
		}
		$returned_order['details'] = $details;

	}else{
		unset($returned_order);
	}

	$returned_order_config = readdata('returnedorderconfig');
	if(!empty($returned_order_config['reason_options'])){
		$returned_order_config['reason_options'] = explode("\n", $returned_order_config['reason_options']);
	}

	include view('return');
	break;

case 'comment':
	if(empty($_GET['orderid'])) exit('access denied');
	$orderid = intval($_GET['orderid']);
	$order = new Order($orderid);
	if(!$order->exists() || $order->userid != $_G['user']->id){
		showmsg('order_not_exist', 'refresh');
	}

	if($_POST){
		$comment = array();
		foreach(array('level1', 'level2', 'level3') as $var){
			$comment[$var] = isset($_POST[$var]) ? intval($_POST[$var]) : 3;
			$comment[$var] = min(max(1, $comment[$var]), 5);
		}
		$comment['content'] = isset($_POST['content']) ? $_POST['content'] : 0;

		$order->makeComment($comment);

		showmsg('successfully_made_comment_for_order', 'refresh');
	}

	$comment = $order->getComment();
	$order = $order->toReadable();
	include view('comment');
	break;

case 'deliveringnum':
	$status = array(Order::Sorted, Order::ToDeliveryStation, Order::Delivering, Order::InDeliveryStation);
	$status = implode(',', $status);
	$num = $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE userid={$_USER['id']} AND status IN ($status)");
	echo $num;
	exit;

case 'pay':
	if(isset($_GET['orderid'])){
		$orderid = intval($_GET['orderid']);
		$order = new Order($orderid);

		if(!$order->exists()){
			showmsg('order_not_exist', 'back');
		}

		if(!empty($order->paymentmethod)){
			$interface = Order::$PaymentInterface[$order->paymentmethod];
			redirect('./?mod='.$interface.'&orderid='.$order->id);
		}
	}else{
		showmsg('illegal_operation');
	}

	$paymentconfig = readdata('payment');

	$enabled_method_count = 0;
	foreach($paymentconfig['enabled_method'] as $methodid => $enabled){
		$enabled_method_count++;
	}

	if($paymentconfig['enabled_method'][Wallet::ViaAlipay] && $paymentconfig['enabled_method'][Wallet::ViaWallet]){
		include view('pay');
		exit;
	}elseif($paymentconfig['enabled_method'][Wallet::ViaAlipay]){
		redirect('./?mod=alipay&orderid='.$orderid);
	}elseif($paymentconfig['enabled_method'][Wallet::ViaWallet]){
		redirect('./?mod=payment&orderid='.$orderid);
	}else{
		showmsg('payment_is_now_disabled', 'back');
	}

	break;

default:
	$limit = 10;
	$offset = ($page - 1) * $limit;

	$table = $db->select_table('order');
	$condition = 'userid='.$_G['user']->id;
	$unsorted = Order::Unsorted;
	$paid_with_cash = Wallet::ViaCash;
	$trade_success = Order::TradeSuccess;
	$orders = $table->fetch_all('*', $condition." ORDER BY
		(CASE paymentmethod
			WHEN $paid_with_cash THEN $trade_success
			ELSE
				(CASE status
					WHEN $unsorted THEN tradestate
					ELSE $trade_success
				END)
		END),id DESC LIMIT $offset,$limit");
	$pagenum = $table->result_first('COUNT(*)', $condition);

	if($orders){
		$orderids = array();
		foreach($orders as &$o){
			$orderids[] = $o['id'];
		}
		unset($o);

		$details = $db->fetch_all("SELECT d.productname,d.subtype,d.amount,d.amountunit,d.number,d.orderid
			FROM {$tpre}orderdetail d
			WHERE d.orderid IN (".implode(',', $orderids).')');

		$order_details = array();
		foreach($details as &$d){
			$order_details[$d['orderid']][] = $d['productname'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
		}
		unset($d);

		foreach($orders as &$o){
			$o['items'] = !empty($order_details[$o['id']]) ? $order_details[$o['id']] : array();
			$o['totalprice'] = floatval($o['totalprice']);
			$o['deliveryfee'] = floatval($o['deliveryfee']);
		}
		unset($o);
	}

	include view('list');
}

?>
