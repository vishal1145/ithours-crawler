$(document).ready(function () {
    var table = $('#first_names').DataTable({
        "processing": true,
        "ajax": "?__viewtype=json",
        "columns": [
            { "data": "id" },
            { "data": "email" },
            { "data": "username" },
            { "data": "password" },
            { "data": "used" }
        ]
    });


    $(document).on('click', '.trash', function () {


        $.ajax({
            type: "GET",
            url: $('#form_action').data("url") + '?id=' + $(this).data("id") + '&remove=remove',
            data: '', // serializes the form's elements.
            success: function (data) {
                table.ajax.reload();
                table
                    .order([0, 'desc'])
                    .draw();
            }
        });

    });

    // process the form
    $('form').submit(function (event) {

        var formData = {
            'first_names': $('textarea[name=first_names]').val()
        };

        // process the form
        $.ajax({
            type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url: $('#form_action').data("url"), // the url where we want to POST
            data: formData, // our data object
            dataType: 'json', // what type of data do we expect back from the server
            encode: true
        })
            // using the done promise callback
            .done(function (data) {

                if (!data.error) {

                    $("#buttonSubmit").toggleClass('alert-none');
                    $("#buttonOk").toggleClass('alert-none');

                    table.ajax.reload();
                    table
                        .order([0, 'desc'])
                        .draw();


                }

            });

        // stop the form from submitting the normal way and refreshing the page
        event.preventDefault();
        return false;
    });


   

    $("#addEmail").click(function () {
        var email = $("#email").val();
        var password = $("#password").val();

        var url = $('#email_insert').data('url') + "?__viewtype=json&action-data=add_Email&bot_email=" + email + "&bot_password=" + password;
        console.log(url);
        if (confirm('Are you sure you want to Add this Mail Id?')) {
        $.ajax({
            type: 'POST',
            url: url,
            data: {}, // serializes the form's elements.
            success: function (data) {
                console.log(data);
                // console.log("Buraya geldi "+url);
                alert(data.bot_email);
                alert(data.bot_password);
            }
            });

        } else {
            // Do nothing!
        }

    });

     $("#refresh").click(function () {
        
         window.location.reload(1);

    });

});