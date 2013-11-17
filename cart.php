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

		if(!empty($_COOKIE['in_cart'])){
			$in_cart = explode(',', $_COOKIE['in_cart']);
			foreach($in_cart as $item){
				$item = explode('=', $item);
				$priceid = intval($item[0]);
				$cart[$priceid] = intval($item[1]);
				$priceids[] = $priceid;
			}
		}

		if(!$cart){
			showmsg('您还没有选购哦，请先把商品放入购物车。', 'market.php');
		}

		if($priceids){
			$priceids = implode(',', $priceids);
			$products = $db->fetch_all("SELECT p.*,r.*
				FROM {$tpre}productprice r
					LEFT JOIN {$tpre}product p ON p.id=r.productid
				WHERE r.id IN ($priceids)");
		}else{
			$products = array();
		}

		if($_POST){
			$addressid = !empty($_POST['deliveryaddressid']) ? intval($_POST['deliveryaddressid']) : 0;

			if($addressid <= 0){
				if(empty($_POST['addressee'])){
					showmsg('请填写收件人姓名。', 'back');
				}

				if(empty($_POST['mobile']) || !preg_match('/^\d{11}$/', $_POST['mobile'])){
					showmsg('请填写正确的手机号码。', 'back');
				}

				$address = array();
				if(!empty($_POST['deliveryaddress'])){
					$address = explode(',', $_POST['deliveryaddress']);
				}else{
					showmsg('请将收件地址填写完整。', 'back');
				}
			}else{
				$db->select_table('deliveryaddress');
				$address = $db->FETCH('*', 'id='.$addressid);
				if(!$address || $address['userid'] != $_G['user']->id){
					showmsg('非法操作。该收货地址不存在。');
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

			foreach(Address::Format() as $format){
				$componentid = intval(array_shift($address));
				if(!$componentid){
					showmsg('请填写完整的收件地址！', 'back');
				}
				
				$order->addAddressComponent(array(
					'formatid' => $format['id'],
					'componentid' => $componentid,
				));
			}
			if(!$address){
				showmsg('请填写完整的收件地址！', 'back');
			}

			$order->userid = $_G['user']->id;
			$order->message = !empty($_POST['message']) ? trim($_POST['message']) : '';

			$order->extaddress = array_shift($address);
			$order->addressee = $_POST['addressee'];
			$order->mobile = $_POST['mobile'];

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

			foreach($total_price as $unit => $total){
				$order->clearDetail();

				$order->totalprice = $total;
				$order->priceunit = $unit;

				foreach($products as &$p){
					if($p['priceunit'] == $unit){
						$order->addDetail(array(
							'productid' => $p['productid'],
							'subtype' => $p['subtype'],
							'amount' => $p['amount'],
							'amountunit' => $p['amountunit'],
							'number' => $p['number'],
							'subtotal' => $p['subtotal'],
						));

						$totalamount = $p['amount'] * $p['number'];
						$db->query("UPDATE LOW_PRIORITY {$tpre}product SET soldout=soldout+$totalamount WHERE id=$p[productid]");
					}
				}
				unset($p);

				$order->insert();
			}

			rsetcookie('in_cart', '');
			showmsg('成功提交订单！', 'market.php');

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
				WHERE a.addressid=$a[id]");
			while($c = $db->fetch_array($query)){
				$a['address_text'].= $c['name'].' ';
			}
			$a['address_text'].= $a['extaddress'].' '.$a['addressee'].'('.$a['mobile'].')';
		}
		unset($a);

		include view('cart');
	break;
}

?>
