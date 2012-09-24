<div class="newRoute beginRoute alert alert-success">Начало</div>
<div id="appendRoute">

<div id="subTable">
	<div class="st_substep">Этап</div>
	<div class="st_step">Задачи</div>
	<div class="st_stepaction">Действия</div>
</div>
	
{% for part in steps %}
<div class="newRoute newStep" id="step_{{ part.step_id }}">
	
	<div class="substep"><h4>{{ part.name }}</h4></div>
	
	<div class="step"></div>

	<div class="stepaction" style="overflow: hidden; font-size: 11px">
	{% for action in part.action %}
	<div style="margin-bottom: 10px">
	{{ action.ifdataval }} {{ action.ifcon }} {{ action.ifval }}
	<br />
	<b>Переход:</b> {{ action.gotoval }}
	</div>
	{% endfor %}
	</div>
	
</div>
{% endfor %}
</div>
<div class="newRoute endRoute alert alert-error">Конец</div>

<script type="text/javascript">
$.ajax({
	type: "POST",
	url: '{{ registry.uri }}ajax/route/',
	data: "action=getRealTasks&rid={{ rid }}",
	dataType: 'json',
	success: function(res) {
		var taskname = "";
		$.each(res, function(key, val) {
			taskname = "Без названия";
			if (val["task"]) {
				$.each(val["task"], function(param, value) {
					if (param == "taskname") {
						taskname = value;
					}
				});
			}
			$("#step_" + val["step_id"] + " .step").append("<div class='info stask' id='task_" + val["tid"] + "'>" + taskname + "<div>");
		});
		
		taskContext();
	}
});

$(".newStep").each(function(){
	var height = $(this).height();
	$(".substep", this).height(height);
	$(".step", this).height(height);
	$(".stepaction", this).height(height);
});
</script>
