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

	$('#orderlist').on('click', '.mark_delivering, .mark_received, .mark_rejected', function(e){
		var a = $(e.target);
		var href = a.attr('href');
		var td = a.parent().parent();
		$.post(href + '&ajax=1', [], function(data){
			if(a.hasClass('mark_delivering')){
				td.html('配送中');
				
				var data = {'mark_received':'[已签收]', 'mark_rejected':'[已拒收]'};
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
				td.html('已签收');
			}else if(a.hasClass('mark_rejected')){
				td.html('已拒收');
			}
		});

		return false;
	});
});
