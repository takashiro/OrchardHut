
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

var ShoppingCart = {
	getItems : function(){
		var items = getcookie('shopping_cart');
		try{
			items = JSON.parse(items);
			if(typeof items == 'object'){
				var cart = {};
				for(var i in items){
					var price_id = parseInt(i, 10);
					var num = parseInt(items[i], 10);
					if(!isNaN(price_id) && price_id > 0 && !isNaN(num) && num > 0){
						cart[price_id] = num;
					}
				}
				return cart;
			}else{
				return {};
			}
		}catch(e){
			return {};
		}
	},

	setItems : function(items){
		setcookie('shopping_cart', JSON.stringify(items));
	},

	setItem : function(price_id, value){
		var items = this.getItems();
		items[price_id] = value;
		this.setItems(items);

		this.itemNumChange();
	},

	getItemNum : function(){
		var num = 0;
		var items = this.getItems();
		for(var i in items){
			num++;
		}
		return num;
	},

	itemNumChange : function(func){
		if(typeof func == 'function'){
			this.itemNumChanged.push(func);
		}else if(func == undefined){
			for(var i = 0; i < this.itemNumChanged.length; i++){
				var func = this.itemNumChanged[i];
				if(typeof func == 'function')
					func();
			}
		}
	},

	'itemNumChanged' : []
};

$(function(){
	ShoppingCart.itemNumChange(function(){
		$('#cart-goods-number').numbernotice(ShoppingCart.getItemNum());
	});
	ShoppingCart.itemNumChange();

	var items = ShoppingCart.getItems();
	for(var price_id in items){
		var number = items[price_id];
		$('li[data-price-id=' + price_id + '] .order_input input').val(number);
	}

	var delivered_num = parseInt(getcookie('delivering-order-number'), 10);
	var cache_time = parseInt(getcookie('order-number-cache-time'), 10);
	var current_time = parseInt(new Date().valueOf() / 1000);
	if(isNaN(delivered_num) || isNaN(cache_time) || current_time - cache_time >= 1800){
		$('#delivering-order-number').numbernotice(0);
		$.post('index.php?mod=order&action=deliveringnum', {}, function(data){
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
