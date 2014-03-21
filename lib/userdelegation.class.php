<?php
  /**
  * 
  */
  
class userdelegation {
      
	function _construct(){
    }
      
	/**
	* get given reponsible users.
	* 
	* @param int $trainer_id responsible user id .
	* @param mixed $order  order field
	* @return mixed  array of user object 
	*/
	public static function get_delegated_users ($trainer_id, $sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0, $search='', $firstinitial='', $lastinitial='', $extraselect=''){ 
		global $CFG;

   		$LIKE = sql_ilike();
		$fullname = sql_fullname();
		$select = "deleted <> '1'";

		if (!empty($search)) {
			$search = trim($search);
			$select .= " AND ($fullname $LIKE '%$search%' OR email $LIKE '%$search%' OR username='$search') ";
		}

		if ($firstinitial) {
	    	$select .= ' AND firstname '. $LIKE .' \''. $firstinitial .'%\' ';
		}

		if ($lastinitial) {
	    	$select .= ' AND lastname '. $LIKE .' \''. $lastinitial .'%\' ';
		}

		if ($extraselect) {
	    	$select .= " AND $extraselect ";
		}
	
		if ($sort) {
	    	$sort = ' ORDER BY '. $sort .' '. $dir;
		}

		/// warning: will return UNCONFIRMED USERS
		/*    return get_records_sql("SELECT id, username, email, firstname, lastname, city, country, lastaccess, confirmed, mnethostid
                          FROM {$CFG->prefix}user
                         WHERE $select $sort", $page, $recordsperpage);

		*/    
  
    	$sql = "
        	SELECT 
        		u.* 
        	FROM
        		{$CFG->prefix}user u,
        		{$CFG->prefix}role_assignments ra,
        		{$CFG->prefix}context ctx 
        	WHERE  
        		ra.userid = {$trainer_id} AND 
        		ra.contextid = ctx.id AND 
        		ctx.contextlevel = ".CONTEXT_USER." AND
        		ctx.instanceid = u.id AND  
        		{$select} 
        		{$sort}
		";
 
		$users = get_records_sql($sql, $page, $recordsperpage);          
		return $users ;         
	}
    
	/**
	*
	*/
	public static function check_user_exist($email, $firstname, $lastname){ 
		global $CFG;
     
    	$sql = "
        	SELECT 
        		* 
        	FROM 
        		{$CFG->prefix}user 
        	WHERE 
        		email='{$email}' AND
         		firstname='{$firstname}' AND 
         		lastname='{$lastname}'
		";
        
        $result = get_records_sql($sql); 
        return $result;        
	}

	/**
	* Attach a user as behalf of another user
	*
	*/      
	public static function attach_user($power_uid, $fellow_uid){
		global $CFG;

		$personalcontext = get_context_instance(CONTEXT_USER,$fellow_uid);		       
		$role = get_record('role', 'shortname', $CFG->block_userdelegation_co_role);
		
		if($role){
			$result = role_assign($role->id, $power_uid, 0, $personalcontext->id);
		}
		return (int)$result; 
	}     

	/**
	* Attach a user as behalf of another user
	*
	*/      
	public static function unattach_user($power_uid, $fellow_uid){
		global $CFG;

		$personalcontext = get_context_instance(CONTEXT_USER,$fellow_uid);		       
		$role = get_record('role', 'shortname', $CFG->block_userdelegation_co_role);
		
		if($role){
			$result = role_unassign($role->id, $power_uid, 0, $personalcontext->id);
		}
		return (int)$result; 
	}     

	/**
	* get the course list of the current user.
	* @return array of courses or empty array
	*/
	public static function get_owned_courses(){
		global $USER;

		if ($courses = get_user_courses_bycap($USER->id, 'block/userdelegation:cancreateusers', $USER->access, false)){
			return $courses;
		}
		return array(); 
	}

	/**
	* checks if an owner is owned by anyone else
	* @return array of owners or false
	*/
	public static function has_owners($userid){
		$personalcontext = get_context_instance(CONTEXT_USER, $userid);
		return get_users_by_capability($personalcontext, 'block/userdelegation:isbehalfof', 'u.id,firstname,lastname', 'lastname,firstname');
	}
}
   
?>
