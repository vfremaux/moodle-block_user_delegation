<?php  

/**
* Allows teachers to designate maximum upload sizes and directory sizes for 
* students in their course
* @package block_userfiles
* @category block
* @author
* 
*/
	//get all role names .

$roles = get_records('role');

$roles_arr = array();
foreach ($roles as $r){
    $roles_arr[$r->shortname] = $r->name ;    
}    

$options = $roles_arr ;

$settings->add(new admin_setting_configselect('block_userdelegation_co_role', get_string('block_userdelegation_co_role', 'block_userdelegation'),
                   get_string('block_userdelegation_co_role_desc', 'block_userdelegation'), SUBMITTERS_ADMIN_ONLY, $options));

$yesnooptions = array('0' => get_string('no'), '1' => get_string('yes'));
$settings->add(new admin_setting_configselect('block_userdelegation_last_owner_deletes', get_string('lastownerdeletes', 'block_userdelegation'),
                   get_string('configlastownerdeletes', 'block_userdelegation'), 0, $yesnooptions));


		   
?>