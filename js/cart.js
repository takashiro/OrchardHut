
function cart_read(){
	var in_cart = getcookie('in_cart').split(',');
	var cart = {};
	for(var i = 0; i < in_cart.length; i++){
		var item = in_cart[i].split('=');
		var price_id = parseInt(item[0], 10);
		var number = parseInt(item[1], 10);
		if(!isNaN(price_id) && !isNaN(number)){
			cart[price_id] = number;
		}
	}
	return cart;
}

function cart_write(cart){
	var in_cart = [];
	for(var i in cart){
		if(!isNaN(i) && !isNaN(cart[i]) && cart[i] > 0){
			in_cart.push(i + '=' + cart[i]);
		}
	}
	setcookie('in_cart', in_cart.join(','));
}

function cart_set(price_id, number){
	var cart = cart_read();
	cart[price_id] = number;
	cart_write(cart);
	$('#cart-goods-number').html(cart_number());
}

$(function(){
	var cart = cart_read();
	for(var price_id in cart){
		var number = cart[price_id];
		$('li[price-id=' + price_id + '] .order_input input').val(number);
	}

	$('.cart .product_list button.remove').click(function(e){
		var button = $(e.target);
		var li = button.parent().parent();
		var price_id = parseInt(li.attr('price-id'), 10);
		if(!isNaN(price_id)){
			var subtotal = parseFloat(li.find('.subtotal .number').html());
			var total_price = parseFloat($('#total_price').html());
			total_price -= subtotal;
			$('#total_price').html(total_price);

			cart_set(price_id, 0);
			li.remove();
		}
	});

	$('#cart-goods-number').html(cart_number());

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
});
