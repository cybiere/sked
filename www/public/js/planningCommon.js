var resizeTimer;

function printPlanningItem(index,element){
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
					popTitle = data.projectName //TODO link to project ?
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
						confirmLink = urlDict['planning_confirm'].replace("123", data.planningId);
						popContent = popContent.concat("<div class='popupaction col-md-3'><a class='btn btn-outline-success' href='"+confirmLink+"'><i class='far fa-check-circle'></i></a></div>")
						if(data.projectBillable){
							meetingLink = urlDict['planning_meeting'].replace("123", data.planningId);
							popContent = popContent.concat("<div class='popupaction col-md-3'><a class='btn btn-outline-info' href='"+meetingLink+"'><i class='fas fa-exclamation-circle'></a></i></div>")
						}
						editLink = urlDict['project_edit'].replace("123", data.projectId);
						popContent = popContent.concat("<div class='popupaction col-md-3'><a class='btn btn-outline-warning' href='"+editLink+"'><i class='fas fa-edit'></i></a></div>")
					}
					deleteLink = urlDict['planning_delete'].replace("123", data.planningId);
					popContent = popContent.concat("<div class='popupaction col-md-3'><a class='btn btn-outline-danger' href='"+deleteLink+"'><i class='fas fa-trash'></i></a></div>")
				}
				popContent = popContent.concat("</div>")
				item.data("content",popContent)
				item.outerWidth(item.parents('td').outerWidth()*item.data("duration")-3);
			}else{
				item.remove();
				message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
				$('#flashMessages').append(message);
			}
		}
	});

}

$('.planningPlaceholder').each(printPlanningItem);

$(window).on('resize', function(e) {
	clearTimeout(resizeTimer);
	resizeTimer = setTimeout(function() {
		$('.project').each(function(index){
			$(this).outerWidth($(this).parents('td').outerWidth()*$(this).data("duration")-3);
		});
	}, 250);

});

$('.project').each(function(index){
	$(this).outerWidth($(this).parents('td').outerWidth()*$(this).data("duration")-3);
});

$( ".project" ).contextmenu(function() {
	$(this).popover('show')
	return false;
});

$( ".project" ).on('blur',function() {
	$(this).popover('hide')
});


