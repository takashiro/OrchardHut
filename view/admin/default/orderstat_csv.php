<?php

if(!defined('IN_ADMINCP')) exit('access denied');

rheader('Cache-Control: no-cache, must-revalidate');
rheader('Content-Type: application/octet-stream');
rheader('Content-Disposition: attachment; filename="'.$_CONFIG['sitename'].'订单分拣报表('.rdate(TIMESTAMP, 'Y-m-d His').').csv"');

//UTF-8 BOM
echo chr(0xEF), chr(0xBB), chr(0xBF);

//表头
echo '产品';
foreach($top_address_list as $address){
	echo ',', $address;
}
echo "\r\n";

//统计结果
foreach($stat_list as $product_id => $subtypes){
	foreach($subtypes as $subtype => $items){
		echo $product_list[$product_id];
		if(!empty($subtype)){
			echo '(', $subtype, ')';
		}

		foreach($top_address_list as $address_id => $address_name){
			echo ',', isset($items[$address_id]) ? $items[$address_id]['totalnum'] : 0;
		}
		echo "\r\n";
	}
}

?>
