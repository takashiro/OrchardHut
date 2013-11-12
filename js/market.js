
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
		if(list.length == 1 && list.children('.subtype').html() == ''){
			more_button.addClass('disabled');
		}else{
			var brief = $($.parseHTML('<div></div>'));
			brief.addClass('brief');
			brief.html('多个品种可选');
			rule.before(brief);

			more_button.click(function(){
				if(list.is(':visible')){
					list.hide();
					brief.show();
					$(this).text('点击展开并购买');
				}else{
					list.show();
					brief.hide();
					$(this).text('点击收起');
				}
			});

			more_button.click();
		}
	});
});
