
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

function updateTotalPrice(){
	var new_total = 0.0;
	$('.flow-area').each(function(){
		var subtotal = parseFloat($(this).data('subtotal'));
		if(!isNaN(subtotal)){
			new_total += subtotal;
		}
		var deliveryfee = parseFloat($(this).data('deliveryfee'));
		if(!isNaN(deliveryfee)){
			new_total += deliveryfee;
		}
	});
	$('#total-price').text(new_total.toFixed());

	$('input[name="paymentmethod"]').each(function(){
		if($(this).val() == Order.PaidWithWallet){
			var userwallet = parseFloat($('#userwallet').text());
			if(userwallet < new_total){
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
}

function updateProductPrice($this){
	var total = 0.0;

	$this.children().each(function(){
		var $this = $(this);
		var price = parseFloat($this.find('.price').text());
		var num = parseInt($this.find('input.number').val(), 10);
		var subtotal = isNaN(price) || isNaN(num) ? 0 : price * num;
		total += subtotal;
	});

	var area = $this.parent();
	area.data('subtotal', total.toFixed(2));
	updateDeliveryFee(area);
}

function updateDeliveryFee(area){
	var input = area.find('.deliverymethod input[type="radio"]');
	if(input.length > 0){
		var deliveryfee = 0;
		var checked_input = input.filter(':checked');
		if(checked_input.length <= 0){
			checked_input = input.eq(0);
			checked_input.prop('checked', true);
		}
		var methodid = checked_input.val();
		var config = DeliveryConfig[methodid];
		if(config && config.fee > 0){
			var subtotal = parseFloat(area.data('subtotal'));
			deliveryfee = subtotal < config.maxorderprice ? config.fee : 0.0;
		}
		area.data('deliveryfee', deliveryfee.toFixed(2));
	}

	updateTotalPrice();
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

	$('.deliverymethod input[type="radio"]').change(function(){
		var area = $(this).parents('.flow-area');
		updateDeliveryFee(area);
	});

	$('input[name="paymentmethod"]').click(function(){
		if($(this).is(':disabled')){
			alert('余额不足！');
		}
	});

	$('ul.product').each(function(){
		updateProductPrice($(this));
	});
});
