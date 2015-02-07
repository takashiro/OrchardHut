$(function(){
	$('.dropmenu').each(function(){
		var menu = $(this);
		var submenu = menu.children('.submenu');

		submenu.css({
			top : -submenu.outerHeight(),
			left : menu.offset().left
		});
	});

	$('.dropmenu').click(function(){
		var submenu = $(this).children('.submenu');
		submenu.fadeToggle();
	});
});