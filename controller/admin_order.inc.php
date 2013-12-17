<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'mark_unsorted', 'mark_delivering', 'mark_received', 'mark_rejected', 'print', 'delete');
$action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

switch($action){
	case 'list':
		$condition = array();

		if(!empty($_POST['display_status']) && is_array($_POST['display_status'])){
			$display_status = $_POST['display_status'];
		}elseif(!empty($_GET['display_status'])){
			$display_status = array();
			foreach(explode(',', $_GET['display_status']) as $status){
				$display_status[$status] = true;
			}
		}else{
			$display_status = Order::$Status;
		}

		if(!$_G['admin']->hasPermission('order_sort')){
			unset($display_status[0]);
		}

		if(!$_G['admin']->hasPermission('order_deliver')){
			unset($display_status[1]);
		}

		if(!empty($_REQUEST['orderid'])){
			$condition[] = 'id='.intval($_REQUEST['orderid']);
			$display_status[2] = $display_status[3] = true;

			$time_start = '';
			$time_end = '';
		}else{
			if(isset($_REQUEST['time_start'])){
				$time_start = empty($_REQUEST['time_start']) ? '' : rstrtotime($_REQUEST['time_start']);
			}else{
				$time_start = rmktime(17, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));
			}
			if(isset($_REQUEST['time_end'])){
				$time_end = empty($_REQUEST['time_end']) ? '' : rstrtotime($_REQUEST['time_end']);
			}else{
				$time_end = TIMESTAMP;
			}
			
			if($time_start !== ''){
				$condition[] = 'dateline>='.$time_start;
				$time_start = rdate($time_start, 'Y-m-d H:i');
			}

			if($time_end !== ''){
				$condition[] = 'dateline<='.$time_end;
				$time_end = rdate($time_end, 'Y-m-d H:i');
			}
		}
		$display_status = array_keys($display_status);
		$condition[] = 'status IN ('.implode(',', $display_status).')';

		$limitation_condition = array();
		foreach($_G['admin']->getLimitations() as $componentid){
			$limitation_condition[] = "id IN (SELECT orderid FROM {$tpre}orderaddresscomponent WHERE componentid=$componentid)";
		}

		if($limitation_condition){
			$condition[] = '('.implode(' OR ', $limitation_condition).')';
		}

		$order_address = array();

		$componentid = 0;
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
				$order_address[] = $componentid;
			}
		}else{
			$delivery_address = '0,0';
		}

		foreach($order_address as $componentid){
			$condition[] = "id IN (SELECT orderid FROM {$tpre}orderaddresscomponent WHERE componentid=$componentid)";
		}

		$condition = implode(' AND ', $condition);

		$limit = 20;
		$offset = ($page - 1) * $limit;
		$orders = $db->fetch_all("SELECT * FROM {$tpre}order WHERE $condition LIMIT $offset,$limit");
		$pagenum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order WHERE $condition");
		
		$stat = array();
		if(!empty($_REQUEST['stat']['totalprice'])){
			$stat['totalprice'] = $db->result_first("SELECT SUM(totalprice) FROM {$tpre}order WHERE $condition");
		}

		$stat_query = '';
		foreach($stat as $field => $value){
			$stat_query.= '&stat['.$field.']=1';
		}

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

		$is_restricted = !empty($_G['admin']->limitation);
		$reserved = array();
		if($is_restricted){
			$find_parent = array();
			foreach($address_components as $c){
				$find_parent[$c['id']] = $c['parentid'];
			}

			foreach($_G['admin']->getLimitations() as $cur){
				while($cur){
					$reserved[] = $cur;
					$cur = $find_parent[$cur];
				}
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
		}

		$available_status = Order::$Status;
		if(!$_G['admin']->hasPermission('order_sort')){
			unset($available_status[0]);
		}
		if(!$_G['admin']->hasPermission('order_deliver')){
			unset($available_status[1]);
		}

		foreach($available_status as &$checked){
			$checked = false;
		}
		unset($checked);

		foreach($display_status as $status){
			$available_status[$status] = true;
		}

		include view('order_list');
	break;

	case 'mark_unsorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->getLimitations())){
			exit('permission denied');
		}

		if($order->status == Order::Unsorted || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Delivering;
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_delivering':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->getLimitations())){
			exit('permission denied');
		}

		if($order->status == Order::Unsorted || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Delivering;
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_received':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->getLimitations())){
			exit('permission denied');
		}

		if($order->status == Order::Delivering || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Received;
		}
		
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_rejected':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->formatid, $_G['admin']->componentid)){
			exit('permission denied');
		}

		if($order->status == Order::Delivering || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Rejected;
		}
		
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'print':
		$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		if($orderid > 0){
			$order = new Order($orderid);
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('access denied');
			}

			$order = $order->toReadable();
			if(!empty($_CONFIG['ticket_tips'])){
				$tips = explode("\n", $_CONFIG['ticket_tips']);
				if(count($tips) > 0){
					$tips = $tips[array_rand($tips)];
				}else{
					$tips = $tips[0];
				}
			}else{
				$tips = '';
			}
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
