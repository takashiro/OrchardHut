<?php

require_once './core/init.inc.php';

$type = isset($_GET['type']) ? intval($_GET['type']) : 0;

$db->select_table('product');
$products = $db->MFETCH('*', 'type='.$type);

$product = new Product;
foreach($products as &$p){
	foreach($p as $attr => $value){
		$product->$attr = $value;
	}
	$p = $product->toArray();
	$p['rule'] = $product->getPrices(true);
}
unset($p);

$announcements = Announcement::GetActiveAnnouncements();

include view('market');

?>
