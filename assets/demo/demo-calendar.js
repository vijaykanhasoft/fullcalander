
// NOTE: HERE I AM CONSIDERING MAIN USER USER_ID 1 , but in other cases consider login user as a MAIN USER.
// IN MY CASE I HAVE THREE TYPE OF USER , 1) EXECUTIVE , 2) MANAGER , 3) BASIC USER.


/* THIS IS A TEMPLATE FOR MAIN USER */

var main_user = '<div class="form-group"><label class="col-sm-5">My Events</label><div class="col-sm-1"><input type="checkbox" class="user_data" id="1" value="1" checked="checked"/>'
			   + '</div><div class="demo2 col-sm-1"  data-color="${own_event}"><input type="text" name="color[own][event]" id="color1" class="color1"  value="${own_event}" /></div></div>'
			   + '<div class="form-group"><label class="col-sm-5">My Tasks</label><div class="col-sm-1"><input type="checkbox" class="user_data" id="2" value="1" checked="checked"/>'
			   + '</div><div class="demo2 col-sm-1"  data-color="${own_task}"><input type="text" name="color[own][task]" id="color1" class="color1"  value="${own_task}" /></div></div>';
$.template("master_user_display", main_user);

/* THIS IS A TEMPLATE FOR DISPLAY OTHER USERS */

var markup = '<div class="form-group"><label class="col-sm-5">${name + " " + last_name} </label><div class="col-sm-1"><input type="checkbox" class="user_data" id="1" value="${user_id}"/></div><div class="demo2 col-sm-1"  data-color="${color_event}">' 
            + '<input type="text" name="color[users][${user_id}][event]" id="color1" class="color1"  value="${color_event}" /></div><div class="col-sm-1">' 
            + '<input type="checkbox" class="user_data" id="2" value="${user_id}"/></div>'
            + '<div class="demo2 col-sm-1"  data-color="${color_task}"><input type="text" name="color[users][${user_id}][task]" id="color1" class="color1"  value="${color_task}" /></div></div>';
$.template("user_display", markup);


/* GET ALL USER LIST. */
jQuery.ajax({
        type: 'GET',
        url: "api/v1/calendar_user/1",
        data: "",
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(data) {
        		
        	if(!data.error){
        		
                /* Executive Information */
        		var executive = data.data[1];
                /* Manager Information */
        		var manager = data.data[2];
                /* Basic User Information */
        		var basic_user = data.data[3];


                /* Bind Login User Information */
        		$.tmpl("master_user_display", data.data).appendTo(".main_user");

                /* Bind Executive Information */
				$.tmpl("user_display", executive).appendTo(".executive_list");

                /* Bind Manager Information */
				$.tmpl("user_display", manager).appendTo(".manager_list");

                /* Bind Basic User Information */
				$.tmpl("user_display", basic_user).appendTo(".basic_user_list");

                /* Bind color picker for all users */
				$('.color1').colorPicker({showHexField: false});

        	}

                /* BIND CALANDER BASED ON SELECTED CHECKBOX IN USER LIST */
				bind_calendar();
        }
    });

function getEventsTask() {
        
        var user_id = [];
        if ($('.user_data:checked').length) {
            var chkId = '';
            var id = '';
            $('.user_data:checked').each(function() {
        
                id = $(this).attr('id');
                chkId = $(this).val();
                var temp = chkId + '-' + id;
                user_id.push(temp);
            });
        }

        return user_id;
    }

function bind_calendar(){

    /* GET LAST CALANDER VIEW(MONTH,WEEK,DAY) OF USER SCREEN WHICH IS SAVED IN LOCALHOST */
	var lastView = localStorage.getItem('lastView');
    if (lastView == null) { // IF NOT FIND LOCALHOST THEN SET A MONTH VIEW.
        lastView = 'month';
    }

	var calendar = $('#calendar-external').fullCalendar({
        header: {
            left: '',
            center: 'prev title next',
            right: 'today month,agendaWeek,agendaDay'
        },
        nextDayThreshold: '00:00:00',
        buttonIcons: {
            prev: 'left-single-arrow',
            next: 'right-single-arrow'
        },
        handleWindowResize: true,
        aspectRatio: 2,
        events: function(start, end, timezone,callback) {
    	 	/* DATA IS FETCHED BASED ON START DATE AND END DATE  */
            $.ajax({
                url: 'api/v1/calendar',
                type: 'GET',
                data: {
                    start: moment(start).unix(), //start date
                    end: moment(end).unix(), // end date.
                    dataType: 'json',
                    user_id: getEventsTask().toString(), // GET CHECKED USER LIST.
                    created_by: 1
                },
                success: function(events) {
                    // CALLBACK EVENTS SO IT WILL DISPLAY INTO THE CALENDER
                    callback(events);
                }
            });
        },
        defaultView: lastView,
        eventLimit: true,
        editable: true,
        slotEventOverlap: false,
        droppable: true,
        selectable: false,
        selectHelper: true,
        timezone: 'local',
        height: $(window).height()*0.83, 
        eventMouseover: function(calEvent, event, view) {

            // THIS IS FOR DISPLAY TOOLTIP WHEN MOUSE OVER ON EVENTS AND TASKS.

            if (calEvent.start_date != undefined && calEvent.end_date != undefined) {

                var event_background = $(event.currentTarget).css("backgroundColor");
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                var start_date = moment(calEvent.start_date).format('ll'); 
                
                var end_date = moment(calEvent.end_date).format('ll');
                

                // CHECK THAT EVENT IS CURRENT TIME LINE OR NOT. IN WEEK AND DAY VIEW THERE IS A RED LINE WHICH IS DISPLYING THE CURRENT TIME.  
                if ($(this).hasClass('current-time-event') == false)
                {
  
                    if (calEvent.calendar_type == 2) // CALANDER_TYPE = 2 is for the TASK.
                        tooltip = '<div class="tooltiptopicevent" style="width:auto;height:auto;background:#F2F9FF;position:absolute;z-index:10001;padding:10px 10px 10px 10px ;border:3px solid ' + event_background + ';  line-height: 200%;"><span style=float:right;>' + start_date + '</span><br />' + calEvent.title + '</br>' + 'Assigned to: ' + calEvent.assignedname + '</div>';
                    else
                        tooltip = '<div class="tooltiptopicevent" style="width:auto;height:auto;background:#F2F9FF;position:absolute;z-index:10001;padding:10px 10px 10px 10px ;border:3px solid ' + event_background + ';  line-height: 200%;"><span style=float:right;>' + start_date + ' - ' + end_date + '</span><br />' + calEvent.title + '</div>';

                    // Append tooltip to body.
                    $("body").append(tooltip);

                    // Move tooltip on mouse move.
                    $(this).mouseover(function(e) {
                        $(this).css('z-index', 10000);
                        $('.tooltiptopicevent').fadeIn('500');
                        $('.tooltiptopicevent').fadeTo('10', 1.9);
                    }).mousemove(function(e) { 
                        if (e.pageY + 170 > $(window).height()) {
                            $('.tooltiptopicevent').css('top', e.pageY - 100);
                            $('.tooltiptopicevent').css('left', e.pageX + 20);
                        }
                        else {
                            $('.tooltiptopicevent').css('top', e.pageY + 10);
                            $('.tooltiptopicevent').css('left', e.pageX + 20);
                        }
                    });
                }
            }
        },
        eventMouseout: function(data, event, view) {

            // REMOVE TOOLTIP WHEN MOUSE OUT OF THE EVENT/TASK DISPLAY
            $(this).css('z-index', 8);
            $('.tooltiptopicevent').remove();

        },
        viewRender: function(view, element) {
            
            // ADD ONE BLANK HEADER FOR DISPLAY DATE PROPER IN WEEK VIEW.
            if (view.name == 'agendaWeek')
                $('th.fc-widget-header:first').before("<th class='fc-day-header fc-widget-header' style='width:51px;'>&nbsp;</th>");
            
            // SET CURRENT VIEW IN LOCALSTORAGE.
            localStorage.setItem('lastView', view.name);

            /* THIS FUNCTION IS FOR DISPLAY RED LINE AT CURRENT TIME IN WEEK AND DAY VIEW. */
            if (view.name == "agendaWeek" || view.name == "agendaDay") {
                
                view.calendar.removeEvents("currenttime");
                var f = view.calendar.renderEvent({id: "currenttime", title: "Current Time", start: moment(), end: moment().add('minutes', 1), className: "current-time-event", editable: false}, true);
                
                setTimeout(function() {
                    /* This is for remove one length from the event/task count in day view.It is considering current time as a one event. */
                    if ($('.fc-time-grid-event').hasClass('current-time-event')) {
                        $('.todaysevent').html($('.fc-event-container a.fc-time-grid-event').length - 1);
                    } else {
                        $('.todaysevent').html($('.fc-event-container a.fc-time-grid-event').length);
                    }
                }, 1500);
            } else {
                /* IF USER CHECKING OTHER VIEW THEN REMOE CURREENT TIME. */
                view.calendar.removeEvents("currenttime");
            }
        }
    });

	$('.user_data').click(function() { //CHECKBOX CLICK EVENT FOR REFETCH DATA.
	        $('#calendar-external').fullCalendar('refetchEvents');
	        $('#calendar-external').fullCalendar('refresh');
	});

    /* ON EVENT AND TASK COLOR CHANGE SAVE NEW SETTING INTO THE DATABASE USING THIS. */
	$('.color1').change(function() {
        jQuery.ajax({
            type: 'POST',
            url: "api/v1/calendarSettings",
            data: jQuery("#form_settings").serialize(),
            success: function(data) {

                if (typeof data != 'object')
                    var result = JSON.parse(jQuery.trim(data));
                else
                    var result = jQuery.trim(data);

                /*Refetch event/task after set new color.*/
                setTimeout(function() {
                    calendar.fullCalendar('refetchEvents');
                }, 2000);
            }
        });
    });

    // PREPEND CALENDER BEFORE TODAY IN RIGHT LIST , USING THIS CALENDER USER CAN EASILY MOVE TO ANOTHER MONTH , ANOTHER YEAR.
    $('.fc-right').prepend('<span class="form-group" id="caldatepicker"><i class="fa fa-calendar"></i></span>');

    /* BIND CALENDER TO ABOVE DEFIND ICON. */
    $('#caldatepicker').datepicker({
         closeOnDateSelect: true
    }).on('changeDate', function (ev) {
			// CHANGE FULL CALENDER MONTH AND YEAR WHEN THIS CALENDER DATE SELECT.
    		calendar.fullCalendar('gotoDate', ev.date);
	});

}



	 