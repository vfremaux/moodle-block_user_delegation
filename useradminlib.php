<?php  // $Id: useradminlib.php,v 1.3 2012-12-11 17:24:18 vf Exp $
	
	/// Common Constants
	
	// Standard User field names included in the listing
	$standarduserfieldnames = array ('firstname', 'lastname', 'username', 'email',
										 'institution', 'department', 'city', 'country',
										 'lastaccess',
										 'auth', 'mnethostname',
										 'confirmed');
	// Extended list 
	$extendeduserfieldnames = array_merge ( $standarduserfieldnames, 
							   array ( 'emailstop', 'lang', 
							           'maildisplay', 'maildigest', 'ajax', 'autosubscribe',
							           'trackforums', 'screenreader',
							           'lastlogin', 'timemodified' ) );

	
	// File Separators
	$fieldseparatoroptions = array( 0 => ",",
	1 => ";",
	2 => "|",
	3 => "\t");
	// File separator menu options
	$fieldseparatormenuoptions = array (    0 => get_string('comma', 'block_userdelegation'),
	1 => get_string('semicolon', 'block_userdelegation'),
	2 => get_string('pipe', 'block_userdelegation'),
	3 => get_string('tab', 'block_userdelegation') );
	
	// File encoding menu options
	$filencodingmenuoptions = array ( 0 => 'UTF-8',
	1 => 'Latin (ISO-8859-1)' );
	
	// Separator encoding
	$separatorencodings = array (   0 => '44',
	1 => '59',
	2 => '124',
	3 => '11');
	
	// Filter params Defaults
	$filterparams_default = array ( 'sort' => 'name', 'dir' => 'ASC',
	                                    'page' => 0, 'perpage' => 50,
	                                    'search' => '',
	    							    'searchcustom' => 0,
	                                    'lastinitial' => '', 'firstinitial' => '',
	                                    'contextlevel' => '', 'contextinstanceid' => '', 'donthaverole' => 0, 'roleid' => '',
	                                    'mnethostid' => '', 'filterconfirmed' => 0, 'filterauth' => '');
	
	// Expand/Collapse fields defaults
	$expandcollapsefields_defaults = array ( '_fullform'=> 1,  // Show/hide full form
	                                    'username' => 0, // Other columns...
	                                    'email' => 1,
	                                    'institution' => 1,
	                                    'department' => 0,
	                                    'city' => 0,
	                                    'country' => 0,
	                                    'lastaccess' => 1,
	                                    'auth' => 0,
	                                    'mnethostname' => 0,
	                                    'confirmed' => 0); 
	
	$options_filterconfirmed = array ( '0' => get_string('filterconfirmed_all','block_userdelegation'),
	                                      '1' => get_string('filterconfirmed_confirmedonly','block_userdelegation'), 
	                                      '2' => get_string('filterconfirmed_unconfirmedonly','block_userdelegation') );
	
	
	$options_eol = array ( 0 => get_string('doseol','block_userdelegation'),
	1 => get_string('unixeol','block_userdelegation'),
	2 => get_string('maceol','block_userdelegation'));
	$eols = array ( 0 => "\r\n",
	1 => "\n",
	2 => "\r");
	
	// E-Mail check errors
	define("MAILCHK_MALFORMEDADDRESS", 1);
	define("MAILCHK_DOMAINNOTALLOWED", 2);
	define("MAILCHK_INVALIDDOMAIN", 3);
	
	/**
	 * Check if the user has base capabilities to see the block, list and download users
	 */
	function useradmin_has_capabilities_to_list() {
		global $COURSE;

		$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		
		if ( has_capability('moodle/site:accessallgroups', $coursecontext)
		&&  has_capability('moodle/user:viewdetails', $coursecontext)
		&&  has_capability('moodle/user:viewhiddendetails', $coursecontext)
		&&  has_capability('moodle/role:viewhiddenassigns', $coursecontext)
		&&  has_capability('moodle/site:viewfullnames', $coursecontext)
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Similar to useradmin_has_capabilities_to_list()
	 * but print error if the current user does not have the required capabilities
	 *
	 * FIXME rewrite to avoid duplicate code with useradmin_has_capabilities_to_list()
	 */
	function useradmin_require_capabilities_to_list() {
		global $USER, $COURSE;

		$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
	
		require_capability('moodle/site:accessallgroups', $coursecontext, $USER->id);
		require_capability('moodle/user:viewdetails', $coursecontext, $USER->id);
		require_capability('moodle/user:viewhiddendetails', $coursecontext, $USER->id);
		require_capability('moodle/role:viewhiddenassigns', $coursecontext, $USER->id);
		require_capability('moodle/site:viewfullnames', $coursecontext, $USER->id);
	}
	
	/**
	 * Check capabilities to use user Upload
	 */
	function useradmin_has_capabilities_to_upload() {
		global $COURSE;
		
		$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);

		if ( has_capability('moodle/site:uploadusers', $coursecontext)
		&&  has_capability('moodle/user:update', $coursecontext)
		&&  has_capability('moodle/user:create', $coursecontext)
		&&  has_capability('moodle/role:assign', $coursecontext)
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Like useradmin_has_capabilities_to_upload() but requires capabilities
	 *
	 * FIXME rewrite to avoid code duplication with useradmin_has_capabilities_to_upload()
	 */
	function useradmin_require_capabilities_to_upload() {
		global $USER, $COURSE;

		$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
	
		require_capability('moodle/site:uploadusers', $coursecontext, $USER->id);
		require_capability('moodle/user:update', $coursecontext, $USER->id);
		require_capability('moodle/user:create', $coursecontext, $USER->id);
		require_capability('moodle/role:assign', $coursecontext, $USER->id);
	}
	
	
	/**
	 * Capabilities to Edit users
	 */
	function useradmin_has_capabilities_to_edit() {
		if ( useradmin_has_capabilities_to_list()
		&&  has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))
		&&  has_capability('moodle/user:editprofile', get_context_instance(CONTEXT_SYSTEM))
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Like useradmin_has_capabilities_to_edit() but requires capabilites
	 *
	 */
	function useradmin_require_capabilities_to_edit() {
		global $USER;
	
		require_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM), $USER->id);
		require_capability('moodle/user:editprofile', get_context_instance(CONTEXT_SYSTEM), $USER->id);
	}
	
	/**
	 * Capabilities to delete users
	 */
	function useradmin_has_capabilities_to_delete() {
		if ( has_capability('moodle/user:delete', get_context_instance(CONTEXT_SYSTEM)) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Like useradmin_has_capabilities_to_delete() but requires capabities
	 */
	function useradmin_require_capabilities_to_delete() {
		global $USER;
		require_capability('moodle/user:delete', get_context_instance(CONTEXT_SYSTEM), $USER->id);
	}
	
	/**
	 * Capabilities to create users
	 */
	function useradmin_has_capabilities_to_create() {
		if ( has_capability('moodle/user:create', get_context_instance(CONTEXT_SYSTEM)) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Like useradmin_has_capabilities_to_create() but require capabilities
	 */
	function useradmin_require_capabilities_to_create() {
		global $USER;
		require_capability('moodle/user:create', get_context_instance(CONTEXT_SYSTEM), $USER->id);
	}
	
	
	/**
	 * Capabilities to Assigne/Unassing roles
	 */
	function useradmin_has_capabilities_to_assign() {
		if ( has_capability('moodle/role:assign', get_context_instance(CONTEXT_SYSTEM)) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Like useradmin_has_capabilities_to_assign() but requires capabilities
	 *
	 * FIXME rewrite to eliminate duplicated code
	 */
	function useradmin_require_capabilities_to_assign() {
		global $USER;
		require_capability('moodle/role:assign', get_context_instance(CONTEXT_SYSTEM), $USER->id);
	}
	 
	/**
	 * Build a string describing search
	 *
	 * @param string $search
	 * @param string $firstinitial
	 * @param string $lastinitial
	 * @param int $contextlevel
	 * @param int $contextinstanceid
	 * @param boolean $donthaverole
	 * @param int $roleid
	 * @param int $mnethostid
	 */
	function useradmin_search_description($search, $firstinitial, $lastinitial,
	$contextlevel, $contextinstanceid, $donthaverole, $roleid, $mnethostid, $filterconfirmed, $filterauth) {
	
		$searchdesc = '';
		$strand = ' '. get_string('and', 'block_useradmin') .' ';
	
		if ( $search ) {
			$searchdesc .= (($searchdesc )?$strand:''). get_string('searchbystring', 'block_useradmin', $search);
		}
	
		if ( $firstinitial ) {
			$searchdesc .= (($searchdesc )?$strand:''). get_string('searchbyfirstinitial', 'block_useradmin', $firstinitial);
		}
	
		if ( $lastinitial ) {
			$searchdesc .= (($searchdesc )?$strand:''). get_string('searchbylastinitial', 'block_useradmin', $lastinitial);
		}
	
		if ( $contextlevel && $contextinstanceid && $roleid ) {
			$str = new object();
			$contextlevels = useradmin_get_context_levels();
			$str->contextlevel = $contextlevels[$contextlevel];
			$contexts = useradmin_get_contexts_by_level($contextlevel);
			$str->context = $contexts[$contextinstanceid];
			$roles = useradmin_get_available_roles($contextlevel, $contextinstanceid);
			$str->role = $roles[$roleid];
			if ( $donthaverole ) {
				$str->donthaverole = get_string('searchhavenot','block_useradmin');
			} else {
				$str->donthaverole = get_string('searchhave','block_useradmin');
			}
			$searchdesc .= (($searchdesc )?$strand:''). get_string('searchbycontext', 'block_useradmin', $str);
		}
	
		if ( $mnethostid ) {
			$hosts = useradmin_get_available_mnet_hosts();
			$host = $hosts[$mnethostid];
			$searchdesc .= (($searchdesc )?$strand:''). get_string('searchbyhost','block_useradmin', $host);
		}
		 
		if ( $filterconfirmed == 1 ) {
			$searchdesc .= (($searchdesc )?$strand:''). get_string('filterconfirmed_confirmedonly','block_useradmin');
		} else if ( $filterconfirmed == 2 ) {
			$searchdesc .= (($searchdesc )?$strand:''). get_string('filterconfirmed_unconfirmedonly','block_useradmin');
		}
	
		if ($filterauth) {
			$searchdesc .= (($searchdesc )?$strand:'').  get_string('searchbyauth','block_useradmin', $filterauth);
		}
	
		if ( $searchdesc ) {
			$searchdesc = get_string('searchconditions', 'block_useradmin') .' '.$searchdesc;
		} else {
			$searchdesc = get_string('nosearchcondition', 'block_useradmin');
		}
	
	
		return $searchdesc;
	}
	
	/**
	 * Execute paged query on Users
	 * In parameter $searchcount (passed by reference) returns the count of the users
	 * retrieved by the query, WITHOUT taking account of paging
	 * @return array of users
	 */
	function useradmin_get_users_listing(&$searchcount, $sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=99999,
	$search='', $firstinitial='', $lastinitial='',
	$contextlevel=NULL, $contextinstanceid=NULL, $donthaverole=false, $roleid=NULL, $mnethostid='', $filterconfirmed=0, $filterauth ='',
	$searchcustomfields = false,
	$customfieldstoshow=NULL,
	$customfields=NULL ) {
	
		global $CFG;
	
	
		$selectlist = "u.*, mh.name AS mnethostname, mh.wwwroot AS mnethostwwwroot";
		 
//		// Adds custom fields to select list if needed
//		// they need to be included as subselects
//		if ( $customfieldstoshow  ) {
//			foreach($customfieldstoshow as $customfield ) {
//				$customfieldid = $customfields[$customfield]->id;
//				$selectlist = $selectlist.", (select data from {$CFG->prefix}user_info_data where fieldid = $customfieldid AND userid = u.id) AS $customfield";
//			}
//		}

		// Adds custom fields to select list, if needed
		if ( $customfieldstoshow  ) {
			$customfldcount = 0;
			foreach($customfieldstoshow as $customfield ) {
				$customfieldid = $customfields[$customfield]->id;
				$custalias = "cust$customfldcount";
				$selectlist .= ", $custalias.data AS $customfield";
				$customfldcount++;
			}
		}
	
		$LIKE      = sql_ilike();
		$fullname  = sql_fullname();
	
		$from = "{$CFG->prefix}user u LEFT OUTER JOIN {$CFG->prefix}mnet_host mh ON u.mnethostid = mh.id";
		
		// Adds custom fields LEFT OUTER JOINs
		if ( $customfieldstoshow  ) {
			$customfldcount = 0;
			foreach($customfieldstoshow as $customfield ) {
				$customfieldid = $customfields[$customfield]->id;
				$custalias = "cust$customfldcount";
				$from .= " LEFT OUTER JOIN {$CFG->prefix}user_info_data $custalias ON ($custalias.userid = u.id AND $custalias.fieldid = $customfieldid ) ";				
				$customfldcount++;
			}
		}
		
	
		$where = "u.deleted <> '1' AND u.username <> 'changeme' AND u.username <> 'guest'";
	
	
		if (!empty($search)) {
			$search = trim($search);
			$where .= " AND ($fullname $LIKE '%$search%' OR u.email $LIKE '%$search%'"
			." OR u.institution $LIKE '%$search%' OR u.department $LIKE '%$search%' OR u.city $LIKE '%$search%'"
			." OR u.username LIKE '%$search%' ";
			
	        // Search also in custom fields, if required
//	        if ( $searchcustomfields  ) {
//	        	$where .= " OR EXISTS ( SELECT * FROM {$CFG->prefix}user_info_data AS cst WHERE cst.userid = u.id AND cst.data $LIKE '%$search%' )";
//	        }
			if ( $searchcustomfields  && $customfieldstoshow ) {
				$customfldcount = 0;
				foreach($customfieldstoshow as $customfield ) {
					$customfieldid = $customfields[$customfield]->id;
					$custalias = "cust$customfldcount";
					$where .= " OR $custalias.data  LIKE '%$search%'";
					$customfldcount++;					
				}				
			}
			 			 
			$where .= ' )';
		}
	
		if ($firstinitial) {
			$where .= ' AND u.firstname '. $LIKE .' \''. $firstinitial .'%\' ';
		}
		if ($lastinitial) {
			$where .= ' AND u.lastname '. $LIKE .' \''. $lastinitial .'%\' ';
		}
		if ($mnethostid) {
			$where .= " AND u.mnethostid = '$mnethostid' ";
		}
		if ($filterconfirmed == 1) {
			$where .= " AND u.confirmed = 1 ";
		} else if ($filterconfirmed == 2) {
			$where .= " AND u.confirmed = 0 ";
		}
		if ( $filterauth ) {
			$where .= " AND u.auth = '$filterauth' ";
		}
	
	
		$rolewhere = '';
		if ( $contextlevel && $contextinstanceid && $roleid ) {
			$rolewhere = "AND " . (($donthaverole)?'NOT':'') . " EXISTS ( SELECT * FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}context c "
			. " WHERE ra.userid = u.id AND ra.contextid = c.id AND c.contextlevel = $contextlevel AND ra.roleid = $roleid";
	
			// Add filter by instanceID only if Context Level is NOT CONTEXT_SYSTEM (that always have instanceID = 0 )
			if ( $contextlevel != CONTEXT_SYSTEM ) {
				$rolewhere .= " AND c.instanceid = $contextinstanceid ";
			}
			$rolewhere .= ')';
		}
	
		if ($sort) {
			$sort = ' ORDER BY '. $sort .' '. $dir;
		}
	
		$limitfrom = $page * $recordsperpage;
		$limitnum = $recordsperpage;
		debugging( "Paging: PAGE $page, PERPAGE $recordsperpage",DEBUG_DEVELOPER);
		debugging( "Paging: FROM $limitfrom, LIMIT $limitnum",DEBUG_DEVELOPER);
		 
		// SQL for paged query
		$sql = "SELECT $selectlist FROM $from WHERE $where $rolewhere $sort ";
		debugging( "SQL: ".htmlentities($sql), DEBUG_DEVELOPER);
	
		// SQL for count query, w/o paging limit
		$sqlcount = "SELECT count(*) FROM $from WHERE $where $rolewhere";
		//echo "<pre>Count: $sqlcount</pre>";
	
		// Execute Count query first
		$searchcount = count_records_sql($sqlcount);
		debugging( "The query should return $searchcount record(s)",DEBUG_DEVELOPER);
	
		// Execute full (paged) query
		$users = get_records_sql($sql, $limitfrom, $limitnum);
	
		return $users;
	}
	
	
	/**
	 * Returns the total count of Users, excluding deleted and guest users,
	 * like get_users_listing2() do
	 */
	function useradmin_get_user_totalcount() {
		$select = "deleted <> '1' AND username <> 'changeme' AND username <> 'guest'";
		return count_records_select('user', $select);
	}
	
	/**
	 * Similar to optional_param() but returns $previousvalue if param is not set at all,
	 * and returns $clearvalue if param is set to empty string
	 */
	function useradmin_optional_param_clearing($paramname, $previousvalue=NULL, $clearvalue=NULL, $type=PARAM_CLEAN ) {
		// detect_unchecked_vars addition
		global $CFG;
		if (!empty($CFG->detect_unchecked_vars)) {
			global $UNCHECKED_VARS;
			unset ($UNCHECKED_VARS->vars[$paramname]);
		}
		 
		// if is empty string, return clear value
		if ( array_key_exists($paramname, $_REQUEST) && $_REQUEST[$paramname] === '' ) {
			$param = $clearvalue;
		}
		// If not set at all, use previous value
		else if ( !array_key_exists($paramname, $_REQUEST) ) {
			$param = $previousvalue;
		}
		// Else use request
		else {
			$param = $_REQUEST[$paramname];
		}
	
		return clean_param($param, $type);
	}
	
	/**
	 * Returns an optionally collapsable text
	 * If collapsed, text is replaced by ellipses with alt-text (if available) or full text,
	 * as tooltip
	 */
	function useradmin_collapsable_text($text, $showfull = TRUE, $alttext = NULL) {
		// If string is empty, return empty string
		if ( !$text  ){
			return '';
		}
		// return full text
		else if ( $showfull ) {
			return $text;
		}
		// return ellipsed text (using overLIB)
		else {
			$tooltiptext = ($alttext)?(s($alttext)):(s($text));
			//            return "<a class=\"tooltip\" hrep=\"#\" >...<span>$tooltiptext</span></a>";
			return  "<a href=\"javascript:void(0);\" onmouseover=\"return overlib('$tooltiptext',HAUTO);\" onmouseout=\"return nd();\">...</a>";
				
		}
	}
	
	/**
	 * Returns an array with all Context Levels (for use in dropdown menu)
	 * @return array $contextlevel=>$name
	 */
	function useradmin_get_context_levels() {
		$contextlevels = array();
		// manage only Course, Category and System
		$contextlevels[CONTEXT_SYSTEM] = get_string('CONTEXT_SYSTEM','block_useradmin');
		$contextlevels[CONTEXT_COURSECAT] = get_string('CONTEXT_COURSECAT','block_useradmin');
		$contextlevels[CONTEXT_COURSE] = get_string('CONTEXT_COURSE','block_useradmin');
	
		return $contextlevels;
	}
	
	/**
	 * Returns an array of Context names for a given context level
	 * Only SYSTEM, COURSECAT and COURSE is supported
	 * @param $contextlevel context level.
	 * @return array $contextid=>$name
	 */
	function useradmin_get_contexts_by_level($contextlevel) {
		$contextnames = array();
	
		switch ($contextlevel) {
			case CONTEXT_SYSTEM:
				$site = get_site();
				$contextnames[$site->id] = $site->shortname;
				break;
				 
			case CONTEXT_COURSECAT:
				// TODO build category names paths and not only plain names. Maybe this will make the dropdown too wide.
				$categories = get_categories();
				foreach ($categories as $category) {
					$contextnames[$category->id] = $category->name;
				}
				break;
				 
			case CONTEXT_COURSE:
				$courses = get_courses();
				$site = get_site();
				foreach ($courses as $course) {
					// Skip Site
					if ( $course->id != $site->id ) {
						$contextnames[$course->id] = $course->shortname;
					}
				}
				break;
				 
		}
		return 	$contextnames;
	}
	
	/**
	 * Similar to get_assignable_roles() but uses
	 * separate $contextlevel and $contextinstanceid
	 * parameters.
	 * If $contextlevel is null, returns all defined roles
	 *
	 * @param $contextlevel
	 * @param $contextinstanceid
	 *
	 * @return array of roles. Empty if any param is invalid or unspecified
	 */
	function useradmin_get_available_roles($contextlevel = '', $contextinstanceid = '') {
		$roles = array();
		if ( $contextlevel && $contextinstanceid ) {
			$context = get_context_instance($contextlevel, $contextinstanceid);
			if ( $context ) {
				// $roles = get_assignable_roles($context);
				$roles = get_assignable_roles($context, 'name', ROLENAME_BOTH);
	
			}
		} else {
			$allroles = get_all_roles();
			foreach ($allroles as $role) {
				$roles[$role->id] = $role->name ;
			}
	
		}
		return $roles;
	
	}
	
	/**
	 * Retrieve a Role by ID
	 */
	function useradmin_get_role($roleid) {
		return get_record("role","id",$roleid);
	}
	
	/**
	 * Returns true if the User has the Role in  Context
	 */
	function userdmin_user_has_role_in_context($userid, $contextid, $roleid) {
		return record_exists('role_assignments','userid',$userid,'contextid',$contextid,'roleid',$roleid);
	}
	
	function useradmin_get_available_mnet_hosts() {
		global $CFG;
	
		$availablehosts = array();
		$hosts = get_records('mnet_host');
		// Local host first
		$availablehosts[$CFG->mnet_localhost_id] = get_string("localhost","block_useradmin");
		foreach ($hosts as $host) {
			// Skip local host and All Hosts
			if ( ($host->id != $CFG->mnet_localhost_id) && $host->wwwroot) {
				$availablehosts[$host->id] = $host->name;
			}
		}
		return $availablehosts;
	}
	
	/**
	 * Get auth plugins available and used by some active user
	 * @return array of plugin instance, keyed by $authtype
	 */
	function useradmin_get_available_auth_plugins() {
		global $CFG;
	
		// Get auth used by any user (retrieve only auth field from user table)
		$usedauths = get_records_sql("select distinct auth from {$CFG->prefix}user where deleted = 0");
	
		// get currently installed and enabled auth plugins
		$authsavailable = get_list_of_plugins('auth');
		// Load all plugins
		$authplugins = array();
		foreach ($authsavailable as $auth) {
			$authplugin = get_auth_plugin($auth);
			if ( array_key_exists($authplugin->authtype, $usedauths)) {
				$authplugins[$authplugin->authtype] = $authplugin;
			}
		}
		return $authplugins;
	}
	
	/**
	 * Returns an array to use in choose_from_menu() with all authtypes
	 * (manual first).
	 * If no array of auth plugins is passed, it
	 * is retrieved by useradmin_get_available_auth_plugins()
	 */
	function useradmin_authfilter_options( $authplugins = null) {
		if (!$authplugins ) {
			$authplugins = useradmin_get_available_auth_plugins();
		}
	
		$authfilter_options = array();
		// Manual
		$authfilter_options['manual'] = 'manual';
	
		// Others
		foreach ($authplugins as $authplugin) {
			if ( $authplugin->authtype != 'manual')
			$authfilter_options[$authplugin->authtype] = $authplugin->authtype;
		}
		return $authfilter_options;
	}
	
	/**
	 * Print notify message
	 *
	 * @param $linenum File line number
	 * @param $message Mesage to print
	 * @param $iserror Is an error message?
	 * @param $userid UserID (if known)
	 * @param $username Username (if known)
	 * @param $skipline The file row will be skipped?
	 *
	 */
	function useradmin_uploaduser_notify($linenum, $message, $iserror = FALSE, $userid=NULL, $username=null, $skipline=FALSE) {
		$msg = get_string('linenumber', 'block_useradmin')." $linenum - ";
		if($username){
			$msg = $msg . get_string('username','block_useradmin') .': '.$username.' - ';
		}
		if ($userid) {
			$msg = $msg."(id:$userid) ";
		}
		$msg = $msg.$message;
	
		if($skipline) {
			$msg = $msg.' - '.get_string('skipthisline', 'block_useradmin');
		}
	
		if ( $iserror ) {
			$fontcolor = '#DC143C'; //red
		} else {
			$fontcolor = '#228B22'; //green
		}
		echo "<span style='color:$fontcolor; padding-left: 20px;'>".$msg."</span><br />";
	}
	
	/**
	 * Notify an error uploading users from file
	 * @param int $linenum upload file line
	 * @param string $message error message
	 * @param int $userid ID of the affected user (if any)
	 * @param string $username username of the affected user (if any)
	 * @param boolean $skipline will this line be skipped (error) or not (just a warning)?
	 */
	function useradmin_uploaduser_notify_error($linenum, $message, $userid=NULL, $username=NULL, $skipline=FALSE) {
		useradmin_uploaduser_notify($linenum, $message, TRUE, $userid, $username, $skipline);
	}
	
	/**
	 * Notify success/notice message uploading users from file
	 * @param int $linenum upload file line
	 * @param string $message error message
	 * @param int $userid ID of the affected user (if any)
	 * @param string $username username of the affected user (if any)
	 */
	function useradmin_uploaduser_notify_success($linenum, $message, $userid=NULL, $username=null) {
		useradmin_uploaduser_notify($linenum, $message, FALSE, $userid, $username, FALSE);
	}
	
	/**
	 * Returns all Users, optionally including unconfirmed and remote
	 * Always remove deleted!
	 * @param $includeunconfirmed include unconfirmed users
	 * @param $includeremote include Remote users
	 * @return array of User objects
	 */
	function useradmin_get_all_users($includeunconfirmed=false, $includeremote=false) {
		global $CFG;
	
		// Setup MNET enviromnent, if needed
		if (!isset($CFG->mnet_localhost_id)) {
			include_once $CFG->dirroot . '/mnet/lib.php';
			$env = new mnet_environment();
			$env->init();
			unset($env);
		}
	
		$selectlist = "u.*, mh.name AS mnethostname, mh.wwwroot AS mnethostwwwroot";
	
		$from = "{$CFG->prefix}user u LEFT OUTER JOIN {$CFG->prefix}mnet_host mh ON u.mnethostid = mh.id";
	
		$where = " u.deleted = 0 AND u.username <> 'changeme'";  // Exclude deleted and 'changeme'
	
		if ( !$includeunconfirmed ) {
			$where .= " AND u.confirmed=1";
		}
		if ( !$includeremote ) {
			$where .= " AND ( u.mnethostid IS NULL OR u.mnethostid = $CFG->mnet_localhost_id )";
		}
	
		$sort = "u.firstname ASC, u.lastname ASC";
	
		$sql = "SELECT $selectlist FROM $from WHERE $where ORDER BY $sort";
	
		return get_records_sql($sql);
	}
	
	/**
	 * Retrieve a 3D array with all roles of users in each course
	 *  First index is userid.
	 *  Second index is couseid (instanceid).
	 *  Third index is roleid.
	 * Content is an object containing: userid, roleid, courseid, roleshortname, courseshortname
	 *
	 * Do not include deleted users
	 *
	 * @return array 3D array of Role objects
	 */
	function useradmin_get_users_courses_roles() {
		global $CFG;
	
		$users_courses_roles = array();
	
		$sql = "SELECT ra.id, u.id AS userid, c.id AS courseid, r.id AS roleid, r.shortname AS roleshortname, c.shortname AS courseshortname, u.lastname, u.firstname,"
                ." ra.timestart AS enrol_start, ra.timeend AS enrol_end, ra.timemodified AS enrol_date, ra.enrol AS enrol_mode"
		." FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra, {$CFG->prefix}role r, {$CFG->prefix}context ctx, {$CFG->prefix}course c"
		." WHERE u.id = ra.userid AND ra.roleid = r.id AND ra.contextid = ctx.id AND ctx.instanceid = c.id"
		.     " AND u.deleted = 0 AND ctx.contextlevel = ".CONTEXT_COURSE;
		//			  ." ORDER BY u.id ASC, c.id ASC, r.sortorder ASC";
	
		//echo "<p><pre>$sql</pre></p>";
		$records = get_records_sql($sql);
		 
		 
		if ( $records ) {
			foreach ($records as $record) {
				 
				if (!isset($users_courses_roles[$record->userid])) {
					$users_courses_roles[$record->userid] = array();
				}
				if (!isset($users_courses_roles[$record->userid][$record->courseid])) {
					$users_courses_roles[$record->userid][$record->courseid] = array();
				}
				if (!isset($users_courses_roles[$record->userid][$record->courseid][$record->roleid])) {
					$users_courses_roles[$record->userid][$record->courseid][$record->roleid] = array();
				}
				$users_courses_roles[$record->userid][$record->courseid][$record->roleid] = $record;
				 
				//echo "<p>$record->lastname $record->firstname: $record->courseshortname,$record->roleshortname</p>";
				 
			}
		}
		return $users_courses_roles;
	}
	
	
	/**
	 * Count (not-deleted) users with the emailstop flag On
	 * @return int
	 */
	function useradmin_count_emailstop_users() {
		$countemailstopusers = count_records('user','deleted','0', 'emailstop','1');
		return $countemailstopusers;
	}
	
	/**
	 * Check e-mail domain validity
	 * returns
	 * false if the mail is OK
	 * 1 (MAILCHK_MALFORMEDADDRESS): pattern check failed
	 * 2 (MAILCHK_DOMAINNOTALLOWED): e-mail not allowrd
	 * 3 (MAILCHK_INVALIDDOMAIN): invalid domain
	 * @param $email
	 * @return int or false
	 */
	function useradmin_check_email($email) {
		// Check address format
		if ( !validate_email($email) ) {
			debugging("Address $email is malformed", DEBUG_DEVELOPER);
			return MAILCHK_MALFORMEDADDRESS;
		}
		// Check if allowed by Moodle
		else if ( email_is_not_allowed($email) ) {
			debugging("Address $email is not allowed", DEBUG_DEVELOPER);
			return MAILCHK_DOMAINNOTALLOWED;
		}
		// Check domain
		else {
			list($mailUser, $mailDomain) = split('@',$email);
			if (!useradmin_checkdns($mailDomain) ) {
				debugging("Address $email domain is invalid", DEBUG_DEVELOPER);
				return MAILCHK_INVALIDDOMAIN;
			}
		}
		return false;
	}
	
	/**
	 * CheckDNS in an OS independend way
	 * @param $hostName
	 * @return unknown_type
	 */
	function useradmin_checkdns($mailDomain) {
		// If this is a Windows Server
		//    	debugging("Checking DNS for MX of domain $mailDomain", DEBUG_DEVELOPER);
		if  ( stristr($_SERVER['SERVER_SOFTWARE'],'microsoft') || stristr($_SERVER['SERVER_SOFTWARE'],'win32') ) {
			//    		debugging("Using Win32 NSLOOKUP", DEBUG_DEVELOPER);
			if(!empty($mailDomain)) {
				$result = array();
				exec('nslookup -type=MX '.escapeshellcmd($mailDomain), $result);
				// check each line to find the one that starts with the host
				// name. If it exists then the function succeeded.
				foreach ($result as $line) {
					//			      if(eregi("^$mailDomain",$line)) {
					if( stripos($line, $mailDomain) === 0 || stripos($line, 'timed out') !== false) { // If DNS timed out cannot say anything
						return true;
					}
				}
				debugging("MX $mailDomain not found", DEBUG_DEVELOPER);
				//			    foreach ($result as $line)
				//			      debugging("> $line", DEBUG_DEVELOPER);
				 
			}
			return false;
		}
		// Otherwise user true-OS version
		else {
			return checkdnsrr($mailDomain, 'MX');
		}
	}
	
	/**
	 * Returns the list of users with emailstop = 0
	 * @return array of users
	 */
	function useradmin_get_users_notemailstop() {
		$select = "deleted <> '1' AND username <> 'changeme' AND username <> 'guest' AND emailstop = 0";
		$users = get_records_select('user', $select, 'lastname, firstname');
		 
		return $users;
	}
	
	/**
	 * Disable e-mail for a given user
	 * @param $user
	 * @return unknown_type
	 */
	function useradmin_disable_email($user) {
		if ($user) {
			debugging("Disabling email for user ID=$user->id", DEBUG_DEVELOPER);
			$usertoupdate->id = $user->id;
			$usertoupdate->emailstop = 1;
			// Update user
			update_record("user", $usertoupdate);
		}
	}
	
	/**
	 * Enable e-mail for a given user
	 * @param $user
	 * @return unknown_type
	 */
	function useradmin_enable_email($user) {
		if ($user) {
			debugging("Disabling email for user ID=$user->id", DEBUG_DEVELOPER);
			$usertoupdate->id = $user->id;
			$usertoupdate->emailstop = 0;
			// Update user
			update_record("user", $usertoupdate);
		}
	}
	
	/**
	 * Returns the role with moodle/legacy:editingteacher capability in a given course
	 * @param $course
	 * @return object role
	 */
	function useradmin_get_editingteacher_role($course) {
		if ($editingteacherroles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW)) {
			return array_shift($editingteacherroles);   /// Take the first one
		}
		return NULL;
	}
	
	/**
	 * Returns the role with moodle/legacy:teacher capability in a given course
	 * @param $course
	 * @return object role
	 */
	function useradmin_get_teacher_role($course) {
		if ($teacherroles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW)) {
			return array_shift($teacherroles);   /// Take the first one
		}
		return NULL;
	}
	
	
	/**
	 * Include settings for hiding standard User fields
	 * (setting params is named 'block_useradmin_hide_' + fieldname)
	 */
	function useradmin_hidestandardfield($settings, $fieldname) {
		$settings->add(new admin_setting_configcheckbox('block_useradmin_hide_'.$fieldname,
		get_string('hidefield', 'block_useradmin', get_string($fieldname)),
		get_string('hidefielddesc', 'block_useradmin', get_string($fieldname)), 0) );
	}
	
	/**
	 * Include settings for hiding field of UserAdmin
	 * (setting params is named 'block_useradmin_hide_' + fieldname)
	 */
	function useradmin_hideuseraminfield($settings, $fieldname) {
		$customsetting = new admin_setting_configcheckbox('block_useradmin_hide_'.$fieldname,
		get_string('hidefield', 'block_useradmin', get_string($fieldname, 'block_useradmin')),
		get_string('hidefielddesc', 'block_useradmin', get_string($fieldname, 'block_useradmin') ), 0);
		$settings->add( $customsetting );
	}
	
	/**
	 * Include settings for showing a custom user profile field
	 * (setting params is named 'block_useradmin_include_' + fieldname)
	 */
	function useradmin_includecustomprofilefield($settings, $fieldshortname, $fieldname ) {
		$customsetting = new admin_setting_configcheckbox('block_useradmin_include_'.$fieldshortname,
		get_string('includecustomfield', 'block_useradmin', $fieldname),
		get_string('includecustomfielddesc', 'block_useradmin', $fieldname), 0);
		$settings->add( $customsetting );
	}
	
	/**
	 * Returns user_info_field records
	 * using the field shortname as array key
	 */
	function useradmin_customuserfields() {
		$fields = get_records_select('user_info_field', "", 'categoryid ASC, sortorder ASC');
		$customfields = array();
		if (is_array($fields) ) {
			foreach($fields as $field) {
				$customfields[$field->shortname] = $field;
			}
		}
		return $customfields;
	}
	
	
	/**
	 * Returns a list of custom user profile field names to show
	 * (depending on $CFG->'block_useradmin_include_' + fieldname configuration parameter)
	 */
	function useradmin_customfieldstoshow() {
		global $CFG;
		$customfieldstoshow = array();
		foreach($CFG as $key=>$show) {
			if ( strpos($key,'block_useradmin_include_') === 0 && $show ) {
				$fieldname = substr($key, strlen('block_useradmin_include_') );
				$customfieldstoshow[] = $fieldname;
			}
		}
		return $customfieldstoshow;
	}
	
	/**
	 * Returns a list of standard user profile field names to hide
	 * (depending on $CFG->'block_useradmin_hide_' + fieldname configuration parameter)
	 */
	function useradmin_standardfieldstohide() {
		global $CFG;
		$standardfieldstohide = array();
		foreach($CFG as $key=>$hide) {
			if ( strpos($key,'block_useradmin_hide_') === 0 && $hide ) {
				$fieldname = substr($key, strlen('block_useradmin_hide_') );
				$standardfieldstohide[] = $fieldname;
			}
		}
		return $standardfieldstohide;
	}
	
	/**
	 * Generate an array for $table row
	 * (actually recieves a lot of page-level variables as global)
	 * @param $user the user object
	 * @param $standardfieldstohide array of standard user field names to show
	 * @param $customfieldstoshow array of custom field shortnames to show
	 * @param $expandcollapsefields array of fieldName=>boolean for expanding/collapsing column shown (true=expand)
	 * @return array that may be added to $table->data[]
	 */
	function useradmin_userlisttablerow($user ) {
		global $CFG, $USER, 
			$standardfieldstohide, $customfieldstoshow, $expandcollapsefields, $standarduserfieldnames, $securewwwroot, 
			$site, $mnet_auth_users_exists, $authplugins, $iconspacer, $stredit, $strdelete ;
		 
		
		// Access $user as an array
		$userarray = get_object_vars($user);
		
		// Force to lowercase al field names (needed as PostgreSQL return custom fields as lowercase even if custom field name has mixed case)
		$userarray = array_change_key_case($userarray, CASE_LOWER);
	
		// Default is no-control
		$confirmbutton = '';
		$editbutton = '';
		$selectcheck = '';
		$emailswitchbutton = '';
	
		// is the user remote?
		$isremoteuser = ($user->mnethostid != $CFG->mnet_localhost_id);
	
		$tablerowdata = array();
		foreach ($standarduserfieldnames as $standarduserfieldname) {
			switch ($standarduserfieldname) {
				case 'firstname':
					// The first column is special
					 
					// Select checkbox (if current user has capabilities to assign roles)
					if ( useradmin_has_capabilities_to_assign() ) {
						$selectcheck = '<input type="checkbox" name="user'.$user->id.'" />';
					}
					// Full name
					$fullname = fullname($user, true);
	
					// Edit user link (link only if user can edit)
					$edituserlink =  s($fullname);
					if ( useradmin_has_capabilities_to_edit() ) {
						$edituserlink = " <a href=\"$securewwwroot/user/view.php?id=$user->id&amp;course=$site->id\">$edituserlink</a>";
					}
					 
					$tablerowdata[] = $selectcheck.$edituserlink;
					break;
				case 'lastname':
					// Lastname is in the first column
					break;
					 
				case 'email':
					// (email cannot be hidden)
					 
					// Enable/Disable e-mail button
					if ( useradmin_has_capabilities_to_edit() ) {
						if ($user->emailstop) {
							$switchparam = 'enablemail';
							$switchtitle = get_string('emaildisable');
							$switchclick = get_string('emailenableclick');
							$switchpix   = 'emailno.gif';
						} else {
							$switchparam = 'disablemail';
							$switchtitle = get_string('emailenable');
							$switchclick = get_string('emaildisableclick');
							$switchpix   = 'email.gif';
						}
						$emailswitchbutton = "<a href=\"?$switchparam=$user->id&amp;sesskey=$USER->sesskey\" title=\"$switchclick\" onClick=\"\" ><img src=\"$CFG->pixpath/t/$switchpix\" alt=\"$switchclick\" /></a>";
					}
						
					$tablerowdata[] = useradmin_collapsable_text( obfuscate_mailto($user->email, '', $user->emailstop), $expandcollapsefields['email'], $user->email ) .' '.$emailswitchbutton;
					break;
					 
				case 'lastaccess':
					// (last access cannot be hidden)
					if ($user->lastaccess) {
						$strlastaccess = format_time(time() - $user->lastaccess);
					} else {
						$strlastaccess = get_string("never");
					}
					$tablerowdata[] = useradmin_collapsable_text( s($strlastaccess), $expandcollapsefields['lastaccess'] );
					break;
					 
				case 'mnethostname':
					$strremotehost = '';
					// (mnethostname may be hidden)
					if ( !in_array('mnethostname', $standardfieldstohide) ) {
						// Only if remote users exists...
						if ( useradmin_has_capabilities_to_edit() ) {
								
							if ( $mnet_auth_users_exists ) {
								if ( $isremoteuser ) {
									// Allow/Deny button (form remote users only)
									$accessctrl = 'allow';
									if ($acl = get_record('mnet_sso_access_control', 'username', $user->username, 'mnet_host_id', $user->mnethostid)) {
										$accessctrl = $acl->accessctrl;
									}
									$strallowdeny = get_string( $accessctrl ,'mnet');
									$changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');
									$strchangeto =  s(($changeaccessto == 'deny')?(get_string('allow_denymnetaccess', 'block_useradmin')):(get_string('deny_allowmnetaccess', 'block_useradmin')));
									$allowdenyiconurl = "$securewwwroot/pix/t/". (($accessctrl == 'allow')?'go.gif':'stop.gif') ;
									$allowdenybutton = "<a href=\"?acl={$user->id}&amp;accessctrl=$changeaccessto&amp;sesskey={$USER->sesskey}\"><img src=\"$allowdenyiconurl\" alt=\"$strchangeto\" title=\"$strchangeto\" /></a>";
									 
									// Remote Host
									$strremotehost .= s($user->mnethostname) . " $allowdenybutton";
								}
							}
						}
						$tablerowdata[] = useradmin_collapsable_text( $strremotehost, $expandcollapsefields['mnethostname'] );
					}
					break;
	
				case 'confirmed':
					// (confirmed may be hidden)
					if ( !in_array('confirmed', $standardfieldstohide) ) {
						// Confirm icon and confirm string(only if local and user's auth allow confirm)
						$strisconfirmed = '';
						if ( useradmin_has_capabilities_to_edit() ) {
							if ( !$isremoteuser && $authplugins[$user->auth]->can_confirm() ) {
								if ( $user->confirmed == 0 ) {
									$strisconfirmed = get_string('no');
									$confirmbutton = "<a href=\"?confirmuser=$user->id&amp;sesskey=$USER->sesskey\"><img src=\"$securewwwroot/pix/t/clear.gif\" alt=\"$strconfirm\" title=\"$strconfirm\" /></a>";
								} else {
									$strisconfirmed = get_string('yes');
									$confirmbutton = $iconspacer;
								}
							} else {
								$strisconfirmed = get_string('n_a','block_useradmin');
								$confirmbutton = $iconspacer;
							}
						}
						$tablerowdata[] = useradmin_collapsable_text( $strisconfirmed, $expandcollapsefields['confirmed'] ) ;
					}
					break;
					 
				default:
					// Shows only if not hidden
					if ( !in_array($standarduserfieldname, $standardfieldstohide) ) {
						$tablerowdata[] = useradmin_collapsable_text( s( $userarray[strtolower($standarduserfieldname)]), $expandcollapsefields["$standarduserfieldname"] );
					}
					break;
			}
		}

		// Custom fields columns
		foreach ($customfieldstoshow as $custfield ) {
			//$userarray = (array)$user;
			$tablerowdata[] = useradmin_collapsable_text(  s( $userarray[strtolower($custfield)]),  $expandcollapsefields["$custfield"] );
		}
			
		// Last column: Action buttons
	
		// Edit icon (only if user is local and has capabilities)
		if ( useradmin_has_capabilities_to_edit() ) {
			if ( $isremoteuser ) {
				$editbutton = $iconspacer;
			} else {
				$editbutton = "<a href=\"$securewwwroot/user/editadvanced.php?id=$user->id&amp;course=$site->id\"><img src=\"$securewwwroot/pix/t/edit.gif\" alt=\"$stredit\" title=\"$stredit\" /></a>";
			}
		}
		 
		// Delete icon
		$deletebutton = '';
		if ( useradmin_has_capabilities_to_delete() ) {
			if ($user->id == $USER->id or $user->username == "changeme") {
				$deletebutton = $iconspacer;
			} else {
				$deletebutton = "<a href=\"?delete=$user->id&amp;sesskey=$USER->sesskey\"><img src=\"$securewwwroot/pix/t/delete.gif\" alt=\"$strdelete\" title=\"$strdelete\" /></a>";
			}
		}
	
		$actionbuttons = $editbutton.' '.$deletebutton.' '.$confirmbutton;
		$tablerowdata[] =  $actionbuttons;
		 
		return $tablerowdata;
	}
?>