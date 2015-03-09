<?php

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

		$db->query("UPDATE {$tpre}productstorage SET num=num+$amount WHERE id=$storageid");
		if($db->affected_rows() > 0){
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
			);
			$totalcosts += $subtotalcosts;
		}
	}

	if($logs){
		$db->query("UPDATE {$tpre}bankaccount SET amount=amount-$totalcosts WHERE id=$bankaccount AND amount>=$totalcosts");
		if($db->affected_rows() > 0){
			$db->query('COMMIT');
			$db->select_table('productstoragelog');
			$db->INSERTS($logs);
			showmsg('storage_is_updated', 'refresh');
		}else{
			$db->query('ROLLBACK');
			showmsg('insufficient_bank_account', 'refresh');
		}
	}

	showmsg('no_storage_need_updating', 'refresh');
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
	$query = $db->query("SELECT storageid,subtype,amountunit,priceunit FROM {$tpre}productprice WHERE storageid IS NOT NULL AND storageid IN ($storageids)");
	while($p = $db->fetch_row($query)){
		$s = &$storageHash[$p[0]];
		$s['subtype'][] = $p[1];
		$s['amountunit'][] = $p[2];
		$s['priceunit'][] = $p[3];
	}

	foreach($storages as &$s){
		$s['priceunit'] = array_unique($s['priceunit']);
		foreach($s['priceunit'] as &$u){
			$u = Product::PriceUnits($u);
		}
		unset($u);
		$s['priceunit'] = implode('/', $s['priceunit']);

		$s['amountunit'] = array_unique($s['amountunit']);
		foreach($s['amountunit'] as &$u){
			$u = Product::AmountUnits($u);
		}
		unset($u);
		$s['amountunit'] = implode('/', $s['amountunit']);
	}
	unset($s);

	$db->select_table('bankaccount');
	$bankaccounts = $db->MFETCH('*');

	include view('productstorage');
}

?>
