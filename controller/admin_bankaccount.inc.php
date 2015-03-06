<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$action = &$_GET['action'];

$db->select_table('bankaccount');

$id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

switch($action){
	case 'edit':
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		if($_POST){
			$bankaccount = array();

			if(isset($_POST['remark'])){
				$bankaccount['remark'] = trim($_POST['remark']);
			}

			if(isset($_POST['addressrange'])){
				$components = explode(',', $_POST['addressrange']);
				do{
					$componentid = array_pop($components);
					$componentid = intval($componentid);
				}while($components && empty($componentid));

				if($componentid){
					$bankaccount['addressrange'] = $componentid;
				}
			}

			if($id > 0){
				$db->UPDATE($bankaccount, 'id='.$id);
			}else{
				$db->INSERT($bankaccount);
			}

			showmsg('edit_succeed', $mod_url);
		}


		if($id > 0){
			$a = $db->FETCH('*', 'id='.$id);
		}else{
			$a = array(
				'id' => 0,
				'remark' => '',
				'amount' => 0,
				'addressrange' => 0,
			);
		}

		$address_format = Address::Format();
		$address_components = Address::Components();
		foreach($address_format as $format){
			array_unshift($address_components, array('id' => 0, 'formatid' => $format['id'], 'name' => '不限', 'parentid' => 0));
		}

		include view('bankaccount_edit');
	break;

	case 'delete':
		if($id <= 0){
			showmsg('illegal_operation');
		}

		if(!empty($_GET['confirm'])){
			Administrator::Delete($id);
			redirect($mod_url);
		}else{
			showmsg('您确认删除该资金账户吗？', 'confirm');
		}

	break;

	case 'transfer':
		$accounts = array();
		$account_amount = array();
		foreach($db->MFETCH('id,remark,amount') as $a){
			$accounts[$a['id']] = $a['remark'];
			$account_amount[$a['id']] = $a['amount'];
		}
		unset($a);

		$sourceaccount = isset($_REQUEST['sourceaccount']) ? intval($_REQUEST['sourceaccount']) : 0;
		$targetaccount = isset($_REQUEST['targetaccount']) ? intval($_REQUEST['targetaccount']) : 0;

		if($_POST && isset($accounts[$sourceaccount]) && $accounts[$targetaccount]){
			$delta = floatval($_POST['delta']);
			if($account_amount[$sourceaccount] < $delta){
				showmsg('insufficient_source_account', 'back');
			}elseif($delta <= 0){
				showmsg('are_you_kidding_me', 'back');
			}

			//@todo: Begin Transaction
			$db->query("UPDATE {$tpre}bankaccount SET amount=amount-$delta WHERE id=$sourceaccount AND amount>=$delta");
			if($db->affected_rows() > 0){
				$db->query("UPDATE {$tpre}bankaccount SET amount=amount+$delta WHERE id=$targetaccount");

				$log = array(
					'accountid' => $sourceaccount,
					'delta' => $delta,
					'reason' => isset($_POST['reason']) ? $_POST['reason'] : '',
					'operatorid' => $_G['admin']->id,
					'targetaccountid' => $targetaccount,
					'dateline' => TIMESTAMP,
				);
				$db->select_table('bankaccountlog');
				$db->INSERT($log);
			}
			//@todo: End Transaction, commit

			showmsg('successfully_transfered', 'refresh');
		}

		include view('bankaccount_transfer');
	break;

	case 'list':default:
		$limit = 20;
		$offset = ($page - 1) * $limit;
		$accounts = $db->MFETCH('*', "1 LIMIT $offset,$limit");
		$pagenum = $db->RESULTF('COUNT(*)');
		include view('bankaccount_list');
	break;
}

?>
