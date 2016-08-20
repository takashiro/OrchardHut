$(function(){
	$('#print_button').click(function(e){
		e.preventDefault();

		if($('#orderid_or_mobile').val() == ''){
			alert('请输入订单号或手机号。');
		}else{
			var input = $('#orderid_or_mobile');

			var parameters;
			if (input.val().length == 11){
				parameters = '&mobile=' + parseInt(input.val(), 10);
			}else{
				parameters = '&orderid=' + parseInt(input.val(), 10);
			}

			parameters += '&time_start=' + escape($('#time_start').val());
			parameters += '&time_end=' + escape($('#time_end').val());

			var url = 'admin.php?mod=order:ticketprinter&auto_receive=1' + parameters;
			var new_window = window.open(url, '打印提货单', 'width=320, height=500, status=no, menubar=no, alwaysraised=yes');
			new_window.focus();

			input.val('');
			input.focus();
		}
	});

	$('#orderid_or_mobile').focus();

	$('.number_board button').click(function(){
		var input = $('#orderid_or_mobile');
		input.val(input.val() + $(this).text());
	});

	$('#backspace_button').click(function(){
		var input = $('#orderid_or_mobile');
		var content = input.val();
		input.val(content.substr(0, content.length - 1));
	});

	$('#clear_button').click(function(){
		var input = $('#orderid_or_mobile');
		input.val('');
	});

	$('.tabview').on('click', '.tab li button', function(e){
		var button = $(e.target);
		var li = button.parent();
		var tab = li.parent();
		tab.children().removeClass('current');
		li.addClass('current');

		var pages = tab.parent().children('.pages').children();
		pages.hide();
		var index = li.index();
		var current = pages.eq(index);
		current.show();

		current.find('input[autofocus="autofocus"]').focus();
	});

	$('#scan_form').on('submit', function(e){
		e.preventDefault();
		var input = $('#scan_input');
		var input_text = input.val();
		input.val('');

		var orderid = 0;
		var packcode = 0;
		if (input_text.substr(0, 7) == "http://"){
			var parameters = input_text.split('&');
			for(var i = 0; i < parameters.length; i++){
				var temp = parameters[i].split('=');
				if(temp.length != 2){
					continue;
				}
				if(temp[0] == 'orderid'){
					orderid = parseInt(temp[1], 10);
				}else if(temp[0] == 'packcode'){
					packcode = parseInt(temp[1], 10);
				}
			}
		}else{
			orderid = parseInt(input_text.substr(0, input_text.length - 5), 10);
			packcode = parseInt(input_text.substr(input_text.length - 5));
		}

		if(orderid <= 0 || packcode <= 0){
			input.focus();
			return;
		}

		var parameters = '&orderid=' + orderid + '&packcode=' + packcode;
		parameters += '&time_start=' + escape($('#time_start').val());
		parameters += '&time_end=' + escape($('#time_end').val());

		var url = 'admin.php?mod=order:ticketprinter&auto_receive=1' + parameters;
		var new_window = window.open(url, '打印提货单', 'width=320, height=500, status=no, menubar=no, alwaysraised=yes');
		new_window.focus();

		input.focus();
	});

	$('#scan_input').focus();
});
