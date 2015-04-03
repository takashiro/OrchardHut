<?php

require_once './core/init.inc.php';

//Disallow guests opening the shopping cart
if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$actions = array('order', 'deleteaddress');
$action = isset($_POST['action']) && in_array($_POST['action'], $actions) ? $_POST['action'] : $actions[0];

switch($action){
	//Now the user is either listing everything in the shopping cart or submitting a new order.
	case 'order':
		$total_price = 0.00;
		$item_deleted = false;//It's a flag indicates some items were deleted out of date.

		//$cart is an array of items, with the key standing for its price id and the value for the number.
		//$priceids is array_keys($cart)
		$cart = $priceids = array();
		if(!empty($_COOKIE['in_cart'])){
			$in_cart = explode(',', $_COOKIE['in_cart']);
			foreach($in_cart as $item){
				$item = explode('=', $item);
				$priceid = intval($item[0]);
				$cart[$priceid] = intval($item[1]);
				$priceids[] = $priceid;
			}
		}

		if($priceids){//Now the shopping cart is not empty. Let's calculate as a cashier.
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
		}else{//The shopping cart is empty... Some items may be out of date. Just empty the $products.
			$products = array();
		}

		if(!$products){
			if($item_deleted){
				showmsg('shopping_cart_empty_because_of_item_deleted', 'market.php');
			}else{
				showmsg('shopping_cart_empty', 'market.php');
			}
		}

		$deliveryconfig = readdata('deliveryconfig');
		$paymentconfig = readdata('payment');

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
				$table = $db->select_table('deliveryaddress');
				$address = $table->fetch_first('*', 'id='.$addressid);
				if(!$address || $address['userid'] != $_G['user']->id){
					showmsg('delivery_address_id_not_exist');
				}

				$_POST['addressee'] = $address['addressee'];
				$_POST['mobile'] = $address['mobile'];

				$extaddress = $address['extaddress'];
				$address = array();

				$table = $db->select_table('deliveryaddresscomponent');
				$components = $table->fetch_all('componentid', 'addressid='.$addressid);
				foreach($components as $c){
					$address[] = $c['componentid'];
				}
				$address[] = $extaddress;
			}

			$order = new Order;

			foreach($products as &$p){
				$p['number'] = $cart[$p['id']];
				$p['price'] = floatval($p['price']);
				$p['subtotal'] = $p['price'] * $p['number'];
			}
			unset($p);

			$order->message = isset($_POST['message']) ? trim($_POST['message']) : '';

			$address_component = array();
			$length = count($address);
			for($i = 1; $i < $length; $i++){
				$address_component[] = intval(array_shift($address));
			}

			$table = $db->select_table('addresscomponent');
			$address_component = $table->fetch_all('formatid,id', 'id IN ('.implode(',', $address_component).')');

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

			if(!empty($_POST['deliverytime'])){
				$dtid = intval($_POST['deliverytime']);
				$table = $db->select_table('deliverytime');
				$delivery = $table->fetch_first('*', 'id='.$dtid);

				list($Y, $m, $d, $H, $i, $s) = explode('-', rdate(TIMESTAMP, 'Y-m-d-H-i-s'));
				$today = gmmktime(0, 0, 0, $m, $d, $Y) - TIMEZONE * 3600;
				$splitter = $H * 3600 + $i * 60 + $s;
				if($delivery['deadline'] <= $splitter){
					$delivery['time_from'] += 24 * 3600;
					$delivery['time_to'] += 24 * 3600;
				}
				$delivery['time_from'] += $today;
				$delivery['time_to'] += $today;

				$order->dtime_from = $delivery['time_from'];
				$order->dtime_to = $delivery['time_to'];
			}

			if($addressid <= 0){
				$delivery_address = array(
					'userid' => $_G['user']->id,
					'extaddress' => $order->extaddress,
					'addressee' => $order->addressee,
					'mobile' => $order->mobile,
				);
				$table = $db->select_table('deliveryaddress');
				$table->insert($delivery_address);
				$addressid = $table->insert_id();

				$delivery_address_components = $order->getAddressComponents();
				foreach($delivery_address_components as &$c){
					unset($c['orderid']);
					$c['addressid'] = $addressid;
				}
				unset($c);
				$table = $db->select_table('deliveryaddresscomponent');
				$table->multi_insert($delivery_address_components);
			}

			$order->paymentmethod = isset($_POST['paymentmethod']) ? intval($_POST['paymentmethod']) : Order::PaidWithCash;
			isset(Order::$PaymentMethod[$order->paymentmethod]) || $order->paymentmethod = Order::PaidWithCash;
			if(empty($paymentconfig['enabled_method'][$order->paymentmethod])){
				foreach($paymentconfig['enabled_method'] as $methodid => $enabled){
					if($enabled){
						$order->paymentmethod = $methodid;
						break;
					}
				}
			}

			$order_succeeded = false;

			foreach($products as &$p){
				$succeeded = $order->addDetail($p);
				$succeeded || $item_deleted = true;

				$totalamount = $p['amount'] * $p['number'];
				$db->query("UPDATE LOW_PRIORITY {$tpre}product SET soldout=soldout+$totalamount WHERE id=$p[productid]");
			}
			unset($p);

			$order->deliverymethod = isset($_POST['deliverymethod']) ? intval($_POST['deliverymethod']) : Order::HomeDelivery;
			isset(Order::$DeliveryMethod[$order->deliverymethod]) || $order->deliverymethod = Order::HomeDelivery;

			$order->deliveryfee = $deliveryconfig['fee'][$order->deliverymethod];
			$order->totalprice += $order->deliveryfee;

			if($order->paymentmethod == Order::PaidWithWallet){
				$db->query("UPDATE {$tpre}user SET wallet=wallet-{$order->totalprice} WHERE id=$_USER[id] AND wallet>={$order->totalprice}");
				if($db->affected_rows <= 0){
					$order->paymentmethod = Order::PaidWithCash;
				}else{
					$order->alipaystate = AlipayNotify::TradeSuccess;
				}
			}

			$succeeded = $order->insert();
			$succeeded && $order_succeeded = true;

			rsetcookie('in_cart', '');

			if($order_succeeded){
				if($order->paymentmethod == Order::PaidOnline){
					redirect('alipay.php?orderid='.$order->id);
				}

				if(!$item_deleted){
					showmsg('successfully_submitted_order', 'order.php');
				}else{
					showmsg('successfully_submitted_order_with_item_deleted', 'order.php');
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
			$p['price'] = floatval($p['price']);
			$p['subtotal'] = $p['price'] * $p['number'];

			$total_price += $p['subtotal'];

			$p['amountunit'] = Product::AmountUnits($p['amountunit']);
		}
		unset($p);

		Product::FetchFilteredPrices($products);
		$priceids = array();
		foreach($products as $product){
			foreach($product['rule'] as $price){
				$priceids[] = $price['id'];
			}
		}
		$quantity_limit = array();
		if($_G['user']->isLoggedIn()){
			$query = $db->query("SELECT priceid,amount FROM {$tpre}productquantitylimit WHERE userid=$_USER[id]");
			while($l = $query->fetch_assoc()){
				$quantity_limit[intval($l['priceid'])] = intval($l['amount']);
			}
		}

		$table = $db->select_table('deliveryaddress');
		$delivery_addresses = $table->fetch_all('*', 'userid='.$_G['user']->id);

		foreach($delivery_addresses as &$a){
			$a['address_text'] = '';
			$query = $db->query("SELECT c.name
				FROM {$tpre}deliveryaddresscomponent a
					LEFT JOIN {$tpre}addresscomponent c ON c.id=a.componentid
					LEFT JOIN {$tpre}addressformat f ON f.id=a.formatid
				WHERE a.addressid=$a[id]
				ORDER BY f.displayorder");
			while($c = $query->fetch_assoc()){
				$a['address_text'].= $c['name'].' ';
			}
			$a['address_text'].= $a['extaddress'].' '.$a['addressee'].'('.$a['mobile'].')';
		}
		unset($a);

		list($Y, $m, $d, $H, $i, $s) = explode('-', rdate(TIMESTAMP, 'Y-m-d-H-i-s'));
		$today = gmmktime(0, 0, 0, $m, $d, $Y) - TIMEZONE * 3600;
		$splitter = $H * 3600 + $i * 60 + $s;
		$delivery_timespans = DeliveryTime::FetchAllEffective();
		foreach($delivery_timespans as &$s){
			if($s['deadline'] <= $splitter){
				$s['time_from'] += 24 * 3600;
				$s['time_to'] += 24 * 3600;
			}
			$s['time_from'] += $today;
			$s['time_to'] += $today;
		}
		unset($s);

		DeliveryTime::SortByTimeFrom($delivery_timespans);

		include view('cart');
	break;

	case 'deleteaddress':
		$address_id = !empty($_POST['address_id']) ? intval($_POST['address_id']) : 0;
		$affected_rows = 0;
		if($address_id > 0){
			$db->query("DELETE FROM {$tpre}deliveryaddress WHERE id=$address_id AND userid=$_USER[id]");
			$affected_rows = $db->affected_rows;
			if($affected_rows > 0){
				$db->query("DELETE FROM {$tpre}deliveryaddresscomponent WHERE addressid=$address_id");
				$affected_rows += $db->affected_rows;
			}
		}
		echo $affected_rows;
	break;
}

?>
