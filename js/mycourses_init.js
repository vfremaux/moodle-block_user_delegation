$(document).ready(function(){
       
    //alert("hi");
    
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