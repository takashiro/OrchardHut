document.write('<script type="text/javascript" src="./plugin/datetimepicker/jquery.datetimepicker.min.js"></script>');
document.write('<style type="text/css">@import url(./plugin/datetimepicker/jquery.datetimepicker.css);</style>');

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
});
