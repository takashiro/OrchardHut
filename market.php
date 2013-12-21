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

include view('market');

?>
