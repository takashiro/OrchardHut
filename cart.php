<?php

require_once './core/init.inc.php';

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

if($priceids){
	$priceids = implode(',', $priceids);
	$products = $db->fetch_all("SELECT p.*,r.*
		FROM {$tpre}productprice r
			LEFT JOIN {$tpre}product p ON p.id=r.productid
		WHERE r.id IN ($priceids)");
}else{
	$products = array();
}

$product = new Product;
foreach($products as &$p){
	$product->id = $p['productid'];
	$product->icon = $p['icon'];
	$product->photo = $p['photo'];
	$p['icon'] = $product->getImage('icon');
	$p['photo'] = $product->getImage('photo');
	$p['number'] = $cart[$p['id']];
	$p['priceunit'] = Product::PriceUnits($p['priceunit']);
	$p['amountunit'] = Product::AmountUnits($p['amountunit']);
	$p['subtotal'] = $p['price'] * $p['number'];
}
unset($p);

include view('cart');

?>
