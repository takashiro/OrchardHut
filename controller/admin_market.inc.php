<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('list', 'edit', 'delete', 'editprice', 'deleteprice', 'editcountdown', 'deletecountdown', 'editstorage', 'deletestorage');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

switch($action){
	case 'list':
		$db->select_table('product');
		$products = $db->MFETCH('*', '1 ORDER BY type,hide,displayorder');
		include view('market');
	break;

	case 'edit':
		$productid = !empty($_REQUEST['id']) ? max(0, intval($_REQUEST['id'])) : 0;

		if($_POST){
			if($productid == 0){
				if(empty($_POST['name'])){
					showmsg('please_fill_in_product_name', 'back');
				}

				$product = new Product;
				$product->name = $_POST['name'];
				$product->type = !empty($_POST['type']) ? 1 : 0;
				$product->insert();
			}else{
				$product = new Product($productid);

				if(isset($_POST['name'])){
					$product->name = $_POST['name'];
				}

				if(isset($_POST['type'])){
					$typeid = intval($_POST['type']);
					$types = Product::Types();
					if(array_key_exists($typeid, $types)){
						$product->type = $typeid;
					}
				}

				if(isset($_POST['displayorder'])){
					$product->displayorder = intval($_POST['displayorder']);
				}

				if(isset($_POST['hide'])){
					$product->hide = !empty($_POST['hide']);
				}

				foreach(array('text_color', 'background_color', 'icon_background') as $attr){
					if(isset($_POST[$attr])){
						$product->$attr = hexdec($_POST[$attr]);
					}
				}

				if(isset($_POST['introduction'])){
					$product->introduction = $_POST['introduction'];
				}
			}

			$product->uploadImage('icon');
			$product->uploadImage('photo');

			if(!empty($_GET['ajax'])){
				echo json_encode($product->toArray());
			}else{
				showmsg('edit_succeed', 'refresh');
			}

		}else{
			$product = new Product($productid);
			$prices = $product->getPrices();
			$countdowns = $product->getCountdowns();
			$storages = $product->getStorages();
			$product = $product->toArray();
			include view('market_edit');
		}
	break;

	case 'delete':
		$id = !empty($_POST['id']) ? max(0, intval($_POST['id'])) : 0;
		if($id > 0){
			Product::Delete($id);
		}
		echo 1;
	break;

	case 'editprice':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editPrice($_POST));
	break;

	case 'deleteprice':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deletePrice($_POST['id']));
	break;

	case 'editcountdown':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editCountdown($_POST));
	break;

	case 'deletecountdown':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteCountdown($_POST['id']));
	break;

	case 'editstorage':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editStorage($_POST));
	break;

	case 'deletestorage':
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteStorage($_POST['id']));
	break;
}

?>
