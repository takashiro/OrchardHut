
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

$.fn.banner = function(options){
	var banner_box = $(this);
	var banner_list = banner_box.find('.list ol');
	if(banner_list.children().length <= 1){
		return;
	}

	var banner_list_wrapper = banner_box.children('.list');

	var dots = $('<div></div>');
	dots.addClass('dots');
	banner_box.append(dots);

	function move_banner(){
		var banners = banner_list.children();
		var current = banners.filter('.current');
		if(current.length == 0){
			current = banners.eq(0);
		}
		var next = current.next();
		var scroll_left = banner_list_wrapper.width();
		if(next.length == 0){
			banner_list_wrapper.scrollLeft(0);
			next = banners.eq(1);
		}else{
			var scroll_left = banner_list_wrapper.width() * next.index() - banner_list_wrapper.scrollLeft();
		}

		current.removeClass('current');
		next.addClass('current');
		banner_list_wrapper.animate({'scrollLeft' : '+=' + scroll_left}, 500);

		var current_dot = dots.children('.current');
		current_dot.removeClass('current');
		var next_dot = dots.children().eq(next.index());
		if(next_dot.length == 0){
			next_dot = dots.children().eq(0);
		}
		next_dot.addClass('current');
	}

	if(banner_list.children().length > 1){
		var banners = banner_list.children();
		var banner_num = banners.length;
		for(var i = 0; i < banner_num; i++){
			var div = $('<div></div>');
			div.appendTo(dots);
		}
		dots.children().eq(0).addClass('current');
		var first = banners.eq(0).clone();
		banner_list.append(first);

		var moveBanner = setInterval(move_banner, options.interval);
		var banner = $('#announcement-banner');
		banner.one('click', function(){clearInterval(moveBanner);});
		banner.on('click', move_banner);
	}
};

$(function(){
	$('section.banner').banner({'interval' : 5000});

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
		var name = detail.children('.name').clone();
		name.children().remove();

		var title = name.html();
		var message = $('<div></div>');
		message.append(photo.clone());
		message.append(introduction.clone());
		message = message.html();
		popup_message(title, message);
		$('.popup_message img').lazyload();
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
