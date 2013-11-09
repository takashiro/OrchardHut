<?php

require_once './core/init.inc.php';

$db->select_table('product');
$products = $db->MFETCH('*');

$product = new Product;
foreach($products as &$p){
	foreach($p as $attr => $value){
		$product->$attr = $value;
	}
	$p = $product->toArray();
	$p['rule'] = $product->getPrices(true);
}
unset($p);

include view('market');

?>
