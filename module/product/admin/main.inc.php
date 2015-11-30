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

class ProductMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		$this->listAction();
	}

	public function listAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$condition = array();
		$query_string = array();

		$product_types = Product::AvailableTypes();
		if($_G['admin']->producttypes){
			$condition[] = 'type IN ('.$_G['admin']->producttypes.')';
		}

		if(isset($_GET['productname'])){
			$productname = addslashes(trim($_GET['productname']));
			$condition[] = 'name LIKE \'%'.$productname.'%\'';
			$query_string['productname'] = $productname;
		}

		if(!empty($_GET['type'])){
			$type = intval($_GET['type']);
			if(isset($product_types[$type])){
				$condition[] = 'type='.$type;
				$query_string['type'] = $type;
			}else{
				$type = 0;
			}
		}else{
			$type = 0;
		}

		$limit = 20;
		$offset = ($page - 1) * $limit;

		$condition = $condition ? implode(' AND ', $condition) : '1';
		$table = $db->select_table('product');
		$products = $table->fetch_all('*', $condition.' ORDER BY hide,type,displayorder LIMIT '.$offset.','.$limit);
		$pagenum = $table->result_first('COUNT(*)', $condition);

		include view('list');
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
				if($_G['admin']->producttypes){
					$typeids = explode(',', $_G['admin']->producttypes);
					if(!in_array($product->type, $typeids))
						exit('permission denied');
				}
			}

			if(isset($_POST['name'])){
				$product->name = $_POST['name'];
			}

			if(isset($_POST['type'])){
				$typeid = intval($_POST['type']);

				$types = Product::Types();
				if($_G['admin']->producttypes){
					$types = explode(',', $_G['admin']->producttypes);
					$types = array_flip($types);
				}

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
			$product = $this->getProductById($productid);
			$prices = $product->getPrices();
			$countdowns = $product->getCountdowns();
			$storages = $product->getStorages();
			$product = $product->toArray();

			$table = $db->select_table('productpricelimit');
			$pricelimits = $table->fetch_all('*', 'productid='.$product['id']);

			include view('edit');
		}
	}

	public function deleteAction(){
		$id = !empty($_POST['id']) ? max(0, intval($_POST['id'])) : 0;
		if($id > 0){
			$extra = '';
			if($_G['admin']->producttypes){
				$extra = 'type IN ('.$_G['admin']->producttypes.')';
			}
			Product::Delete($id, $extra);
		}
		echo 1;
	}

	public function editpriceAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->editPrice($_POST));
	}

	public function deletepriceAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->deletePrice($_POST['id']));
	}

	public function editcountdownAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->editCountdown($_POST));
	}

	public function deletecountdownAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->deleteCountdown($_POST['id']));
	}

	public function editstorageAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->editStorage($_POST));
	}

	public function deletestorageAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$product = $this->getProductById($_GET['productid']);
		echo json_encode($product->deleteStorage($_POST['id']));
	}

	public function editPriceLimitAction(){
		if(empty($_GET['productid']))
			exit('access denied');
		$productid = intval($_GET['productid']);
		$product = $this->getProductById($productid);
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

		$attrs = array();
		if(isset($_POST['priceid'])){
			$attrs['priceid'] = intval($_POST['priceid']);
		}
		if(isset($_POST['usergroupid'])){
			$attrs['usergroupid'] = intval($_POST['usergroupid']);
		}

		global $db;
		$table = $db->select_table('productpricelimit');
		if($id > 0){
			$table->update($attrs, array('id' => $id, 'productid' => $productid));
			$attrs['id'] = $id;
		}else{
			$attrs['productid'] = $productid;
			$table->insert($attrs);
			$attrs['id'] = $table->insert_id();
		}
		echo json_encode($attrs);
		exit;
	}

	public function deletePriceLimitAction(){
		if(empty($_GET['productid']) || empty($_POST['id']))
			exit('access denied');

		$productid = intval($_GET['productid']);
		$product = $this->getProductById($productid);

		$id = intval($_POST['id']);
		global $db;
		$table = $db->select_table('productpricelimit');
		$table->delete(array('id' => $id, 'productid' => $productid));
		echo $table->affected_rows();
		exit;
	}

	protected function getProductById($productid){
		global $_G;
		$product = new Product($productid);
		if($_G['admin']->producttypes){
			$typeids = explode(',', $_G['admin']->producttypes);
			if(!in_array($product->type, $typeids))
				exit('permission denied');
		}
		return $product;
	}
}

?>
