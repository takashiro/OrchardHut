<?php

require_once './core/init.inc.php';

if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$actions = array('order', 'deleteaddress');
$action = isset($_POST['action']) && in_array($_POST['action'], $actions) ? $_POST['action'] : $actions[0];

switch($action){
	case 'order':
		$cart = $priceids = array();
		$total_price = array();
		$item_deleted = false;

		if(!empty($_COOKIE['in_cart'])){
			$in_cart = explode(',', $_COOKIE['in_cart']);
			foreach($in_cart as $item){
				$item = explode('=', $item);
				$priceid = intval($item[0]);
				$cart[$priceid] = intval($item[1]);
				$priceids[] = $priceid;
			}
		}

		if($priceids){
			$priceids = implode(',', $priceids);
			$products = $db->fetch_all("SELECT p.*,r.*,r.id AS priceid
				FROM {$tpre}productprice r
					LEFT JOIN {$tpre}product p ON p.id=r.productid
					LEFT JOIN {$tpre}productcountdown c ON c.id=r.id
					LEFT JOIN {$tpre}productstorage s ON s.id=r.storageid
				WHERE r.id IN ($priceids)
					AND p.hide=0
					AND (r.storageid IS NULL OR s.num>=r.amount)
					AND (c.id IS NULL OR (c.start_time<=$timestamp AND c.end_time>=$timestamp))
					AND r.id NOT IN (SELECT masked_priceid
									FROM {$tpre}productcountdown c
										LEFT JOIN {$tpre}productprice m ON m.id=c.id
									WHERE m.productid=r.productid AND c.start_time<=$timestamp AND c.end_time>=$timestamp)");

			//Remove deleted product prices from the shopping cart and update cookie
			$filtered_cart = array();
			$in_cart = array();
			foreach($products as $p){
				$filtered_cart[$p['priceid']] = $cart[$p['priceid']];
				$in_cart[] = $p['priceid'].'='.$cart[$p['priceid']];
			}

			//Check if some items are deleted
			foreach($cart as $priceid => $number){
				if(!isset($filtered_cart[$priceid]) || $filtered_cart[$priceid] != $number){
					$item_deleted = true;
					break;
				}
			}

			$cart = &$filtered_cart;
			$in_cart = implode(',', $in_cart);
			rsetcookie('in_cart', $in_cart);
		}else{
			$products = array();
		}

		if(!$products){
			if($item_deleted){
				showmsg('shopping_cart_empty_because_of_item_deleted', 'market.php');
			}else{
				showmsg('shopping_cart_empty', 'market.php');
			}
		}

		if($_POST){
			$addressid = !empty($_POST['deliveryaddressid']) ? intval($_POST['deliveryaddressid']) : 0;

			if($addressid <= 0){
				if(empty($_POST['addressee'])){
					showmsg('please_fill_in_addressee', 'back');
				}

				if(empty($_POST['mobile']) || !preg_match('/^\d{11}$/', $_POST['mobile'])){
					showmsg('incorrect_mobile_number', 'back');
				}

				$address = array();
				if(!empty($_POST['deliveryaddress'])){
					$address = explode(',', $_POST['deliveryaddress']);
				}else{
					showmsg('invalid_delivery_address_with_inadquate_components', 'back');
				}
			}else{
				$db->select_table('deliveryaddress');
				$address = $db->FETCH('*', 'id='.$addressid);
				if(!$address || $address['userid'] != $_G['user']->id){
					showmsg('delivery_address_id_not_exist');
				}

				$_POST['addressee'] = $address['addressee'];
				$_POST['mobile'] = $address['mobile'];

				$extaddress = $address['extaddress'];
				$address = array();

				$db->select_table('deliveryaddresscomponent');
				$components = $db->MFETCH('componentid', 'addressid='.$addressid);
				foreach($components as $c){
					$address[] = $c['componentid'];
				}
				$address[] = $extaddress;
			}

			$order = new Order;

			foreach($products as &$p){
				$p['number'] = $cart[$p['id']];
				$p['subtotal'] = $p['price'] * $p['number'];

				if(array_key_exists($p['priceunit'], $total_price)){
					$total_price[$p['priceunit']] += $p['subtotal'];
				}else{
					$total_price[$p['priceunit']] = $p['subtotal'];
				}
			}
			unset($p);

			$order->message = isset($_POST['message']) ? trim($_POST['message']) : '';

			$address_component = array();
			$length = count($address);
			for($i = 1; $i < $length; $i++){
				$address_component[] = intval(array_shift($address));
			}

			$db->select_table('addresscomponent');
			$address_component = $db->MFETCH('formatid,id', 'id IN ('.implode(',', $address_component).')');
			
			//Validate Address Components
			$format2component = array();
			foreach($address_component as $c){
				$format2component[$c['formatid']] = $c['id'];
			}
			foreach(Address::Format() as $format){
				if(!array_key_exists($format['id'], $format2component)){
					showmsg('invalid_delivery_address_with_inadquate_components', 'back');
				}
			}

			foreach($address_component as $component){
				$order->addAddressComponent(array(
					'formatid' => $component['formatid'],
					'componentid' => $component['id'],
				));
			}

			$order->userid = $_G['user']->id;
			$order->message = !empty($_POST['message']) ? trim($_POST['message']) : '';

			$order->extaddress = array_shift($address);
			$order->addressee = $_POST['addressee'];
			$order->mobile = $_POST['mobile'];
			@$order->deliverytime = intval($_POST['deliverytime']);

			if($addressid <= 0){
				$delivery_address = array(
					'userid' => $_G['user']->id,
					'extaddress' => $order->extaddress,
					'addressee' => $order->addressee,
					'mobile' => $order->mobile,
				);
				$db->select_table('deliveryaddress');
				$db->INSERT($delivery_address);
				$addressid = $db->insert_id();

				$delivery_address_components = $order->getAddressComponents();
				foreach($delivery_address_components as &$c){
					unset($c['orderid']);
					$c['addressid'] = $addressid;
				}
				unset($c);
				$db->select_table('deliveryaddresscomponent');
				$db->INSERTS($delivery_address_components);
			}

			$order_succeeded = false;
			foreach($total_price as $unit => $total){
				$order->clearDetail();

				$order->priceunit = $unit;

				foreach($products as &$p){
					if($p['priceunit'] == $unit){
						$succeeded = $order->addDetail($p);
						$succeeded || $item_deleted = true;

						$totalamount = $p['amount'] * $p['number'];
						$db->query("UPDATE LOW_PRIORITY {$tpre}product SET soldout=soldout+$totalamount WHERE id=$p[productid]");
					}
				}
				unset($p);

				$succeeded = $order->insert();
				$succeeded && $order_succeeded = true;
			}

			rsetcookie('in_cart', '');

			if($order_succeeded){
				if(!$item_deleted){
					showmsg('successfully_submitted_order', 'home.php');
				}else{
					showmsg('successfully_submitted_order_with_item_deleted', 'home.php');
				}
			}else{
				showmsg('failed_to_submit_order', 'market.php');
			}
		}

		$product = new Product;
		foreach($products as &$p){
			$number = $cart[$p['id']];
			foreach($p as $attr => $value){
				$product->$attr = $value;
			}
			$product->id = $p['productid'];
			
			$p = $product->toArray();
			$p['icon'] = $product->getImage('icon');
			$p['photo'] = $product->getImage('photo');
			$p['number'] = $number;
			$p['subtotal'] = $p['price'] * $p['number'];

			if(array_key_exists($p['priceunit'], $total_price)){
				$total_price[$p['priceunit']] += $p['subtotal'];
			}else{
				$total_price[$p['priceunit']] = $p['subtotal'];
			}

			$p['priceunit'] = Product::PriceUnits($p['priceunit']);
			$p['amountunit'] = Product::AmountUnits($p['amountunit']);
		}
		unset($p);

		$db->select_table('deliveryaddress');
		$delivery_addresses = $db->MFETCH('*', 'userid='.$_G['user']->id);
		
		foreach($delivery_addresses as &$a){
			$a['address_text'] = '';
			$query = $db->query("SELECT c.name
				FROM {$tpre}deliveryaddresscomponent a
					LEFT JOIN {$tpre}addresscomponent c ON c.id=a.componentid
					LEFT JOIN {$tpre}addressformat f ON f.id=a.formatid
				WHERE a.addressid=$a[id]
				ORDER BY f.displayorder");
			while($c = $db->fetch_array($query)){
				$a['address_text'].= $c['name'].' ';
			}
			$a['address_text'].= $a['extaddress'].' '.$a['addressee'].'('.$a['mobile'].')';
		}
		unset($a);

		list($Y, $m, $d) = explode('-', rdate(TIMESTAMP, 'Y-m-d'));
		$today = gmmktime(0, 0, 0, $m, $d, $Y) - TIMEZONE * 3600;
		$db->select_table('deliverytime');
		$delivery_timespans = $db->MFETCH('*', 'hidden=0');
		foreach($delivery_timespans as &$s){
			$s['time_deadline'] += $today;
			$s['time_from'] += $today;
			$s['time_to'] += $today;
			while($s['time_deadline'] <= TIMESTAMP){
				$s['time_deadline'] += 24 * 3600;
				$s['time_from'] += 24 * 3600;
				$s['time_to'] += 24 * 3600;
			}
		}
		unset($s);

		function __sort_by_time($s1, $s2){
			return $s1['time_from'] > $s2['time_from'];
		}
		usort($delivery_timespans, '__sort_by_time');

		include view('cart');
	break;

	case 'deleteaddress':
		$address_id = !empty($_POST['address_id']) ? intval($_POST['address_id']) : 0;
		$affected_rows = 0;
		if($address_id > 0){
			$db->query("DELETE FROM {$tpre}deliveryaddress WHERE id=$address_id AND userid=$_USER[id]");
			$affected_rows = $db->affected_rows();
			if($affected_rows > 0){
				$db->query("DELETE FROM {$tpre}deliveryaddresscomponent WHERE addressid=$address_id");
				$affected_rows += $db->affected_rows();
			}
		}
		echo $affected_rows;
	break;
}

?>
