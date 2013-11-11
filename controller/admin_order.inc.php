<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$db->select_table('order');
$orders = $db->MFETCH('*', 'userid='.$_G['user']->id.' AND status<2 ORDER BY dateline DESC');

$orderids = array();
foreach($orders as &$o){
	$orderids[] = $o['id'];
}
unset($o);
$orderids = implode(',', $orderids);

$details = $db->fetch_all("SELECT p.name,d.subtype,d.amount,d.amountunit,d.number,d.orderid
	FROM {$tpre}orderdetail d
		LEFT JOIN {$tpre}product p ON p.id=d.productid
	WHERE d.orderid IN ($orderids)");

$order_details = array();
foreach($details as &$d){
	$order_details[$d['orderid']][] = $d['name'].(!empty($d['subtype']) ? '('.$d['subtype'].')' : '').' '.($d['amount'] * $d['number']).$d['amountunit'];
}
unset($d);

$addresses = $db->fetch_all("SELECT o.*,c.name componentname
	FROM {$tpre}orderaddresscomponent o
		LEFT JOIN {$tpre}addresscomponent c ON c.id=o.componentid
	WHERE o.orderid IN ($orderids)");

$order_addresses = array();
foreach($addresses as &$a){
	$order_addresses[$a['orderid']][$a['formatid']] = $a['componentname'];
}
unset($a);

foreach($orders as &$o){
	$o['items'] = implode('<br />', $order_details[$o['id']]);
	$o['priceunit'] = Product::PriceUnits($o['priceunit']);
	$o['address'] = &$order_addresses[$o['id']];
}
unset($o);

include view('order');

?>
