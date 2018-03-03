var isDown=false;

$(document).on('mousedown','.kanProject', function(){
	var isDown=true;
	console.log('down');
	$(this).addClass("border-primary");
});

$(document).on('mouseenter','.kanCol', function(){
	console.log(isDown);
	if(isDown){
		$(this).find('.kanGhost').show();
	}
});
$(document).on('mouseleave','.kanCol', function(){
	$(this).find('.kanGhost').hide();
});
