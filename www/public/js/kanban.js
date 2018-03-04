$(function(){
	$('.kanProject').draggable({
		containment : '#kanban',
		revert : true,
	    revertDuration: 0,
		cursor: "grab",
		start : function(){
			$(this).addClass("border-primary");
			$(this).addClass("kanFloat");
		},
		stop : function(){
			$(this).removeClass("border-primary");
			$(this).removeClass("kanFloat");
		}
	}); // appel du plugin
	$('.kanCol').droppable({
		classes: {
			"ui-droppable-hover":"kanActiveCol",
		},
		drop : function(event, ui){
			ui.draggable.detach().appendTo(this);
		}
	});
});
