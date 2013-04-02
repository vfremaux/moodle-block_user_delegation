$(document).ready(function(){

   //alert("Hi !");
   load_course_gorups();
   
   
   $('#menucoursetoassign').change(function(){
       
         load_course_gorups();
       
   });
   
   
   function load_course_gorups()
   {
       var course_id = $('#menucoursetoassign').val();
       var skey = $('#sesskey').val();
      
   
       if(course_id == "" || course_id == null)
       {
        //   console.log("No course selected");
       }
       else if(course_id==-1)
       {
           alert("Adding new group....");
       }
       else{
       
              $('#coursegroup').empty();
              $('#coursegroup').append("<option>Loading groups...  please wait</option>");
      
           $.getJSON('serverside/service.php',{action:'GetCourseGroups',sesskey:skey,cid:course_id},function(data){
                    
                    if(data.result == false)
                    {
                        $('#coursegroup').empty();
                        $('#coursegroup').append("<option>No groups available</option>");
                       

                    }
                    else{
                        $('#coursegroup').empty();                        
                        $.each(data.result,function(i,obj){
                        $('#coursegroup').append("<option value='"+obj.id+"'>"+obj.name+"</option>");     
                        });
                       
                    }
                    
                    $('#coursegroup').append("<option value='-1'><b>Add new group....</b></option>");
                   
                    $('#coursegroup').live('change',function(){
                        
                        var selected_value = $('#coursegroup option:selected').val();
                        if(selected_value == -1)
                        {
                            //console.log("here");
                            $('#newgroupnamerow').show();
                        }else{
                            $('#newgroupnamerow').hide();
                            
                        }
                         
                    });   
                      
            });
           
       }
       
         
   }
   
   

});