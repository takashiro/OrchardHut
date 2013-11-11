
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
	var expires = (argc > 2) ? argv[2] : null;
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

function cart_number(){
	var in_cart = getcookie('in_cart');
	if(in_cart == ''){
		return 0;
	}else{
		return in_cart.split(',').length;
	}
}

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

	$('#cart-goods-number').html(cart_number());

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
		if(child.attr('hidden_children') != undefined){
			div.html(child.attr('hidden_children'));
		}

		child.children().each(function(){
			var parentid = $(this).attr('parentid');
			if(parentid != '0' && parentid != cur.val()){
				$(this).appendTo(div);
			}
		});

		div.children().each(function(){
			var parentid = $(this).attr('parentid');
			if(parentid == '0' || parentid == cur.val()){
				$(this).appendTo(child);
			}
		});

		child.attr('hidden_children', div.html());
		child.val('');
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
});
