<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete');
$action = &$_GET['action'];
if(!in_array($action, $actions)){
	$action = $actions[0];
}

$db->select_table('administrator');

$id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

switch($action){
	case 'edit':
		$admin = new Administrator($id);
		if($admin->isSuperAdmin()){
			showmsg('illegal_operation', 'back');
		}
		$id = $admin->id;

		if($_POST){
			if(!empty($_POST['password']) && $_POST['password'] != $_POST['password2']){
				showmsg('two_different_passwords', 'back');
			}

			if($id <= 0){
				@$new_admin = array(
					'account' => $_POST['account'],
					'password' => $_POST['password'],
				);
				$new_id = Administrator::Register($new_admin);
				if($new_id <= 0){
					showmsg('duplicated_account', 'back');
				}

				$admin = new Administrator($new_id);
			}

			$admin->clearPermission();
			foreach($_POST['p'] as $permission => $value){
				if($_G['admin']->hasPermission($permission)){
					$admin->setPermission($permission, true);
				}
			}

			if(!empty($_POST['password'])){
				$admin->pwmd5 = rmd5($_POST['password']);
			}

			if(!empty($_POST['nickname'])){
				$admin->nickname = trim($_POST['nickname']);
			}

			if(!empty($_POST['realname'])){
				$admin->realname = trim($_POST['realname']);
			}

			if(!empty($_POST['mobile'])){
				$admin->mobile = trim($_POST['mobile']);
			}

			if(isset($_POST['limitation'])){
				if(is_array($_POST['limitation'])){
					$limitation = array();
					foreach($_POST['limitation'] as $components){
						$components = explode(',', $components);
						do{
							$componentid = array_pop($components);
							$componentid = intval($componentid);
						}while($components && empty($componentid));

						if($componentid){
							$limitation[] = $componentid;
						}
					}

					$limitation = array_unique($limitation);
					$admin->limitation = implode(',', $limitation);
				}else{
					$admin->limitation = '';
				}
			}

			redirect($mod_url);
		}

		$a = $admin->toArray();

		$address_format = Address::Format();
		$address_components = Address::Components();
		foreach($address_format as $format){
			array_unshift($address_components, array('id' => 0, 'formatid' => $format['id'], 'name' => '不限', 'parentid' => 0));
		}

		include view('admin_edit');
	break;

	case 'delete':
		if($id <= 0){
			showmsg('illegal_operation');
		}

		if(!empty($_GET['confirm'])){
			Administrator::Delete($id);
			redirect($mod_url);
		}else{
			showmsg('confirm_to_delete_administrator', 'confirm');
		}

	break;

	case 'list':default:
		$limit = 20;
		$offset = ($page - 1) * $limit;
		$admins = $db->MFETCH('*', "1 LIMIT $offset,$limit");
		$pagenum = $db->RESULTF('COUNT(*)');
		include view('admin_list');
	break;
}

?>
