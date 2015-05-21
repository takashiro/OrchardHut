<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$condition = array();		//SQL
$query_string = array();	//分页

function parse_time_range($var){
	$start = !empty($_REQUEST[$var.'_start']) ? rstrtotime($_REQUEST[$var.'_start']) : null;
	$end = !empty($_REQUEST[$var.'_end'])  ? rstrtotime($_REQUEST[$var.'_end']) : null;
	$end && $start && $end < $start && $end = $start;
	return array($start, $end);
}

//注册时间范围
list($regtime_start, $regtime_end) = parse_time_range('regtime');
if($regtime_start !== null){
	$condition[] = 'u.regtime>='.$regtime_start;
	$regtime_start = rdate($regtime_start);
	$query_string[] = 'regtime_start='.$regtime_start;
}
if($regtime_end !== null){
	$condition[] = 'u.regtime<='.$regtime_end;
	$regtime_end = rdate($regtime_end);
	$query_string[] = 'regtime_end='.$regtime_end;
}

//下单时间范围
$order_condition = array();
list($ordertime_start, $ordertime_end) = parse_time_range('ordertime');
if($ordertime_start !== null){
	$order_condition[] = 'o.dateline>='.$ordertime_start;
	$ordertime_start = rdate($ordertime_start);
	$query_string[] = 'ordertime_start='.$ordertime_start;
}
if($ordertime_end !== null){
	$order_condition[] = 'o.dateline<='.$ordertime_end;
	$ordertime_end = rdate($ordertime_end);
	$query_string[] = 'ordertime_end='.$ordertime_end;
}

if($order_condition){
	$order_condition = ' AND '.implode(' AND ', $order_condition);
}else{
	$order_condition = '';
}

//下单数量范围
$subquery_ordernum = "(SELECT COUNT(*) FROM {$tpre}order o WHERE o.userid=u.id $order_condition)";
$ordernum_min = '';
if(isset($_REQUEST['ordernum_min'])){
	$ordernum_min = max(0, intval($_REQUEST['ordernum_min']));
	$condition[] = $subquery_ordernum.'>='.$ordernum_min;
	$query_string[] = 'ordernum_min='.$ordernum_min;
}
$ordernum_max = '';
if(isset($_REQUEST['ordernum_max'])){
	$ordernum_max = max(0, intval($_REQUEST['ordernum_max']));
	$condition[] = $subquery_ordernum.'<='.$ordernum_max;
	$query_string[] = 'ordernum_max='.$ordernum_max;
}

//生成条件子句
if($condition){
	$condition = implode(' AND ', $condition);
}else{
	$condition = '1';
}

$total_user_num = $db->result_first("SELECT COUNT(*) FROM {$tpre}user WHERE 1");

$user_num = $db->result_first("SELECT COUNT(*)
	FROM {$tpre}user u
	WHERE $condition");

$limit = 20;
$offset = ($page - 1) * $limit;
$user_list = $db->fetch_all("SELECT u.*, $subquery_ordernum AS ordernum
	FROM {$tpre}user u
	WHERE $condition
	LIMIT $offset, $limit");

if($query_string){
	$query_string = '&'.implode('&', $query_string);
}else{
	$query_string = '';
}

include view('user_list');

?>
