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

class OrderPackModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		include view('pack');
	}

	public function mark_packingAction(){
		global $_G;

		if(empty($_REQUEST['orderid'])) exit('access denied');
		$order = new Order($_REQUEST['orderid']);

		if($order->exists()){
			if(!$order->belongToAddress($_G['admin']->getLimitations())){
				exit('permission denied');
			}

			if($order->status == Order::WaitForPacking || $_G['admin']->isSuperAdmin()){
				$order->status = Order::Packing;
				$order->addLog($_G['admin'], Order::StatusChanged, Order::Packing);
			}
		}

		empty($_GET['ajaxform']) || exit('1');
		empty($_SERVER['HTTP_REFERER']) || redirect($_SERVER['HTTP_REFERER']);
	}

	public function getStatusTimeAction(){
		if(empty($_REQUEST['orderids']) || !is_array($_REQUEST['orderids'])){
			exit('access denied');
		}

		$orderids = &$_REQUEST['orderids'];
		foreach($orderids as &$id){
			$id = intval($id);
		}
		unset($id);
		$orderids = implode(',', $orderids);

		global $db, $tpre;
		$operation = Order::StatusChanged;
		$status = Order::WaitForPacking;
		$statustime = $db->result_first("SELECT MAX(dateline) FROM {$tpre}orderlog WHERE orderid IN ($orderids) AND operation=$operation AND extra=$status");
		echo json_encode(array('statustime' => $statustime));
		exit;
	}

}
