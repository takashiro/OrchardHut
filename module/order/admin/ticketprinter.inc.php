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

class OrderTicketPrinterModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('order');
	}

	public function defaultAction(){
		global $db, $_G;
		$table = $db->select_table('station');
		$limitation_addressids = $_G['admin']->getLimitations();
		if($limitation_addressids){
			$condition = 'orderrange IN ('.implode(',', $limitation_addressids).')';
		}else{
			$condition = '1';
		}
		$stations = $table->fetch_all('*', $condition);

		extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
		include view('station_list');
	}

	public function pauseAction(){
		if(empty($_POST['stationid'])) exit('invalid station id');
		$stationid = intval($_POST['stationid']);
		if($stationid <= 0) exit('invalid station id');

		$paused = !empty($_POST['paused']) ? 1 : 0;

		global $db, $tpre;
		$db->query("UPDATE {$tpre}station SET pauseprinting=$paused WHERE id=$stationid");
		echo $db->affected_rows;
		exit;
	}

	public function printAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$stationid = isset($_GET['stationid']) ? intval($_GET['stationid']) : 0;

		global $db, $_G;
		$table = $db->select_table('station');
		$addresses = $_G['admin']->getLimitations();
		if($addresses){
			$condition = 'orderrange IN ('.implode(',', $addresses).')';
		}else{
			$condition = '1';
		}
		$condition.= ' LIMIT 1';
		if($stationid <= 0){
			$station = $table->fetch_first('*', $condition);
			if(empty($station)){
				exit('invalid station id');
			}
			$stationid = $station['id'];
		}else{
			$station = $table->fetch_first('*', 'id='.$stationid.' AND '.$condition);
			if(!$station){
				exit('invalid station id');
			}
		}

		if(!empty($_GET['check'])){
			if($station['pauseprinting']){
				exit('-2');
			}
			if(empty($_REQUEST['orderid']) && empty($_REQUEST['mobile']) && empty($_REQUEST['orderids'])){
				exit('0');
			}
		}

		$condition = array();

		//下单起始时间
		if(!isset($_REQUEST['time_start'])){
			$time_start = rmktime(0, 0, 0, rdate(TIMESTAMP, 'm'), rdate(TIMESTAMP, 'd'), rdate(TIMESTAMP, 'Y'));

			//根据截单时间调整时分秒
			$deliverytimes = DeliveryTime::FetchAllEffective();
			usort($deliverytimes, function($t1, $t2){
				return $t1['deadline'] > $t2['deadline'];
			});
			$dt = current($deliverytimes);
			$time_start += $dt['deadline'];
			unset($deliverytimes, $dt);

			$condition[] = 'dateline>='.$time_start;
		}else{
			if(!empty($_REQUEST['time_start'])){
				$time_start = rstrtotime($_REQUEST['time_start']);
				$condition[] = 'dateline>='.$time_start;
			}
		}

		//下单截止时间
		if(!isset($_REQUEST['time_end'])){
			$time_end = $time_start + 1 * 24 * 3600;
			$condition[] = 'dateline<='.$time_end;
		}else{
			if(!empty($_REQUEST['time_end'])){
				$time_end = rstrtotime($_REQUEST['time_end']);
				$time_end < $time_start && $time_end = $time_start;
				$condition[] = 'dateline<='.$time_end;
			}
		}

		if(isset($_REQUEST['orderid']) || isset($_REQUEST['mobile']) || isset($_REQUEST['orderids'])){
			//过滤掉送货地点不在当前管理员的管辖范围内的订单
			$limited_addressids = Address::Extension($station['orderrange']);
			$admin_limited_addressids = $_G['admin']->getLimitations();
			if($admin_limited_addressids){
				if($limited_addressids){
					$limited_addressids = array_intersect($limited_addressids, $admin_limited_addressids);
					if(!$limited_addressids){
						$condition[] = '0';
					}
				}else{
					$limited_addressids = $admin_limited_addressids;
				}
			}
			if($limited_addressids){
				$condition[] = 'addressid IN ('.implode(',', $limited_addressids).')';
			}

			//到自提点的订单
			$condition[] = 'status='.Order::InDeliveryStation;

			if(!empty($_REQUEST['orderid'])){
				if(!empty($_REQUEST['packcode'])){
					$condition[] = 'id='.intval($_REQUEST['orderid']);
					$condition[] = 'packcode='.intval($_REQUEST['packcode']);
				}else{
					exit('invalid pack code');
				}
			}elseif(!empty($_REQUEST['mobile'])){
				$mobile = trim($_REQUEST['mobile']);
				$condition[] = 'mobile=\''.raddslashes($mobile).'\'';
			}elseif(!empty($_REQUEST['orderids']) && is_array($_REQUEST['orderids'])){
				$orderids = array();
				foreach($_REQUEST['orderids'] as $orderid){
					$orderid = intval($orderid);
					if($orderid > 0){
						$orderids[] = $orderid;
					}
				}

				if($orderids){
					$condition[] = 'id IN ('.implode(',', $orderids).')';
				}
			}else{
				exit;
			}

			$condition = implode(' AND ', $condition);

			if(!empty($_REQUEST['mark_received'])){
				$orders = $db->fetch_all("SELECT id FROM {$tpre}order WHERE $condition");
				if($orders){
					foreach($orders as $o){
						$order = new Order($o['id']);
						$order->status = Order::Received;
						$order->addLog($_G['admin'], Order::StatusChanged, Order::Received);
					}
					exit('1');
				}else{
					exit('0');
				}
			}

			if(!empty($_GET['check'])){
				$result = $db->result_first("SELECT 1 FROM {$tpre}order WHERE $condition LIMIT 1");
				exit($result ? '0' : '-1');
			}

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
			include view('list_ticket');

		}else{
			$station['pauseprinting'] = intval($station['pauseprinting']);
			include view('ticket_printer');
		}
	}

	public function getPackQRCodeAction(){
		if(empty($_GET['stationid'])) exit;
		$stationid = intval($_GET['stationid']);
		$qrcode = rand(0, 0xFFFF);
		$timeout = 60 * 5;
		$expiry = TIMESTAMP + $timeout;

		global $db, $tpre;
		$timestamp = TIMESTAMP;
		$db->query("UPDATE {$tpre}station SET packqrcode=$qrcode,packqrcodeexpiry=$expiry WHERE id=$stationid");
		if($db->affected_rows > 0){
			$result = array(
				'qrcode' => $qrcode,
				'timeout' => $timeout,
			);
			echo json_encode($result);
		}else{
			echo '{}';
		}
		exit;
	}

	public function getPackOrderAction(){
		if(empty($_GET['stationid'])) exit;
		$stationid = intval($_GET['stationid']);

		global $db, $tpre;
		$result = array(
			'orders' => array(),
			'refreshqrcode' => false,
		);
		$query = $db->query("SELECT orderid FROM {$tpre}stationorder WHERE stationid=$stationid");
		while($row = $query->fetch_array()){
			$db->query("DELETE FROM {$tpre}stationorder WHERE orderid={$row[0]} AND stationid=$stationid");
			if($db->affected_rows > 0){
				$packcode = rand(1, 0xFF);
				$db->query("UPDATE {$tpre}order SET packcode=$packcode WHERE id={$row[0]}");
				$result['orders'][] = array(
					'orderid' => $row[0],
					'packcode' => $packcode,
				);
			}
		}

		if(!$result['orders']){
			$timestamp = TIMESTAMP;
			$qrcode = $db->result_first("SELECT packqrcode FROM {$tpre}station WHERE id=$stationid AND packqrcodeexpiry>=$timestamp");
			if(!$qrcode){
				$result['refreshqrcode'] = true;
			}
		}

		echo json_encode($result);
		exit;
	}

}
