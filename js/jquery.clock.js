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
