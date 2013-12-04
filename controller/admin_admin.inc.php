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
			showmsg('非法操作。', 'back');
		}
		$id = $admin->id;

		if($_POST){
			if(!empty($_POST['password']) && $_POST['password'] != $_POST['password2']){
				showmsg('两次输入的密码不一致。', 'back');
			}

			if($id <= 0){
				@$new_admin = array(
					'account' => $_POST['account'],
					'password' => $_POST['password'],
				);
				$new_id = Administrator::Register($new_admin);
				if($new_id <= 0){
					showmsg('您输入的登录名已经被使用，请重新输入一个登录用户名。', 'back');
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

			if(!empty($_POST['formatid'])){
				$admin->formatid = intval($_POST['formatid']);
			}

			if(!empty($_POST['componentid'])){
				$admin->componentid = intval($_POST['componentid']);
			}

			if(!empty($_POST['limitation'])){
				$limitation = explode(',', $_POST['limitation']);
				foreach($limitation as $format_order => $componentid){
					$componentid = intval($componentid);
					if($componentid <= 0){
						$format_order--;
						break;
					}

					$admin->componentid = $componentid;
				}

				if($format_order < 0){
					$admin->formatid = 0;
					$admin->componentid = 0;
				}else{
					$admin->formatid = $db->result_first("SELECT id FROM {$tpre}addressformat ORDER BY displayorder LIMIT $format_order,1");
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
			showmsg('非法操作。');
		}

		if(!empty($_GET['confirm'])){
			Administrator::Delete($id);
			redirect($mod_url);
		}else{
			showmsg('您确认要删除该管理员吗？', 'confirm');
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
