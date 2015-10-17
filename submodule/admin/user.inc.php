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

function parse_time_range($var){
	$start = !empty($_REQUEST[$var.'_start']) ? rstrtotime($_REQUEST[$var.'_start']) : null;
	$end = !empty($_REQUEST[$var.'_end'])  ? rstrtotime($_REQUEST[$var.'_end']) : null;
	$end && $start && $end < $start && $end = $start;
	return array($start, $end);
}

class UserModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array();		//SQL
		$query_string = array();	//分页

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
			$order_condition = implode(' AND ', $order_condition);
			$condition[] = "EXISTS (SELECT * FROM {$tpre}order o WHERE $order_condition)";
			$order_condition = 'AND '.$order_condition;
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
		if(!empty($_REQUEST['address'])){
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
				WHERE $condition");

			$limit = 20;
			$offset = ($page - 1) * $limit;
			$limit_subsql = "LIMIT $offset, $limit";
		}

		$user_list = $db->fetch_all("SELECT u.*
			FROM {$tpre}user u
			WHERE $condition $limit_subsql");

		$userids = array();
		$user_map = array();
		foreach($user_list as &$u){
			$userids[] = $u['id'];
			$user_map[$u['id']] = &$u;
		}
		unset($u);
		$userids = implode(',', $userids);
		$user_ordernum = $db->fetch_all("SELECT userid,COUNT(*) AS ordernum FROM {$tpre}order WHERE userid IN ($userids) GROUP BY userid");
		foreach($user_ordernum as $u){
			$user_map[$u['userid']]['ordernum'] = $u['ordernum'];
		}
		unset($u, $user_map);
		foreach($user_list as &$u){
			isset($u['ordernum']) || $u['ordernum'] = 0;
		}
		unset($u);

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
	}

}

?>
