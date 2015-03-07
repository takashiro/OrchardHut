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

function makeToast(data){
	var toast = $('<div class="toast"></div>');
	toast.html(data.message);

	toast.appendTo($('body'));
	toast.animate({
		top : '-=40px',
		opacity : 1
	}, 300);

	if(data.url_forward != undefined){
		setTimeout(function(){
			toast.fadeOut(500, function(){
				toast.remove();
				if(data.url_forward == 'refresh'){
					location.reload();
				}else if(data.url_forward == 'back'){
					toast.remove();
				}else{
					location.href = data.url_forward;
				}
			});
		}, 1500);
	}
}

$(function(){
	$('.menu > li > .submenu').each(function(){
		var submenu = $(this);
		var menu = submenu.parent();
		submenu.css({
			top: 0,
			left: menu.outerWidth(),
		});
	});

	$('.menu > li').mouseenter(function(){
		var submenu = $(this).children('.submenu');
		if(submenu.is(':visible') && submenu.css('opacity') >= 1.0)
			return;
		var offset_left = $(this).outerWidth();
		submenu.css({'left' : offset_left - 10, 'opacity' : 0.0});
		submenu.show();
		submenu.data('isShowing', true);
		submenu.animate({'left' : offset_left, 'opacity' : 1.0}, 300, 'swing', function(){
			submenu.data('isShowing', false);
		});
	});

	$('.menu > li').mouseleave(function(){
		var submenu = $(this).find('.submenu');
		setTimeout(function(){
			var li = submenu.parent();
			if(li.is(':hover') || submenu.data('isShowing'))
				return;
			submenu.fadeOut(300);
		}, 300);
	});

	$('form.toast').submit(function(){
		var form = $(this);
		var data = form.serialize();
		var url = form.attr('action');
		if(url == '###'){
			url = location.href;
		}
		url += (url.indexOf('?') >= 0 ? '&' : '?') + 'ajaxform=1';
		$.post(url, data, function(response){
			eval('var response = ' + response + ';');
			makeToast(response);
		});
		return false;
	});
});
