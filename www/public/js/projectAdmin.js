$(function(){
	$('.kanProject.hasAdmin').each(function(){
		var $el = $(this);
		$el.draggable({
			containment:$el.closest('.kanban'),
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
		});
	});
	$('.kanCol').droppable({
		classes: {
			"ui-droppable-hover":"kanActiveCol",
		},
		accept: ".kanProject",
		drop : function(event, ui){
			url = urlDict["project_move"];
			url = url.replace("idPlaceHold", ui.draggable.data('projectid'));
			url = url.replace("statusPlaceHold", $(this).data('status'));
			destCol = this;
			$.ajax(url,{
				async:false,
				error:function(xhr,status,error){
					message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
					$('#flashMessages').append(message);
				},
				success:function(data, status, xhr){
					if(data.success){
						ui.draggable.detach().appendTo(destCol);
					}else{
						message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
						$('#flashMessages').append(message);
					}
				}
			});
		}
	});
});
