
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
	$('.order_input input').change(function(e){
		var input = $(e.target);
		var numberbox = input.parent();
		var li = numberbox.parent();

		var data = {
			'product_id' : li.attr('product-id'),
			'price_id' : li.attr('price-id'),
			'number' : parseInt(input.val(), 10)
		};

		cart_set(data.price_id, data.number);
	});

	var cart = cart_read();
	for(var price_id in cart){
		var number = cart[price_id];
		$('li[price-id=' + price_id + '] .order_input input').val(number);
	}

	$('.cart').on('click', 'button.remove', function(e){
		var button = $(e.target);
		var li = button.parent().parent();
		var price_id = parseInt(li.attr('price-id'), 10);
		if(!isNaN(price_id)){
			cart_set(price_id, 0);
			li.remove();
		}
	});
});
