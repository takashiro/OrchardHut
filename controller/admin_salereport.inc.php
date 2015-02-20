<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$template_formats = array('html', 'csv');
$format = &$_GET['format'];
in_array($format, $template_formats) || $format = $template_formats[0];

$items = $db->fetch_all("SELECT productid,productname,amountunit,SUM(`amount`*`number`) AS amount,subtype,SUM(subtotal) AS totalprice
	FROM {$tpre}orderdetail
	WHERE state=0
	GROUP BY productid");

$statistic = array(
	'totalprice' => 0,
);
foreach($items as $item){
	$statistic['totalprice'] += $item['totalprice'];
}

include view('salereport_'.$format);

?>
