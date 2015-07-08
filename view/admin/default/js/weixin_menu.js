
function update_parent_select(){
	var parent_buttons = ['无'];
	$('#menu_list tbody tr:not(:last-child)').each(function(){
		var td = $(this).children();
		var button_name = td.eq(0).children('input').val();
		var parent = td.eq(3).children('select').val();
		parent_buttons.push(button_name);
	});

	$('select.parent_buttons').children().remove();
	$('select.parent_buttons').each(function(){
		for(var id in parent_buttons){
			var option = $('<option></option>');
			option.attr('value', id);
			option.html(parent_buttons[id]);
			$(this).append(option);
		}

		if($(this).data('value') != undefined){
			$(this).val(parseInt($(this).data('value'), 10) + 1);
			$(this).removeData('value');
		}
	});
}

$(function(){
	$('#menu_list tbody').on('click', '.delete', function(e){
		var button = $(e.target);
		var tr = button.parent().parent();
		tr.remove();
	});

	$('#menu_list tbody').on('click', '.add', function(e){
		var button = $(e.target);
		var tr = button.parent().parent();

		var new_tr = tr.clone();
		new_tr.find('text, select, textarea').val('');
		var tbody = tr.parent();
		tbody.append(new_tr);

		button.html('删除');
		button.attr('class', 'delete');
	});

	$('#update_button').click(function(){
		var buttons = [];
		$('#menu_list tbody tr:not(:last-child)').each(function(){
			var td = $(this).children();

			var button = {};
			button.name = td.eq(0).children('input').val();
			button.type = td.eq(1).children('select').val();
			button[button['type'] == 'view' ? 'url' : 'key'] = td.eq(2).children('input').val();
			if(td.length > 4){
				var parent = td.eq(3).children('select').val();
				parent = parseInt(parent, 10) - 1;
				parent = buttons[parent];
				if(parent.sub_button == undefined)
					parent.sub_button = [];
				parent.sub_button.push(button);
			}else{
				buttons.push(button);
			}
		});

		$.post('$mod_url&action=menu&ajax=1', JSON.stringify(buttons), function(result){
			makeToast(result);
		}, 'json');

		return false;
	});

	update_parent_select();
});
