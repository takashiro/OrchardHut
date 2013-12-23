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
				td.html(order_status['sorted']);

				var button = $('<a></a>');
				button.attr('class', 'mark_delivering');
				button.attr('href', href.replace('mark_sorted', 'mark_delivering'));
				button.html('[' + order_status['delivering'] + ']');
				
				var div = $('<div></div>');
				div.append(button);
				td.append(div);

				td.parent().find('a.delete').remove();

			}else if(a.hasClass('mark_delivering')){
				td.html(order_status['delivering']);
				
				var data = {'mark_received':'[' + order_status['received'] + ']', 'mark_rejected':'[' + order_status['rejected'] + ']'};
				for(var action in data){
					var button = $('<a></a>');
					button.attr('class', action);
					button.attr('href', href.replace('mark_delivering', action));
					button.html(data[action]);

					var div = $('<div></div>');
					div.append(button);
					td.append(div);
				}
			}else if(a.hasClass('mark_received')){
				td.html(order_status['received']);
			}else if(a.hasClass('mark_rejected')){
				td.html(order_status['rejected']);
			}
		});

		return false;
	});
});
