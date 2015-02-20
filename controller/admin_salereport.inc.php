<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$template_formats = array('html', 'csv');
$format = &$_GET['format'];
in_array($format, $template_formats) || $format = $template_formats[0];

$condition = array('d.state=0');

//下单起始时间
if(isset($_REQUEST['time_start'])){
	$time_start = empty($_REQUEST['time_start']) ? '' : rstrtotime($_REQUEST['time_start']);
}else{
	$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), 1, rdate(TIMESTAMP, 'Y'));
}
if($time_start){
	$condition[] = 'o.dateline>='.$time_start;
}

//下单截止时间
if(isset($_REQUEST['time_end'])){
	$time_end = empty($_REQUEST['time_end']) ? '' : rstrtotime($_REQUEST['time_end']);
}else{
	$time_end = rmktime(23, 59, 59, rdate($time_start, 'm') + 1, rdate($time_start, 'd') - 1, rdate($time_start, 'Y'));
}
if($time_end){
	$condition[] = 'o.dateline<='.$time_end;
}

$condition = implode(' AND ', $condition);

$items = $db->fetch_all("SELECT d.productid,d.productname,d.amountunit,SUM(d.amount*d.number) AS amount,d.subtype,SUM(d.subtotal) AS totalprice
	FROM {$tpre}orderdetail d
		LEFT JOIN {$tpre}order o ON o.id=d.orderid
	WHERE $condition
	GROUP BY productid");

//总计
$statistic = array(
	'totalprice' => 0,
);
foreach($items as $item){
	$statistic['totalprice'] += $item['totalprice'];
}

$time_start = rdate($time_start);
$time_end = rdate($time_end);
include view('salereport_'.$format);

?>
