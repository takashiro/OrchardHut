<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class MarketModule extends AdminControlPanelModule{

	public function defaultAction(){
		$this->listAction();
	}

	public function listAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

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
		$products = $table->fetch_all('*', $condition.' ORDER BY hide,type,displayorder LIMIT '.$offset.','.$limit);
		$pagenum = $table->result_first('COUNT(*)', $condition);

		include view('market');
	}

	public function editAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$productid = !empty($_REQUEST['id']) ? max(0, intval($_REQUEST['id'])) : 0;

		if($_POST){
			if($productid == 0){
				if(empty($_POST['name'])){
					showmsg('please_fill_in_product_name', 'back');
				}

				$product = new Product;
			}else{
				$product = new Product($productid);
			}

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

			if($productid == 0){
				$product->insert();
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
	}

	public function deleteAction(){
		$id = !empty($_POST['id']) ? max(0, intval($_POST['id'])) : 0;
		if($id > 0){
			Product::Delete($id);
		}
		echo 1;
	}

	public function editpriceAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editPrice($_POST));
	}

	public function deletepriceAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deletePrice($_POST['id']));
	}

	public function editcountdownAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editCountdown($_POST));
	}

	public function deletecountdownAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteCountdown($_POST['id']));
	}

	public function editstorageAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->editStorage($_POST));
	}

	public function deletestorageAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = new Product;
		$product->id = intval($_GET['productid']);
		echo json_encode($product->deleteStorage($_POST['id']));
	}
}

?>
