<?php
    include("../../../config.php");
    $id = optional_param('id', SITEID, PARAM_INT);
    require_course_login($id);

    $lastmodified = filemtime("user_edit.php");
    $lifetime = 1800;

    // Commenting this out since it's creating problems
    // where solution seem to be hard to find...
    // http://moodle.org/mod/forum/discuss.php?d=34376
    //if ( function_exists('ob_gzhandler') ) {
    //    ob_start("ob_gzhandler");
    //}

    header("Content-type: application/x-javascript; charset: utf-8");  // Correct MIME type
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmodified) . " GMT");
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT");
    header("Cache-control: max_age = $lifetime");
    header("Pragma: ");
?>
$(document).ready(function(){

  
    $('#id_submitbutton').click(function(event){
        
       event.preventDefault();
       
       var uid = $('#id').val();
       
       if(uid != -1)
       {
           $('#mform1').submit();
       }
       else{
           
       $(this).val("Validating User.... Please wait"); 
       
       var email = $('#id_email').val();
       var firstname = $('#id_firstname').val();
       var lastname = $('#id_lastname').val();
     
       check_user_exist(email,firstname,lastname);
       }
        
    });
 
    
 function check_user_exist(email,firstname,lastname)
 {
     var skey= $('input[name=sesskey]').val();
   
     $.getJSON('serverside/service.php',{action:'CheckUserExist',sesskey:skey,f_name:firstname,l_name:lastname,e:email},function(data){
   
         if (data.result==0)
         {
          $('#id_submitbutton').val("Valid ... Redirecting");
          $('#mform1').submit();   
         }
         else{
             $('#id_submitbutton').val("User exists");          
             $.each(data.users,function(i,obj){
             
              $('#exisiting_users').append('<div style="padding-left:10px;margin-bottom: 15px;">Opps, it looks like that the user is already exists in our database, please choose one of the following:</div>' );
             $('#exisiting_users').append(
              '<div class="info-exist-user-cont">'+
              '<img src="images/user.png" /> ' +
             obj.firstname+" "+obj.lastname+
             '&nbsp;&nbsp;&nbsp;&nbsp;'+
             '<a class="addtoaccount" uid="'+obj.id+'" href="#">+ Attach to me</a>'+
             '</div>'
               );
              

                 
             });
   
              $("#exisiting_users a").live("click", function(event){
                 event.preventDefault();
                
                  var power_uid = $('input[name=power_uid]').val();
                  var fellow_uid= $(this).attr('uid');
                  $.getJSON('serverside/service.php',{action:'AttachUser',sesskey:skey,puid:power_uid,fuid:fellow_uid},function(data){
                    
                    if(data.result == 1)
                    {
                        alert("Added !!");
                        window.location="user.php?course="+$('input[name=course]').val();
                    }
                      
                  });

                
              });
            
             $('#exisiting_users').show();
             
         }

     }) ;

     
 }
 

         

    
});