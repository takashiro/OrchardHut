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

		$order_address = array();
		if($_G['admin']->formatid > 0){
			$order_address[] = array(
				'formatid' => $_G['admin']->formatid,
				'componentid' => $_G['admin']->componentid,
			);
		}

		if(!empty($_POST['delivery_address'])){
			$address = explode(',', $_POST['delivery_address']);
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
		}

		$condition = array("status IN ($display_status)");
		foreach($order_address as $a){
			$condition[] = "id IN (SELECT orderid FROM {$tpre}orderaddresscomponent WHERE formatid=$a[formatid] AND componentid=$a[componentid])";
		}

		$condition = implode(' AND ', $condition);
		$orders = $db->fetch_all("SELECT * FROM {$tpre}order WHERE $condition");

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
