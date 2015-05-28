
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

$(function(){
	$('#price_list').editlist({
		'edit' : mod_url + '&action=editprice&productid=' + product_id,
		'delete' : mod_url + '&action=deleteprice&productid=' + product_id,
		'attr' : ['id', 'subtype', 'briefintro', 'price', 'priceunit', 'amount', 'amountunit', 'storageid', 'quantitylimit', 'displayorder'],
		'buttons' : {'delete':'删除'}
	});

	$('#countdown_list').editlist({
		'edit' : mod_url + '&action=editcountdown&productid=' + product_id,
		'delete' : mod_url + '&action=deletecountdown&productid=' + product_id,
		'attr' : ['masked_priceid', 'subtype', 'briefintro', 'price', 'priceunit', 'amount', 'amountunit', 'start_time', 'end_time', 'storageid', 'quantitylimit', 'displayorder'],
		'buttons' : {'delete':'删除'}
	});

	$('#storage_list').editlist({
		'edit' : mod_url + '&action=editstorage&productid=' + product_id,
		'delete' : mod_url + '&action=deletestorage&productid=' + product_id,
		'attr' : ['id', 'remark', 'num', 'mode'],
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
