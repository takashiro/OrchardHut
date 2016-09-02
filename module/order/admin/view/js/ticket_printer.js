function showmsg(message){
	var p = $('<p></p>');
	p.text(message);
	var box = $('#message_box');
	p.css('opacity', 0);

	p.appendTo(box);
	p.animate({'opacity' : 1}, 500);

	var messages = box.children();
	if(messages.length > 4){
		var p = messages.eq(0);
		var height = p.outerHeight(true);
		box.animate({'scrollTop' : '+=' + height + 'px'}, 500, function(){
			box.css('scrollTop', '-=' + height + 'px');
			p.remove();
		});
	}
}

$(function(){
	function updateWaitingNum(){
		var data = {
			'time_start' : $('#time_start').val(),
			'time_end' : $('#time_end').val()
		};
		$.get('admin.php?mod=order:ticketprinter&action=update', data, function(result){
			var num = parseInt(result, 10);
			if(!isNaN(num)){
				$('#waiting_num').text(num);
				setTimeout(updateWaitingNum, 1000);
			}
		});
	}
	updateWaitingNum();

	$('#scan_form').submit(function(e){
		e.preventDefault();

		if($('#mobile').val() == ''){
			return;
		}else{
			var input = $('#mobile');
			var input_text = input.val();

			var parameters;
			var orderid = 0;
			var mobile = 0;
			if(input_text.charAt(0) == '0' && input_text.length == 13){
				orderid = parseInt(input_text.substr(0, 9), 10);
				var packcode = parseInt(input_text.substr(9, 4), 10);
				if(!isNaN(orderid) && !isNaN(packcode) && orderid > 0 && packcode > 0){
					parameters += '&orderid=' + orderid + '&packcode=' + packcode;
				}
			}else if(input_text.charAt(0) == '{'){
				var value = JSON.parse(input_text);
				if(value.orderid != undefined && value.packcode != undefined){
					orderid = parseInt(value.orderid, 10);
					var packcode = parseInt(value.packcode, 10);
					if(!isNaN(orderid) && !isNaN(packcode) && orderid > 0 && packcode > 0){
						parameters += '&orderid=' + orderid + '&packcode=' + packcode;
					}
				}
			}else if(input_text.length == 11){
				mobile = parseInt(input_text, 10);
				parameters = '&mobile=' + parseInt(input_text, 10);
			}
			if(!parameters){
				return;
			}

			parameters += '&time_start=' + escape($('#time_start').val());
			parameters += '&time_end=' + escape($('#time_end').val());

			if(orderid > 0){
				showmsg(orderid + '号订单正在等待拣货...');
			}else if(mobile > 0){
				showmsg('手机尾号' + input_text.substr(7, 4) + '的订单正在等待拣货...');
			}

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
