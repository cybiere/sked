$(function() {
  $('form.addTask select#task_project').change(function() {

    if ($(this).val() == 0)
      return;

    url = urlDict["project_getUsers"];
    url = url.replace("idPlaceHold", $(this).val());

    $.ajax(url,{
      async: false,
      error: function(xhr, status, error) {
        message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + error + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
        $('#flashMessages').append(message);
      },
      success:function(data, status, xhr) {
        if (data.success) {
          $('#task_assignedTo').empty();
          $('#task_assignedTo').append('<option value=""></option>');

          data.users.forEach(function(e) {
            $('#task_assignedTo').append('<option value="' + e.id + '">' + e.name + '</option>');
            $('#addForm_taskId').prop("disabled", false);
          });
        } else{
          message='<div class="alert alert-danger alert-dismissible fade show" role="alert">\nErreur : ' + data.errormsg + '\n<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n<span aria-hidden="true">&times;</span>\n</button>\n</div>';
          $('#flashMessages').append(message);
        }
      }
    });

  });
});