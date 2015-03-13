<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$template_formats = array('html', 'csv');
$format = &$_GET['format'];
in_array($format, $template_formats) || $format = $template_formats[0];

$condition = array();

//起始时间
if(isset($_REQUEST['time_start'])){
	$time_start = empty($_REQUEST['time_start']) ? '' : rstrtotime($_REQUEST['time_start']);
}else{
	$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), 1, rdate(TIMESTAMP, 'Y'));
}
if($time_start){
	$condition[] = 'al.dateline>='.$time_start;
}

//截止时间
if(isset($_REQUEST['time_end'])){
	$time_end = empty($_REQUEST['time_end']) ? '' : rstrtotime($_REQUEST['time_end']);
}else{
	$time_end = rmktime(23, 59, 59, rdate($time_start, 'm') + 1, rdate($time_start, 'd') - 1, rdate($time_start, 'Y'));
}
if($time_end){
	$condition[] = 'al.dateline<='.$time_end;
}

//账号
$bankaccountid = isset($_REQUEST['bankaccountid']) ? intval($_REQUEST['bankaccountid']) : 0;
if($bankaccountid){
	$condition[] = 'al.accountid='.$bankaccountid;
}

//产品种类
$producttype = isset($_REQUEST['producttype']) ? intval($_REQUEST['producttype']) : 0;
if($producttype){
	$condition[] = 'p.type='.$producttype;
}


//产品
$productid = isset($_REQUEST['productid']) ? intval($_REQUEST['productid']) : 0;
if($productid){
	$condition[] = 'p.id='.$productid;
}


//查询符合条件的记录
$condition = $condition ? implode(' AND ', $condition) : '1';

$stat = array(
	'out' => 0,
	'in' => 0,
	'all' => 0,
);

$logs = array();

$fields = 'a.remark AS bankaccount, p.name AS productname';

$query = $db->query("SELECT $fields, l.dateline, l.totalcosts AS delta
	FROM {$tpre}productstoragelog l
		LEFT JOIN {$tpre}bankaccountlog al ON al.id=l.bankaccountlogid
		LEFT JOIN {$tpre}bankaccount a ON a.id=al.accountid
		LEFT JOIN {$tpre}productstorage s ON s.id=l.storageid
		LEFT JOIN {$tpre}product p ON p.id=s.productid
	WHERE $condition");

while($l = $db->fetch_array($query)){
	$stat['out'] += $l['delta'];

	$l['reason'] = lang('common', 'storage_import');
	$l['delta'] = -$l['delta'];
	$logs[] = $l;
}

$query = $db->query("SELECT $fields, o.dateline, d.subtotal AS delta
	FROM {$tpre}orderdetail d
		LEFT JOIN {$tpre}product p ON p.id=d.productid
		LEFT JOIN {$tpre}order o ON o.id=d.orderid
		LEFT JOIN {$tpre}bankaccountlog al ON al.targetid=o.id
		LEFT JOIN {$tpre}bankaccount a ON a.id=al.accountid
	WHERE $condition AND d.state=0 AND o.status=".Order::Received);

while($l = $db->fetch_array($query)){
	$stat['in'] += $l['delta'];

	$l['reason'] = lang('common', 'order').lang('common', 'order_received');
	$logs[] = $l;
}

$stat['all'] = $stat['in'] - $stat['out'];

usort($logs, function($l1, $l2){
	return $l1['dateline'] > $l2['dateline'];
});

//显示界面
$time_start = rdate($time_start);
$time_end = rdate($time_end);

$bankaccounts = array(0 => '不限');
$db->select_table('bankaccount');
$query = $db->SELECT('id,remark');
while($a = $db->fetch_array($query)){
	$bankaccounts[$a['id']] = $a['remark'];
}

$producttypes = array(0 => '不限');
$producttypes = array_merge($producttypes, Product::Types());

$productids = array();
$db->select_table('product');
$query = $db->SELECT('id,name,type');
while($p = $db->fetch_array($query)){
	$productids[$p['type']][$p['id']] = $p['name'];
}

include view('balancereport_html');

?>
