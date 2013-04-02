<?php

/**
* A library to interact with other parts of Moodle
*
*
*/

/**
* checks if user has some user creation cap somewhere
*/
function userdelegation_has_delegation_somewhere(){
	global $USER;

	// TODO : explore caps for a moodle/local:overridemy positive answer.
	$hassome = get_user_capability_course('block/userdelegation:cancreateusers', $USER->id, false); 
	if (!empty($hassome)){
		return true;
	}

	return false;
}