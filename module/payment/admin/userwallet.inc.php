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

class PaymentUserWalletModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('payment');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array('l.recharged=1');

		if(!empty($_REQUEST['time_start'])){
			$time_start = rstrtotime($_REQUEST['time_start']);
			$condition[] = "l.dateline>=$time_start";
		}else{
			$time_start = '';
		}

		if(!empty($_REQUEST['time_end'])){
			$time_end = rstrtotime($_REQUEST['time_end']);
			$condition[] = "l.dateline<=$time_end";
		}else{
			$time_end = '';
		}

		$condition = empty($condition) ? '1' : implode(' AND ', $condition);

		$limit = 20;
		$offset = ($page - 1) * $limit;
		$logs = $db->fetch_all("SELECT l.*, u.nickname
			FROM {$tpre}userwalletlog l
				LEFT JOIN {$tpre}user u ON u.id=l.uid
			WHERE $condition
			ORDER BY l.id DESC
			LIMIT $offset, $limit");

		$time_start && $time_start = rdate($time_start);
		$time_end && $time_end = rdate($time_end);

		$pagenum = $db->result_first("SELECT COUNT(*)
			FROM {$tpre}userwalletlog l
			WHERE $condition");

		$stat = array(
			'totaldelta' => $db->result_first("SELECT SUM(l.delta) FROM {$tpre}userwalletlog l WHERE $condition"),
			'totalcost' => $db->result_first("SELECT SUM(l.cost) FROM {$tpre}userwalletlog l WHERE $condition"),
		);

		$totalwallet = array(
			'amount' => $db->result_first("SELECT SUM(wallet) FROM {$tpre}user"),
			'gifted' => $db->result_first("SELECT SUM(delta-cost) FROM {$tpre}userwalletlog WHERE recharged=1"),
			'realcharged' => $db->result_first("SELECT SUM(delta) FROM {$tpre}userwalletlog WHERE recharged=1"),
		);

		include view('userwallet_log');
	}

}

?>
