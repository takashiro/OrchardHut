<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

//Disallow guests opening the shopping cart
if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$tradestates = array(0, Order::WaitBuyerPay);
$tradestates = implode(',', $tradestates);
$unsorted_status = Order::Unsorted;
$paid_with_cash = Order::PaidWithCash;
$unpaid_num = $db->result_first("SELECT COUNT(*)
	FROM {$tpre}order
	WHERE userid={$_G['user']->id} AND tradestate IN ($tradestates) AND status=$unsorted_status AND paymentmethod!=$paid_with_cash");
if($unpaid_num > 0){
	showmsg('please_cancel_or_pay_your_previous_order', './?mod=order');
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
		if(!empty($_COOKIE['shopping_cart'])){
			$shopping_cart = json_decode($_COOKIE['shopping_cart'], true);
			foreach($shopping_cart as $priceid => $value){
				$priceid = intval($priceid);
				$value = intval($value);
				if($priceid > 0 && $value > 0){
					$cart[$priceid] = intval($value);
					$priceids[] = $priceid;
				}
			}
		}

		if($priceids){//Now the shopping cart is not empty. Let's calculate as a cashier.
			//Check if the current user group can buy it
			$price_limit = Product::PriceLimits($priceids);
			foreach($priceids as $i => $priceid){
				if(!empty($price_limit[$priceid]) && !in_array($_G['user']->groupid, $price_limit[$priceid])){
					unset($priceids[$i]);
				}
			}

			$check_booking_mode = ProductStorage::IsBookingMode() ? ' OR s.mode='.ProductStorage::BookingMode : '';

			if($priceids){
				$priceids = implode(',', $priceids);
				$products = $db->fetch_all("SELECT p.*,r.*,r.id AS priceid
					FROM {$tpre}productprice r
						LEFT JOIN {$tpre}product p ON p.id=r.productid
						LEFT JOIN {$tpre}productcountdown c ON c.id=r.id
						LEFT JOIN {$tpre}productstorage s ON s.id=r.storageid
					WHERE r.id IN ($priceids)
						AND p.hide=0
						AND (r.storageid IS NULL OR s.num>=r.amount $check_booking_mode)
						AND (c.id IS NULL OR (c.start_time<=$timestamp AND c.end_time>=$timestamp))
						AND r.id NOT IN (SELECT masked_priceid
										FROM {$tpre}productcountdown c
											LEFT JOIN {$tpre}productprice m ON m.id=c.id
										WHERE m.productid=r.productid AND c.start_time<=$timestamp AND c.end_time>=$timestamp)");
			}else{
				$products = array();
			}

			//Remove deleted product prices from the shopping cart and update cookie
			$filtered_cart = array();
			foreach($products as $p){
				$filtered_cart[$p['priceid']] = $cart[$p['priceid']];
			}

			//Check if some items are deleted
			foreach($cart as $priceid => $number){
				if(empty($filtered_cart[$priceid])){
					$item_deleted = true;
					break;
				}
			}

			$cart = $filtered_cart;
			rsetcookie('shopping_cart', json_encode($cart));
		}else{//The shopping cart is empty... Some items may be out of date. Just empty the $products.
			$products = array();
		}

		if(!$products){
			if($item_deleted){
				showmsg('shopping_cart_empty_because_of_item_deleted', './?mod=product');
			}else{
				showmsg('shopping_cart_empty', './?mod=product');
			}
		}

		$deliveryconfig = readdata('deliveryconfig');
		$paymentconfig = readdata('payment');

		if($_POST){
			if($_G['user']->hasTrickFlag(User::ORDER_IGNORING_TRICK)){
				rsetcookie('shopping_cart', '{}');
				writelog('trick', "{$_G['user']->id}\torder ignored");
				showmsg('successfully_submitted_order', './?mod=order');
			}

			//处理提交的订单开始
			if(empty($_POST['formkey']) || !$_G['user']->checkFormKey($_POST['formkey'])){
				showmsg('you_submitted_a_duplicated_order', './?mod=order');
			}

			$order = new Order;

			$deliveryaddressid = !empty($_POST['deliveryaddressid']) ? intval($_POST['deliveryaddressid']) : 0;
			//若选择了使用新的收货地址
			if($deliveryaddressid <= 0){
				if(empty($_POST['addressee'])){
					showmsg('please_fill_in_addressee', 'back');
				}

				if(empty($_POST['mobile']) || !preg_match('/^\d{11}$/', $_POST['mobile'])){
					showmsg('incorrect_mobile_number', 'back');
				}

				$order->addressee = htmlspecialchars(trim($_POST['addressee']));
				$order->mobile = $_POST['mobile'];

				if(!empty($_POST['deliveryaddress'])){
					$address = explode(':', $_POST['deliveryaddress']);
					$order->addressid = intval($address[0]);
					$order->extaddress = empty($address[1]) ? '' : $address[1];
				}else{
					showmsg('invalid_delivery_address_with_inadquate_components', 'back');
				}

			//若选择了原有的收货地址
			}else{
				$table = $db->select_table('deliveryaddress');
				$a = $table->fetch_first('*', 'id='.$deliveryaddressid);
				if(!$a || $a['userid'] != $_G['user']->id || empty(Address::FindComponentById($a['addressid']))){
					showmsg('delivery_address_id_not_exist');
				}

				$order->addressee = $a['addressee'];
				$order->mobile = $a['mobile'];

				$order->addressid = intval($a['addressid']);
				$order->extaddress = $a['extaddress'];
				unset($a);
			}

			//判断收货地址是否合法（尽量详细）
			$address_component = Address::FindComponentById($order->addressid);
			if(empty($address_component)){
				showmsg('invalid_delivery_address_with_inadquate_components', 'back');
			}
			$children = $db->fetch_first("SELECT id FROM {$tpre}addresscomponent WHERE parentid={$order->addressid} LIMIT 1");
			if($children){
				showmsg('invalid_delivery_address_with_inadquate_components', 'back');
			}

			//若地址为新地址，自动记录之
			if($deliveryaddressid <= 0){
				$delivery_address = array(
					'userid' => $_G['user']->id,
					'addressid' => $order->addressid,
					'extaddress' => $order->extaddress,
					'addressee' => $order->addressee,
					'mobile' => $order->mobile,
				);
				$table = $db->select_table('deliveryaddress');
				$table->insert($delivery_address);
			}

			//根据购物车中的信息计算每项小计价格
			foreach($products as &$p){
				$p['number'] = $cart[$p['id']];
				$p['price'] = floatval($p['price']);
				$p['subtotal'] = $p['price'] * $p['number'];
			}
			unset($p);

			//录入用户ID、留言
			$order->userid = $_G['user']->id;
			$order->message = !empty($_POST['message']) ? trim($_POST['message']) : '';

			//判断订单的支付方式是否合法
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

			//增加产品对应的销量，该销量仅供参考
			foreach($products as &$p){
				$succeeded = $order->addDetail($p);
				$succeeded || $item_deleted = true;

				$totalamount = $p['amount'] * $p['number'];
				$db->query("UPDATE LOW_PRIORITY {$tpre}product SET soldout=soldout+$totalamount WHERE id={$p['productid']}");
			}
			unset($p);

			//判断订单的配送方式是否合法
			$order->deliverymethod = isset($_POST['deliverymethod']) ? intval($_POST['deliverymethod']) : Order::HomeDelivery;
			isset(Order::$DeliveryMethod[$order->deliverymethod]) || $order->deliverymethod = Order::HomeDelivery;
			//收取配送费用
			$df = isset($deliveryconfig[$order->deliverymethod]) ? $deliveryconfig[$order->deliverymethod] : null;
			if($df && isset($df['fee']) && $df['fee'] > 0 && isset($df['maxorderprice']) && $order->totalprice < $df['maxorderprice']){
				$order->deliveryfee = $df['fee'];
				$order->totalprice += $order->deliveryfee;
			}else{
				$order->deliveryfee = 0;
			}

			//将订单插入到数据库中
			$order->tradestate = 0;
			$order->tradetime = 0;
			$order_succeeded = $order->insert();

			//更新用户信息
			$_G['user']->addressid = $order->addressid;

			//清空购物车
			rsetcookie('shopping_cart', '{}');

			//显示订单提交结果
			if($order_succeeded){
				//若为钱包支付，直接扣款
				if($order->paymentmethod == Order::PaidWithWallet){
					$wallet = new Wallet($_G['user']);
					$wallet->pay($order);

				//若使用线上支付，进入支付宝界面
				}elseif($order->paymentmethod != Order::PaidWithCash){
					if(!empty(Order::$PaymentInterface[$order->paymentmethod])){
						redirect(Order::$PaymentInterface[$order->paymentmethod].'?orderid='.$order->id.'&'.User::COOKIE_VAR.'='.urlencode($_COOKIE[User::COOKIE_VAR]));
					}
				}

				//货到付款的时间默认为下单时间，方便后台统一处理
				if($order->paymentmethod == Order::PaidWithCash)
					$order->tradetime = TIMESTAMP;

				if(!$item_deleted){
					showmsg('successfully_submitted_order', './?mod=order');
				}else{
					showmsg('successfully_submitted_order_with_item_deleted', './?mod=order');
				}
			}else{
				showmsg('failed_to_submit_order', './?mod=product');
			}
		}


		//取得购物车中的产品的信息
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

		//补充完整购物车中的产品的价格
		Product::FetchFilteredPrices($products);
		$priceids = array();
		$storageids = array();
		foreach($products as $product){
			foreach($product['rule'] as $price){
				$priceids[] = $price['id'];
				if($price['storageid']){
					$storageids[] = $price['storageid'];
				}
			}
		}

		//取得产品限购数据
		$quantity_limit = Product::QuantityLimits($priceids);

		//取得产品库存信息
		$product_storages = Product::Storages($storageids);

		//取得用户的所有收货地址
		$table = $db->select_table('deliveryaddress');
		$delivery_addresses = $table->fetch_all('*', 'userid='.$_G['user']->id);

		foreach($delivery_addresses as $aid => &$a){
			if(empty(Address::FindComponentById($a['addressid']))){
				unset($delivery_addresses[$aid]);
			}else{
				$a['address_text'] = Address::FullPathString($a['addressid']);
				$a['address_text'].= ' '.$a['extaddress'].' '.$a['addressee'].'('.$a['mobile'].')';
			}
		}
		unset($a);

		//显示可选的收货时间
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

		//生成随机表单key防止重复提交
		$_G['user']->refreshFormKey();

		include view('cart');
	break;

	case 'deleteaddress':
		$address_id = !empty($_POST['address_id']) ? intval($_POST['address_id']) : 0;
		if($address_id > 0){
			$db->query("DELETE FROM {$tpre}deliveryaddress WHERE id=$address_id AND userid={$_USER['id']}");
			echo $db->affected_rows;
		}else{
			echo 0;
		}
	break;
}

?>
