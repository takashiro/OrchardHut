<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

if($_G['admincp']['mode'] == 'permission'){
	return array();
}

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
if(isset($_REQUEST['ordernum_min']) && $_REQUEST['ordernum_min'] != ''){
	$ordernum_min = max(0, intval($_REQUEST['ordernum_min']));
	$condition[] = $subquery_ordernum.'>='.$ordernum_min;
	$query_string[] = 'ordernum_min='.$ordernum_min;
}
$ordernum_max = '';
if(isset($_REQUEST['ordernum_max']) && $_REQUEST['ordernum_max'] != ''){
	$ordernum_max = max(0, intval($_REQUEST['ordernum_max']));
	$condition[] = $subquery_ordernum.'<='.$ordernum_max;
	$query_string[] = 'ordernum_max='.$ordernum_max;
}

//根据最后下单的送货范围查询
$addressid = null;
if(isset($_REQUEST['address'])){
	$addressid = intval($_REQUEST['address']);
	$address_range = Address::Extension($addressid);
	$condition[] = 'o.addressid IN ('.implode(',', $address_range).')';
	$query_string[] = 'address='.$addressid;
}

//生成条件子句
if($condition){
	$condition = implode(' AND ', $condition);
}else{
	$condition = '1';
}

$output_formats = array('csv', 'html');
$output_format = isset($_REQUEST['format']) && in_array($_REQUEST['format'], $output_formats) ? $_REQUEST['format'] : 'html';

$limit_subsql = '';
if($output_format == 'html'){
	$total_user_num = $db->result_first("SELECT COUNT(*) FROM {$tpre}user WHERE 1");

	$user_num = $db->result_first("SELECT COUNT(*)
		FROM {$tpre}user u
			LEFT JOIN {$tpre}order o ON o.userid=u.id AND o.id=(SELECT MAX(id) FROM {$tpre}order WHERE userid=u.id)
		WHERE $condition");

	$limit = 20;
	$offset = ($page - 1) * $limit;
	$limit_subsql = "LIMIT $offset, $limit";
}

$user_list = $db->fetch_all("SELECT u.*, $subquery_ordernum AS ordernum, o.addressid, o.mobile
	FROM {$tpre}user u
		LEFT JOIN {$tpre}order o ON o.userid=u.id AND o.id=(SELECT MAX(id) FROM {$tpre}order WHERE userid=u.id)
	WHERE $condition
	$limit_subsql");

if($output_format == 'html'){
	if($query_string){
		$query_string = '&'.implode('&', $query_string);
	}else{
		$query_string = '';
	}

	include view('user_list');
}else{
	include view('user_csv');
}

?>
