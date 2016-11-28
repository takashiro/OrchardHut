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

class OrderRibbonModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('order');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
		include view('ribbon');
	}

	public function queryAction(){
		if(empty($_POST['userid']) || empty($_POST['formkey'])){
			exit('illegal operation');
		}

		$userid = intval($_POST['userid']);
		$formkey = intval($_POST['formkey']);

		$response = array(
			'error' => 0,
			'error_message' => '',
			'data' => array(),
		);

		global $db, $tpre;
		$db->query("UPDATE {$tpre}user SET formkey=0 WHERE id=$userid AND formkey=$formkey");
		if($db->affected_rows <= 0){
			$response['error'] = 1;
			$response['error_message'] = lang('message', 'invalid_qrcode_please_refresh');
			echo json_encode($response);
			exit;
		}

		$paidstate = array(Wallet::TradeSuccess, Wallet::TradeFinished);
		$paidstate = implode(',', $paidstate);
		$ribbons = $db->fetch_all("SELECT r.*
			FROM {$tpre}ribbon r
				LEFT JOIN {$tpre}ribbonorder o ON o.id=r.orderid
			WHERE r.userid=$userid
				AND r.restnum>0
				AND o.tradestate IN ($paidstate)");

		$response['data'] = $ribbons;
		echo json_encode($response);
		exit;
	}

	public function consumeAction(){
		if(empty($_POST['consume'])){
			exit('illegal operation');
		}

		$consume = json_decode($_POST['consume'], true);
		if(!is_array($consume)){
			exit('invalid input');
		}

		global $db, $tpre, $_G;
		$db->query('START TRANSACTION');
		foreach($consume as $ribbonid => $num){
			$ribbonid = intval($ribbonid);
			$num = intval($num);
			if($num <= 0){
				continue;
			}

			$db->query("UPDATE {$tpre}ribbon SET restnum=restnum-$num WHERE id=$ribbonid AND restnum>=$num");
			if($db->affected_rows > 0){
				$table = $db->select_table('ribbonlog');
				$log = array(
					'ribbonid' => $ribbonid,
					'dateline' => TIMESTAMP,
					'costnum' => $num,
					'adminid' => $_G['admin']->id,
				);
				$table->insert($log);
			}else{
				$db->query('ROLLBACK');
				$response = array(
					'error' => 1,
					'data' => $ribbonid,
				);
				echo json_encode($response);
				exit;
			}
		}
		$db->query('COMMIT');

		$response = array(
			'error' => 0,
			'data' => null,
		);
		echo json_encode($response);
		exit;
	}

}
