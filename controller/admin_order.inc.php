<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'mark_sorted', 'mark_delivered', 'print');
$action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

switch($action){
	case 'list':
		$display_status = array();
		if($_G['admin']->hasPermission('order_sort')){
			$display_status[] = 0;
		}

		if($_G['admin']->hasPermission('order_deliver')){
			$display_status[] = 1;
		}

		$display_status = implode(',', $display_status);

		if($_G['admin']->formatid > 0){
			$formatid = $_G['admin']->formatid;
			$componentid = $_G['admin']->componentid;
			$orders = $db->fetch_all("SELECT o.* FROM {$tpre}orderaddresscomponent c
				LEFT JOIN {$tpre}order o ON o.id=c.orderid
				WHERE c.formatid=$formatid AND c.componentid=$componentid AND o.status IN ($display_status)");
		}else{
			$orders = $db->fetch_all("SELECT * FROM {$tpre}order WHERE status IN ($display_status)");
		}

		if($orders){
			$orderids = array();
			foreach($orders as &$o){
				$orderids[] = $o['id'];
			}
			unset($o);

			$orderids = implode(',', $orderids);

			$details = $db->fetch_all("SELECT p.name,d.subtype,d.amount,d.amountunit,d.number,d.orderid
				FROM {$tpre}orderdetail d
					LEFT JOIN {$tpre}product p ON p.id=d.productid
				WHERE d.orderid IN ($orderids)");

			$order_details = array();
			foreach($details as &$d){
				$order_details[$d['orderid']][] = $d['name'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
			}
			unset($d);

			$addresses = $db->fetch_all("SELECT o.*,c.name componentname
				FROM {$tpre}orderaddresscomponent o
					LEFT JOIN {$tpre}addresscomponent c ON c.id=o.componentid
				WHERE o.orderid IN ($orderids)");

			$order_addresses = array();
			foreach($addresses as &$a){
				$order_addresses[$a['orderid']][$a['formatid']] = $a['componentname'];
			}
			unset($a);

			foreach($orders as &$o){
				$o['items'] = implode('<br />', $order_details[$o['id']]);
				$o['priceunit'] = Product::PriceUnits($o['priceunit']);
				$o['address'] = &$order_addresses[$o['id']];
			}
			unset($o);
		}

		include view('order_list');
	break;

	case 'mark_sorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
			exit('permission denied');
		}

		if($order->status == 0){
			$order->status = 1;
		}
		redirect($mod_url);
	break;

	case 'mark_delivered':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
			exit('permission denied');
		}

		if($order->status == 1){
			$order->status = 2;
		}
		redirect($mod_url);
	break;

	case 'print':
		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			$order = new Order($orderid);
			$order = $order->toReadable();
			include view('order_print');
		}
	break;
}

?>
