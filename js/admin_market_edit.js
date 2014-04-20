$(function(){
	$('#price_list').editlist({
		'edit' : mod_url + '&action=editprice&productid=' + product_id,
		'delete' : mod_url + '&action=deleteprice&productid=' + product_id,
		'attr' : ['id', 'subtype', 'price', 'priceunit', 'amount', 'amountunit', 'storageid', 'quantitylimit', 'displayorder'],
		'buttons' : {'delete':'删除'}
	});

	$('#countdown_list').editlist({
		'edit' : mod_url + '&action=editcountdown&productid=' + product_id,
		'delete' : mod_url + '&action=deletecountdown&productid=' + product_id,
		'attr' : ['masked_priceid', 'subtype', 'price', 'priceunit', 'amount', 'amountunit', 'start_time', 'end_time', 'storageid', 'quantitylimit', 'displayorder'],
		'buttons' : {'delete':'删除'}
	});

	$('#storage_list').editlist({
		'edit' : mod_url + '&action=editstorage&productid=' + product_id,
		'delete' : mod_url + '&action=deletestorage&productid=' + product_id,
		'attr' : ['id', 'remark', 'num', 'addnum'],
		'buttons' : {'delete':'删除'}
	});

	$('input.color').spectrum({
		'chooseText' : '选择',
		'cancelText' : '取消',
		'preferredFormat' : 'hex',
		'showInput' : true
	});

	$('#price_list tr:not(:last-child)').click(function(){
		var id = $(this).children('td:first-child').html();
		$('#countdown_list tr:last-child td:first-child input').val(id);
	});

	$('#storage_list tr:not(:last-child)').click(function(){
		var id = $(this).children('td:first-child').html();
		$('input.storageid').val(id);
	});
});
