<?php // $Id: uploaduser.php,v 1.13 2012-03-04 22:30:27 vf Exp $
    
    /// Bulk user registration script from a comma separated file
    /// Returns list of users with their user ids
    /// Based on admin/userupload.php.
    /// Modified by Lorenzo Nicora and included in useradmin block
    
    require_once('../../config.php');
    require_once($CFG->libdir.'/uploadlib.php');
    require_once('./useradminlib.php');
    require_once('./lib/userdelegation.class.php');
    
    $course_id = optional_param('course', 1, PARAM_INT);

    echo('<script type="text/javascript" src="js/jquery-1.4.2.min.js" ></script>');
    echo('<script type="text/javascript" src="js/uploaduser.php?id='.$course_id.'" ></script>');
       
    if (! $site = get_site()) {
        error("Could not find site-level course");
    }
    if (!$adminuser = get_admin()) {
        error("Could not find site admin");
    }

    $course = get_record('course', 'id', $course_id);
    
    require_login($course);

    // Require Upload User capability
	// require_capability('moodle/site:uploaduers', get_context_instance(CONTEXT_SYSTEM));
    useradmin_require_capabilities_to_upload();
    
    /// Set defaults
    $defaultseparatoropt = 0;
    if (isset($CFG->CSV_DELIMITER)) {
        foreach ($fieldseparatoroptions as $opt => $sep) {
            if ( $CFG->CSV_DELIMITER == $sep ) {
                $defaultseparatoropt = $opt;
            }
        }
    }
    
    $defaultnomail = 'NOMAIL';
    if ( isset($CFG->CSV_NOMAIL) ) {
        $defaultnomail = $CFG->CSV_NOMAIL;
    }
    
    $defaultfakedomain = 'NO.MAIL';
    if ( isset($CFG->CSV_FAKEMAILDOMAIN) ) {
        $defaultfakedomain = $CFG->CSV_FAKEMAILDOMAIN;
    }
    
    $defaultmaxnumberofcoursefield = 5;
    $defaultfilencodingopt = 0; // UTF-8
    
    // Get parameters
    $createpassword     = optional_param('createpassword', 0, PARAM_BOOL);
    $updateaccounts     = optional_param('updateaccounts', 0, PARAM_BOOL);
    $allowrenames       = optional_param('allowrenames', 0, PARAM_BOOL);
    $separatoropt       = optional_param('separatoropt', $defaultseparatoropt, PARAM_INT);
    $csv_nomail         = optional_param('nomail', $defaultnomail, PARAM_CLEAN);
    $filencodingopt     = optional_param('filencodingopt', $defaultfilencodingopt, PARAM_INT );
    $grouptoassign 		= optional_param('coursegroup',null,PARAM_INT);

    $group_name = optional_param('newgroupname', null, PARAM_TEXT);
    if ($course_id > SITEID){
	    $coursetoassign = optional_param('coursetoassign', $course_id, PARAM_INT );
	} else {
	    $coursetoassign = optional_param('coursetoassign', 0, PARAM_INT );
	}
	    
    
    // CSV separator and encoding
    if ( !array_key_exists($separatoropt, $fieldseparatoroptions)  ) {
        error("Invalid field separator");
    }
    $csv_delimiter2 = $fieldseparatoroptions[$separatoropt];
    $csv_delimiter = "\\".$csv_delimiter2;
    $csv_encode = '/\&\#' . $separatorencodings[$separatoropt] . '/';
    
    // Need to convert to UTF-8?
    $latin2utf8 = False;
    if ( $filencodingopt ) {
        $latin2utf8 = True;
    }
    
    $streditmyprofile = get_string('editmyprofile');
    $stradministration = get_string('administration');
    $strfile = get_string('file');
    $struser = get_string('user');
    $strusers = get_string('users');
    $strusersnew = get_string('usersnew');
    $strusersupdated = get_string('usersupdated');
    $struploadusers = get_string('uploadusers', 'block_userdelegation');
    $straddnewuser = get_string('importuser');
        
    /// Print the header
    $struploaduser = get_string('uploadusers', 'block_userdelegation');
    $strblockname = get_string('blockname', 'block_userdelegation');
    
    $navlinks = array();
    $navlinks[] = array('name' => $strblockname, 'link' => 'user.php', 'type' => 'misc');
    $navlinks[] = array('name' => $struploaduser, 'link' => '', 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    
    print_header($site->fullname.': '.$struploaduser, $site->fullname,
//    "$struploaduser",
    $navigation,
    '', '', true);
    
    
    //check if there is a group that needs to be created 
      //chcking group 
//      debugbreak();
    if($grouptoassign != null && $grouptoassign != ""){
       	//check if an already existing group 
       	if($grouptoassign == -1){
           	//this is a nw group, lets create it.
           	$g = new stdClass();
           	$g->courseid = $coursetoassign;
           	$g->name = $group_name;
           $grouptoassign =  insert_record('groups',$g);           
       	}        
    }
    
    /// If a file has been uploaded, then process it
    
    $um = new upload_manager('userfile', false,false,null,false,0);
    
    if ($um->preprocess_files() && confirm_sesskey()) {
        $filename = $um->files['userfile']['tmp_name'];
    
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        @raise_memory_limit('128M');
        if (function_exists('apache_child_terminate')) {
            @apache_child_terminate();
        }
    
        /// Fix file
        $text = my_file_get_contents($filename);
    
        // Trim UTF-8 BOM
        $textlib = new textlib();
        $text = $textlib->trim_utf8_bom($text);
    
        //Fix mac/dos newlines
        $text = preg_replace('!\r\n?!',"\n",$text);
    
        // Save the file back
        $fp = fopen($filename, 'w');
        fwrite($fp,$text);
        fclose($fp);
    
        // Reopen the file
        $fp = fopen($filename, 'r');
    
        // make arrays of valid fields for error checking
        $requiredFields = array(  'username' => 1,
                            'password' => !$createpassword,
                            'firstname' => 1,
                            'lastname' => 1,
                            'email' => 1);
        
        // Optional fields that takes default value formn
        $optionalFieldsAdmin = array( 'mnethostid' => 1,
                                    'institution' => 1,
                                    'department' => 1,
                                    'city' => 1,
                                    'country' => 1,
                                    'lang' => 1,
                                    'auth' => 1,
                                    'timezone' => 1);
        
        // Optional fields 
        $optionalFields = array( 'idnumber' => 1,
                            'icq' => 1,
                            'skype' => 1,
                            'yahoo' => 1,
                            'msn' => 1,
                            'aim' => 1,
                            'phone1' => 1,
                            'phone2' => 1,
                            'address' => 1,
                            'url' => 1,
                            'description' => 1,
                            'mailformat' => 1,
                            'maildisplay' => 1,
                            'htmleditor' => 1,
                            'autosubscribe' => 1,
                            'password' => $createpassword,
                            'oldusername' => $allowrenames,
                            'emailstop' => 1,
                            'trackforums' => 1,
                            'screenreader' => 1 );
        
        // Default values for optional fields (only for  NOT NULL fields without DEFAULT in db schema)
        $optionalDefaults = array( 'idnumber' => '',
                            'icq' => '',
                            'skype' => '',
                            'yahoo' => '',
                            'msn' => '',
                            'aim' => '',
                            'phone1' => '',
                            'phone2' => '',
                            'address' => '',
                            'url' => '',
                            'lastip' => '',
                            'secret' => '' );
        
            
        // String fields length        
        $field_length = array ('username' => 100,
                            'password' => 32,
                            'firstname' => 100,
                            'lastname' => 100,
                            'email' => 100,
                            'institution' => 40,
                            'department' => 30,
                            'city' => 20,
                            'lang' => 30,
                            'auth' => 20,
                            'timezone' => 100,
                            'idnumber' => 64,
                            'icq' => 15,
                            'skype' => 50,
                            'yahoo' => 50,
                            'msn' => 50,
                            'aim' => 50,
                            'phone1' => 20,
                            'phone2' => 20,
                            'address' => 70,
                            'url' => 255,
                            'description' => 255,
                            'oldusername' => 100);
                                   
        // --- get header (field names) ---
        $header = split($csv_delimiter, fgets($fp,1024));
        // check for valid field names
        foreach ($header as $i => $h) {
            $h = trim($h); $header[$i] = $h; // remove whitespace
            if (!(isset($requiredFields[$h]) or isset($optionalFieldsAdmin[$h]) or isset($optional[$h]))) {
                error(get_string('invalidfieldname_areyousure', 'block_userdelegation', $h), 'uploaduser.php?sesskey='.$USER->sesskey);
            }
            if (@$requiredFields[$h]) {
                $requiredFields[$h] = 0;
            }
        }
        // check for required fields
        foreach ($requiredFields as $key => $value) {
            if ($value) { //required field missing
                error(get_string('fieldrequired', 'error', $key), 'uploaduser.php?sesskey='.$USER->sesskey);
            }
        }
    
        $linenum = 1; // since header is line 0
    
        // Prepare counts
        $usersnew     = 0;
        $usersupdated = 0;
        $userserrors  = 0;
        $renames      = 0;
        $renameerrors = 0;
        $fakemails    = 0;
        $invalidmails = 0;
        $duplicatemails = 0;
    
        // Will use this course array a lot
        // so fetch it early and keep it in memory
        $courses = get_courses('all', 'c.sortorder', 'c.id,c.shortname, c.fullname, c.sortorder, c.teacher');
    
        // Preload all available Roles
        $roles = get_records('role', '', '', '', 'id, shortname, name');
        
        while (!feof ($fp)) {
    
            echo '<hr />';
    
            // setup optional-fields-with-admin-defaults using administrator data
            foreach ($optionalFieldsAdmin as $key => $value) {
                $user->$key = addslashes($adminuser->$key);
            }
            
            // setup optional-fields defaults
            foreach ($optionalDefaults as $key => $value ) {
                $user->$key =  $optionalDefaults[$key];
            }
    
            //Note: separator within a field should be encoded as &#XX (for semicolon separated csv files)
            $line = split($csv_delimiter, fgets($fp, 1024));
            foreach ($line as $key => $value) {
                //decode encoded separator
                $record[$header[$key]] = preg_replace($csv_encode,$csv_delimiter2,trim($value));
            }
            if ($record[$header[0]]) {    // The line is not empty
    
                // add fields to object $user
                foreach ($record as $name => $value) {
                    // If needed, convert to UTF8
                    if ( $latin2utf8 ) {
                        $value = utf8_encode($value);    
                    }
                    
                    // Trim fields
                   	$value = trim($value);
                   	// Truncate string fields
                   	if ( isset($field_length[$name]) && strlen($value) > $field_length[$name] ) {
                    	$value = substr($value, 0, $field_length[$name] ); 
                        $a = new object();
                        $a->fieldname = $name;
                        $a->length = $field_length[$name];
                        useradmin_uploaduser_notify_error($linenum, get_string('truncatefield','block_userdelegation', $a), NULL, NULL, NULL  );
                    }
                    
                    // TODO add other fields validation
                    
                    // check for required values
                    if (@$requiredFields[$name] and !$value) {
                        error(get_string('missingfield', 'error', $name). " ".
                        get_string('erroronline', 'error', $linenum) .". ".
                        get_string('processingstops', 'error'),
                        'uploaduser.php?sesskey='.$USER->sesskey);
                    }
    
                    // password (needs to be encrypted)
                    else if ($name == 'password' && !empty($value)) {
                        $user->password = hash_internal_user_password($value);
                    }
                    // Username (escape and force lowercase)
                    else if ($name == 'username') {
                        $user->username = addslashes(moodle_strtolower($value));
                    }
                    // normal entry (escape only)
                    else {
                        $user->{$name} = addslashes($value);
                    }
                }
    
                // By default the user is confirmed and modified now
                $user->confirmed = 1;
                $user->timemodified = time();
    
                $linenum++;
    
                $username = $user->username;
                
                // check if trying to upload 'changeme' user. If not, skip the line
                if ($user->username === 'changeme') {
                    useradmin_uploaduser_notify_error( $linenum, get_string('invaliduserchangeme', 'admin'), NULL, $user->username, TRUE );
                    $userserrors++;                    
                    continue; // Skip line
                }
              
                // If a real mail has been specified, check it is a valid address (if not, skip line)
                if ( !validate_email( $user->email ) ) {
                    useradmin_uploaduser_notify_error($linenum, get_string('invalidemail').": $user->email", NULL, $user->username, TRUE  );    
                    $invalidmails++;
                    $userserrors++;                    
                    continue;  // Skip line
                }
                // Check duplicate mail
                else if ($otheruser = get_record('user', 'email', $user->email )) {
                    if ($otheruser && $otheruser->username <> $user->username) {
                        useradmin_uploaduser_notify_error($linenum, get_string('emailexists').": $user->email", NULL, $user->username, TRUE );    
                        $duplicatemails++;
                        $userserrors++;                        
                        continue; // Skip line
                    }
                }
                
                // If mnethost ist not localhost, check if mnethost exist
                if ( $user->mnethostid != $CFG->mnet_localhost_id && !record_exists('mnet_host','id',$user->mnethostid) ) {
                    useradmin_uploaduser_notify_error($linenum, get_string('mnethostidnotexists', 'block_userdelegation', $user->mnethostid), NULL, $user->username, TRUE );
                    $userserrors++;
                    continue;
                }
    
                // before insert/update, check whether we should be updating
                // an old record instead (if allowrenames)
                if ($allowrenames && !empty($user->oldusername) ) {
                    $user->oldusername = moodle_strtolower($user->oldusername);
//                    if ($olduser = get_record('user','username',$user->oldusername)) {
					if ($olduser = get_record('user', 'username', $user->oldusername, 'mnethostid', $user->mnethostid)) {
                        // Immediately rename the user
                        if (set_field('user', 'username', $user->username, 'username', $user->oldusername)) {
                            useradmin_uploaduser_notify_success($linenum, get_string('userrenamed', 'admin')." : $user->oldusername ---> $user->username", NULL, $user->username  );
                            $renames++;
                        } else {
                            // An error is probably caused by violation of unique key (username+mnethosti)
                            useradmin_uploaduser_notify_error($linenum, get_string('usernotrenamedexists', 'error')." : $user->oldusername -X--> $user->username", NULL, $user->username, TRUE );

                            $renameerrors++;
                            continue; // skip line
                        }
                    }
                    // If the user you are trying to rename does not exists, skip line                 
                    else {
                        useradmin_uploaduser_notify_error($linenum, get_string('usernotrenamedmissing', 'error')." : $user->oldusername -?--> $user->username", NULL, $user->username, TRUE );

                        $renameerrors++;
                        continue; // skip line
                    }
                }
    
                // Check if username already exists
                if ($olduser = get_record('user', 'username', $username)) {
                    // If update is allowed, update record
                    $user->id = $olduser->id;
                    if ($updateaccounts) {
                        // Record is being updated
                        if (update_record('user', $user)) {
                            useradmin_uploaduser_notify_success($linenum, get_string('useraccountupdated', 'admin') , $user->id, $user->username );
                            $usersupdated++;
                            userdelegation::attach_user($USER->id, $user->id);
                            /*
                        	$personalcontext = get_context_instance(CONTEXT_USER,$user->id);
                        	$role = get_record('role','shortname',$CFG->block_userdelegation_co_role);
                        
	                        if($role){
		                        role_assign($role->id,$USER->id,0,$personalcontext->id);
	                        }
	                        */

	                        //assign them to the selected course if any .
	                        if($coursetoassign != '' && $coursetoassign != null){                            
	                            $course = get_record('course', 'id', $coursetoassign);                            
	                            if($course){
	                                $course_context  = get_context_instance(CONTEXT_COURSE, $coursetoassign);                                
	                                if(has_capability('moodle:role/assign', $course_context)){
	                                	echo "enrolling user $user->id in $course->id ";
	                                    enrol_into_course($course, $user, $user->auth);                                    
	                                }                                
	                            }
	                        }

	                        if($grouptoassign){
	                            groups_add_member($grouptoassign, $user->id);                            
	                        }                        
                                                        
                        } else {
                            useradmin_uploaduser_notify_error($linenum, get_string('usernotupdatederror', 'block_userdelegation'), $user->id, $user->username, TRUE );
                            $userserrors++;
                            continue;
                        }
                    }
                    // If update is not allowed, skip line. 
                    else {
                        useradmin_uploaduser_notify_error($linenum, get_string('usernotaddedregistered', 'block_userdelegation'), $user->id, $user->username, FALSE );
                        $userserrors++;
                        // Do not skip line, as enrolments and groups should be processed 
                    }
                }
                // username does not exists, so create a new user 
                else { // new user
                    if ($user->id = insert_record('user', $user)) {
                        useradmin_uploaduser_notify_success($linenum, get_string('newuseradded', 'block_userdelegation'), $user->id, $user->username );
                        $usersnew++;
                        
                        //add the uploaded user on behalf of the uploader.
                       
                        $personalcontext = get_context_instance(CONTEXT_USER, $user->id);
                        $role = get_record('role', 'shortname', $CFG->block_userdelegation_co_role);
//                        DebugBreak();   
                        if($role){
                        	role_assign($role->id, $USER->id, 0, $personalcontext->id);
                        }

                        //assign them to the selected course if any .
                        if($coursetoassign != '' && $coursetoassign != null){                            
                            $course = get_record('course', 'id', $coursetoassign);                            
                            if($course){
                                $course_context  = get_context_instance(CONTEXT_COURSE, $coursetoassign);                                
                                if(has_capability('moodle:role/assign', $course_context)){
                                	echo "enrolling user $user->id in $course->id ";
                                    enrol_into_course($course, $user, $user->auth);                                    
                                }                                
                            }
                        }
                        
                        if($grouptoassign){
                            groups_add_member($grouptoassign, $user->id);                            
                        }
                        
                        if (empty($user->password) && $createpassword) {
                            // passwords will be created and sent out on cron
                            insert_record('user_preferences', array( userid => $user->id,
                                        'name'   => 'create_password',
                                        'value'  => 1));
                            insert_record('user_preferences', array( userid => $user->id,
                                        'name'   => 'auth_forcepasswordchange',
                                        'value'  => 1));
                        }
                    } else {
                        // Record not added -- possibly some other error
                        useradmin_uploaduser_notify_error($linenum, get_string('usernotaddederror', 'block_userdelegation'), $user->id, $user->username, TRUE );
                        $userserrors++;
                        continue;
                    }
                }
                
                /// Process courses, groups and roles
                
                // Check if required courses match with any existing course
                unset ($user);
            }
        }
        fclose($fp);
        
        // Print a small report
        echo '<hr height="2" />';
        notify("$strusersnew: $usersnew");
        notify(get_string('usersupdated', 'admin') . ": $usersupdated");
        notify(get_string('errors', 'admin') . ": $userserrors");
        if ($allowrenames) {
            notify(get_string('usersrenamed', 'admin') . ": $renames");
            notify(get_string('renameerrors', 'admin') . ": $renameerrors");
        }
        notify( get_string('fakemails', 'block_userdelegation') . ": $fakemails" );
        notify( get_string('invalidmails', 'block_userdelegation') . ": $invalidmails" );
        notify( get_string('duplicatemails', 'block_userdelegation') . ": $duplicatemails" );
        echo '<hr />';
    }
    
    /// Print the form
    print_heading_with_help($struploaduser, 'uploadusers', 'block_userdelegation');
    
    $courseparam = ($course->id > SITEID)? "?course={$course->id}" : '' ;
    print('<div class="userpage-toolbar" style="float:right;">
    <img src="images/users.png" /> <a href="'.$CFG->wwwroot.'/blocks/userdelegation/user.php'.$courseparam.'">'.get_string('myusers', 'block_userdelegation').'</a>'); 
    print('</div>'); 
    
    
    $noyesoptions = array( get_string('no'), get_string('yes') );
    
    $maxuploadsize = get_max_upload_file_size();
    echo '<center>';
    
    $csvparams->separator = $csv_delimiter2;
    $csvparams->nomail = $csv_nomail;
    // print_simple_box(get_string('explain', 'block_userdelegation', $csvparams), 'center');
    
    echo '<form method="post" enctype="multipart/form-data" action="uploaduser.php">'.
    $strfile.'&nbsp;<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
    '<input type="hidden" id="sesskey" name="sesskey" value="'.$USER->sesskey.'">'.
    '<input type="file" name="userfile" size="30">'.
    '<input type="hidden" name="course" value="'.$course->id.'">';
    
    
    echo ' <input type="submit" value="'.get_string('uploadfile', 'block_userdelegation' ).'">';
    
    print_heading(get_string('settings'));    
    print_simple_box_start('center');
    
    echo '<center><table>';
 
    // Choose Course
    echo '<tr><td>'.get_string('coursetoassign', 'block_userdelegation').'</td><td>';
    $courses = get_user_courses_bycap($USER->id, 'block/userdelegation:cancreateusers', $USER->access, false);
    $courses_arr = array();
    //DebugBreak(); 
    foreach($courses as $c){
        $course = get_record('course', 'id', $c->id, '', '', '', '', 'id,fullname');
      	$courses_arr[$course->id] = $course->fullname ; 
    }
    
    choose_from_menu($courses_arr, 'coursetoassign', $coursetoassign, 'choose', '', "preloadgroups('$coursetoassign')");
    echo '</td></tr>';
 
    // Choose group
    echo '<tr><td>'.get_string('grouptoassign', 'block_userdelegation').'</td><td>';

    echo '<select name="coursegroup" id="coursegroup">';
    echo '<option value="">'.get_string('loadingcoursegroups', 'block_userdelegation').'</option> ';
    echo '</select> ';
    echo '</td></tr>';
 
    // Choose group
    echo '<tr id="newgroupnamerow" style="display:none;"><td>' . get_string('newgroupname', 'block_userdelegation') . '</td><td>';
    echo '<input type="text" id="newgroupname" name="newgroupname" />';
    echo '</td></tr>';

    // Choose Separator
    echo '<tr><td>' . get_string('fieldseparator', 'block_userdelegation');
    helpbutton('fieldseparator', get_string('fieldseparator', 'block_userdelegation'), 'block_userdelegation');
    echo '</td><td>';
    choose_from_menu($fieldseparatormenuoptions, 'separatoropt', $separatoropt, '');
    echo '</td></tr>';
    
    // choose file encoding
    echo '<tr><td>' . get_string('filencoding', 'block_userdelegation');
    helpbutton('filencoding', get_string('filencoding','block_userdelegation'), 'block_userdelegation');
    echo '</td><td>';
    choose_from_menu($filencodingmenuoptions, 'filencodingopt', $filencodingopt, '');
    echo '</td></tr>';
    
    // No-mail placeholder
    echo '<tr><td>' . get_string('nomailplaceholder', 'block_userdelegation');
    helpbutton('fakemail', get_string('fakemailgeneration','block_userdelegation'), 'block_userdelegation');
    echo '</td><td>';
    echo "<input type=\"text\" name=\"nomail\" value=\"".s($csv_nomail, true)."\" size=\"20\" />";
    echo ' (' . get_string('onlyalphanum', 'block_userdelegation') . ')';
    echo '</td></tr>';

    echo '<tr><td colspan="2"><hr /></td><td>';
    
    // Password handling
    echo '<tr><td>' . get_string('passwordhandling', 'auth') . '</td><td>';
    $passwordopts = array(
    	0 => get_string('infilefield', 'auth'),
    	1 => get_string('createpasswordifneeded', 'auth'),
    );
    choose_from_menu($passwordopts, 'createpassword', $createpassword);
    echo '</td></tr>';
    
    // Update Accounts
    echo '<tr><td>'.get_string('updateaccounts', 'admin').'</td><td>';
    choose_from_menu($noyesoptions, 'updateaccounts', $updateaccounts);
    echo '</td></tr>';
    
    // Allow rename
    echo '<tr><td>'.get_string('allowrenames', 'admin').'</td><td>';
    choose_from_menu($noyesoptions, 'allowrenames', $allowrenames);
    echo '</td></tr>';
    
    echo '</table>';
    
    echo '</center>';
    print_simple_box_end();
    
    echo '</form>';    
    echo '</center>';
    
    print_footer();
 //   execute_sql('delete from mdl_user where id > 27');
        
    function my_file_get_contents($filename, $use_include_path = 0) {
        /// Returns the file as one big long string
    
        $data = '';
        $file = @fopen($filename, 'rb', $use_include_path);
        if ($file) {
            while (!feof($file)) {
                $data .= fread($file, 1024);
            }
            fclose($file);
        }
        return $data;
    }

?>

