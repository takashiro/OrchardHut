
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

	$('.cart .product_list button.remove').click(function(e){
		var button = $(e.target);
		var li = button.parent().parent();
		var price_id = parseInt(li.data('price-id'), 10);
		if(!isNaN(price_id)){
			var subtotal = parseFloat(li.find('.subtotal .number').html());
			var total_price = parseFloat($('#total_price').html());
			total_price -= subtotal;
			$('#total_price').text(total_price.toFixed(2));
			$('#total_price').change();

			cart_set(price_id, 0);
			li.remove();
		}
	});

	$('.cart .product_list .order_input input').change(function(e){
		var input = $(e.target);
		var numberbox = input.parent();
		var li = numberbox.parent();
		var rule = li.parent();
		var detail = rule.parent();
		var subtotal = detail.children('.subtotal');

		var new_input = parseInt(input.val(), 10);
		var new_subtotal = isNaN(new_input) ? 0 : new_input * parseFloat(li.children('.price').text());
		subtotal.children('.number').text(new_subtotal.toFixed(2));

		var new_total = 0.0;
		$('.cart .product_list .subtotal').each(function(){
			new_total += parseFloat($(this).children('.number').text());
		});
		$('#total_price').text(new_total.toFixed(2));
		$('#total_price').change();
	});

	$('#cart-goods-number').numbernotice(cart_number());

	$('.deliveryaddress ul li a.remove').click(function(e){
		if(confirm('您确认要删除该收货地址吗？')){
			var button = $(e.target);
			var li = button.parent();
			var address_id = li.children('input').val();

			var data = {
				'action' : 'deleteaddress',
				'address_id' : address_id
			};
			$.post('cart.php', data, function(response){
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

	$('input[type="radio"][name="paymentmethod"]:first').parent().click();

	$('#total_price, input[name="deliverymethod"]').change(function(){
		var deliverymethod = $('input[name="deliverymethod"]:checked').val();
		var totalprice = parseFloat($('#total_price').text());
		var df = DeliveryConfig[deliverymethod];
		if(df.fee > 0 && totalprice < df.maxorderprice){
			$('#deliveryfee').text(df.fee);
		}else{
			$('#deliveryfee').text('0');
		}
	});

	$('#total_price').change(function(){
		$('input[name="paymentmethod"]').each(function(){
			if($(this).val() == Order.PaidWithWallet){
				var userwallet = parseFloat($('#userwallet').text());
				var totalprice = parseFloat($('#total_price').text());
				if(userwallet < totalprice){
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

	$('#total_price').change();

});
