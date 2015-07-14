
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

(function($){
	$.fn.clock = function(option){
		var obj = this;

		var clock = function(){
			var date;
			if(typeof option.timestamp == 'number'){
				date = new Date(option.timestamp * 1000);
				option.timestamp++;
			}else{
				date = new Date();
			}

			var time = {
				'Y' : date.getFullYear(),
				'm' : date.getMonth() + 1,
				'd' : date.getDate(),
				'H' : date.getHours(),
				'i' : date.getMinutes(),
				's' : date.getSeconds()
			};

			var str = option.format;
			for(var t in time){
				if(time[t] < 10){
					time[t] = '0' + time[t];
				}
				str = str.replace(t, time[t]);
			}

			obj.html(str);
		};

		clock();
		setInterval(clock, 1000);
	};
})(jQuery);
