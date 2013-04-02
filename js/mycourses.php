<?php
    require_once("../../../config.php");
    $id = optional_param('id', SITEID, PARAM_INT);
    require_course_login($id);

    $lastmodified = filemtime("mycourses.php");
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
       
    
    $('.courseteachers-btn').click(function(event){
       event.preventDefault(); 
       var id = $(this).attr('id');
       
       if($('#cteacherscont-'+id).css('display')=='none')
       {
           $('#cteacherscont-'+id).css('display','block');
       }
       else{
       
       $('#cteacherscont-'+id).css('display','none');
       }
        
    });
    
    
    $('.coursestudents-btn').click(function(event){
     
       event.preventDefault(); 
       var id = $(this).attr('id');
       
       if($('#cstudentscont-'+id).css('display')=='none')
       {
           $('#cstudentscont-'+id).css('display','block');
       }
       else{  
           $('#cstudentscont-'+id).css('display','none');
       }
        
    });
    
});

