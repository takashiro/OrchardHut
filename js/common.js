
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

function getcookie(Name){
	Name = cookiepre + Name;

	var search = Name + "=";
	if(document.cookie.length > 0){
		offset = document.cookie.indexOf(search);
		if(offset != -1){
			offset += search.length;
			end = document.cookie.indexOf(";", offset);
			if(end == -1){
				end = document.cookie.length;
			}
			return unescape(document.cookie.substring(offset, end));
		}
	}

	return "";
}

function setcookie(name, value){
	name = cookiepre + name;

	var argv = setcookie.arguments;
	var argc = setcookie.arguments.length;
	var expires = (argc > 2) ? argv[2] : 9999;
	if(expires != null){
		var LargeExpDate = new Date ();
		LargeExpDate.setTime(LargeExpDate.getTime() + (expires*1000*3600*24));
	}
	document.cookie = name + "=" + escape (value)+((expires == null) ? "" : ("; expires=" +LargeExpDate.toGMTString()));
}

function in_array(needle, arr){
	for(var i = 0; i < arr.length; i++){
		if(needle == arr[i]){
			return true;
		}
	}

	return false;
}

function popup_message(title, message){
	var popup_message = $('<div></div>');
	popup_message.addClass('popup_message');

	var header = $('<header></header>');
	var h4 = $('<h4></h4>');
	h4.html(title);
	var remove_button = $('<button></button>');
	remove_button.attr('type', 'button');
	remove_button.addClass('remove');
	header.append(h4);
	header.append(remove_button);

	var content = $('<div></div>');
	content.addClass('content');
	content.html(message);

	popup_message.append(header);
	popup_message.append(content);

	var wrapper = $('<div></div>');
	wrapper.addClass('popup_message_wrapper');
	popup_message.appendTo(wrapper);

	remove_button.click(function(){
		wrapper.remove();
	});

	wrapper.click(function(){
		$(this).remove();
	});

	$('body').append(wrapper);

	wrapper.fadeIn();
}

(function($){
	$.fn.numbernotice = function(val){
		this.text(val);
		if(val == 0){
			this.hide();
		}else{
			this.css('display', 'inline');
		}
	}
})(jQuery);

$(function(){
	var button_increase = $.parseHTML('<button class="increase"></button>');
	var button_decrease = $.parseHTML('<button class="decrease"></button>');
	var number_input = $.parseHTML('<input type="text" />');
	$('.numberbox').append(button_decrease);
	$('.numberbox').append(number_input);
	$('.numberbox').append(button_increase);

	$('.numberbox').on('click', '.increase', function(e){
		var button = $(e.target);
		var box = button.parent();
		var input = box.children('input');
		var number = parseInt(input.val(), 10);
		if(isNaN(number)){
			number = 1;
		}else{
			number = parseInt(number, 10);
			number++;
		}
		input.val(number);
		input.change();
	});

	$('.numberbox').on('click', '.decrease', function(e){
		var button = $(e.target);
		var box = button.parent();
		var input = box.children('input');
		var number = parseInt(input.val(), 10);
		if(!isNaN(number) && number > 1){
			number--;
		}else{
			number = '';
		}
		input.val(number);
		input.change();
	});

	$('.tselect').on('change', 'select', function(e){
		var cur = $(e.target);
		var child = cur.next();
		var tselect = cur.parent();

		var input = tselect.children('.value');

		var address = [];
		tselect.children('select').each(function(){
			address.push($(this).val());
		});
		var ext = tselect.children('.ext');
		if(ext.length > 0){
			address.push(ext.val());
		}
		input.val(address.join(','));

		if(child.length < 1 || !child.is('select')){
			return false;
		}

		var div = $($.parseHTML('<div></div>'));
		if(child.data('hidden_children') != undefined){
			div.html(child.data('hidden_children'));
		}

		child.children().each(function(){
			var parentid = $(this).data('parentid');
			if(parentid != '0' && parentid != cur.val()){
				$(this).appendTo(div);
			}
		});

		div.children().each(function(){
			var parentid = $(this).data('parentid');
			if(parentid == '0' || parentid == cur.val()){
				$(this).appendTo(child);
			}
		});

		child.data('hidden_children', div.html());
	});

	$('.tselect .ext').blur(function(e){
		var ext = $(e.target);
		var tselect = ext.parent();

		var input = tselect.children('.value');
		var address = [];
		tselect.children('select').each(function(){
			address.push($(this).val());
		});
		var ext = tselect.children('.ext');
		if(ext.length > 0){
			address.push(ext.val());
		}
		input.val(address.join(','));
	});

	$('.tselect select').change();

	$('.mselect').on('click', 'li', function(e){
		var li = $(e.target);
		var radio = li.children('input');
		var mselect = li.parent();

		mselect.children('li').removeClass('checked');
		li.addClass('checked');

		radio.click();
	});
});
