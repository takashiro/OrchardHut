
(function($){
	$.fn.editlist = function(options){
		var defaults = {
			'edit' : '',
			'delete' : '',
			'primarykey' : 'id',
			'noedit' : false,
			'attr' : [],
			'buttons' : {'edit':'编辑', 'delete':'删除'}
		};

		options = $.extend(defaults, options);

		options.ajax_edit = options.edit + (options.edit.indexOf('?') == -1 ? '?' : '&') + 'ajax=1';
		options.ajax_delete = options.delete + (options.delete.indexOf('?') == -1 ? '?' : '&') + 'ajax=1';

		var display_operations = function(operation_td){
			operation_td.html('');
			for(var i in options.buttons){
				var button = $('<button></button>');
				button.attr('type', 'button');
				button.attr('class', i);
				button.html(options.buttons[i]);
				operation_td.append(button);
			}
		}

		var operation_td = this.find('tbody tr:not(:last-child) td:last-child');
		display_operations(operation_td);

		if(!options.noedit){
			this.on('dblclick', 'tbody tr:not(:last-child) td:not(:last-child)', function(e){
				var td = $(e.target);

				var index = td.index();
				var tbody = td.parent().parent();

				var input = tbody.children(':last-child').children().eq(index).find('input,select,textarea').clone();

				if(td.attr('realvalue')){
					input.val(td.attr('realvalue'));
				}else{
					input.val(td.html());
				}

				td.html('');
				td.append(input);
				input.focus();
			});

			this.on('blur', 'tbody tr:not(:last-child) td input, tbody tr:not(:last-child) td textarea, tbody tr:not(:last-child) td select', function(e){
				var input = $(e.target);
				var td = input.parent();
				var tr = td.parent();
				var index = td.index();
				var attr = options.attr[index];
				var value = input.val();

				var data = {};
				data[options.primarykey] = tr.attr('primaryvalue');
				data[attr] = value;

				$.post(options.ajax_edit, data, function(data){
					if(input.is('select')){
						td.attr('realvalue', value);
						td.html(input.children(':selected').html());
					}else{
						td.html(value);
					}

				}, 'json');
			});
		}

		this.on('click', '.add', function(e){
			var button = $(e.target);
			var new_tr = button.parent().parent();
			var empty_tr = new_tr.clone();

			var data = {};

			for(var i = 0; i < options.attr.length; i++){
				var attr = options.attr[i];
				var td = new_tr.children().eq(i);
				var input = td.find('input,select,textarea');
				var value = input.val();
				
				data[attr] = value;
			}

			$.post(options.ajax_edit, data, function(data){
				new_tr.attr('primaryvalue', data[options.primarykey]);

				for(var i = 0; i < options.attr.length; i++){
					var attr = options.attr[i];
					var td = new_tr.children().eq(i);
					var input = td.find('input,select');
					if(input.is('select')){
						input.val(data[attr]);
						td.html(input.find(':selected').html());
						td.attr('realvalue', data[attr]);
					}else{
						td.html(data[attr]);
					}
				}

				empty_tr.find('input,select').val('');
				new_tr.parent().append(empty_tr);

				display_operations(new_tr.children('td:last-child'));
			}, 'json');
		});

		this.on('click', '.edit', function(e){
			var button = $(e.target);
			var tr = button.parent().parent();
			location.href = options.edit + (options.edit.indexOf('?') == -1 ? '?' : '&') + options.primarykey + '=' + tr.attr('primaryvalue');
		});

		this.on('click', '.delete', function(e){
			var button = $(e.target);
			var tr = button.parent().parent();
			var data = {};
			data[options.primarykey] = tr.attr('primaryvalue');

			$.post(options.ajax_delete, data, function(){
				tr.remove();
			});
		});
	}
})(jQuery);
