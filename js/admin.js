function randomstr(length){
	var str = '';
	for(var i = 0; i < length; i++){
		var rand = Math.floor(Math.random() * 62);
		if(rand >= 0 && rand <= 25){
			rand += 0x41;
		}else if(rand <= 51){
			rand -= 26;
			rand += 0x61;
		}else{
			rand -= 52;
			rand += 0x30;
		}
		str += String.fromCharCode(rand);
	}

	return str;
}

$(function(){
	$('.menu > li').mouseenter(function(e){
		var submenu = $(this).children('.submenu');
		submenu.data('isSlidingDown', true);
		submenu.slideDown(300, function(){
			submenu.data('isSlidingDown', false);
		});
	});

	$('.menu > li').mouseleave(function(){
		var submenu = $(this).children('.submenu');
		var menu_li = submenu.parent();
		setTimeout(function(){
			if(menu_li.is(':hover') || submenu.data('isSlidingDown'))
				return false;
			submenu.slideUp();
		}, 500);
	});
});
