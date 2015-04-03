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

$action = &$_GET['action'];
switch($action){
case 'import':
	if(!isset($_POST['amount']) || !isset($_POST['price']) || !is_array($_POST['amount']) || !is_array($_POST['price']) || empty($_POST['bankaccount'])){
		showmsg('illegal_operation');
	}

	$bankaccount = isset($_POST['bankaccount']) ? intval($_POST['bankaccount']) : 0;

	$db->query('START TRANSACTION');

	$totalcosts = 0;
	$logs = array();
	foreach($_POST['amount'] as $storageid => $amount) {
		$amount = intval($amount);
		if($amount <= 0)
			continue;
		if(!isset($_POST['price'][$storageid]))
			continue;
		$storageid = intval($storageid);

		$unitprice = floatval($_POST['price'][$storageid]);
		$subtotalcosts = $amount * $unitprice;

		$importamount = floatval($_POST['importamount'][$storageid]);
		$importamountunit = Product::AmountUnits(intval($_POST['importamountunit'][$storageid]));

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
			$bankaccountlogid = $table->insert_id();
			$db->query('COMMIT');

			foreach($logs as &$l){
				$l['bankaccountlogid'] = $bankaccountlogid;
			}
			unset($l);
			$table = $db->select_table('productstoragelog');
			$table->multi_insert($logs);

			showmsg('storage_is_updated', 'refresh');
		}else{
			$db->query('ROLLBACK');
			showmsg('insufficient_bank_account', 'refresh');
		}
	}

	showmsg('no_storage_need_updating', 'refresh');
	break;

case 'log':
	$operation = BankAccount::OPERATION_PRODUCT_IMPORT;
	$condition = array("l.operation=$operation");

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

	$condition = implode(' AND ', $condition);

	$limit = 20;
	$offset = ($page - 1) * $limit;
	$logs = $db->fetch_all("SELECT l.*,b.remark,a.realname
		FROM {$tpre}bankaccountlog l
			LEFT JOIN {$tpre}bankaccount b ON b.id=l.accountid
			LEFT JOIN {$tpre}administrator a ON a.id=l.operatorid
		wHERE $condition
		ORDER BY l.dateline DESC");

	$total = $db->result_first("SELECT COUNT(*) FROM {$tpre}bankaccountlog l WHERE $condition");

	$time_start && $time_start = rdate($time_start);
	$time_end && $time_end = rdate($time_end);
	include view('productstorage_log');
	break;

case 'importdetail':
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$table = $db->select_table('productstoragelog');
	$items = $table->fetch_all('*', 'bankaccountlogid='.$id);
	$log = $db->fetch_first("SELECT l.*,b.remark,a.realname
		FROM {$tpre}bankaccountlog l
			LEFT JOIN {$tpre}bankaccount b ON b.id=l.accountid
			LEFT JOIN {$tpre}administrator a ON a.id=l.operatorid
		wHERE l.id=$id");
	include view('productstorage_importdetail');
	break;

default:
	$storages = $db->fetch_all("SELECT s.*, p.name as productname
		FROM {$tpre}productstorage s
			LEFT JOIN {$tpre}product p ON p.id=s.productid
		WHERE p.hide=0");

	$storageHash = array();
	foreach($storages as &$s){
		$storageHash[$s['id']] = &$s;
	}
	unset($s);

	$storageids = array();
	foreach($storages as $s)
		$storageids[] = $s['id'];
	$storageids = implode(',', $storageids);
	$query = $db->query("SELECT storageid,subtype,amountunit FROM {$tpre}productprice WHERE storageid IS NOT NULL AND storageid IN ($storageids)");
	while($p = $query->fetch_row()){
		$s = &$storageHash[$p[0]];
		$s['subtype'][] = $p[1];
		$s['amountunit'][] = $p[2];
	}

	foreach($storages as &$s){
		$s['amountunit'] = array_unique($s['amountunit']);
		foreach($s['amountunit'] as &$u){
			$u = Product::AmountUnits($u);
		}
		unset($u);
		$s['amountunit'] = implode('/', $s['amountunit']);
	}
	unset($s);

	$table = $db->select_table('bankaccount');
	$bankaccounts = $table->fetch_all('*');

	include view('productstorage');
}

?>
