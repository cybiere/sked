$(".hasAdmin").resizable({
	handles: "e",
	containment: ".schedule",
	resize: function(e,ui){
		newSize = Math.round(((ui.size.width-(ui.size.width%$(this).parent().parent().outerWidth()))/$(this).parent().parent().outerWidth())+1);
		$(this).find('i').text(newSize/2);
		$(this).outerHeight(30);
	},
	stop : function(event, ui){
		newSize = Math.round(((ui.size.width-(ui.size.width%$(this).parent().parent().outerWidth()))/$(this).parent().parent().outerWidth())+1);
		if(newSize > $(this).parent().data("remainingslices")+1){
			newSize = $(this).parent().data("remainingslices")+1;
		}
		url= urlDict["planning_resize"];
		url = url.replace("idPlaceHold", ui.element.data('planningid'));
		url = url.replace("sizePlaceHold", newSize);
		$.ajax(url,{
			async:false,
			error:function(xhr,status,error){
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
				ui.element.width(ui.originalSize.width);
			},
			success:function(data, status, xhr){
				if(data.success){
					ui.element.data("duration",newSize);
					$(ui.element).outerWidth($(ui.element).parent().parent().outerWidth()*newSize-3);
				}else{
					message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
					$('#flashMessages').append(message);
					ui.element.width(ui.originalSize.width);
				}
			}
		});
	}
});
$('.project').on('mousedown',function(e){
	e.stopPropagation();
});
$('.hasAdmin').draggable({
	revert : true,
	revertDuration: 0,
	cursor: "grab",
	cursorAt: { left:5 }
}); 

$('.projectContainerAdmin').droppable({
	classes: {
		"ui-droppable-hover":"activeDrop",
	},
	tolerance: "pointer",
	drop : function(e, ui){
		destCell = this;
		url= urlDict["planning_move"];
		url = url.replace("idPlaceHold", ui.draggable.data('planningid'));
		url = url.replace("startPlaceHold", $(this).data('date'));
		url = url.replace("hourPlaceHold", $(this).data('hour'));
		url = url.replace("userPlaceHold", $(this).data('user'));
		if(ui.draggable.data("duration") > $(this).data("remainingslices")+1){
			ui.draggable.data("duration",$(this).data("remainingslices")+1);
			ui.draggable.find('i').text(($(this).data("remainingslices")+1)/2);
			ui.draggable.outerWidth($(this).parent().outerWidth()*ui.draggable.data("duration")-3);
		}
		url = url.replace("sizePlaceHold", ui.draggable.data('duration'));
		$.ajax(url,{
			async:false,
			error:function(xhr,status,error){
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			},
			success:function(data, status, xhr){
				if(data.success){
					ui.draggable.detach().appendTo(destCell);
					ui.draggable.outerWidth($(destCell).parent().outerWidth()*ui.draggable.data("duration")-3);
				}else{
					message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
					$('#flashMessages').append(message);
				}
			}
		});
	}
});

$('.projectContainer').on('mousedown',function(e){
	e.preventDefault();
});

baseX=0;
mouseDown=false;
newGhost=null;
add_startDate=null;
add_startHour=null;
add_userId=null;
gridWidth=0;
$('.projectContainerAdmin').mousedown(function(e){
	if(e.which != 1) return;
	$("<div class='projectGhost'>Nouveau projet</div>").appendTo(this);
	add_startDate=$(this).data('date');
	add_startHour=$(this).data('hour');
	gridWidth=$(this).parent().outerWidth();
	add_userId=$(this).data('user');
	newGhost = $(this).find('.projectGhost');
	baseX = newGhost.offset().left;
	mouseDown=true;
});
$(document).mousemove(function(e){
	if(mouseDown){
		newGhost.outerWidth(e.pageX - baseX);
	}
});
$(document).mouseup(function(e){
	if(mouseDown){
		ghostSize = e.pageX-baseX
		modulo = ghostSize % gridWidth;
		nbSlices = Math.round((ghostSize-modulo)/gridWidth+1);

		newGhost.remove();

		$('#planning_user').val(add_userId);
		$('#planning_startDate').val(add_startDate);
		$('#planning_startHour').val(add_startHour);
		$('#planning_nbSlices').val(nbSlices);

		$('#addModal_ressource').html(users[add_userId]);
		$('#addModal_ressourceForm').html(add_userId);
		$('#addModal_startDate').html(new Date(add_startDate).toLocaleDateString('fr-FR'));
		$('#addModal_startDateForm').html(add_startDate);
		$('#addModal_startHour').html(add_startHour == "am" ? "matin" : "midi");
		$('#addModal_startHourForm').html(add_startHour);
		$('#addModal_duration').html(nbSlices/2 + 'jh');
		$('#addModal_durationForm').html(nbSlices);
		$('#addPlanning_Modal').modal();

		baseX=0;
		mouseDown=false;
		newGhost=null;
		add_startDate=null;
		add_startHour=null;
		add_userId=null;
		gridWidth=0;
	}
});

var newPlanning = {}
function addPlanning(){
	$('#planning_meeting').prop('checked',$('#addForm_meeting').prop('checked'));
	$('#planning_confirmed').prop('checked',$('#addForm_confirmed').prop('checked'));
	$('#planning_project').val($('#addForm_projectId').val());
	$('#planning_task').val($('#addForm_taskId').val());

	newPlanning['user'] = $('#addModal_ressourceForm').html()
	newPlanning['startDate'] = $('#addModal_startDateForm').html()
	newPlanning['startHour'] = $('#addModal_startHourForm').html()
	newPlanning['nbSlices'] = $('#addModal_durationForm').html()
	newPlanning['meeting'] = $('#addForm_meeting').prop('checked')
	newPlanning['confirmed'] = $('#addForm_confirmed').prop('checked')
	newPlanning['project'] = $('#addForm_projectId').val()
	newPlanning['task'] = $('#addForm_taskId').val()

	$('#addPlanning_Modal').modal('hide');
	//TODO RAZ champs modal
	if(projectsRemaining[newPlanning['project']] != null && newPlanning['nbSlices'] > projectsRemaining[newPlanning['project']]){
		$('#overrun_Modal').modal();
		return false;
	}else{
		sendPlanningNewRequest()
	}
}

function sendPlanningNewRequest(){
	$('#overrun_Modal').modal('hide');
	$.ajax({
		type: "POST",
		url:urlDict["planning_new"],
		data:newPlanning,
		error:function(xhr,status,error){
			message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
			$('#flashMessages').append(message);
		},
		success:function(data, status, xhr){
			if(data.success){
				newDiv = $("<div data-planningid='"+data.id+"' class='planningPlaceholder'>Planning</div>")
				newDiv.appendTo($("div[data-date='"+newPlanning['startDate']+"'][data-hour='"+newPlanning['startHour']+"'][data-user='"+newPlanning['user']+"']"))
				newDiv.each(printPlanningItem);
				newDiv.contextmenu(function() {
					$(this).popover('show')
						return false;
					});
				newDiv.on('blur',function() {
					$(this).popover('hide')
				});


			}else{
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});
}

$('#addForm_projectId').change(function(){
	if($(this).val() != 0){
		$('#addForm_taskId').prop("disabled",true);
		$('#addForm_taskId').val(0).change();
		$('#addForm_taskId').find('option').not('#nullTask').remove();
		url= urlDict["task_byproject"];
		url = url.replace("idPlaceHold",$(this).val());
		$.ajax(url,{
			async:false,
			error:function(xhr,status,error){
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			},
			success:function(data, status, xhr){
				if(data.success){
					data.tasks.forEach(function(e){
						$('#addForm_taskId').append('<option value="' + e.id + '">' + e.name + '</option>');
						$('#addForm_taskId').prop("disabled",false);
					});
				}else{
					message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
					$('#flashMessages').append(message);
				}
			}
		});

	}
});


