
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

function wxShare(title, link, imgUrl){
	var data = {
		'title': title,
		'link': link,
		'imgUrl': imgUrl
	};
	wx.onMenuShareTimeline(data);
	wx.onMenuShareAppMessage(data);
	wx.onMenuShareQQ(data);
	wx.onMenuShareQZone(data);
}

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
			$('.product_list .rule li').each(function(){
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

	$('.product_list .rule').each(function(){
		var rule = $(this);
		var list = rule.children();
		var more = rule.next();
		var more_button = more.children('a');
		if(list.length == 1){
			more_button.addClass('disabled');
		}else{
			var brief = $($.parseHTML('<div></div>'));
			brief.addClass('brief');
			brief.html('多个品种可选');
			if(list.length == 2){
				if(list.eq(0).find('.subtype').html() == '' && list.eq(1).find('.subtype').html() == ''
					&& list.eq(0).find('.briefintro').html() == '' && list.eq(1).find('.briefintro').html() == ''){
					brief.html('');
					brief.append(list.eq(0).children('span').clone());
					brief.append($.parseHTML('<span class="split"> / </span>'));
					brief.append(list.eq(1).children('span').clone());
				}
			}else{
				var prev = null;
				list.each(function(){
					if(prev != null && prev == $(this).find('.subtype').html()){
						$(this).children('.intro').html('');
					}else{
						prev = $(this).find('.subtype').html();
					}
				});
			}

			rule.before(brief);

			var countdown_icon = rule.find('.countdown:eq(0)').clone();
			if(countdown_icon.length > 0){
				var detail = rule.parent();
				var icon = detail.prev();
				icon.append(countdown_icon);
			}

			more_button.click(function(){
				if(list.is(':visible')){
					list.hide();
					brief.show();
					countdown_icon.show();
					$(this).text('点击展开并购买');
				}else{
					list.show();
					brief.hide();
					countdown_icon.hide();
					$(this).text('点击收起');
				}
			});

			more_button.click();
		}
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

	$('.product_type .more').click(function(e){
		var more = $(e.target);
		var wrapper = more.parent();
		var ul = wrapper.children('ul');
		if(ul.children().length <= 3){
			more.animate({opacity: 0.4});
			more.unbind('click');
		}else{
			var original_height = wrapper.data('original_height');
			if(!original_height){
				wrapper.data('original_height', wrapper.height());
				wrapper.animate({height: ul.height()});
			}else{
				wrapper.data('original_height', '');
				wrapper.animate({height: original_height});
			}
		}
	});

	$(window).scroll(function(){
		var product_type = $('.product_type');
		if(product_type.length <= 0)
			return;
		var offset_top = product_type.offset().top - $(window).scrollTop();
		if(product_type.hasClass('product_type_fixed')){
			if(offset_top > 0){
				product_type.removeClass('product_type_fixed');
			}
		}else{
			if(offset_top <= 0){
				product_type.addClass('product_type_fixed');
			}
		}
	});

	$('.product_list').on('click', '.icon, .name', function(e){
		var target = $(this);
		var detail = target.is('.icon') ? target.next() : target.parent();
		var photo = detail.children('.photo');
		var introduction = detail.children('.introduction');
		var name = detail.children('.name');

		var title = name.html();
		var message = $('<div></div>');
		message.append(photo.clone());
		message.append(introduction.clone());
		message = message.html();
		popup_message(title, message);
	});

	$('.product_list > li').click(function(){
		var product_id = $(this).data('product-id');

		var title = CONFIG['sitename'] + '的' + $(this).children('.detail').children('.name').text() + '竟然只要';
		var price = $(this).children('.detail').children('ul.rule').children('li[data-price-id]');
		if(price.length > 0){
			price = price.eq(0);
			title += price.children('.price').text();
			title += price.children('.priceunit').text();
			title += price.children('.amount').text();
			title += price.children('.amountunit').text();
			title += '，买买买！';
		}

		var link = location.href + (location.href.indexOf('?') == -1 ? '?' : '&');
		link += 'referrerid=' + User.id;
		link += '&productid=' + product_id;

		var link_parts = location.href.split('/');
		var image_url = link_parts[0] + '//' + link_parts[2] + '/data/attachment/product_' + product_id + '_icon.png';

		wxShare(title, link, image_url);
	});

	if(location.href.indexOf('productid=') != -1){
		var link = location.href.split('productid=')[1];
		var product_id = parseInt(link.split('&')[0], 10);
		if(isNaN(product_id))
			return;

		$('.product_list li').each(function(){
			if($(this).data('product-id') != product_id)
				return true;

			var offset = $(this).offset();
			$('html, body').animate({scrollTop: offset.top - $(window).height() / 2 + $(this).outerHeight() / 2});

			return false;
		});
	}

	wx.ready(function(){
		var link = location.href + (location.href.indexOf('?') == -1 ? '?' : '&');
		link += 'referrerid=' + User.id;

		var link_parts = location.href.split('/');
		var image_url = link_parts[0] + '//' + link_parts[2] + '/view/user/default/image/logo.png';

		wxShare(CONFIG['sitename'], link, image_url);
	});
});
