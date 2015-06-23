(function($){
	$.fn.levelinput = function(){
		this.each(function(){
			var box = $('<div></div>');
			box.addClass('levelinput');
			$(this).wrap(box);
			box = $(this).parent();

			var maxlevel = parseInt($(this).data('maxlevel'), 10);
			if(isNaN(maxlevel)){
				maxlevel = 5;
			}

			for(var i = 0; i < maxlevel; i++){
				var star = $('<div></div>');
				star.addClass('unselected');
				box.append(star);
			}

			box.on('click', 'div', function(e){
				var current = $(e.target);
				var box = current.parent();
				var input = box.children('input');
				if(!input.attr('readonly')){
					input.val(current.index());
					input.change();
				}
			});
		});

		this.change(function(e){
			var input = $(e.target);
			var box = input.parent();
			var value = parseInt(input.val(), 10);
			if(isNaN(value))
				value = 1;

			var stars = box.children('div');
			for(var i = 0; i < value; i++){
				stars.eq(i).removeClass('unselected');
			}

			var maxlevel = parseInt($(this).data('maxlevel'), 10);
			if(isNaN(maxlevel)){
				maxlevel = 5;
			}
			for(var i = value; i < maxlevel; i++){
				stars.eq(i).addClass('unselected');
			}
		});

		this.change();
	};

	$(function(){
		$('input.level').levelinput();
	});
})(jQuery);
