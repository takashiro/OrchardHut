function update_focus(){
	$('#scaninput').focus();
}

$(function(){
	update_focus();

	$('#mark_form').submit(function(e){
		e.preventDefault();
		var scaninput = $('#scaninput');
		var scanresult = scaninput.val();
		scaninput.val('');

		var userid;
		var formkey;
		var items = [];
		if(scanresult.charAt(0) == '{'){
			scanresult = JSON.parse(scanresult);
			userid = parseInt(scanresult.userid, 10);
			formkey = parseInt(scanresult.formkey, 10);
			if(typeof scanresult.selected == 'object'){
				for(var ribbonid in scanresult.selected){
					var use_value = parseInt(scanresult.selected[ribbonid], 10);
					if(!isNaN(use_value) && use_value > 0){
						ribbonid = parseInt(ribbonid, 10);
						if(!isNaN(ribbonid) && ribbonid > 0){
							items[ribbonid] = use_value;
						}
					}
				}
			}
		}else{
			userid = parseInt(scanresult.substr(0, 10), 10);
			formkey = parseInt(scanresult.substr(10, 3), 10);
		}

		if(isNaN(userid) || userid <= 0 || isNaN(formkey) || formkey <= 0){
			makeToast('二维码错误。');
			return;
		}

		var parameters = {
			'userid' : userid,
			'formkey' : formkey
		};
		$.post('admin.php?mod=order:ribbon&action=query', parameters, function(response){
			var list = $('#ribbon-list');
			list.html('');

			if(response.error > 0){
				makeToast(response.error_message);
				return;
			}

			var ribbons = response.data;
			if(ribbons.length <= 0){
				makeToast('该用户无可用代金券。');
				return;
			}

			for(var i = 0; i < ribbons.length; i++){
				var ribbon = ribbons[i];
				var tr = $('<tr></tr>');
				tr.data('id', ribbon.id);

				tr.append($('<th></th>'));

				var td = $('<td></td>');
				var name = ribbon.productname;
				if(ribbon.productsubtype){
					name += '(' + ribbon.productsubtype + ')';
				}
				td.html(name);
				tr.append(td);

				td = $('<td></td>');
				td.html(ribbon.boughtnum + ribbon.amountunit);
				tr.append(td);

				td = $('<td></td>');
				td.html(ribbon.restnum + ribbon.amountunit);
				tr.append(td);

				td = $('<td></td>');
				td.html('<input type="text" class="number">');
				if(items[ribbon.id]){
					td.children('input').val(items[ribbon.id]);
				}
				tr.append(td);

				list.append(tr);
			}

			update_focus();
		}, 'json');
	});

	$('#consume-form').submit(function(e){
		e.preventDefault();

		var consume = {};
		var empty = true;

		var list = $('#ribbon-list tr');
		for(var i = 0; i < list.length; i++){
			var tr = list.eq(i);
			var id = tr.data('id');
			var num = tr.find('input.number').val();
			if(num > 0){
				consume[id] = num;
				empty = false;
			}
		}

		if(!empty){
			$.post('admin.php?mod=order:ribbon&action=consume', {'consume' : JSON.stringify(consume)}, function(response){
				if(response.error > 0){
					for(var i = 0; i < list.length; i++){
						var tr = list.eq(i);
						if(tr.data('id') == response.data){
							var name = tr.children('td').eq(0).html();
							var input = tr.find('input.number');
							input.select();
							makeToast(name + '数量不足，请重新输入。');
							break;
						}
					}
				}else{
					makeToast('成功消费代金券。');
					$('#ribbon-list').html('');
					update_focus();
				}
			}, 'json');
		}
	});
});
