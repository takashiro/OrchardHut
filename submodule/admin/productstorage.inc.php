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

class ProductStorageModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('market');
	}

	public function editAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if(!isset($_POST['amount']) || !is_array($_POST['amount'])){
			showmsg('illegal_operation');
		}

		$type = (isset($_POST['type']) && $_POST['type'] == 'import') ? 'import' : 'loss';
		if($type == 'import'){
			if(!isset($_POST['price']) || !is_array($_POST['price']) || empty($_POST['bankaccount'])){
				showmsg('illegal_operation');
			}

			$bankaccount = isset($_POST['bankaccount']) ? intval($_POST['bankaccount']) : 0;
		}

		$db->query('START TRANSACTION');

		$totalcosts = 0;
		$logs = array();
		foreach($_POST['amount'] as $storageid => $amount) {
			$amount = intval($amount);
			if($type == 'import'){
				if($amount <= 0)
					continue;
				if(!isset($_POST['price'][$storageid]))
					continue;
			}else{
				if($amount >= 0)
					continue;
			}

			$storageid = intval($storageid);

			if($type == 'import'){
				$unitprice = abs(floatval($_POST['price'][$storageid]));
				$subtotalcosts = $amount * $unitprice;

				$importamount = isset($_POST['importamount'][$storageid]) ? floatval($_POST['importamount'][$storageid]) : 0;
				$importamountunit = isset($_POST['importamountunit'][$storageid]) ? Product::AmountUnits(intval($_POST['importamountunit'][$storageid])) : 0;
			}else{
				$unitprice = 0;
				$subtotalcosts = 0;
				$importamount = 0;
				$importamountunit = '';
			}

			$db->query("UPDATE {$tpre}productstorage SET num=num+$amount WHERE id=$storageid");
			if($db->affected_rows > 0){
				$s = $db->fetch_first("SELECT p.name AS productname, s.remark
					FROM {$tpre}productstorage s
						LEFT JOIN {$tpre}product p ON p.id=s.productid
					WHERE s.id=$storageid");
				$logs[] = array(
					'storageid' => $storageid,
					'dateline' => TIMESTAMP,
					'amount' => $amount,
					'totalcosts' => $subtotalcosts,
					'adminid' => $_G['admin']->id,
					'productname' => $s['productname'],
					'storageremark' => $s['remark'],
					'importamount' => $importamount,
					'importamountunit' => $importamountunit,
				);
				$totalcosts += $subtotalcosts;
			}
		}

		if($logs){
			if($type == 'import' && $totalcosts > 0){
				$db->query("UPDATE {$tpre}bankaccount SET amount=amount-$totalcosts WHERE id=$bankaccount AND amount>=$totalcosts");
				if($db->affected_rows > 0){
					$bankaccountlog = array(
						'accountid' => $bankaccount,
						'delta' => -$totalcosts,
						'reason' => lang('common', 'storage_import'),
						'operatorid' => $_G['admin']->id,
						'operation' => BankAccount::OPERATION_PRODUCT_IMPORT,
						'targetid' => 0,
						'dateline' => TIMESTAMP,
					);
					$table = $db->select_table('bankaccountlog');
					$table->insert($bankaccountlog);
					$db->query('COMMIT');

					foreach($logs as &$l){
						$l['bankaccountid'] = $bankaccount;
					}
					unset($l);
					$table = $db->select_table('productstoragelog');
					$table->multi_insert($logs);

					showmsg('storage_is_updated', 'refresh');
				}else{
					$db->query('ROLLBACK');
					showmsg('insufficient_bank_account', 'back');
				}
			}else{
				$db->query('COMMIT');

				$table = $db->select_table('productstoragelog');
				$table->multi_insert($logs);

				showmsg('storage_is_updated', 'refresh');
			}
		}

		showmsg('no_storage_need_updating');
	}

	public function logAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array();

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

		$condition = $condition ? implode(' AND ', $condition) : '1';

		$limit = 20;
		$offset = ($page - 1) * $limit;
		$logs = $db->fetch_all("SELECT l.*,b.remark AS bankaccountremark,a.realname
			FROM {$tpre}productstoragelog l
				LEFT JOIN {$tpre}bankaccount b ON b.id=l.bankaccountid
				LEFT JOIN {$tpre}administrator a ON a.id=l.adminid
			wHERE $condition
			ORDER BY l.dateline DESC
			LIMIT $offset,$limit");

		$total = $db->result_first("SELECT COUNT(*) FROM {$tpre}productstoragelog l WHERE $condition");

		$time_start && $time_start = rdate($time_start);
		$time_end && $time_end = rdate($time_end);
		include view('productstorage_log');
	}

	public function configAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if($_POST){
			$config = array();
			foreach(array('bookingtime_start', 'bookingtime_end') as $var){
				$config[$var] = 0;
				if(isset($_POST[$var])){
					$time = explode(':', $_POST[$var]);
					$config[$var] = $time[0] * 3600;
					if(isset($time[1])){
						$config[$var] += $time[1] * 60;
						if(isset($time[2])){
							$config[$var] += $time[2];
						}
					}
				}
			}

			ProductStorage::WriteConfig($config);
			showmsg('edit_succeed', 'back');
		}

		$storageconfig = ProductStorage::ReadConfig();

		foreach(array('bookingtime_start', 'bookingtime_end') as $var){
			$s = $storageconfig[$var];
			$i = $s / 60;
			$H = $i / 60;
			$i %= 60;
			$s %= 60;
			$storageconfig[$var] = sprintf('%02d', $H).':'.sprintf('%02d', $i).':'.sprintf('%02d', $s);
		}

		include view('productstorage_config');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$storages = $db->fetch_all("SELECT s.*, p.name as productname
			FROM {$tpre}productstorage s
				LEFT JOIN {$tpre}product p ON p.id=s.productid
			WHERE p.hide=0");

		$storage_unit_ratio = array();

		if($storages){
			$storageHash = array();
			foreach($storages as &$s){
				$storageHash[$s['id']] = &$s;
			}
			unset($s);

			$storageids = array();
			foreach($storages as $s)
				$storageids[] = $s['id'];

			$storageids = implode(',', $storageids);
			$query = $db->query("SELECT storageid,subtype,amountunit
				FROM {$tpre}productprice
				WHERE storageid IS NOT NULL AND storageid IN ($storageids)");
			while($p = $query->fetch_row()){
				$s = &$storageHash[$p[0]];
				$s['subtype'][] = $p[1];
				$s['amountunit'][] = $p[2];
			}

			$storage_unit_ratio = $db->fetch_all("SELECT storageid,amount,importamount,importamountunit
				FROM `hut_productstoragelog`
				WHERE id IN (SELECT MAX(id)
					FROM `hut_productstoragelog`
					WHERE storageid IN ($storageids) GROUP BY storageid,importamountunit)
				ORDER BY id DESC");
		}

		foreach($storages as &$s){
			if(!empty($s['amountunit'])){
				$s['amountunit'] = array_unique($s['amountunit']);
				foreach($s['amountunit'] as &$u){
					$u = Product::AmountUnits($u);
				}
				unset($u);
				$s['amountunit'] = implode('/', $s['amountunit']);
			}else{
				$s['amountunit'] = '';
			}

			if(isset($s['subtype']) && count($s['subtype']) > 1){
				$s['subtype'] = array_unique($s['subtype']);
			}
		}
		unset($s);

		$table = $db->select_table('bankaccount');
		$bankaccounts = $table->fetch_all('*');

		include view('productstorage');
	}

}

?>
