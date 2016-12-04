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

if(!defined('S_ROOT')) exit('access denied');

if(isset($_GET['referrerid']) && !$_G['user']->isLoggedIn()){
	$referrerid = intval($_GET['referrerid']);
	rsetcookie('referrerid', $referrerid);
}

$type = isset($_GET['type']) ? intval($_GET['type']) : -1;
$all_types = Product::Types();
if($all_types){
	isset($all_types[$type]) || $type = current(array_keys($all_types));
}else{
	$type = 0;
}
unset($all_types);

$table = $db->select_table('product');
$products = $table->fetch_all('*', 'type='.$type.' AND hide=0 ORDER BY displayorder');

$product = new Product;
foreach($products as &$p){
	foreach($p as $attr => $value){
		$product->$attr = $value;
	}
	$p = $product->toArray();
	$p['introduction'] = str_replace(array("\r\n", "\n", "\r"), '<br />', $p['introduction']);
	$p['flowname'] = Product::Flow($p['flowid']);
}
unset($p);

Product::FetchFilteredPrices($products);

$announcements = Announcement::GetActiveAnnouncements();

$priceids = array();
$storageids = array();
foreach($products as $product){
	foreach($product['rule'] as $price){
		$priceids[] = $price['id'];
		if($price['storageid']){
			$storageids[] = $price['storageid'];
		}
	}
}

//取得价格限定的用户组
$price_limit = Product::PriceLimits($priceids);

//取得产品库存信息
$quantity_limit = Product::QuantityLimits($priceids);

//取得产品限购数据
$product_storages = Product::Storages($storageids);

include view('market');
