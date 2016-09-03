$(function(){
	$('.station_list button.pause').click(function(e){
		var button = $(e.target);
		var stationid = parseInt(button.data('stationid'), 10);
		var paused = parseInt(button.data('paused'), 10);
		paused = paused ? 0 : 1;

		var data = {
			'stationid' : stationid,
			'paused' : paused
		};
		$.post('admin.php?mod=order:ticketprinter&action=pause', data, function(response){
			var response = parseInt(response, 10);
			if(!isNaN(response) && response > 0){
				button.data('paused', paused);
				var alternative_text = button.data('alternative-text');
				var text = button.text();
				button.text(alternative_text);
				button.data('alternative-text', text);
			}
		}, 'text');
	});
});