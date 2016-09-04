
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

function updateProductPrice(product_list){
	var new_total = 0.0;
	product_list.children().each(function(){
		var $this = $(this);
		var price = parseFloat($this.find('.price').text());
		var num = parseInt($this.find('input.number').val(), 10);
		var subtotal = isNaN(price) || isNaN(num) ? 0 : price * num;
		new_total += subtotal;
	});

	var input = $('#product_price');
	input.val(new_total.toFixed(2));
	input.change();
}

$(function(){

	$('ul.product button.remove').click(function(e){
		var button = $(e.target);
		var li = button.parent();
		var price_id = parseInt(li.data('price-id'), 10);
		if(!isNaN(price_id)){
			var product_list = li.parent();
			ShoppingCart.setItem(price_id, 0);
			li.remove();
			updateProductPrice(product_list);
		}
	});

	$('ul.product .order_input input').change(function(e){
		var input = $(e.target);
		var numberbox = input.parent();
		var li = numberbox.parent().parent();
		var rule = li.parent();
		var product_list = rule.parent().parent();
		updateProductPrice(product_list);
	});

	$('#product_price').change(function(){
		var product_price = parseFloat($(this).val());
		var delivery_fee = parseFloat($('#deliveryfee').val());
		var total_price = product_price + delivery_fee;
		$('#total_price').text(total_price.toFixed(2));
		$('#total_price').change();
	});

	$('.deliveryaddress ul li a.remove').click(function(e){
		e.preventDefault();
		if(confirm('您确认要删除该收货地址吗？')){
			var button = $(e.target);
			var li = button.parent();
			var address_id = li.children('input').val();

			var data = {
				'action' : 'deleteaddress',
				'address_id' : address_id
			};
			$.post('index.php?mod=product:cart', data, function(response){
				li.remove();
			});
		}
	});

	$('input[type="radio"][name="deliveryaddressid"]').change(function(){
		if(parseInt($(this).val(), 10) == 0){
			$('#new_address_table').show();
		}else{
			$('#new_address_table').hide();
		}
	});

	$('input[type="radio"][name="deliverymethod"]').change(function(){
		var deliverymethod = $(this).val();
		var product_price = parseFloat($('#product_price').val());
		var config = DeliveryConfig[deliverymethod];
		var delivery_fee = 0;
		if(config.fee > 0 && product_price < config.maxorderprice){
			delivery_fee = config.fee;
			$('#deliveryfee').val(delivery_fee.toFixed(2));
		}else{
			$('#deliveryfee').val('0');
		}
		var total_price = product_price + delivery_fee;
		$('#total_price').text(total_price.toFixed(2));
		$('#total_price').change();
	});

	$('#total_price').change(function(){
		$('input[name="paymentmethod"]').each(function(){
			if($(this).val() == Order.PaidWithWallet){
				var userwallet = parseFloat($('#userwallet').text());
				var total_price = parseFloat($('#total_price').text());
				if(userwallet < total_price){
					if($(this).is(':checked')){
						alert('钱包余额不足，请重新选择支付方式。');
						$(this).prop('checked', false);
					}
					$(this).prop('disabled', true);
				}else{
					$(this).prop('disabled', false);
				}
			}
		});
	});

	$('input[name="paymentmethod"]').click(function(){
		if($(this).is(':disabled')){
			alert('余额不足！');
		}
	});

	updateProductPrice($('ul.product'));
});
