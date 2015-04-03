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

require_once './core/init.inc.php';

$type = isset($_GET['type']) ? intval($_GET['type']) : -1;
$all_types = Product::Types();
isset($all_types[$type]) || $type = current(array_keys($all_types));
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
}
unset($p);

Product::FetchFilteredPrices($products);

$announcements = Announcement::GetActiveAnnouncements();

$priceids = array();
foreach($products as $product){
	foreach($product['rule'] as $price){
		$priceids[] = $price['id'];
	}
}
$quantity_limit = array();
if($_G['user']->isLoggedIn()){
	$query = $db->query("SELECT priceid,amount FROM {$tpre}productquantitylimit WHERE userid={$_USER['id']}");
	while($l = $query->fetch_assoc()){
		$quantity_limit[intval($l['priceid'])] = intval($l['amount']);
	}
}

include view('market');

?>
