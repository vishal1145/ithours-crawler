$(document).ready( function(){

    

    var getCountBots = function(type, url, formData, exec){
         $.ajax({
           type: type,
           url: url,
           data: formData, // serializes the form's elements.
           success: function(data)
            { 
                
               // console.log("Buraya geldi "+url);
                exec(data);

            }
               
         });

    }

    var countUp = function(selector, settings){

        $(selector).each(function () {
            $(this).prop('Counter', settings.start).animate({
                Counter: $(this).text()
            }, {
                duration: settings.duration,
                easing: 'swing',
                step: function (now) {
                    $(this).text(Math.ceil(now));
                }
            });
        });
    }


    function count(element, from, to, duration) {
        $({value:from}).animate({value:to}, {
            duration:   duration,
            step:   function() {$(element).html(Math.ceil(this.value));},
            always: function() {$(element).html(Math.ceil(this.value));}
        });
    }


    
    getCountBots('POST', $('#total_bots').data('url')+"&action-data=all", {}, function(data){
        
       
        console.log("All Bots: "+data);
        $('#total_bots').html(data.total_bots);
        $('#bots_24_hours').html(data.bots_24_hours);
        $('#bots_1_hours').html(data.bots_1_hours);
        $('#waiting_bots').html(data.waiting_bots);

        $('.counter').each(function () {
            if($(this).attr('id')=='waiting_bots'){
                count($(this), (data.waiting_bots+data.bots_24_hours), parseInt($(this).text()), 5000);
            } else {
                count($(this), 0, parseInt($(this).text()), 5000);
            }
            

        });
         
        //count($('.counter'), 0, parseInt($('.counter').text()), 5000);
        //countUp('.counter', {start: 0, duration: 4000});
        
    });
    
    $('#create_bot').submit(function(event) {

        var formData = {
                    'count_of_bots'              : $('#count_of_bots').val()
            };

        getCountBots('POST', $('#create_bot').data('url'), formData, function(data){

            console.log("Answer: "+data);
            $('#count_of_bots').val('');
           // $('#waiting_bots').html(data.count_of_waiting_boat_total);
           // countUp('#waiting_bots', {start: data.waiting_bots, duration: 4000});
            count($('#waiting_bots'), parseInt($('#waiting_bots').text()), parseInt(data.count_of_waiting_boat_total), 5000);

        });

        event.preventDefault();
        return false;
    });

    $('#update_setting_min_max').submit(function(event) {

        var formData = {
                    'bot_username_min'              : $('#bot_username_min').val(),
                    'bot_username_max'              : $('#bot_username_max').val()
            };

        getCountBots('POST', $('#update_setting_min_max').data('url'), formData, function(data){

            console.log("Answer: "+data);
            //$('#count_of_bots').val('');
           // $('#waiting_bots').html(data.count_of_waiting_boat_total);
           // countUp('#waiting_bots', {start: data.waiting_bots, duration: 4000});
          //  count($('#waiting_bots'), parseInt($('#waiting_bots').text()), parseInt(data.count_of_waiting_boat_total), 5000);

        });

        event.preventDefault();
        return false;
    });

     $('#daily_bot_creation_min_max').submit(function(event) {

        var formData = {
                    'daily_bot_creation_min'              : $('#daily_bot_creation_min').val(),
                    'daily_bot_creation_max'              : $('#daily_bot_creation_max').val()
            };

        getCountBots('POST', $('#daily_bot_creation_min_max').data('url'), formData, function(data){

            console.log("Answer: "+data);
            //$('#count_of_bots').val('');
           // $('#waiting_bots').html(data.count_of_waiting_boat_total);
           // countUp('#waiting_bots', {start: data.waiting_bots, duration: 4000});
          //  count($('#waiting_bots'), parseInt($('#waiting_bots').text()), parseInt(data.count_of_waiting_boat_total), 5000);

        });

        event.preventDefault();
        return false;
    });

 // 

    setInterval(function(){ 
        
        getCountBots('POST', $('#waiting_bots').data('url')+"&action-data=all", {}, function(data){
        
            count($('#waiting_bots'), parseInt($('#waiting_bots').text()), data.waiting_bots, 5000);
            count($('#bots_1_hours'), parseInt($('#bots_1_hours').text()), data.bots_1_hours, 5000);

            count($('#total_bots'), parseInt($('#total_bots').text()), data.total_bots, 5000);
            count($('#bots_24_hours'), parseInt($('#bots_24_hours').text()), data.bots_24_hours, 5000);       
        });


    }, 20000);


  //  $('.counter').counterUp({time:15000, delay:20});
});