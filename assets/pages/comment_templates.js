$(document).ready(function() {
    var table = $('#template').DataTable( {
        "processing": true,
        "ajax": "?__viewtype=json",
        "columns": [
            { "data": "id" },
            { "data": "name" },
            { "data": "action", "class":"text-center", "orderable": false }
        ]
    } );

    var commentTree = function(){
                        $('#container').jstree({
                                    'core' : {
                                        "check_callback" : true,
                                    'multiple' : false,
                                      'data' : {
                                        "url" : "http://35.224.251.105/comment-templates/?treeView=true",
                                        "dataType" : "json", // needed only if you do not supply JSON headers
                                      }
                                    },
                                    "checkbox" : { 
                                        "whole_node" : false, 
                                        "keep_selected_style" : false, 
                                        "three_state" : true,
                                        "tie_selection": false
                                    },
                                    "plugins" : ["contextmenu", "checkbox", "types"]
                         }).refresh();
        return true;
    }

    
    

    $(document).on('click', '.trash', function(){
        
       
        $.ajax({
           type: "GET",
           url: $('#form_action').data("url")+'?id='+$(this).data("id")+'&remove=remove',
           data: '', // serializes the form's elements.
           success: function(data)
            {
               table.ajax.reload();
                table
                    .order( [ 0, 'desc' ] )
                    .draw();

                    commentTree();
            }
         });

        });

    // process the form
     $('form').submit(function(event) {

                $('#buttonSubmit').html(' <i class="fa fa-spinner fa-spin fa-2x fa-fw margin-bottom " id="but_spin"></i> ');

                // 

                var formData = {
                    'template'              : $('textarea[name=template]').val()
                };

                                    // process the form
                 $.ajax({
                     type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                     url         : $('#form_action').data("url"), // the url where we want to POST
                     data        : formData, // our data object
                     dataType    : 'json', // what type of data do we expect back from the server
                     encode      : true
                 })
                                        // using the done promise callback
                 .done(function(data) {

                  if(!data.error) {
                     
                        $('textarea[name=template]').val('');

                        $('#buttonSubmit').html(' ADD <i class="fa fa-check-circle f-30"></i> ');
                        //<i class="fa fa-check-circle f-30"></i> 
                        // buttonSubmit


                        commentTree();

                        table.ajax.reload();
                        table
                            .order( [ 0, 'desc' ] )
                            .draw();

                           
                     } 

                });

                // stop the form from submitting the normal way and refreshing the page
        event.preventDefault();
        return false;
    });

    
} );