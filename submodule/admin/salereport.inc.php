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
if(!empty($_POST['order_address'])){
	$order_address = intval($_POST['order_address']);
	$extension = Address::Extension($order_address);
	if($extension){
		$condition[] = 'o.addressid IN ('.implode(',', $extension).')';
	}else{
		$condition[] = '0';
	}
}else{
	$order_address = 0;
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

$address_components = Address::AvailableComponents();

include view('salereport_'.$format);

?>