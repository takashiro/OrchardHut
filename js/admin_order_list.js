$(function(){
	$('#orderlist').on('click', '.mark_sorted, .mark_delivering, .mark_in_delivery_point, .mark_received, .mark_rejected', function(e){
		var a = $(e.target);
		var href = a.attr('href');
		var td = a.parent().parent();
		$.post(href + '&ajax=1', [], function(data){
			if(a.hasClass('mark_sorted')){
				td.html(lang['order_sorted']);

				if(admin.hasPermission('order_deliver_w')){
					var delivering_button = $('<a></a>');
					delivering_button.attr('class', 'mark_delivering');
					delivering_button.attr('href', href.replace('mark_sorted', 'mark_delivering'));
					delivering_button.html('[' + lang['order_delivering'] + ']');

					var indp_button = $('<a></a>');
					indp_button.attr('class', 'mark_in_delivery_point');
					indp_button.attr('href', href.replace('mark_sorted', 'mark_indp'));
					indp_button.html('[' + lang['order_in_delivery_point'] + ']');

					var div = $('<div></div>');
					div.append(delivering_button);
					div.append(indp_button);
					td.append(div);
				}

				var tr = td.parent();
				tr.find('a.delete').remove();
				tr.find('ul.order_detail').addClass('disabled');

			}else if(a.hasClass('mark_delivering') || a.hasClass('mark_in_delivery_point')){
				td.html(a.hasClass('mark_delivering') ? lang['order_delivering'] : lang['order_in_delivery_point']);

				if(admin.hasPermission('order_deliver_w')){
					var data = {'mark_received':'[' + lang['order_received'] + ']', 'mark_rejected':'[' + lang['order_rejected'] + ']'};
					for(var action in data){
						var button = $('<a></a>');
						button.attr('class', action);
						button.attr('href', href.replace('mark_delivering', action));
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
			'detailid' : li.attr('primaryvalue'),
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
