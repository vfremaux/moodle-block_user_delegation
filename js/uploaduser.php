<?php
    include("../../../config.php");
    $id = optional_param('id', SITEID, PARAM_INT);
    require_course_login($id);

    $lastmodified = filemtime("uploaduser.php");
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

   //alert("Hi !");
   load_course_groups();
      
   $('#menucoursetoassign').change(function(){    
         load_course_groups();    
   });
      
   function load_course_groups(){
       var course_id = $('#menucoursetoassign').val();
       var skey = $('#sesskey').val();
         
       if(course_id == '' || course_id == null){
        //   console.log("No course selected");
       } else if(course_id == -1) {
           alert("Adding new group....");
       } else{       
          	$('#coursegroup').empty();
          	$('#coursegroup').append("<option><?php echo get_string('loadinggroups', 'block_userdelegation') ?></option>");
           	$.getJSON('serverside/service.php',{action:'GetCourseGroups',sesskey:skey,cid:course_id},function(data){
                    
	            if(data.result == false){
	                $('#coursegroup').empty();
	                $('#coursegroup').append("<option><?php echo get_string('nogroups', 'block_userdelegation') ?></option>");
	            } else {
	                $('#coursegroup').empty();                        
	                $.each(data.result,function(i,obj){
	                $('#coursegroup').append("<option value='"+obj.id+"'>"+obj.name+"</option>");     
	                });               
	            }
                    
	            $('#coursegroup').append("<option value='-1'><b><?php echo get_string('addnewgroup', 'block_userdelegation') ?></b></option>");           

	            $('#coursegroup').live('change',function(){	                        
		            var selected_value = $('#coursegroup option:selected').val();
		            if(selected_value == -1){
		                //console.log("here");
		                $('#newgroupnamerow').show();
		            } else {
		                $('#newgroupnamerow').hide();               
		            }                         
				});                  
			});           
		}         
	}
});