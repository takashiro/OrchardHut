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

class ReturnedOrderModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array();

		//过滤退款状态
		$returned_order_states = array(ReturnedOrder::Submitted);
		$returned_order_states = implode(',', $returned_order_states);
		$condition[] = "r.state IN ($returned_order_states)";

		//过滤掉送货地点不在当前管理员的管辖范围内的订单
		$limitation_addressids = $_G['admin']->getLimitations();
		if($limitation_addressids){
			$condition[] = 'o.addressid IN ('.implode(',', $limitation_addressids).')';
		}

		if($condition){
			$condition = implode(' AND ', $condition);
		}else{
			$condition = '1';
		}

		if($_POST){
			if(is_array($_POST['reply'])){
				foreach($_POST['reply'] as $orderid => $reply){
					$orderid = intval($orderid);
					if($orderid <= 0)
						continue;

					if($limitation_addressids){
						$addressid = $db->result_first("SELECT addressid FROM {$tpre}order WHERE id='$orderid'");
						if(!in_array($addressid, $limitation_addressids))
							continue;
					}

					$reply = htmlspecialchars($reply);
					$reply = addslashes($reply);
					$db->query("UPDATE {$tpre}returnedorder SET adminreply='$reply' WHERE id='$orderid'");
				}
			}

			if(is_array($_POST['detail'])){
				foreach($_POST['detail'] as $detailid => $state){
					$detailid = intval($detailid);
					if($detailid <= 0)
						continue;

					if($limitation_addressids){
						$addressid = $db->result_first("SELECT o.addressid
							FROM {$tpre}orderdetail d
								LEFT JOIN {$tpre}order o ON o.id=d.orderid
							WHERE d.id='$detailid'");
						if(!in_array($addressid, $limitation_addressids))
							continue;
					}

					$state = intval($state);
					if(!isset(ReturnedOrder::$DetailResult[$state]))
						continue;

					$db->query("UPDATE {$tpre}returnedorderdetail SET state='$state' WHERE id='$detailid'");
				}

				//查询出已经处理完毕的订单
				$unhandled_order = ReturnedOrder::Submitted;
				$handled_order = ReturnedOrder::Handled;
				$unhandled_detail = ReturnedOrder::UnhandledDetail;
				$returned_orders = $db->fetch_all("SELECT o.id
					FROM {$tpre}returnedorder o
					WHERE o.state=$unhandled_order
						AND NOT EXISTS (SELECT * FROM {$tpre}returnedorderdetail WHERE orderid=o.id AND state=$unhandled_detail)");

				foreach($returned_orders as $o){
					$returned_fee = isset($_POST['returnedfee'][$o['id']]) ? floatval($_POST['returnedfee'][$o['id']]) : 0;
					$db->query("UPDATE {$tpre}returnedorder
						SET state=$handled_order,returnedfee=$returned_fee
						WHERE id={$o['id']} AND state=$unhandled_order");

					if($db->affected_rows > 0){
						//退款至用户账户
						$order = $db->fetch_first("SELECT userid FROM {$tpre}order WHERE id={$o['id']}");
						$db->query("UPDATE {$tpre}user SET wallet=wallet+$returned_fee WHERE id={$order['userid']}");
						if($db->affected_rows > 0){
							$log = array(
								'uid' => $order['userid'],
								'dateline' => TIMESTAMP,
								'type' => Wallet::OrderRefundLog,
								'delta' => $returned_fee,
								'orderid' => $o['id'],
							);
							$table = $db->select_table('userwalletlog');
							$table->insert($log);
						}

						//返回库存
						$order = new ReturnedOrder;
						$order->id = $o['id'];
						$details = $order->getDetails();
						foreach($details as $d){
							if($d['state'] != ReturnedOrder::FeeAndItem){
								continue;
							}

							$detail = $db->fetch_first("SELECT storageid,productname FROM {$tpre}orderdetail WHERE id={$d['id']}");
							$storage = new ProductStorage($detail['storageid']);
							if($storage->exists() && $storage->updateNum($d['number'])){
								$log = array(
									'storageid' => $detail['storageid'],
									'dateline' => TIMESTAMP,
									'amount' => $d['number'],
									'adminid' => $_G['admin']->id,
									'productname' => $detail['productname'],
								);
								$table = $db->select_table('productstoragelog');
								$table->insert($log);
							}
						}
					}
				}
			}

			showmsg('successfully_handled_returned_order', 'refresh');
		}

		$returned_orders = $db->fetch_all("SELECT r.*,o.addressid,o.extaddress,o.totalprice,o.userid,o.mobile,o.addressee,o.dateline AS orderdateline,o.paymentmethod,o.alipaystate
			FROM {$tpre}returnedorder r
				LEFT JOIN {$tpre}order o ON o.id=r.id
			WHERE $condition");
		self::FetchOrderDetails($returned_orders);

		$address_format = Address::Format();

		include view('returnedorder_list');
	}

	public function configAction(){
		if($_POST){
			$config = array();

			if($_POST['reason_options']){
				$config['reason_options'] = htmlspecialchars($_POST['reason_options']);
			}

			writedata('returnedorderconfig', $config);
			showmsg('edit_succeed', 'refresh');
		}

		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		$config = readdata('returnedorderconfig');
		include view('returnedorder_config');
	}

	public function logAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array();

		//过滤退款状态
		$returned_order_states = array(ReturnedOrder::Handled);
		$returned_order_states = implode(',', $returned_order_states);
		$condition[] = "r.state IN ($returned_order_states)";

		//过滤掉送货地点不在当前管理员的管辖范围内的订单
		$limitation_addressids = $_G['admin']->getLimitations();
		if($limitation_addressids){
			$condition[] = 'o.addressid IN ('.implode(',', $limitation_addressids).')';
		}

		$query_data = array();

		//订单号
		if(isset($_REQUEST['orderid'])){
			$orderid = intval($_REQUEST['orderid']);
			$condition[] = 'r.id='.$orderid;
		}

		//退单时间
		if(isset($_REQUEST['time_start'])){
			$time_start = rstrtotime($_REQUEST['time_start']);
			$condition[] = 'r.dateline>='.$time_start;
		}else{
			$time_start = '';
		}
		if(isset($_REQUEST['time_end'])){
			$time_end = rstrtotime($_REQUEST['time_end']);
			$time_end < $time_start && $time_end = $time_start;
			$condition[] = 'r.dateline<='.$time_end;
		}else{
			$time_end = '';
		}

		//限定当前管理员可以使用的地址
		$all_addresses = Address::Components();
		$limitation = $_G['admin']->getLimitations();
		if($limitation){
			foreach($all_addresses as $cid => $c){
				if(!in_array($cid, $limitation)){
					unset($all_addresses[$cid]);
				}
			}
		}
		if($_REQUEST['address']){
			$address = intval($_REQUEST['address']);
			isset($all_addresses[$address]) || $address = 0;
		}else{
			$address = 0;
		}
		if($address){
			$address_limitation = Address::Extension($address);
			$condition[] = 'o.addressid IN ('.implode(',', $address_limitation).')';
			$query_data['address'] = $address;
		}

		//处理输出格式
		$formats = array('html', 'csv');
		$format = $formats[0];
		if(isset($_REQUEST['format']) && in_array($_REQUEST['format'], $formats)){
			$format = $_REQUEST['format'];
		}

		$limit_sql = '';
		if($format == 'html'){
			$limit = 20;
			$offset = ($page - 1) * $limit;
			$limit_sql = "LIMIT $offset,$limit";
		}

		if($condition){
			$condition = implode(' AND ', $condition);
		}else{
			$condition = '1';
		}

		$returned_orders = $db->fetch_all("SELECT r.*,o.addressid,o.extaddress,o.totalprice,o.userid,o.mobile,o.addressee,o.dateline AS orderdateline,o.paymentmethod,o.alipaystate
			FROM {$tpre}returnedorder r
				LEFT JOIN {$tpre}order o ON o.id=r.id
			WHERE $condition
			ORDER BY r.dateline DESC
			$limit_sql");
		self::FetchOrderDetails($returned_orders);

		$pagenum = $db->result_first("SELECT COUNT(*)
			FROM {$tpre}returnedorder r
				LEFT JOIN {$tpre}order o ON o.id=r.id
			WHERE $condition");

		$address_format = Address::Format();

		if(is_numeric($time_start)){
			$time_start = rdate($time_start);
			$query_data['time_start'] =$time_start;
		}
		if(is_numeric($time_end)){
			$time_end = rdate($time_end);
			$query_data['time_end'] = $time_end;
		}

		$query_string = http_build_query($query_data);

		include view('returnedorder_log_'.$format);
	}


	static private function FetchOrderDetails(&$returned_orders){
		global $db, $tpre;

		$order_details = array();

		$orderids = array();
		foreach($returned_orders as $o){
			$orderids[] = $o['id'];
		}

		if($orderids){
			$orderids = implode(',', $orderids);
			$query = $db->query("SELECT o.*, (o.number * o.amount) AS boughtnum, r.*
				FROM {$tpre}returnedorderdetail r
					LEFT JOIN {$tpre}orderdetail o ON o.id=r.id
				WHERE r.orderid IN ($orderids)");
			while($d = $query->fetch_assoc()){
				$order_details[$d['orderid']][] = $d;
			}
		}

		foreach($returned_orders as &$o){
			if(isset($order_details[$o['id']])){
				$o['details'] = $order_details[$o['id']];
			}else{
				//It should never runs here...
				$o['details'] = array();
			}
		}
		unset($o);
	}

}

?>
