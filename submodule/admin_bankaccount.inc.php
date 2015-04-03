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

$table = $db->select_table('bankaccount');

$id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

switch($action){
	case 'edit':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		if($_POST){
			$bankaccount = new BankAccount($id);

			if(isset($_POST['remark'])){
				$bankaccount->remark = trim($_POST['remark']);
			}

			if(isset($_POST['addressrange'])){
				$components = explode(',', $_POST['addressrange']);
				do{
					$componentid = array_pop($components);
					$componentid = intval($componentid);
				}while($components && empty($componentid));

				if($componentid){
					$bankaccount->addressrange = $componentid;
				}
			}

			if($id <= 0){
				$bankaccount->insert();
			}

			showmsg('edit_succeed', $mod_url);
		}


		$a = new BankAccount($id);

		$address_format = Address::Format();
		$address_components = Address::Components();
		foreach($address_format as $format){
			array_unshift($address_components, array('id' => 0, 'formatid' => $format['id'], 'name' => '不限', 'parentid' => 0));
		}

		$a = $a->toReadable();
		include view('bankaccount_edit');
	break;

	case 'delete':
		if($id <= 0){
			showmsg('illegal_operation');
		}

		if(!empty($_GET['confirm'])){
			BankAccount::Delete($id);
			redirect($mod_url);
		}else{
			showmsg('confirm_to_delete_bank_account', 'confirm');
		}

	break;

	case 'transfer':
		$sourceaccount = isset($_REQUEST['sourceaccount']) ? intval($_REQUEST['sourceaccount']) : 0;
		$targetaccount = isset($_REQUEST['targetaccount']) ? intval($_REQUEST['targetaccount']) : 0;

		if($_POST){
			$delta = floatval($_POST['delta']);
			if($delta <= 0){
				showmsg('the_number_you_must_be_kidding_me', 'back');
			}

			$bankaccount = new BankAccount($sourceaccount);
			$result = $bankaccount->transferTo($targetaccount, $delta, isset($_POST['reason']) ? $_POST['reason'] : '');

			if($result === true){
				showmsg('successfully_transfered', 'refresh');
			}else{
				switch($result){
				case BankAccount::ERROR_INVALID_INSUFFICIENT_AMOUNT:
					showmsg('source_account_is_insufficient', 'back');
				case BankAccount::ERROR_TARGET_NOT_EXIST:
					showmsg('target_does_not_exist', 'back');
				case BankAccount::ERROR_INVALID_ARGUMENT:
					showmsg('invalid_argument_received', 'back');
				default:
					showmsg('unknown_error', 'back');
				}
			}
		}

		$accounts = array();
		$account_amount = array();
		foreach($table->fetch_all('id,remark,amount') as $a){
			$accounts[$a['id']] = $a['remark'];
			$account_amount[$a['id']] = $a['amount'];
		}
		unset($a);

		include view('bankaccount_transfer');
	break;

	case 'list':default:
		$limit = 20;
		$offset = ($page - 1) * $limit;
		$accounts = $table->fetch_all('*', "1 LIMIT $offset,$limit");
		$pagenum = $table->result_first('COUNT(*)');
		include view('bankaccount_list');
	break;
}

?>
