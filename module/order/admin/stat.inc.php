<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class OrderStatModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('order');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		$condition = array();

		//根据付款状态查询订单
		if(empty($_REQUEST['tradestate'])){
			$tradestate = Wallet::TradeSuccess;
		}else{
			$tradestate = intval($_REQUEST['tradestate']);

			//@todo: resolve the hack
			if($tradestate != 1){
				$condition[] = 'o.tradestate='.$tradestate;
			}else{
				$condition[] = 'o.tradestate IN (0,1)';
			}
		}

		//时间条件
		$timefield = $tradestate >= Wallet::TradeSuccess ? 'tradetime' : 'dateline';
		$time_start = null;
		if(isset($_REQUEST['time_start'])){
			$time_start = rstrtotime($_REQUEST['time_start']);
			$condition[] = 'o.'.$timefield.'>='.$time_start;
		}else{
			$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd') - 1, rdate(TIMESTAMP, 'Y'));

			//根据截单时间调整时分秒
			$deliverytimes = DeliveryTime::FetchAllEffective();
			usort($deliverytimes, function($t1, $t2){
				return $t1['deadline'] > $t2['deadline'];
			});
			$dt = current($deliverytimes);
			$time_start += $dt['deadline'];
			unset($deliverytimes, $dt);
		}
		$time_end = null;
		if(isset($_REQUEST['time_end'])){
			$time_end = rstrtotime($_REQUEST['time_end']);
			$condition[] = 'o.'.$timefield.'<='.$time_end;
		}else{
			$time_end = $time_start + 24 * 3600;
		}

		//过滤商品类型
		$available_product_types = Product::AvailableTypes();

		$product_type = array();
		if(isset($_REQUEST['product_type']) && is_array($_REQUEST['product_type'])){
			$types = array();
			foreach($_REQUEST['product_type'] as $type_id => $checked){
				if(isset($available_product_types[$type_id])){
					$product_type[$type_id] = true;
					$types[] = $type_id;
				}
			}

			$types && $condition[] = 'p.type IN ('.implode(',', $types).')';
		}else{
			$product_type = $available_product_types;
			foreach($product_type as &$checked){
				$checked = false;
			}
			unset($checked);
		}

		//过滤订单状态
		$available_status = Order::$Status;
		if(!$_G['admin']->hasPermission('order_sort')){
			unset($available_status[Order::Unsorted]);
		}
		if(!$_G['admin']->hasPermission('order_deliver')){
			unset($available_status[Order::Sorted], $available_status[Order::Delivering]);
		}
		foreach($available_status as &$checked){
			$checked = false;
		}
		unset($checked);
		if(isset($_REQUEST['display_status']) && is_array($_REQUEST['display_status'])){
			foreach($_REQUEST['display_status'] as $status_id => $checked){
				$available_status[$status_id] = !empty($checked);
			}
		}

		$display_status = array();
		foreach($available_status as $status_id => $checked){
			$checked && $display_status[] = $status_id;
		}
		if($display_status){
			$condition[] = 'o.status IN ('.implode(',', $display_status).')';
		}else{
			$condition[] = '0';
		}

		//过滤配送方式
		$delivery_methods = Order::$DeliveryMethod;
		$delivery_methods[-1] = '';
		$deliverymethod = -1;
		isset($_REQUEST['deliverymethod']) && $deliverymethod = intval($_REQUEST['deliverymethod']);
		isset($delivery_methods[$deliverymethod]) || $deliverymethod = -1;
		if($deliverymethod != -1){
			$condition[] = 'o.deliverymethod='.$deliverymethod;
		}

		//生成查询条件子句
		if($condition){
			$condition = implode(' AND ', $condition);
		}else{
			$condition = '1';
		}

		$address_tables = array();
		$address_format = Address::Format();
		$cascade_level = count($address_format);
		$i = $cascade_level;
		$address_tables[] = "LEFT JOIN {$tpre}addresscomponent a{$i} ON a{$i}.id=o.addressid";
		for($i = $cascade_level - 1; $i >= 1; $i--){
			$j = $i + 1;
			$address_tables[] = "LEFT JOIN {$tpre}addresscomponent a{$i} ON IF(a{$j}.parentid!=0, a{$i}.id=a{$j}.parentid, a{$i}.id=a{$j}.id)";
		}
		$address_tables = implode(' ', $address_tables);

		$item_list = $db->fetch_all("SELECT a1.id AS topaddressid, a1.name AS topaddressname,
				d.productid,d.productname,d.subtype, SUM(d.amount*d.number) AS totalnum
			FROM {$tpre}orderdetail d
				LEFT JOIN {$tpre}product p ON p.id=d.productid
				LEFT JOIN {$tpre}order o ON o.id=d.orderid
				$address_tables
			WHERE $condition
			GROUP BY a1.id,d.productid,d.subtype");

		$top_address_list = array();
		$product_list = array();
		$stat_list = array();
		foreach($item_list as $item){
			$stat_list[$item['productid']][$item['subtype']][$item['topaddressid']] = $item;
			$top_address_list[$item['topaddressid']] = $item['topaddressname'];
			$product_list[$item['productid']] = $item['productname'];
		}

		if($time_start !== null){
			$time_start = rdate($time_start, 'Y-m-d H:i');
		}
		if($time_end !== null){
			$time_end = rdate($time_end, 'Y-m-d H:i');
		}

		$formats = array('html', 'csv');
		$format = isset($_REQUEST['format']) && in_array($_REQUEST['format'], $formats) ? $_REQUEST['format'] : 'html';
		include view('stat_'.$format);
	}

}
