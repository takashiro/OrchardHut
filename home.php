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
			showmsg('成功取消该订单！', 'refresh');
		}
	}

	showmsg('该订单不存在！', 'back');
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

		$details = $db->fetch_all("SELECT p.name,d.subtype,d.amount,d.amountunit,d.number,d.orderid
			FROM {$tpre}orderdetail d
				LEFT JOIN {$tpre}product p ON p.id=d.productid
			WHERE d.orderid IN (".implode(',', $orderids).')');

		$order_details = array();
		foreach($details as &$d){
			$order_details[$d['orderid']][] = $d['name'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
		}
		unset($d);

		foreach($orders as &$o){
			$o['items'] = implode('<br />', $order_details[$o['id']]);
			$o['priceunit'] = Product::PriceUnits($o['priceunit']);
		}
		unset($o);
	}

	include view('home');
}

?>
