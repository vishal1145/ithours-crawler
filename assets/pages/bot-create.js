$(document).ready( function(){

	

    var getCountBots = function(type, url, data, exec){
         $.ajax({
           type: type,
           url: url,
           data: data, // serializes the form's elements.
           success: function(data)
            { 
                
                console.log("Buraya geldi "+url);
                exec();
                console.log("Burdan gitti");

            }
               
         });

    }

    getCountBots('GET', 'http://192.168.0.126/www/tw/v0.1/bot-create/?deneme=deger', '', function(){
        $('#total_bots').html('1524');
    });
    




    $('.counter').counterUp({time:15000, delay:20, startNum: 0});
});