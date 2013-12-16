<?php

require_once './core/init.inc.php';

if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action){
case 'delete':
	if(empty($_GET['confirm'])){
		showmsg('您确定取消该订单吗？', 'confirm');
	}

	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid > 0){
		$db->query("DELETE FROM {$tpre}order WHERE id=$orderid AND userid=$_USER[id] AND status=0");
		if($db->affected_rows() > 0){
			showmsg('成功取消该订单！', 'home.php');
		}
	}

	showmsg('该订单不存在！', 'back');
	break;

case 'mark_delivered':
	if(empty($_GET['confirm'])){
		showmsg('您确认已经收到订单吗？', 'confirm');
	}

	$orderid = !empty($_GET['orderid']) ? intval($_GET['orderid']) : 0;
	if($orderid > 0){
		$db->query("UPDATE {$tpre}order SET status=2 WHERE id=$orderid AND userid=$_USER[id] AND status=1");
		if($db->affected_rows() > 0){
			showmsg('成功确认收货！欢迎再光临'.$_CONFIG['sitename'].'！', 'home.php');
		}
	}

	showmsg('该订单不存在！', 'back');
	break;

case 'view':
	if(empty($_GET['orderid'])) exit('access denied');
	$orderid = intval($_GET['orderid']);
	$order = new Order($orderid);
	if($order->id <= 0){
		showmsg('该订单已不存在。', 'refresh');
	}

	$order = $order->toReadable();

	include view('home_orderdetail');
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
			$o['items'] = implode('<br />', $order_details[$o['id']]);
			$o['totalprice'] = floatval($o['totalprice']);
			$o['priceunit'] = Product::PriceUnits($o['priceunit']);
		}
		unset($o);
	}

	include view('home');
}

?>
