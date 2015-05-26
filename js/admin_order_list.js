
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
	$('#orderlist').on('click', '.mark_sorted, .mark_delivering, .mark_in_delivery_point, .mark_received, .mark_rejected', function(e){
		var a = $(e.target);
		var href = a.attr('href');
		var td = a.parent().parent();
		$.post(href + '&ajax=1', [], function(data){
			if(a.hasClass('mark_sorted')){
				td.html(lang['order_sorted']);

				if(admin.hasPermission('order_deliver_w')){
					var button = $('<a></a>');

					if(td.data('deliverymethod') == Order.StationDelivery){
						button.attr('class', 'mark_in_delivery_point');
						button.attr('href', href.replace('mark_sorted', 'mark_indp'));
						button.html('[' + lang['order_in_delivery_point'] + ']');
					}else{
						button.attr('class', 'mark_delivering');
						button.attr('href', href.replace('mark_sorted', 'mark_delivering'));
						button.html('[' + lang['order_delivering'] + ']');
					}

					var div = $('<div></div>');
					div.append(button);
					td.append(div);
				}

				var tr = td.parent();
				tr.find('a.delete').remove();
				tr.find('ul.order_detail').addClass('disabled');

			}else if(a.hasClass('mark_delivering') || a.hasClass('mark_in_delivery_point')){
				td.html(a.hasClass('mark_delivering') ? lang['order_delivering'] : lang['order_in_delivery_point']);

				if(admin.hasPermission('order_deliver_w')){
					var data = {};
					data['mark_received'] = '[' + lang['order_received'] + ']';
					data['mark_rejected'] = '[' + lang['order_rejected'] + ']';

					for(var action in data){
						var button = $('<a></a>');
						button.attr('class', action);
						if(a.hasClass('mark_delivering')){
							button.attr('href', href.replace('mark_delivering', action));
						}else{
							button.attr('href', href.replace('mark_indp', action));
						}
						button.html(data[action]);

						var div = $('<div></div>');
						div.append(button);
						td.append(div);
					}
				}
			}else if(a.hasClass('mark_received')){
				td.html(lang['order_received']);
			}else if(a.hasClass('mark_rejected')){
				td.html(lang['order_rejected']);
			}
		});

		return false;
	});

	$('#multi_mark_sorted').click(function(){
		$('a.mark_sorted').click();
	});

	$('#multi_mark_delivering').click(function(){
		$('a.mark_delivering').click();
	});

	$('#multi_mark_in_delivery_point').click(function(){
		$('a.mark_in_delivery_point').click();
	});

	$('ul.order_detail').on('dblclick', 'li', function(e){
		var li = $(e.target);
		var ul = li.parent();
		if(ul.hasClass('disabled')){
			return false;
		}

		var data = {
			'detailid' : li.data('primaryvalue'),
			'state' : li.hasClass('outofstock') ? 0 : 1
		};

		var message = 'confirm_to_mark_detail_' + (data.state ? 'out_of' : 'in') + '_stock';
		if(confirm(lang[message])){
			$.get(mod_url + '&action=detail_outofstock', data, function(result){
				if(data.state == 0){
					li.removeClass('outofstock');
				}else{
					li.addClass('outofstock');
				}

				if(result.totalprice !== undefined){
					var td = ul.parent();
					var tr = td.parent();
					tr.find('.totalprice').html(result.totalprice);
				}
			}, 'json');
		}
	});
});
