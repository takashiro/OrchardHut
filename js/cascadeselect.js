if(TSelectData != undefined){
	for(var i in TSelectData){
		var components = TSelectData[i];
		components[0] = {id: 0, name: "", children: {}};
		for(var j in components){
			var c = components[j];
			if(c.parentid != undefined){
				var p = components[c.parentid];
				if(p){
					if(p.children == undefined)
						p.children = {};
					p.children[c.id] = c;
				}
			}
		}
	}
}

$(function(){
	$('.tselect').on('change', 'select', function(e){
		var cur = $(e.target);
		var tselect = cur.parent();
		var input = tselect.children('input.value');

		//清除之前的子选项
		var child = cur.next();
		while(child && child.is('select')){
			child.remove();
			child = cur.next();
		}

		//取得当前值
		var addressid = input.val().split(':')[0];
		var curvalue = components[addressid];

		//显示现在的子选项
		var curdata = TSelectData[input.data('components')][cur.val()];
		if(curdata != undefined && curdata.children != undefined){
			var child = $('<select></select>');
			if(!input.data('require-fullpath')){
				var option = $('<option></option>');
				option.val('');
				child.append(option);
			}
			for(var i in curdata.children){
				var option = $('<option></option>');
				var c = curdata.children[i];
				option.val(c.id);
				option.text(c.name);
				if(curvalue != undefined && (c.id == curvalue.id || in_array(c.id, curvalue.parents)))
					option.attr('selected', true);
				option.appendTo(child);
			}
			cur.after(child);
			child.change();
		}

		//改变input标签的值
		var address = tselect.children('select:last');
		var addressid = parseInt(address.val(), 10);
		while(isNaN(addressid) || addressid <= 0){
			address = address.prev();
			if(address == undefined || address.length <= 0 || !address.is('select'))
				break;
			addressid = parseInt(address.val(), 10);
		}

		var address = addressid;
		var ext = tselect.children('.ext');
		if(ext.length > 0){
			address += ':' + ext.val();
		}
		input.val(address);
	});

	$('.tselect .ext').blur(function(e){
		var ext = $(e.target);
		var tselect = ext.parent();

		var input = tselect.children('input.value');
		var address = tselect.children('select:last');
		var addressid = parseInt(address.val(), 10);
		while(isNaN(addressid) || addressid <= 0){
			address = address.prev();
			if(address == undefined || address.length <= 0 || !address.is('select'))
				break;
			addressid = parseInt(address.val(), 10);
		}

		var address = addressid;
		var ext = tselect.children('.ext');
		if(ext.length > 0){
			address += ':' + ext.val();
		}
		input.val(address);
	});

	$('.tselect input').each(function(){
		var input = $(this);
		var components = input.data('components');
		if (TSelectData != undefined && TSelectData[components] != undefined){
			var components = TSelectData[components];
			var addressid = input.val().split(':')[0];
			var curvalue = components[addressid];

			var select = $('<select></select>');
			if(!input.data('require-fullpath')){
				var option = $('<option></option>');
				option.val('');
				select.append(option);
			}
			for(var i in components[0].children){
				var c = components[0].children[i];
				var option = $('<option></option>');
				option.val(c.id);
				option.html(c.name);
				if(curvalue != undefined && (c.id == curvalue.id || in_array(c.id, curvalue.parents)))
					option.attr('selected', true);
				option.appendTo(select);
			}
			input.after(select);
			select.change();
		}
	});
});
