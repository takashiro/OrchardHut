function showmsg(message){
	var p = $('<p></p>');
	p.text(message);
	var box = $('#message_box');
	p.addClass('active');
	p.css('opacity', 0);

	box.children('.active').removeClass('active');
	p.appendTo(box);
	p.animate({'opacity' : 1}, 500);

	var messages = box.children();
	var total_height = 0;
	messages.each(function(){
		total_height += $(this).outerHeight();
	});
	if(total_height > box.height()){
		var p = messages.eq(0);
		var height = p.outerHeight(true);
		box.animate({'scrollTop' : '+=' + height + 'px'}, 500, function(){
			box.css('scrollTop', '-=' + height + 'px');
			p.remove();
		});
	}
}

$(function(){
	$('#scan_form').submit(function(e){
		e.preventDefault();

		if($('#mobile').val() == ''){
			return;
		}else{
			var input = $('#mobile');
			var input_text = input.val();

			var parameters = '&stationid=' + stationid;
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
				parameters += '&mobile=' + parseInt(input_text, 10);
			}
			if(!parameters){
				return;
			}

			parameters += '&time_start=' + escape($('#time_start').val());
			parameters += '&time_end=' + escape($('#time_end').val());

			var order_text = orderid > 0 ? orderid + '号订单' : '手机尾号' + input_text.substr(7, 4) + '的订单';
			var url = 'admin.php?mod=order:ticketprinter&action=print' + parameters;
			$.post(url + '&check=1', {}, function(error){
				error = parseInt(error, 10);
				if(isNaN(error)){
					showmsg('未知故障。');
				}else if(error >= 0){
					showmsg(order_text + '正在等待拣货...');
					url += '&auto_receive=1&auto_print=1';
					var new_window = window.open(url, '打印提货单', 'width=320, height=500, status=no, menubar=no, alwaysraised=yes');
					new_window.focus();
				}else if(error == -1){
					showmsg('未查询到' + order_text);
				}else if(error == -2){
					showmsg('工作人员忙不过来了，您稍等一下');
					input.val(input_text);
					input.focus();
				}
			}, 'text');

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
