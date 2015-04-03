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
empty($action) && $action = 'list';

switch($action){
	case 'list':
		$condition = array();

		if(isset($_GET['productname'])){
			$productname = addslashes(trim($_GET['productname']));
			$condition[] = 'name LIKE \'%'.$productname.'%\'';
		}

		if(!empty($_GET['type'])){
			$_GET['type'] = intval($_GET['type']);
			$condition[] = 'type='.$_GET['type'];
		}

		$limit = 20;
		$offset = ($page - 1) * $limit;

		$condition = $condition ? implode(' AND ', $condition) : '1';
		$table = $db->select_table('product');
		$products = $table->fetch_all('*', $condition.' ORDER BY type,hide,displayorder LIMIT '.$offset.','.$limit);
		$pagenum = $table->result_first('COUNT(*)', $condition);

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
					}else{
						foreach($types as $typeid => $name){
							$product->type = $typeid;
							break;
						}
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

				if(isset($_POST['briefintro'])){
					$product->briefintro = $_POST['briefintro'];
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
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editPrice($_POST));
	break;

	case 'deleteprice':
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deletePrice($_POST['id']));
	break;

	case 'editcountdown':
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editCountdown($_POST));
	break;

	case 'deletecountdown':
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteCountdown($_POST['id']));
	break;

	case 'editstorage':
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editStorage($_POST));
	break;

	case 'deletestorage':
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteStorage($_POST['id']));
	break;
}

?>
