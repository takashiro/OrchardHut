$(function(){
	$('#scan_form').submit(function(e){
		e.preventDefault();

		if($('#mobile').val() == ''){
			return;
		}else{
			var input = $('#mobile');
			var input_text = input.val();

			var parameters;
			if(input_text.charAt(0) == '0' && input_text.length == 13){
				var orderid = parseInt(input_text.substr(0, 9), 10);
				var packcode = parseInt(input_text.substr(9, 4), 10);
				if(!isNaN(orderid) && !isNaN(packcode) && orderid > 0 && packcode > 0){
					parameters += '&orderid=' + orderid + '&packcode=' + packcode;
				}
			}else if(input_text.charAt(0) == '{'){
				var value = JSON.parse(input_text);
				if(value.orderid != undefined && value.packcode != undefined){
					var orderid = parseInt(value.orderid, 10);
					var packcode = parseInt(value.packcode, 10);
					if(!isNaN(orderid) && !isNaN(packcode) && orderid > 0 && packcode > 0){
						parameters += '&orderid=' + orderid + '&packcode=' + packcode;
					}
				}
			}else if(input_text.length == 11){
				parameters = '&mobile=' + parseInt(input_text, 10);
			}
			if(!parameters){
				return;
			}

			parameters += '&time_start=' + escape($('#time_start').val());
			parameters += '&time_end=' + escape($('#time_end').val());

			var url = 'admin.php?mod=order:ticketprinter&auto_receive=1&auto_print=1' + parameters;
			var new_window = window.open(url, '打印提货单', 'width=320, height=500, status=no, menubar=no, alwaysraised=yes');
			new_window.focus();

			input.val('');
			input.focus();
		}
	});

	$('#mobile').focus();

	$('.number_board button').click(function(){
		var input = $('#mobile');
		var number = parseInt($(this).text(), 10);
		if(!isNaN(number)){
			input.val(input.val() + number);
			input.focus();
		}
	});

	$('#backspace_button').click(function(){
		var input = $('#mobile');
		var content = input.val();
		input.val(content.substr(0, content.length - 1));
		input.focus();
		return false;
	});

	$('#clear_button').click(function(){
		var input = $('#mobile');
		input.val('');
		input.focus();
		return false;
	});
});
