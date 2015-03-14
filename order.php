<?php

require_once './core/init.inc.php';

if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action){
case 'delete':
	if(empty($_GET['confirm'])){
		showmsg('confirm_to_cancel_order', 'confirm');
	}

	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid > 0){
		//@todo: Return fee here
		$paidstate = array(AlipayNotify::TradeSuccess, AlipayNotify::TradeFinished);
		$paidstate = implode(',', $paidstate);
		Order::Delete($orderid, "userid=$_USER[id] AND status=0 AND alipaystate NOT IN ($paidstate)");
		showmsg('successfully_canceled_order', 'order.php');
	}

	showmsg('order_not_exist', 'back');
	break;

case 'mark_received':
	if(empty($_GET['confirm'])){
		showmsg('confirm_to_mark_order_as_received', 'confirm');
	}

	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid > 0){
		$old_status = array(Order::Sorted, Order::Delivering, Order::InDeliveryPoint);
		$old_status = implode(',', $old_status);
		$new_status = Order::Received;
		$db->query("UPDATE {$tpre}order SET status=$new_status WHERE id=$orderid AND userid=$_USER[id] AND status IN ($old_status)");
		if($db->affected_rows() > 0){
			$order = new Order($orderid);
			$order->addLog($_G['user'], Order::StatusChanged, Order::Received);

			rsetcookie('order-number-cache-time', 0);
			showmsg('successfully_received', 'order.php');
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

	include view('home_orderdetail');
	break;

case 'deliveringnum':
	$status = array(Order::Sorted, Order::Delivering, Order::InDeliveryPoint);
	$status = implode(',', $status);
	$num = $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE userid=$_USER[id] AND status IN (".$status.")");
	echo $num;
	exit;

case 'pay':
	if(isset($_GET['orderid'])){
		$orderid = intval($_GET['orderid']);
		$order = new Order($orderid);

		if(!$order->exists()){
			showmsg('order_not_exist', 'back');
		}

		if(!empty($order->alipaystate)){
			showmsg('your_alipay_wallet_is_processing_the_order', 'back');
		}
	}else{
		showmsg('illegal_operation');
	}

	include view('order_pay');
	break;

default:
	$limit = 10;
	$offset = ($page - 1) * $limit;

	$db->select_table('order');
	$condition = 'userid='.$_G['user']->id;
	$orders = $db->MFETCH('*', $condition." ORDER BY id DESC LIMIT $offset,$limit");
	$pagenum = $db->RESULTF('COUNT(*)', $condition);

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
			$o['items'] = !empty($order_details[$o['id']]) ? implode('<br />', $order_details[$o['id']]) : '';
			$o['totalprice'] = floatval($o['totalprice']);
			$o['deliveryfee'] = floatval($o['deliveryfee']);
		}
		unset($o);
	}

	include view('home');
}

?>
