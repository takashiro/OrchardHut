<?php

if(!defined('S_ROOT')) exit('access denied');

if(empty($_GET['full'])){
	foreach($orders as &$o){
		$o = array(
			'id' => intval($o['id']),
			'deliverymethod' => intval($o['deliverymethod']),
			'status' => intval($o['status']),
		);
	}
	unset($o);
}

echo json_encode(array(
	'totalnum' => $pagenum,
	'page' => $page,
	'data' => $orders
));
