var resizeTimer;

$(window).on('resize', function(e) {
	clearTimeout(resizeTimer);
	resizeTimer = setTimeout(function() {
		$('.project').each(function(index){
			$(this).outerWidth(($(this).parents('td').outerWidth() * $(this).data("duration") - 3) * 2);
		});
	}, 250);

});

$('.project').each(function(index){
	$(this).outerWidth(($(this).parents('td').outerWidth() * $(this).data("duration") - 3 ) * 2);
});

$( ".project" ).contextmenu(function() {
	$(this).popover('show')
	return false;
});

$( ".project" ).on('blur',function() {
	$(this).popover('hide')
});


