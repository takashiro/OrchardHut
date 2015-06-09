
/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

(function($){
	$.fn.cascadeselect = function(){
		var input = this;
		var ext = input.next();
		if(!ext.is('input.ext'))
			ext = null;

		input.wrap('<span></span>');
		var box = input.parent();
		if(ext !== null)
			ext.appendTo(box);

		box.on('change', 'select', function(e){
			var cur = $(e.target);
			var box = cur.parent();
			var input = box.children('input.cascadeselect');

			//清除之前的子选项
			var child = cur.next();
			while(child && child.is('select')){
				child.remove();
				child = cur.next();
			}

			//取得所有可选项
			var options = input.data('options');
			if(typeof options == 'string'){
				options = $.cascadeselect.commonOptions[options];
			}

			//取得当前值
			var addressid = input.val().split(':')[0];

			//显示现在的子选项
			var curvalue = options[addressid];
			var curdata = options[cur.val()];
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
			var address = box.children('select:last');
			var addressid = parseInt(address.val(), 10);
			while(isNaN(addressid) || addressid <= 0){
				address = address.prev();
				if(address == undefined || address.length <= 0 || !address.is('select'))
					break;
				addressid = parseInt(address.val(), 10);
			}

			var address = isNaN(addressid) ? 0 : addressid;
			var ext = box.children('.ext');
			if(ext.length > 0){
				address += ':' + ext.val();
			}
			input.val(address);
		});

		box.find('.ext').blur(function(e){
			var ext = $(e.target);
			var box = ext.parent();
			var input = box.children('input.cascadeselect');

			var address = box.children('select:last');
			var addressid = parseInt(address.val(), 10);
			while(isNaN(addressid) || addressid <= 0){
				address = address.prev();
				if(address == undefined || address.length <= 0 || !address.is('select'))
					break;
				addressid = parseInt(address.val(), 10);
			}

			var address = addressid;
			var ext = box.children('.ext');
			if(ext.length > 0){
				address += ':' + ext.val();
			}
			input.val(address);
		});

		box.find('input').each(function(){
			var input = $(this);

			var options = input.data('options');
			if(typeof options == 'string')
				options = $.cascadeselect.commonOptions[options];

			if (options != undefined){
				var addressid = input.val().split(':')[0];
				var curvalue = options[addressid];

				var select = $('<select></select>');
				if(!input.data('require-fullpath')){
					var option = $('<option></option>');
					option.val('');
					select.append(option);
				}
				for(var i in options[0].children){
					var c = options[0].children[i];
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
	};

	$.cascadeselect = {
		commonOptions : {},

		addCommonOptions : function(key, options){
			options[0] = {id: 0, name: "", children: {}};
			for(var j in options){
				var c = options[j];
				if(c.parentid != undefined){
					var p = options[c.parentid];
					if(p){
						if(p.children == undefined)
							p.children = {};
						p.children[c.id] = c;
					}
				}
			}

			this.commonOptions[key] = options;
		}
	};
})(jQuery);

$(function(){
	$('input.cascadeselect').cascadeselect();
});
