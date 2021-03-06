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
	redirect('index.php?mod=user:login');
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
		if($order->paymentmethod == Wallet::ViaAlipay && $order->tradestate != Wallet::TradeSuccess){
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
		showmsg('successfully_canceled_order', 'index.php?mod=order');
	}

	showmsg('order_not_exist', 'back');
	break;

case 'mark_received':
	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid <= 0){
		showmsg('order_not_exist', 'back');
	}
	$order = new Order($orderid);
	if(!$order->exists()){
		showmsg('order_not_exist', 'back');
	}

	$old_status = array(Order::Sorted, Order::Delivering);
	if(in_array($order->status, $old_status)){
		if(empty($_GET['confirm'])){
			showmsg('confirm_to_mark_order_as_received', 'confirm');
		}

		$old_status = implode(',', $old_status);
		$new_status = Order::Received;
		$home_delivery = Order::HomeDelivery;
		$db->query("UPDATE {$tpre}order SET status=$new_status WHERE id=$orderid AND userid={$_USER['id']} AND status IN ($old_status)");
		if($db->affected_rows > 0){
			$order = new Order($orderid);
			$order->addLog($_G['user'], Order::StatusChanged, Order::Received);
		}
		rsetcookie('order-number-cache-time', 0);
		showmsg('successfully_received', 'index.php?mod=order');
	}
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
			$interface = Wallet::$PaymentInterface[$order->paymentmethod];
			redirect('index.php?mod='.$interface.'&orderid='.$order->id.'&enable_trade_query=1');
		}
	}else{
		showmsg('illegal_operation');
	}

	$paymentconfig = Wallet::ReadConfig();
	include view('pay');
	break;

case 'pack':
	if(empty($_GET['orderid'])){
		exit('access denied');
	}
	$orderid = intval($_GET['orderid']);
	$order = new Order($orderid);

	if(!$order->exists() || $order->userid != $_G['user']->id){
		showmsg('order_not_exist', 'back');
	}

	if($order->status != Order::ToDeliveryStation && $order->status != Order::InDeliveryStation){
		showmsg('order_not_exist', 'back');
	}

	$order->packcode = rand(1, 0xFF);
	$order = $order->toReadable();

	include view('pack');
	break;

case 'qrpack':
	if(empty($_GET['qrcode']) || empty($_GET['stationid'])) exit;

	$qrcode = intval($_GET['qrcode']);
	$stationid = intval($_GET['stationid']);
	$db->query("UPDATE {$tpre}station SET packqrcode=0 WHERE id=$stationid AND packqrcode=$qrcode");
	if($db->affected_rows > 0){
		$order_range = $db->result_first("SELECT orderrange FROM {$tpre}station WHERE id=$stationid");
		$order_range = $order_range ? explode(',', $order_range) : array();
		$order_range = Address::Extension($order_range);

		$condition = array('userid='.$_G['user']->id, 'status='.Order::InDeliveryStation);
		if($order_range){
			$condition[] = 'addressid IN ('.implode(',', $order_range).')';
		}

		$condition = implode(' AND ', $condition);
		$orderid = $db->result_first("SELECT id FROM {$tpre}order WHERE $condition ORDER BY dateline DESC LIMIT 1");
		if(!$orderid){
			showmsg('you_have_no_order_in_this_station');
		}

		$table = $db->select_table('stationorder');
		$row = array(
			'stationid' => $stationid,
			'orderid' => $orderid,
			'dateline' => TIMESTAMP,
		);
		$table->insert($row, false, 'IGNORE');

		showmsg('please_wait_for_printing_ticket');
	}else{
		showmsg('qrcode_expired_please_rescan');
	}

default:
	$limit = 10;
	$offset = ($page - 1) * $limit;

	$table = $db->select_table('order');
	$condition = 'userid='.$_G['user']->id;
	$unsorted = Order::Unsorted;
	$paid_with_cash = Wallet::ViaCash;
	$trade_success = Wallet::TradeSuccess;
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

	$view = 'list';
	if(isset($_GET['packqrcode']) && isset($_GET['stationid'])){
		$stationid = intval($_GET['stationid']);
		$packqrcode = intval($_GET['packqrcode']);
		foreach($orders as $i => $order){
			if($order['status'] != Order::InDeliveryStation){
				unset($orders[$i]);
			}
		}
		$view = 'confirm_pack';
	}

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
			$order_details[$d['orderid']][] = array(
				'name' => $d['productname'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : ''),
				'num' => ($d['amount'] * $d['number']).$d['amountunit'],
			);
		}
		unset($d);

		foreach($orders as &$o){
			$o['items'] = !empty($order_details[$o['id']]) ? $order_details[$o['id']] : array();
			$o['totalprice'] = floatval($o['totalprice']);
			$o['deliveryfee'] = floatval($o['deliveryfee']);
		}
		unset($o);
	}

	include view($view);
}
