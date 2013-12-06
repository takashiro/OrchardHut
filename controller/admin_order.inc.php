<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'mark_sorted', 'mark_delivered', 'print', 'delete');
$action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

switch($action){
	case 'list':
		$condition = array();

		$display_status = array();
		if($_G['admin']->hasPermission('order_sort')){
			$display_status[] = 0;
		}

		if($_G['admin']->hasPermission('order_deliver')){
			$display_status[] = 1;
		}

		if(!empty($_REQUEST['orderid'])){
			$condition[] = 'id='.intval($_REQUEST['orderid']);
			$completed_order = 0;

			$display_status[] = 2;
			$display_status = implode(',', $display_status);
			$condition[] = "status IN ($display_status)";
		}else{
			if(empty($_REQUEST['completed_order'])){
				$completed_order = 0;

				$display_status = implode(',', $display_status);
				$condition[] = "status IN ($display_status)";
			}else{
				$completed_order = 1;
				$condition[] = 'status>=2';
			}
		}

		$order_address = array();
		if($_G['admin']->formatid > 0){
			$order_address[] = array(
				'formatid' => $_G['admin']->formatid,
				'componentid' => $_G['admin']->componentid,
			);
		}

		$formatid = $componentid = 0;
		if(!empty($_REQUEST['delivery_address'])){
			$delivery_address = $_REQUEST['delivery_address'];
			$address = explode(',', $delivery_address);
			foreach($address as $format_order => $id){
				$id = intval($id);
				if($id <= 0){
					$format_order--;
					break;
				}

				$componentid = $id;
			}

			if($format_order >= 0){
				$formatid = $db->result_first("SELECT id FROM {$tpre}addressformat ORDER BY displayorder LIMIT $format_order,1");

				$order_address[] = array(
					'formatid' => $formatid,
					'componentid' => $componentid,
				);
			}
		}else{
			$delivery_address = '0,0';
		}

		foreach($order_address as $a){
			$condition[] = "id IN (SELECT orderid FROM {$tpre}orderaddresscomponent WHERE formatid=$a[formatid] AND componentid=$a[componentid])";
		}

		$condition = implode(' AND ', $condition);

		$limit = 20;
		$offset = ($page - 1) * $limit;
		$orders = $db->fetch_all("SELECT * FROM {$tpre}order WHERE $condition LIMIT $offset,$limit");
		$pagenum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE $condition");

		if($orders){
			$orderids = array();
			foreach($orders as &$o){
				$orderids[] = $o['id'];
			}
			unset($o);

			$orderids = implode(',', $orderids);

			$details = $db->fetch_all("SELECT d.productname,d.subtype,d.amount,d.amountunit,d.number,d.orderid
				FROM {$tpre}orderdetail d
				WHERE d.orderid IN ($orderids)");

			$order_details = array();
			foreach($details as &$d){
				$order_details[$d['orderid']][] = $d['productname'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
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

		$address_format = Address::Format();
		$address_components = Address::Components();

		$is_restricted = $_G['admin']->formatid != 0;
		$reserved = array();
		if($is_restricted){
			$find_parent = array();
			foreach($address_components as $c){
				$find_parent[$c['id']] = $c['parentid'];
			}

			$cur = $_G['admin']->componentid;
			while($cur){
				$reserved[] = $cur;
				$cur = $find_parent[$cur];
			}
		}

		foreach($address_format as $format){
			if($is_restricted){
				foreach($address_components as $i => $c){
					if($c['formatid'] == $format['id'] && !in_array($c['id'], $reserved)){
						unset($address_components[$i]);
					}
				}
			}else{
				array_unshift($address_components, array('id' => 0, 'formatid' => $format['id'], 'name' => '不限', 'parentid' => 0));
			}

			if($format['id'] == $_G['admin']->formatid){
				$is_restricted = false;
			}
		}

		include view('order_list');
	break;

	case 'mark_sorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
			exit('permission denied');
		}

		if($order->status == 0){
			$order->status = 1;
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_delivered':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
			exit('permission denied');
		}

		if($order->status == 1){
			$order->status = 2;
		}
		
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'print':
		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			$order = new Order($orderid);
			$order = $order->toReadable();
			include view('order_print');
		}
	break;

	case 'delete':
		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			if(empty($_GET['confirm'])){
				showmsg('您确认删除该订单吗？', 'confirm');
			}

			Order::Delete($orderid);
			redirect($_COOKIE['http_referer']);
		}
	break;
}

?>
