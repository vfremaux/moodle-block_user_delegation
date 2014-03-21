<?php 

class block_userdelegation extends block_base {

    function init() {
        $this->title = get_string('userdelegation', 'block_userdelegation');
        $this->version = 2012030100;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        // $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('new_userdelegation', 'block_userdelegation'));
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return false;
    }

    function has_config() {
        return true;
    }

    function get_content() {
    	global $CFG;
        
        $id = optional_param('id', SITEID, PARAM_INT);
        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!empty($this->instance->pinned) or $this->instance->pagetype === 'course-view') {
            // fancy html allowed only on course page and in pinned blocks for security reasons
            $filteropt = new stdClass;
            $filteropt->noclean = true;
        } else {
            $filteropt = null;
        }

        $menu = '<ul>';
        $moodleuserimportstr = get_string('moodleuserimport', 'block_userdelegation');
        $viewmyusersstr = get_string('viewmyusers', 'block_userdelegation');
        $viewmycoursesstr = get_string('viewmycourses', 'block_userdelegation');
        
        $menu .= " <li><a href=\"".$CFG->wwwroot."/blocks/userdelegation/uploaduser.php?course={$id}\">$moodleuserimportstr</a></li>"; 
        $menu .= " <li><a href=\"".$CFG->wwwroot."/blocks/userdelegation/user.php?course=".$id."\">$viewmyusersstr</a></li>"; 
        $menu .= " <li><a href=\"".$CFG->wwwroot."/blocks/userdelegation/mycourses.php?course=".$id."\">$viewmycoursesstr</a></li>"; 

        $menu .= '</ul>';
        $this->content = new stdClass;
        $this->content->text = $menu ; 
        $this->content->footer = '';

        unset($filteropt); // memory footprint

        return $this->content;
    }

    /**
     * Will be called before an instance of this block is backed up, so that any links in
     * any links in any HTML fields on config can be encoded.
     * @return string
     */
    function get_backup_encoded_config() {
        /// Prevent clone for non configured block instance. Delegate to parent as fallback.
        if (empty($this->config)) {
            return parent::get_backup_encoded_config();
        }
        $data = clone($this->config);
        $data->text = backup_encode_absolute_links($data->text);
        return base64_encode(serialize($data));
    }

    /**
     * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
     * function in order to decode contents of this block from the backup 
     * format to destination site/course in order to mantain inter-activities 
     * working in the backup/restore process. 
     * 
     * This is called from {@link restore_decode_content_links()} function in the restore process.
     *
     * NOTE: There is no block instance when this method is called.
     *
     * @param object $restore Standard restore object
     * @return boolean
     **/

    /**
    *
    */
    function user_can_addto($page) {
        global $CFG, $COURSE;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (has_capability('block/userdelegation:canaddto', $context)){
        	return true;
        }
        return false;
    }

    /**
    *
    */
    function user_can_edit() {
        global $CFG, $COURSE;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        
        if (has_capability('block/userdelegation:configure', $context)){
 	       return true;
        }

		return false;
    }
    
    function after_install(){

		$shortname = 'courseowner';		
		$name = get_string('courseowner', 'block_userdelegation');
		$description = get_string('courseownerdescription', 'block_userdelegation');
		$legacy = 'editingteacher';
		
		if ($roleid = create_role(addslashes($name), $shortname, addslashes($description), $legacy)){

	        // boostrap courseowner to the same as editingteacher
	        $editingteacher = get_record('role', 'shortname', 'editingteacher');
	        role_cap_duplicate($editingteacher, $roleid);
	        $syscontext = get_context_instance(CONTEXT_SYSTEM);
					
			assign_capability('block/userdelegation:cancreateusers', CAP_ALLOW, $roleid, $syscontext->id, true);
			assign_capability('block/userdelegation:canbulkaddusers', CAP_ALLOW, $roleid, $syscontext->id, true);
			assign_capability('block/userdelegation:isbehalfof', CAP_ALLOW, $roleid, $syscontext->id, true);

			// add role assign allowance to owner 
			// We only allow and override on standard roles.
			$assigntargetrole[] = get_field('role', 'id', 'shortname', 'student');
			$assigntargetrole[] = get_field('role', 'id', 'shortname', 'teacher');
			$assigntargetrole[] = get_field('role', 'id', 'shortname', 'editingteacher');
			$assigntargetrole[] = get_field('role', 'id', 'shortname', 'guest');
			foreach($assigntargetrole as $t){
				allow_assign($roleid, $t);
			}

			$overridetargetrole[] = get_field('role', 'id', 'shortname', 'student');
			$overridetargetrole[] = get_field('role', 'id', 'shortname', 'teacher');
			$overridetargetrole[] = get_field('role', 'id', 'shortname', 'guest');
			foreach($overridetargetrole as $t){
				allow_override($roleid, $t);
			}
			
			set_config('block_userdelegation_co_role', $shortname);
		}
				
    }

    function before_delete() {
		global $CFG;
		
		// switch to legacy editing teacher when bloc is removed from Moodle.
		
    	if ($corole = get_record('role', 'shortname', 'courseowner')){ 
	    	$legacyrole = get_record('role', 'shortname', 'editingteacher'); 
	
	    	delete_records('role', 'shortname', 'courseowner');
	    	
	    	$sql = "
				UPDATE
					{$CFG->prefix}role_assignments
				SET
					roleid = {$legacyrole->id}
				WHERE
					roleid = {$corole->id}			
	    	";
	    	execute_sql($sql);
	    	
	    	delete_records('config', 'name', 'block_userdelegation_co_role');
	    	unset($CFG->block_userdelegation_co_role);
	    }
    }
    
}
?>
