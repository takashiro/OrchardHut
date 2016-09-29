
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

$(function(){
	$('.order_input input').change(function(e){
		var input = $(e.target);
		var numberbox = input.parent();
		var li = numberbox.parent().parent();

		var product_id = li.data('product-id');
		var price_id = li.data('price-id');
		var storage_id = li.data('storage-id');
		var number = parseInt(input.val(), 10);

		if(typeof ProductStorage[storage_id] != 'undefined'){
			var total = ProductStorage[storage_id];
			var cart = ShoppingCart.getItems();
			$('ul.product .rule li').each(function(){
				if($(this).data('storage-id') == storage_id){
					var ordered = parseInt($(this).find('.order_input input').val(), 10);
					if(!isNaN(ordered)){
						var amount = parseInt($(this).children('.amount').text(), 10);
						total -= ordered * amount;
					}
				}
			});

			if(total < 0){
				var amount = parseInt(li.children('.amount').text(), 10);
				number -= -total / amount;
				number = Math.floor(number);
				if(number > 0){
					input.val(number);
				}else{
					input.val('');
					number = 0;
				}

				alert(lang['storage_inadequate']);
			}
		}

		var quantity_limit = parseInt(li.data('quantity-limit'), 10);
		if(quantity_limit > 0){
			if(User == undefined || User.id == undefined || User.id <= 0){
				input.val('');
				alert(lang['log_in_to_buy']);
			}else{
				var bought = parseInt(ProductQuantityLimit[price_id], 10);
				if(bought + number >= quantity_limit){
					number = quantity_limit - bought;
					if(number > 0){
						input.val(number);
					}else{
						input.val('');
						number = 0;
					}

					var msg = lang['out_of_product_quantity_limit']
						.replace('%bought', bought)
						.replace('%limit', quantity_limit);
					alert(msg);
				}
			}
		}

		ShoppingCart.setItem(price_id, number);
	});

	$('.announcement .title').each(function(){
		var title = $(this);
		var detail = title.next();
		if (detail.html().length > 0)
			title.addClass('title_link');
	});

	$('.announcement .title_link').click(function(e){
		var title = $(e.target);
		var detail = title.next();
		popup_message(title.html(), detail.html());
	});

	var autoFixSideBar = function(){
		var product_type = $('section.sidebar.auto-fixed');
		if(product_type.length <= 0)
			return;
		var offset_top = $('section.main').offset().top - $(window).scrollTop();
		if(product_type.hasClass('fixed')){
			if(offset_top > 0){
				product_type.removeClass('fixed');
			}
		}else{
			if(offset_top <= 0){
				product_type.addClass('fixed');
			}
		}
	};
	$(window).scroll(autoFixSideBar);
	autoFixSideBar();

	$('ul.product').on('click', '.icon, .name', function(e){
		var target = $(this);
		var detail = target.is('.icon') ? target.next() : target.parent();
		var rightpanel = detail.parent();
		var photo = rightpanel.children('.photo');
		var introduction = rightpanel.children('.introduction');
		var name = detail.children('.name');

		var title = name.html();
		var message = $('<div></div>');
		message.append(photo.clone());
		message.append(introduction.clone());
		message = message.html();
		popup_message(title, message);
	});

	if(location.href.indexOf('productid=') != -1){
		var link = location.href.split('productid=')[1];
		var product_id = parseInt(link.split('&')[0], 10);
		if(isNaN(product_id))
			return;

		$('ul.product li').each(function(){
			if($(this).data('product-id') != product_id)
				return true;

			var offset = $(this).offset();
			$('html, body').animate({scrollTop: offset.top - $(window).height() / 2 + $(this).outerHeight() / 2});

			return false;
		});
	}
});
