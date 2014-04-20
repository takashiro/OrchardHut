<?php

require_once './core/init.inc.php';

$type = isset($_GET['type']) ? intval($_GET['type']) : 0;

$db->select_table('product');
$products = $db->MFETCH('*', 'type='.$type.' AND hide=0 ORDER BY displayorder');

$product = new Product;
foreach($products as &$p){
	foreach($p as $attr => $value){
		$product->$attr = $value;
	}
	$p = $product->toArray();
	$p['introduction'] = str_replace(array("\r\n", "\n", "\r"), '<br />', $p['introduction']);
}
unset($p);

Product::FetchFilteredPrices($products);

$announcements = Announcement::GetActiveAnnouncements();

$priceids = array();
foreach($products as $product){
	foreach($product['rule'] as $price){
		$priceids[] = $price['id'];
	}
}
$quantity_limit = array();
if($_G['user']->isLoggedIn()){
	$query = $db->query("SELECT priceid,amount FROM {$tpre}productquantitylimit WHERE userid=$_USER[id]");
	while($l = $db->fetch_array($query)){
		$quantity_limit[intval($l['priceid'])] = intval($l['amount']);
	}
}

include view('market');

?>
