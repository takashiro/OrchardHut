<?php

require_once './core/init.inc.php';

$products = array();

$products[] = array(
	'id' => 1,
	'name' => '火龙果',
	'icon' => './data/attachment/product_1_icon.png',
	'icon_background' => 'lightblue',
	'soldout' => 12345,
	'rule' => array(
		array(
			'price' => 2.5,
			'priceunit' => '元',
			'amount' => 1,
			'amountunit' => '个',
		),
		array(
			'price' => 3.5,
			'priceunit' => '元',
			'amount' => 2,
			'amountunit' => '个',
		)
	)
);

$products[] = array(
	'id' => 2,
	'name' => '梨',
	'icon' => './data/attachment/product_2_icon.png',
	'icon_background' => 'lightgreen',
	'soldout' => 12345,
	'rule' => array(
		array(
			'price' => 2.5,
			'priceunit' => '元',
			'amount' => 1,
			'amountunit' => '个',
		),
		array(
			'price' => 3.5,
			'priceunit' => '元',
			'amount' => 2,
			'amountunit' => '个',
		)
	)
);

include view('market');

?>
