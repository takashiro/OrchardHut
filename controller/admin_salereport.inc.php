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

//根据送货地址统计报表
$order_address = array();

$delivery_address = array();
if(!empty($_REQUEST['delivery_address'])){
	$delivery_address = &$_REQUEST['delivery_address'];
	$componentid = NULL;
	$delivery_address = explode(',', $delivery_address);
	foreach($delivery_address as $format_order => $id){
		$id = intval($id);
		if($id <= 0){
			$format_order--;
			break;
		}

		$componentid = $id;
	}

	if($format_order >= 0 && $componentid !== NULL){
		$order_address[] = $componentid;
	}
}

if($order_address){
	$order_address = array_unique($order_address);
	$condition[] = 'o.id IN (SELECT orderid FROM '.$tpre.'orderaddresscomponent WHERE componentid IN ('.implode(',', $order_address).'))';
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


$address_format = Address::Format();
$address_components = Address::Components();
foreach($address_format as $f){
	array_unshift($address_components, array('id' => 0, 'formatid' => $f['id'], 'name' => '不限', 'parentid' => 0));
}

include view('salereport_'.$format);

?>
