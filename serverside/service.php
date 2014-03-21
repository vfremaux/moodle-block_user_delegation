<?php
/**
 * This service file handles the operations for the reports activities.
 *                  
 *
 * @package   hr
 * @copyright 2010 Wafa Adham                                          
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */

require_once ('../../../config.php');
require_once ("../lib/userdelegation.class.php");

require_login ();

$action = required_param("action", PARAM_TEXT);
$sesskey = required_param("sesskey", PARAM_TEXT);

if(!confirm_sesskey($sesskey))
{
    error("invalid session");
}

// execute the necessary function, according to the operation code in the post variables
switch (@$action) {

	case "CheckUserExist":
          
		$email = required_param("e",PARAM_TEXT);
        $firstname = required_param("f_name",PARAM_TEXT);
        $lastname = required_param("l_name",PARAM_TEXT);       
        $users = userdelegation::check_user_exist($email,$firstname,$lastname);  
        $data = new stdClass();

        if(count($users) > 0){
        	$data->result = 1;
			$data->users = $users; 
		} else {             
            $data->result = 0;
        }
        
        print(json_encode($data));
        exit;        
		break;
        
	case "AttachUser":
      
        $power_uid = required_param('puid', PARAM_TEXT);
        $fellow_uid = required_param('fuid', PARAM_TEXT);
        $result = userdelegation::attach_user($power_uid, $fellow_uid);
        $data->result = $result;
        print(json_encode($data));
        break;
        
    case "GetCourseGroups":
//                         DebugBreak();
        $course_id = required_param('cid', PARAM_TEXT);
        
        
        $result = groups_get_all_groups($course_id);
           
        $data->result = $result;
        
        print(json_encode($data));
        break;
}

?>
