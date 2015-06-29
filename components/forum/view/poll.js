$(document).ready(function() 
{
	$('.poll_toggle').click(function()
	{
				
		$('#poll_menu div:first').slideToggle();
		$('#poll').val('');
		
	});
	
	$('#add_answer').click(function()
	{
		$("<input type='text' name='answer[]' style='width: 280px;'><br />").appendTo("#answers");
	});
	
	$("#datepicker").datepicker({
		showOn: 'button',
		buttonImage: '/templates/admin/images/calendar.gif',
		buttonImageOnly: true,
		dateFormat: 'dd.mm.yy',
		monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
		dayNamesShort: ['Вскр', 'Пнд', 'Вт', 'Ср', 'Чтв', 'Птн', 'Сбт'],
		dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
	});
});
