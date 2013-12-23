<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'mark_unsorted', 'mark_sorted', 'mark_delivering', 'mark_received', 'mark_rejected', 'print', 'delete', 'search');
$action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions) ? $_REQUEST['action'] : $actions[0];

switch($action){
	case 'list':case 'search':
		if($action == 'list'){
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
				unset($display_status[Order::Unsorted]);
			}

			if(!$_G['admin']->hasPermission('order_deliver')){
				unset($display_status[Order::Sorted], $display_status[Order::Delivering]);
			}

			if(!empty($_REQUEST['orderid'])){
				$condition[] = 'id='.intval($_REQUEST['orderid']);
				$display_status[Order::Received] = $display_status[Order::Rejected] = true;

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
					$time_end = $time_start + 24 * 3600;
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

			$limitation_condition = array();
			foreach($_G['admin']->getLimitations() as $componentid){
				$limitation_condition[] = "o.id IN (SELECT orderid FROM {$tpre}orderaddresscomponent WHERE componentid=$componentid)";
			}

			if($limitation_condition){
				$condition[] = '('.implode(' OR ', $limitation_condition).')';
			}

			$order_address = array();

			$delivery_address = array();
			if(!empty($_REQUEST['delivery_address']) && is_array($_REQUEST['delivery_address'])){
				$delivery_address = &$_REQUEST['delivery_address'];
				foreach($delivery_address as $address){
					$componentid = NULL;
					$address = explode(',', $address);
					foreach($address as $format_order => $id){
						$id = intval($id);
						if($id <= 0){
							$format_order--;
							break;
						}

						$componentid = $id;
					}

					if($format_order >= 0 && $componentid !== NULL){
						$order_address[] = $componentid;
					}
				}
			}

			if($order_address){
				$order_address = array_unique($order_address);
				$condition[] = 'o.id IN (SELECT orderid FROM '.$tpre.'orderaddresscomponent WHERE componentid IN ('.implode(',', $order_address).'))';
			}

			if(!empty($_REQUEST['userid'])){
				$userid = intval($_REQUEST['userid']);
				$condition[] = 'o.userid='.$userid;
			}else{
				$userid = '';
			}

			if(!empty($_REQUEST['addressee'])){
				$addressee = trim($_REQUEST['addressee']);
				$condition[] = 'o.addressee LIKE \'%'.$addressee.'%\'';
			}else{
				$addressee = '';
			}

			if(!empty($_REQUEST['mobile'])){
				$mobile = trim($_REQUEST['mobile']);
				$condition[] = 'o.mobile=\''.$mobile.'\'';
			}else{
				$mobile = '';
			}

			if(!empty($_REQUEST['administrator'])){
				$administrator = trim($_REQUEST['administrator']);
				$limitation = $db->result_first("SELECT limitation FROM {$tpre}administrator WHERE account='$administrator'");
				$limitation = explode(',', $limitation);
				foreach($limitation as &$limitation_i){
					$limitation_i = intval($limitation_i);
				}
				unset($limitation_i);
				$limitation = array_unique($limitation);

				if($limitation){
					$limitation = implode(',', $limitation);
					$condition[] = 'o.id IN (SELECT orderid FROM '.$tpre.'orderaddresscomponent WHERE componentid IN ('.$limitation.'))';
				}
			}else{
				$administrator = '';
			}

			$condition = implode(' AND ', $condition);
			
			$stat = array(
				'statonly' => !empty($_REQUEST['stat']['statonly']),
				'totalprice' => !empty($_REQUEST['stat']['totalprice']),
				'item' => !empty($_REQUEST['stat']['item']),
			);

			$limit = 20;
			$offset = ($page - 1) * $limit;
			$pagenum = $db->result_first("SELECT COUNT(*) FROM {$tpre}order o WHERE $condition");
			if(!$stat['statonly']){
				$orders = $db->fetch_all("SELECT *,(SELECT COUNT(*) FROM {$tpre}order WHERE userid=o.userid AND dateline<o.dateline) AS ordernum
					FROM {$tpre}order o
					WHERE $condition ORDER BY o.dateline LIMIT $offset,$limit");
			}else{
				$orders = array();
			}

			$statdata = array();
			if($stat['totalprice']){
				$statdata['totalprice'] = $db->fetch_all("SELECT SUM(totalprice) AS price,priceunit FROM {$tpre}order o WHERE $condition GROUP BY priceunit");
				foreach($statdata['totalprice'] as &$total){
					$total['priceunit'] = Product::PriceUnits($total['priceunit']);
				}
				unset($total);
			}else{
				$statdata['totalprice'] = array();
			}

			if($stat['item']){
				$statdata['item'] = $db->fetch_all("SELECT d.productname,d.subtype,d.amountunit,SUM(d.amount*d.number) AS num,o.priceunit,SUM(d.subtotal) AS totalprice
					FROM {$tpre}orderdetail d
						LEFT JOIN {$tpre}order o ON d.orderid=o.id
					WHERE $condition
					GROUP BY d.productname,d.subtype,d.amountunit,o.priceunit");

				foreach($statdata['item'] as &$item){
					$item['priceunit'] = Product::PriceUnits($item['priceunit']);
				}
				unset($item);
			}else{
				$statdata['item'] = array();
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
		}else{
			$display_status = array_keys(Order::$Status);
			$time_start = rmktime(17, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));
			$time_end = $time_start + 24 * 3600;
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
				$count = 0;
				foreach($address_components as $i => $c){
					if($c['formatid'] == $format['id']){
						if(!in_array($c['id'], $reserved)){
							unset($address_components[$i]);
						}else{
							$count++;
						}
					}
				}
			}else{
				$count = 2;
			}

			if($count > 1){
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

		include view('order_'.$action);
	break;

	case 'mark_unsorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if($_G['admin']->isSuperAdmin() && $order->status != Order::Unsorted){
			$order->status = Order::Unsorted;
			$order->addLog($_G['admin'], Order::StatusChanged, Order::Unsorted);
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_sorted':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_sort_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->getLimitations())){
			exit('permission denied');
		}

		if($order->status == Order::Unsorted || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Sorted;
			$order->addLog($_G['admin'], Order::StatusChanged, Order::Sorted);
		}

		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	break;

	case 'mark_delivering':
		if(empty($_GET['orderid']) || !$_G['admin']->hasPermission('order_deliver_w')) exit('permission denied');
		$order = new Order($_GET['orderid']);

		if(!$order->belongToAddress($_G['admin']->getLimitations())){
			exit('permission denied');
		}

		if($order->status == Order::Sorted || $_G['admin']->isSuperAdmin()){
			$order->status = Order::Delivering;
			$order->addLog($_G['admin'], Order::StatusChanged, Order::Delivering);
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
			$order->addLog($_G['admin'], Order::StatusChanged, Order::Received);
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
			$order->addLog($_G['admin'], Order::StatusChanged, Order::Rejected);
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

			$ordernum = $order->getUserOrderNum();
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
				showmsg('confirm_to_delete_order', 'confirm');
			}

			Order::Delete($orderid);
			redirect($_COOKIE['http_referer']);
		}
	break;
}

?>
