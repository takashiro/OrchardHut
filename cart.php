<?php

require_once './core/init.inc.php';

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
			showmsg('成功提交订单！', 'back');

		}else{
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

			include view('cart');
		}
	break;
}

?>
