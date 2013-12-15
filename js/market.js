
$(function(){
	$('.order_input input').change(function(e){
		var input = $(e.target);
		var numberbox = input.parent();
		var li = numberbox.parent();

		var data = {
			'product_id' : li.attr('product-id'),
			'price_id' : li.attr('price-id'),
			'number' : parseInt(input.val(), 10)
		};

		cart_set(data.price_id, data.number);
	});

	$('.product_list .rule').each(function(){
		var rule = $(this);
		var list = rule.children();
		var more = rule.next();
		var more_button = more.children('a');
		if(list.length == 1){
			more_button.addClass('disabled');
		}else{
			var brief = $($.parseHTML('<div></div>'));
			brief.addClass('brief');
			brief.html('多个品种可选');
			if(list.length == 2){
				if(list.eq(0).children('.subtype').html() == '' && list.eq(1).children('.subtype').html() == ''){
					brief.html('');
					brief.append(list.eq(0).children('span').clone());
					brief.append($.parseHTML('<span class="split"> / </span>'));
					brief.append(list.eq(1).children('span').clone());
				}
			}else{
				var prev = null;
				list.each(function(){
					if(prev != null && prev == $(this).children('.subtype').html()){
						$(this).children('.subtype').html('');
					}else{
						prev = $(this).children('.subtype').html();
					}
				});
			}

			rule.before(brief);

			var countdown_icon = rule.find('.countdown:eq(0)').clone();
			if(countdown_icon.length > 0){
				var detail = rule.parent();
				var icon = detail.prev();
				icon.append(countdown_icon);
			}

			more_button.click(function(){
				if(list.is(':visible')){
					list.hide();
					brief.show();
					countdown_icon.show();
					$(this).text('点击展开并购买');
				}else{
					list.show();
					brief.hide();
					countdown_icon.hide();
					$(this).text('点击收起');
				}
			});

			more_button.click();
		}
	});

	$('.announcement a').click(function(e){
		var a = $(e.target);
		var detail = a.prev();
		var title = detail.prev();
		popup_message(title.html(), detail.html());
	});

	$('.product_list').on('click', '.icon, .name', function(e){
		var target = $(this);
		var detail = target.is('.icon') ? target.next() : target.parent();
		var photo = detail.children('.photo');
		var introduction = detail.children('.introduction');
		var name = detail.children('.name');

		var title = name.html();
		var message = photo.html() + '<div>' + introduction.html() + '</div>';
		popup_message(title, message);
	});
});
