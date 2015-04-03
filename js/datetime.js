
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
