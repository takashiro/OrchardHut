$(function(){
	$('input.datetime').datetimepicker({
		lang : 'cn',
		i18n : {
			cn : {
				months : [
					'1月','2月','3月','4月',
					'5月','6月','7月','8月',
					'9月','10月','11月','12月',
				],
				dayOfWeek : [
					"日", "一", "二", "三", 
					"四", "五", "六",
				]
			}
		},
		format : 'Y-m-d H:i',
	});

	$('#orderlist').on('click', '.mark_sorted, .mark_delivering, .mark_received, .mark_rejected', function(e){
		var a = $(e.target);
		var href = a.attr('href');
		var td = a.parent().parent();
		$.post(href + '&ajax=1', [], function(data){
			if(a.hasClass('mark_sorted')){
				td.html(lang['order_sorted']);

				if(admin.hasPermission('order_deliver_w')){
					var button = $('<a></a>');
					button.attr('class', 'mark_delivering');
					button.attr('href', href.replace('mark_sorted', 'mark_delivering'));
					button.html('[' + lang['order_delivering'] + ']');

					var div = $('<div></div>');
					div.append(button);
					td.append(div);
				}

				var tr = td.parent();
				tr.find('a.delete').remove();
				tr.find('ul.order_detail').addClass('disabled');

			}else if(a.hasClass('mark_delivering')){
				td.html(lang['order_delivering']);
				
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
