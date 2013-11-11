<?php

require_once './core/init.inc.php';

if(!$_G['user']->isLoggedIn()){
	redirect('memcp.php');
}

$db->select_table('order');
$orders = $db->MFETCH('*', 'userid='.$_G['user']->id.' AND status<2 ORDER BY dateline DESC');

$orderids = array();
foreach($orders as &$o){
	$orderids[] = $o['id'];
}
unset($o);

$details = $db->fetch_all("SELECT p.name,d.subtype,d.amount,d.amountunit,d.number,d.orderid
	FROM {$tpre}orderdetail d
		LEFT JOIN {$tpre}product p ON p.id=d.productid
	WHERE d.orderid IN (".implode(',', $orderids).')');

$order_details = array();
foreach($details as &$d){
	$order_details[$d['orderid']][] = $d['name'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
}
unset($d);

foreach($orders as &$o){
	$o['items'] = implode('<br />', $order_details[$o['id']]);
	$o['priceunit'] = Product::PriceUnits($o['priceunit']);
}
unset($o);

include view('home');

?>
