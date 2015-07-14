
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

	setTimeout(function(){
		toast.fadeOut(500, function(){
			toast.remove();
			if(data.url_forward){
				if(data.url_forward == 'refresh'){
					location.reload();
				}else if(data.url_forward == 'back'){
					toast.remove();
				}else{
					location.href = data.url_forward;
				}
			}
		});
	}, 1500);
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
		if(submenu.length <= 0 || (submenu.is(':visible') && submenu.css('opacity') >= 1.0))
			return;

		var offset_left = $(this).outerWidth();
		submenu.css({top: 0, left: offset_left - 10, opacity: 0});
		submenu.show();

		var submenu_rect = submenu.offset();
		submenu_rect.bottom = submenu_rect.top + submenu.outerHeight();
		submenu_rect.right = submenu_rect.left + submenu.outerWidth();

		var viewport = {
			top: $(window).scrollTop(),
			left: $(window).scrollLeft()
		};
		viewport.bottom = viewport.top + $(window).height();
		viewport.right = viewport.left + $(window).width();
		if(viewport.bottom < submenu_rect.bottom){
			submenu.css({top: $(this).outerHeight() - submenu.outerHeight()});
		}

		submenu.show();
		submenu.data('isShowing', true);
		submenu.animate({'left' : offset_left, 'opacity' : 1.0}, 300, 'swing', function(){
			submenu.data('isShowing', false);
		});
	});

	$('.menu > li').mouseleave(function(){
		var submenu = $(this).find('.submenu');
		if(submenu.length <= 0)
			return;

		setTimeout(function(){
			var li = submenu.parent();
			if(li.is(':hover') || submenu.data('isShowing'))
				return;
			submenu.fadeOut(300, function(){
				$(this).hide();
			});
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
