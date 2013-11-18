<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$data = isset($_GET['data']) && $_GET['data'] == 'format' ? 'format' : 'component';
$actions = array('list', 'edit', 'delete');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($data == 'format'){
	$db->select_table('addressformat');

	if($action == 'edit'){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		@$format = array(
			'name' => $_POST['name'],
			'displayorder' => intval($_POST['displayorder']),
		);

		if($id > 0){
			$db->UPDATE($format, 'id='.$id);
		}else{
			$db->INSERT($format);
			$id = $db->insert_id();
		}

		$format['id'] = $id;
		echo json_encode($format);

	}elseif($action == 'delete'){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
		if($id > 0){
			$db->DELETE('id='.$id);
			echo $db->affected_rows();
		}else{
			echo 0;
		}

	}else{
		$address_format = Address::Format();
		include view('address_format');
	}

}else{
	$db->select_table('addresscomponent');

	if($_POST){
		if($action == 'edit'){
			$component = array();

			@$id = intval($_REQUEST['id']);
			if($id > 0){
				if(!empty($_POST['name'])){
					$component['name'] = $_POST['name'];
				}
				if(isset($_POST['displayorder'])){
					$component['displayorder'] = intval($_POST['displayorder']);
				}

				$db->UPDATE($component, 'id='.$id);
				$component['id'] = $id;

			}else{
				@$component = array(
					'name' => $_POST['name'],
					'displayorder' => intval($_POST['displayorder']),
					'parentid' => intval($_GET['parentid']),
				);

				$parent_format = $db->RESULTF('formatid', 'id='.$component['parentid']);
				
				$format = Address::Format();
				while($format && $format[0]['id'] != $parent_format){
					array_shift($format);
				}
				if(array_key_exists(1, $format)){
					$component['formatid'] = $format[1]['id'];
				}

				$db->select_table('addresscomponent');
				$db->INSERT($component);
				$component['id'] = $db->insert_id();
			}

			echo json_encode($component);

		}elseif($action == 'delete'){
			$id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
			$db->DELETE('id='.$id);
			echo $db->affected_rows();
		}

		exit;
	}

	$parentid = !empty($_GET['id']) ? intval($_GET['id']) : 0;

	$prev_address = array();
	$cur = $parentid;
	while($cur){
		$a = $db->FETCH('id,name,parentid', 'id='.$cur);
		$prev_address[] = $a;
		$cur = $a['parentid'];
	}
	$prev_address = array_reverse($prev_address);

	$address_components = $db->MFETCH('*', 'parentid='.$parentid.' ORDER BY displayorder,id');

	include view('address_component');
}

?>
