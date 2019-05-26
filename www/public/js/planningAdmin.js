function printPlanningItem(){
	$(this).removeClass('planningPlaceholder')
	planningId = $(this).data('planningid')
	$(this).addClass("project");
	item = $(this)

	url= urlDict["planning_info"];
	url = url.replace("idPlaceHold", planningId);

	$.ajax(url,{
		async:false,
		error:function(xhr,status,error){
			message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
			$('#flashMessages').append(message);
		},
		success:function(data, status, xhr){
			if(data.success){
				if(data.admin){
					item.addClass("hasAdmin")
				}
				if(data.projectId == 0){
					item.addClass("absence")
				}else{
					if(data.projectBillable){
						if(data.meeting){
							if(data.confirmed){
								item.addClass("meeting")
							}else{
								item.addClass("meeting-unconfirmed")
							}
						}else{
							if(data.confirmed){
								item.addClass("billable")
							}else{
								item.addClass("billable-unconfirmed")
							}
						}
					}else{
						item.addClass("non-billable")
					}
				}
				item.data("duration",data.duration)
				item.attr("tabindex","0")
				item.data("toggle","popover")
				item.data("placement","bottom")
				item.data("html",true)

				if(data.taskId != 0)
					if(data.taskComments != null)
						popTitle = "<a href='#' data-toggle='tooltip' title='"+data.taskComments+"'>"+data.taskName+"</a>";
					else
						popTitle = data.taskName
				else
					popTitle = data.projectName
				item.attr("title",popTitle)
				if(data.projectId != 0)
					itemName = data.projectClient+" "+data.projectName
				else
					itemName = data.projectName
				item.html(itemName.concat("<i class='duration'>"+(data.duration/2)+"</i>"))
				
				popContent = "<div class='row'>"
				if(data.projectId != 0){
					projectLink = urlDict['project_view'].replace("123", data.projectId);
					popContent = popContent.concat(`<dt class='col-md-6'>Projet</dt><dd class='col-md-6'><a href='`+projectLink+`'>`+data.projectName+`</a></dd>
<dt class='col-md-6'>Code projet</dt><dd class='col-md-6'>`+data.projectReference+`</dd>
<dt class='col-md-6'>Client</dt><dd class='col-md-6'>`+data.projectClient+`</dd>
<dt class='col-md-6'>Responsable</dt><dd class='col-md-6'>`);
					if(data.projectManagerName) popContent = popContent.concat(data.projectManagerName)
					popContent = popContent.concat("</dd><dt class='col-md-6'>jh planif/vendus</dt><dd class='col-md-6'>")
					if(data.projectNbDays) popContent = popContent.concat(data.projectPlannedDays,'/',data.projectNbDays)
					popContent = popContent.concat("</dd><dt class='col-md-6'>Commentaires</dt><dd class='col-md-6'>")
					if(data.projectComments) popContent = popContent.concat(data.projectComments)
					popContent = popContent.concat("</dd>")
				}
				if(data.admin){
					if(data.projectId != 0){
					popContent = popContent.concat("<div class='popupaction col-md-3'><button class='btn btn-outline-success' onclick='confirmPlanning("+data.planningId+")'><i class='far fa-check-circle'></i></button></div>")
						if(data.projectBillable){
							popContent = popContent.concat("<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='meetingPlanning("+data.planningId+")'><i class='fas fa-exclamation-circle'></i></button></div>")
						}
						editLink = urlDict['project_edit'].replace("123", data.projectId);
						popContent = popContent.concat("<div class='popupaction col-md-3'><a class='btn btn-outline-warning' href='"+editLink+"'><i class='fas fa-edit'></i></a></div>")
					}
					popContent = popContent.concat("<div class='popupaction col-md-3'><button class='btn btn-outline-danger' onclick='delPlanning("+data.planningId+")'><i class='fas fa-trash'></i></button></div>")
				}
				popContent = popContent.concat("</div>")
				item.data("content",popContent)
				item.outerWidth(item.parents('td').outerWidth()*item.data("duration")-3);
				item.resizable(resizeOptions);
				item.draggable(dragOptions); 
				item.on('mousedown',function(e){
					e.stopPropagation();
				});
			}else{
				item.remove();
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});

}

function delPlanning(planningId){
	url=urlDict["planning_delete"].replace("123", planningId);
	$.ajax(url,{
		async:false,
		error:function(xhr,status,error){
			message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
			$('#flashMessages').append(message);
		},
		success:function(data, status, xhr){
			if(data.success){
				$("div[data-planningId='"+planningId+"']").remove()
			}else{
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});
}

function confirmPlanning(planningId){
	url=urlDict["planning_confirm"].replace("123", planningId);
	$.ajax(url,{
		async:false,
		error:function(xhr,status,error){
			message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
			$('#flashMessages').append(message);
		},
		success:function(data, status, xhr){
			if(data.success){
				$("div[data-planningId='"+planningId+"']").removeClass("meeting meeting-unconfirmed billable billable-unconfirmed").addClass(data.addclass)
			}else{
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});
}

function meetingPlanning(planningId){
	url=urlDict["planning_meeting"].replace("123", planningId);
	$.ajax(url,{
		async:false,
		error:function(xhr,status,error){
			message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
			$('#flashMessages').append(message);
		},
		success:function(data, status, xhr){
			if(data.success){
				$("div[data-planningId='"+planningId+"']").removeClass("meeting meeting-unconfirmed billable billable-unconfirmed").addClass(data.addclass)
			}else{
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});
}

resizeOptions = {
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
}
dragOptions = {
	revert : true,
	revertDuration: 0,
	cursor: "grab",
	cursorAt: { left:5 }
}
$(".project.hasAdmin").resizable(resizeOptions);
$('.project.hasAdmin').draggable(dragOptions); 
$('.project').on('mousedown',function(e){
	e.stopPropagation();
});

$('.projectContainerAdmin').droppable({
	classes: {
		"ui-droppable-hover":"activeDrop",
	},
	accept: ".project",
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
	newPlanning['user'] = $('#addModal_ressourceForm').html()
	newPlanning['startDate'] = $('#addModal_startDateForm').html()
	newPlanning['startHour'] = $('#addModal_startHourForm').html()
	newPlanning['nbSlices'] = $('#addModal_durationForm').html()
	newPlanning['meeting'] = $('#addForm_meeting').prop('checked')
	newPlanning['confirmed'] = $('#addForm_confirmed').prop('checked')
	newPlanning['project'] = $('#addForm_projectId').val()
	newPlanning['task'] = $('#addForm_taskId').val()


	$('#addPlanning_Modal').modal('hide');

	$('#addForm_meeting').prop('checked',false)
	$('#addForm_confirmed').prop('checked',true)
	$('#addForm_projectId').val("")
	$('#addForm_taskId').val("")
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
				newDiv.appendTo($("div[data-date='"+newPlanning['startDate']+"'][data-hour='"+newPlanning['startHour']+"'][data-user='"+newPlanning['user']+"']:first"))
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


