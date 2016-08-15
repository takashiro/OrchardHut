function address_full_string(addressid){
	var path = [];
	while(!isNaN(addressid) && addressid > 0){
		if(!addresscomponent[addressid]){
			break;
		}
		var c = addresscomponent[addressid];
		path.unshift(c.name);
		addressid = c.parentid;
	}
	return path.join(' ');
}

var statustime = 0;

function update_pack_list(){
	var data = {'time_start' : '', 'time_end' : '', 'statusid' : Order.WaitForPacking, 'statustime' : statustime};
	data['display_status[' + Order.WaitForPacking + ']'] = 'on';
	$.post('admin.php?mod=order&format=json&full=1&ajaxform=1', data, function(response){
		var list = $('#orderlist');
		var orderids = [];
		for(var i = 0; i < response.data.length; i++){
			var order = response.data[i];
			orderids.push(order.id);
			statustime = parseInt(order.dateline, 10);
			var row = $('<tr></tr>');

			row.attr('primaryvalue', order.id);

			var td = $('<td></td>');
			td.text(order.id);
			row.append(td);

			var td = $('<td></td>');
			td.text(address_full_string(order.addressid) + ' ' + order.extaddress);
			row.append(td);

			var td = $('<td></td>');
			td.html(order.addressee + '<div>' + order.mobile + '</div>');
			row.append(td);

			var td = $('<td></td>');
			if(order.nickname){
				td.text(order.nickname);
			}else if(order.account){
				td.text(order.account);
			}else{
				td.text(order.userid);
			}
			row.append(td);

			var td = $('<td></td>');
			var ul = $('<ul></ul>');
			for(var j = 0; j < order.detail.length; j++){
				var d = order.detail[j];
				var li = $('<li></li>');
				var content = d.productname;
				if(d.subtype){
					content += '(' + d.subtype + ')';
				}
				content += ' ' + parseInt(d.amount, 10) * parseInt(d.number, 10);
				content += d.amountunit;
				li.text(content);
				ul.append(li);
			}
			td.append(ul);
			row.append(td);

			var td = $('<td></td>');
			td.text(order.totalprice + Product.PriceUnit);
			row.append(td);

			var td = $('<td></td>');
			td.html('<a href="###" class="mark_packing">[已拣货]</a>');
			row.append(td);

			var td = $('<td></td>');
			td.text(order.message);
			row.append(td);

			list.append(row);
		}

		if(orderids.length > 0){
			$.post('admin.php?mod=order:pack&action=getstatustime', {'orderids' : orderids}, function(result){
				statustime = parseInt(result.statustime, 10);
				setTimeout(update_pack_list, 1000);
			}, 'json');
		}else{
			setTimeout(update_pack_list, 1000);
		}
	}, 'json');
}

$(function(){
	update_pack_list();

	$('#orderlist').on('click', 'a.mark_packing', function(e){
		var a = $(e.target);
		var td = a.parent();
		var tr = td.parent();
		var orderid = tr.attr('primaryvalue');

		td.text('拣货完成...');
		$.post('admin.php?mod=order:pack&action=mark_packing&ajaxform=1', {'orderid' : orderid}, function(){
			tr.remove();
		});
	});
});