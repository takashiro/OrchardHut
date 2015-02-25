<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = &$_GET['action'];
if(!in_array($action, $actions)){
	$action = $actions[0];
}

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

	case 'list':default:
		$limit = 20;
		$offset = ($page - 1) * $limit;
		$accounts = $db->MFETCH('*', "1 LIMIT $offset,$limit");
		$pagenum = $db->RESULTF('COUNT(*)');
		include view('bankaccount_list');
	break;
}

?>
