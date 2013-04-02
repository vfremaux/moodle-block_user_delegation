<?php
    require_once('../../config.php');
   
    $course = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
    $cancelemailchange = optional_param('cancelemailchange', false, PARAM_INT);   // course id (defaults to Site)
    $page = optional_param('page', 1, PARAM_INT);   // course id (defaults to Site)
    
    
    echo('<script type="text/javascript" src="js/jquery-1.4.2.min.js" ></script>');
    echo('<script type="text/javascript" src="js/mycourses.php?id='.$course.'" ></script>');
   
    echo('<input type="hidden" id="sesskey"  name="sesskey" value="'.sesskey().'" /> ');
    if (!$course = get_record('course', 'id', $course)) {
        error('Course ID was incorrect');
    }

    if ($course->id != SITEID) {
        require_login($course);
    } else if (!isloggedin()) {
        if (empty($SESSION->wantsurl)) {
            $SESSION->wantsurl = $CFG->httpswwwroot.'/user/edit.php';
        }
        redirect($CFG->httpswwwroot.'/login/index.php');
    }

    // Guest can not edit
    if (isguestuser()) {
        print_error('guestnoeditprofile');
    }
    
    $navlinks[] = array('name' => get_string('userdelegation', 'block_userdelegation'), 'link' => null, 'type' => 'misc');
    $navlinks[] = array('name' => get_string('mycourses', 'block_userdelegation'), 'link' => null, 'type' => 'misc');
    
    $navigation = build_navigation($navlinks);
    print_header(get_string('mycourses', 'block_userdelegation'), $course->fullname, $navigation, "");
    
    $perpage = 10;
    
    //next , lets load the user courses .
    // $user_courses = get_my_courses($USER->id);
    // print_object($USER);
    $user_courses = get_user_courses_bycap ($USER->id, 'block/userdelegation:cancreateusers', $USER->access, false);
    $coursescount = count($user_courses);
    
    print_heading(get_string('mydelegatedcourses', 'block_userdelegation'));
    $totalcoursesstr = get_string('totalcourses', 'block_userdelegation');
    
    print('<div id="userdelegation-toolbar">');//toolbar
    print ('<div style="float:left;"><b>'.$totalcoursesstr.': </b>'.$coursescount.'</div>');
    
    $courseparam = ($course->id > SITEID)? "?course={$course->id}" : '' ;
    print('<div class="userpage-toolbar" style="float:right;">
     <img src="images/users.png" /> <a href="'.$CFG->wwwroot.'/blocks/userdelegation/user.php'.$courseparam.'">'.get_string('myusers', 'block_userdelegation').'</a>'); 
    print('</div>'); 
    
    print('</div>');
    
    $changeenrolmentstr = get_string('changeenrolment','block_userdelegation');
    $uploadusersstr = get_string('uploadusers', 'block_userdelegation');
    
    if (!empty($user_courses)){
	    foreach($user_courses as $c){
	    	$c = get_record('course', 'id', $c->id);
	    	$coursecontext = get_context_instance(CONTEXT_COURSE, $c->id);
	        print('<div class="userdelegation-course-cont">');//course-cont
	        print('<div style="overflow:auto;">');
	        print('<div style="float:left;"><b><a href="'.$CFG->wwwroot.'/course/view.php?id='.$c->id.'">'.$c->fullname.'</a></b></div>');
	        print('<div style="float:right;font-size:11px;">
	            <div><b><a href="'.$CFG->wwwroot.'/blocks/userdelegation/user.php?course='.$course->id.'" >'.$changeenrolmentstr.'</a></b></div>
	            <div><b><img src="images/upload.png" /><a href="'.$CFG->wwwroot.'/blocks/userdelegation/uploaduser.php?course='.$course->id.'&coursetoassign='.$c->id.'" >'.$uploadusersstr.'</a></b></div>            
	            </div>');
	            
	        print('</div>');
	        
	        // TODO : Do not use Moodle since 1.8 obsolete functions
	        $course_teachers = get_users_by_capability($coursecontext, 'moodle/couse:grade', 'u.id,firstname,lastname');
	        $teachersstr = get_string('teachers', 'block_userdelegation');
	        
	        print("<a style='text-decoration:none;' href='#' class='courseteachers-btn' id='".$c->id."'  ><b>+ {$teachersstr}</b></a>");
	        print('<div class="cteacherscont" id="cteacherscont-'.$c->id.'">');//all users 
	          
			if(count( $course_teachers) > 0 && $course_teachers!= null){
	          	foreach($course_teachers as $u){
	            	print('<div class="userdelegation-user" style="padding-left:10px;"><img style="padding-top:1px;" src="images/user-teacher.png" /> '.$u->firstname.' '.$u->lastname.' </div>');    
	            }              
	        } else {
	        	print('<div class="userdelegation-user" style="padding-left:10px;">'.get_string('noteachers', 'block_userdelegation').'</div>');                  
	        }
	                    
	        print('</div>');//allteachers
	            
	        // TODO : Do not use Moodle since 1.8 obsolete functions
	        $course_students = get_course_students($c->id);
	        $studentsstr = get_string('students');
	  
	        print('</br>');
	        print("<a style='text-decoration:none;' href='#' class='coursestudents-btn' id='".$c->id."'  ><b>+ {$studentsstr}</b></a>");
	        print('<div class="cstudentscont" id="cstudentscont-'.$c->id.'">');//all users 
	         
			if(count( $course_students) > 0 && $course_students != null){         
	        	foreach($course_students as $u){
	            	print('<div class="userdelegation-user" style="padding-left:10px;"><img style="padding-top:1px;" src="images/user.png" /> '.$u->firstname.' '.$u->lastname.' </div>');    
	            }              
	        } else {
				print('<div class="userdelegation-user" style="padding-left:10px;">'.get_string('nostudents','block_userdelegation').'</div>');
			}
	            
	        print('</div>');//allusers        
	        print('</div>');
	    }
	    
	    print_paging_bar($coursescount, $page, $perpage, 'mycourses.php');
	} else {
		print('<br/>');
		print_box(get_string('noownedcourses', 'block_userdelegation'));
		print('<br/>');
	}
                
    print_footer();
?>
