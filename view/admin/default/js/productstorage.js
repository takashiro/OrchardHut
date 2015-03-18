$(function(){
	function updateTotalPrice(){
		var totalprice = 0;
		$('.subtotal').each(function(){
			totalprice += parseFloat($(this).val());
		});
		$('#totalprice').text(totalprice.toFixed(2));
	}

	$('input.amount, input.unitprice').change(function(){
		var tr = $(this).parent().parent();
		var amount = tr.find('.amount');
		var unitprice = tr.find('.unitprice');
		var subtotal = tr.find('.subtotal');
		var importamount = tr.find('.importamount');
		var importunitprice = tr.find('.importunitprice');

		amount.val(parseInt(amount.val(), 10));
		unitprice.val(parseFloat(unitprice.val()).toFixed(2));

		var subtotal_value = parseInt(amount.val(), 10) * parseFloat(unitprice.val());
		subtotal.val(subtotal_value.toFixed(2));

		var importunitprice_value = subtotal_value / parseFloat(importamount.val());
		importunitprice.val(importunitprice_value.toFixed(2));

		updateTotalPrice();
	});

	$('input.importamount, input.importunitprice').change(function(){
		var tr = $(this).parent().parent();
		var amount = tr.find('.amount');
		var unitprice = tr.find('.unitprice');
		var subtotal = tr.find('.subtotal');
		var importamount = tr.find('.importamount');
		var importunitprice = tr.find('.importunitprice');

		importamount.val(parseFloat(importamount.val()).toFixed(2));
		importunitprice.val(parseFloat(importunitprice.val()).toFixed(2));

		var subtotal_value = parseFloat(importamount.val()) * parseFloat(importunitprice.val());
		subtotal.val(subtotal_value.toFixed(2));

		var unitprice_value = subtotal_value / parseInt(amount.val(), 10);
		unitprice.val(unitprice_value.toFixed(2));

		updateTotalPrice();
	});

	$('input.subtotal').change(function(){
		var tr = $(this).parent().parent();
		var amount = tr.find('.amount');
		var unitprice = tr.find('.unitprice');
		var subtotal = tr.find('.subtotal');
		var importamount = tr.find('.importamount');
		var importunitprice = tr.find('.importunitprice');

		var subtotal_value = parseFloat(subtotal.val());
		subtotal.val(subtotal_value.toFixed(2));

		var unitprice_value = subtotal_value / parseInt(amount.val(), 10);
		unitprice.val(unitprice_value.toFixed(2));
		var importunitprice_value = subtotal_value / parseInt(importamount.val(), 10);
		importunitprice.val(importunitprice_value.toFixed(2));

		updateTotalPrice();
	});

	$('tr.selectable').click(function(){
		var radio = $(this).children('td').eq(0).children('input');
		$('input[type="radio"][name="' + radio.attr('name') + '"]').prop('checked', false);
		radio.prop('checked', true);
	});
});
