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

define('IN_ADMINCP', true);
require_once './core/init.inc.php';

//下单起始时间
$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));

//根据截单时间调整时分秒
$deliverytimes = DeliveryTime::FetchAllEffective();
usort($deliverytimes, function($t1, $t2){
	return $t1['deadline'] > $t2['deadline'];
});
$dt = current($deliverytimes);
$time_start += $dt['deadline'];
unset($deliverytimes, $dt);

//下单截止时间
$time_end = $time_start + 1 * 24 * 3600;


if($_POST){
	$condition = array('dateline>='.$time_start, 'dateline<='.$time_end);

	if(!empty($_POST['orderid'])){
		$condition[] = 'id='.intval($_POST['orderid']);
	}elseif(!empty($_POST['mobile'])){
		$condition[] = 'mobile=\''.raddslashes($_POST['mobile']).'\'';
	}else{
		showmsg('please_input_order_id_or_mobile', 'back');
	}

	$condition = implode(' AND ', $condition);

	$orders = $db->fetch_all("SELECT * FROM {$tpre}order WHERE $condition");

	if($orders){
		$orderids = array();
		foreach($orders as $o){
			$orderids[] = $o['id'];
		}
		$orderids = implode(',', $orderids);

		$order_details = array();
		$query = $db->query("SELECT * FROM {$tpre}orderdetail WHERE orderid IN ($orderids)");
		while($d = $query->fetch_assoc()){
			$order_details[$d['orderid']][] = $d;
		}

		foreach($orders as &$o){
			$o['dateline'] = rdate($o['dateline']);
			$o['deliveryaddress'] = Address::FullPathString($o['addressid']).' '.$o['extaddress'];
			$o['detail'] = isset($order_details[$o['id']]) ? $order_details[$o['id']] : array();
		}
		unset($o);
	}

	$ticketconfig = readdata('ticket');
	include view('order_wideticket');

}else{
	include view('ticket_printer');
}

?>
