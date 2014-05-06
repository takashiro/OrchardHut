
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

	$('#cart-goods-number').numbernotice(cart_number());
}

function cart_number(){
	var in_cart = getcookie('in_cart');
	if(in_cart == ''){
		return 0;
	}else{
		return in_cart.split(',').length;
	}
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

	var delivered_num = parseInt(getcookie('delivering-order-number'), 10);
	var cache_time = parseInt(getcookie('order-number-cache-time'), 10);
	var current_time = parseInt(new Date().valueOf() / 1000);
	if(isNaN(delivered_num) || isNaN(cache_time) || current_time - cache_time >= 1800){
		$('#delivering-order-number').numbernotice(0);
		$.post('home.php?action=deliveringnum', {}, function(data){
			delivered_num = parseInt(data, 10);
			if(!isNaN(delivered_num)){
				setcookie('delivering-order-number', delivered_num);
				setcookie('order-number-cache-time', current_time);
				$('#delivering-order-number').numbernotice(delivered_num);
			}
		});
	}else{
		$('#delivering-order-number').numbernotice(delivered_num);
	}
});
