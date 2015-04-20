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

		//to-do: God knows when you would want them back
		var importamount = tr.find('.importamount');
		var importunitprice = tr.find('.importunitprice');

		amount.val(parseInt(amount.val(), 10));
		unitprice.val(parseFloat(unitprice.val()).toFixed(2));

		var unitprice_value = parseFloat(subtotal.val()) / parseInt(amount.val(), 10);
		unitprice.val(unitprice_value.toFixed(2));

		var importunitprice_value = parseFloat(subtotal.val()) / parseFloat(importamount.val());
		importunitprice.val(importunitprice_value.toFixed(2));

		updateTotalPrice();
	});

	if(StorageUnitRatio != undefined && StorageUnitRatio.length > 0){
		$('.storageid').each(function(){
			var tr = $(this).parent();
			var storageid = parseInt($(this).html(), 10);

			for(var i = 0; i < StorageUnitRatio.length; i++){
				var ratio = StorageUnitRatio[i];
				if(ratio.storageid == storageid){
					var importamountunit = tr.find('.importamount').next();
					importamountunit.children().each(function(){
						$(this).prop('selected', $(this).html() == ratio.importamountunit);
					});
					break;
				}
			}
		});

		$('input.importamount').change(function(){
			var tr = $(this).parent().parent();
			var storageid = parseInt(tr.find('.storageid').html(), 10);
			if(isNaN(storageid) || storageid <= 0)
				return;

			var amount = tr.find('.amount');
			var importamount = tr.find('.importamount');
			var importunitprice = tr.find('.importunitprice');
			var importamountunit = importamount.next().find(':selected').html();
			for(var i = 0; i < StorageUnitRatio.length; i++){
				var ratio = StorageUnitRatio[i];
				if(ratio.storageid == storageid && ratio.importamountunit == importamountunit){
					var r = parseInt(ratio.amount, 10) / parseInt(ratio.importamount, 10);
					if(!isNaN(r)){
						amount.val(r * parseInt(importamount.val(), 10));
						amount.change();
					}
					ratio.storageid = 0;
				}
			}
		});
	}

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
