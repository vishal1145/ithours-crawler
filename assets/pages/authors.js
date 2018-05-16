$(document).ready(function() {

     var table = $('#authors').DataTable( {
        "processing": true,
        "ajax": "?__viewtype=json",
        "order": [ 0, 'desc' ],
        "columns": [
            { 
                "data": "id"
            },
            { 
                "data": "img",
                render: function ( data, type, row ) {
                            return '<img class="img-circle img-fluid" src="../assets/images/authors/'+row.id+'.'+data+'" alt="">';
                                   
                },
                "class":"text-center text-middle", "orderable": false

            },
            { 
                "data": "name",
                render: function ( data, type, row ) {
                            return '<h6>'+data+'</h6>';
                },
                "class":" text-middle"      

            },
            { 
                "data": "follows",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_follow_'+row.id+'" name="target_follow_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "likes",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_likes_'+row.id+'" name="target_article_likes_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "likesmax",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_likes_max_'+row.id+'" name="target_article_likes_max_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "comments",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_comments_'+row.id+'" name="target_article_comments_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "commentsmax",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_comments_max_'+row.id+'" name="target_article_comments_max_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "views",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_views_'+row.id+'" name="target_article_views_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "viewsmax",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_views_max_'+row.id+'" name="target_article_views_max_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "gmin",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_gmin_'+row.id+'" name="target_article_gmin_'+row.id+'" min="0">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },
            { 
                "data": "gmax",
                render: function ( data, type, row ) {
                            return ' <div class="form-group row">'
                                    +'   '  
                                    +'    <div class="col-sm-6 col-md-6 col-xs-12 ">'  
                                    +'        <input class="form-control anti-form-control" type="text" value="'+data+'" id="target_article_gmax_'+row.id+'" name="target_article_gmax_'+row.id+'" min="1">'  
                                    +'    </div>'  
                                    +'</div> ';
                },
                "class":"text-center text-middle", "orderable": false
            },

            { 
                "data": "action",
                render: function ( data, type, row ) {
                            return '<div class="card-block button-list"> '                               
                                +'      <button type="submit" id="buttonSubmit_'+row.id+'" data-id ="'+row.id+'" class="btn btn-sm btn-success btn-block waves-effect  f-30 authorUpdate" >update</button>'
                                +' '+data+''
                                +'  </div>';
                },
                "class":"text-center text-middle", "orderable": false
            }
        ],


    } );

    var sendAjaxForm = function(type, url, formData, exec){
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

     $('#form_action').submit(function(event) {

        var formData = {
                    'action'                     : 'addAuthor',
                    'author_url'                 : $('#author_url').val() ,
                    'target_follow'              : $('#target_follow').val(),
                    'target_article_likes'       : $('#target_article_likes').val(),
                    'target_article_comments'    : $('#target_article_comments').val(),
                    'target_article_views'       : $('#target_article_views').val(),
                    'target_article_likes_max'       : $('#target_article_likes_max').val(),
                    'target_article_comments_max'    : $('#target_article_comments_max').val(),
                    'target_article_views_max'       : $('#target_article_views_max').val(),

                    'gmin'                     : $('#gmin').val(),
                    'gmax'                     : $('#gmax').val()
            };

        

        $("#but_spin").toggleClass('alert-none');
        $("#but_add").toggleClass('alert-none');

        sendAjaxForm('POST', $('#form_action').data('url'), formData, function(data){

            

            if(!data.error) {
                     
                      $("#buttonSubmit").toggleClass('alert-none');
                      $("#buttonOk").toggleClass('alert-none');

                        table.ajax.reload();
                        table
                            .order( [ 0, 'desc' ] )
                            .draw();
                           
            }  else {
                console.log("Answer: "+data);
                $("#but_error").toggleClass('alert-none');
                 $("#but_spin").toggleClass('alert-none');
            }
           

        });

        event.preventDefault();
        return false;
    });


// authorUpdate

    $(document).on('click', '.authorUpdate', function(){

        
        var id = $(this).data("id");

        console.log('B ' +id);

        var target_follow           = ($('[name="target_follow_'+id+'"]').eq(1).length) ? $('[name="target_follow_'+id+'"]').eq(1).val() : $('[name="target_follow_'+id+'"]').eq(0).val();
        var target_article_likes    = ($('[name="target_article_likes_'+id+'"]').eq(1).length) ? $('[name="target_article_likes_'+id+'"]').eq(1).val() : $('[name="target_article_likes_'+id+'"]').eq(0).val();
        var target_article_comments = ($('[name="target_article_comments_'+id+'"]').eq(1).length) ? $('[name="target_article_comments_'+id+'"]').eq(1).val() : $('[name="target_article_comments_'+id+'"]').eq(0).val();
        var target_article_views    = ($('[name="target_article_views_'+id+'"]').eq(1).length) ? $('[name="target_article_views_'+id+'"]').eq(1).val() : $('[name="target_article_views_'+id+'"]').eq(0).val();

        var target_article_likes_max    = ($('[name="target_article_likes_max_'+id+'"]').eq(1).length) ? $('[name="target_article_likes_max_'+id+'"]').eq(1).val() : $('[name="target_article_likes_max_'+id+'"]').eq(0).val();
        var target_article_comments_max = ($('[name="target_article_comments_max_'+id+'"]').eq(1).length) ? $('[name="target_article_comments_max_'+id+'"]').eq(1).val() : $('[name="target_article_comments_max_'+id+'"]').eq(0).val();
        var target_article_views_max    = ($('[name="target_article_views_max_'+id+'"]').eq(1).length) ? $('[name="target_article_views_max_'+id+'"]').eq(1).val() : $('[name="target_article_views_max_'+id+'"]').eq(0).val();


        var gmin    = ($('[name="target_article_gmin_'+id+'"]').eq(1).length) ? $('[name="target_article_gmin_'+id+'"]').eq(1).val() : $('[name="target_article_gmin_'+id+'"]').eq(0).val();
        var gmax    = ($('[name="target_article_gmax_'+id+'"]').eq(1).length) ? $('[name="target_article_gmax_'+id+'"]').eq(1).val() : $('[name="target_article_gmax_'+id+'"]').eq(0).val();


        var formData = {
                    'action'                     : 'updateAuthor',
                    'author_id'                  : id,
                    'target_follow'              : target_follow,
                    'target_article_likes'       : target_article_likes,
                    'target_article_comments'    : target_article_comments,
                    'target_article_views'       : target_article_views,
                    'target_article_likes_max'       : target_article_likes_max,
                    'target_article_comments_max'    : target_article_comments_max,
                    'target_article_views_max'       : target_article_views_max,
                    'gmin'                     : gmin,
                    'gmax'                     : gmax
            };

        sendAjaxForm('POST', $('#form_action').data('url'), formData, function(data){

            

            if(!data.error) {
                     
                      
                        table.ajax.reload();
                        table
                            .order( [ 0, 'desc' ] )
                            .draw();



                           
            }  else {
                console.log("Answer: "+data);
            }
           

        console.log(id);
       
       /*
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
                }
             });

             */

        });


    });


    $(document).on('click', '.authorDelete', function(){
        
        var id = $(this).data("id");

        var formData = {
                    'action'                     : 'authorDelete',
                    'author_id'                  : id
            };

        sendAjaxForm('POST', $('#form_action').data('url'), formData, function(data){

            

            if(!data.error) {
                     
                      
                        table.ajax.reload();
                        table
                            .order( [ 0, 'desc' ] )
                            .draw();



                           
            }  else {
                console.log("Answer: "+data);
            }
           

        console.log(id);
       
       /*
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
                }
             });

             */

        });


    });

});