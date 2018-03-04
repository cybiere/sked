$(function(){
	$('.kanProject').draggable({
		containment : '#kanban',
		revert : true,
		classes: {
			"ui-draggable": "highlight"
		},
		stack: "div",
		start : function(){
			console.log('dragged');
			$(this).addClass("border-primary");
			$(this).addClass("kanFloat");
			$(document).on('mouseenter','.kanCol', function(event){
				$(this).find('.kanGhost').show();
			});
			$(document).on('mouseleave','.kanCol', function(event){
				$(this).find('.kanGhost').hide();
			});
		},
		stop : function(){
			$(this).removeClass("border-primary");
			$(this).removeClass("kanFloat");
			console.log('released');
			$(document).off('mouseenter','.kanCol');
			$(document).off('mouseleave','.kanCol');
			$('.kanGhost').hide();
		}
	}); // appel du plugin
});
