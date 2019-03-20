<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['user_delegation:addinstance'] = 'Can add an instance';
$string['user_delegation:myaddinstance'] = 'Can add an instance to My page';
$string['user_delegation:canbulkaddusers'] = 'Can bulk add users';
$string['user_delegation:cancreateusers'] = 'Can create users';
$string['user_delegation:candeleteusers'] = 'Can delete users';
$string['user_delegation:configure'] = 'Can configure';
$string['user_delegation:isbehalfof'] = 'Is behalf of';
$string['user_delegation:hasasbehalf'] = 'Has as behalf';
$string['user_delegation:owncourse'] = 'Own the course and can manage users (independently of course enrols)';
$string['user_delegation:owncoursecat'] = 'Own the course category and can manage users in all courses within (independently of course enrols)';
$string['user_delegation:view'] = 'Can view the block';

// Privacy.
$string['privacy:metadata'] = "The User Delegation Block needs to be implemented to reflect user behalves information";

$string['addnewgroup'] = 'Add new group....';
$string['addbulkusers'] = 'Add a bunch of users';
$string['alphabet'] = 'Alphabet';
$string['assignto'] = 'Enrol into course {$a}';
$string['attachtome'] = 'Attach this user to me';
$string['backtocourse'] = 'Back to course';
$string['backtohome'] = 'Back to home';
$string['badblockid'] = 'Bad block id';
$string['blockname'] = 'User Administration Subdelegation';
$string['changeenrolment'] = 'Change enrolments';
$string['colon'] = ':';
$string['comma'] = ',';
$string['configallowenrol'] = 'Allow enrol';
$string['configcsvseparator'] = 'CSV separator';
$string['configcsvseparator_desc'] = 'Separator of fields in csv file';
$string['configdelegationownerrole'] = 'Role for user delegation binding';
$string['configdelegationownerrole_desc'] = 'The role that will be used to assign fellow ownership. Only roles with "user_delegation:isbehaslfof" capability can be used.';
$string['configenrolduration'] = 'Enrol duration (in days, leave empty for no limit';
$string['configlastownerdeletes'] = 'Last owner deletes';
$string['configlastownerdeletes_desc'] = 'If enabled, the last behalfer of a user will mark him as deleted when removing the user from his behalf.';
$string['configuseadvancedform'] = 'Use advanced user form';
$string['configuseadvancedform_desc'] = 'Let enter much more user options if enabled';
$string['configuseuserquota'] = 'Use user quota';
$string['configuseuserquota_desc'] = 'If the local plugin "resource_limiter" is installed, use user quota limitation for delegated owners.';
$string['courseowner'] = 'Delegated owner';
$string['courseownerdescription'] = 'Is delegated owner of the object, can edit it and import new (delegated) users for use in his (delegated) courses.';
$string['coursetoassign'] = 'Course to assign';
$string['createpassword'] = 'Password handling';
$string['doseol'] = 'DOS Line endings';
$string['duplicatemails'] = 'Duplicate mails count';
$string['edituser'] = 'Edit User';
$string['edituseradvanced'] = 'Edit User (advanced mode)';
$string['emulatecommunity'] = 'Emulate community version';
$string['emulatecommunity_desc'] = 'If enabled, the plugin will behave as the public community version. This might loose features !';
$string['enrolnotallowed'] = 'Enrol not allowed';
$string['errorcreateuser'] = 'Error creating user record';
$string['errorinvalidaccess'] = 'Invalid access parameter.';
$string['errormisconfig'] = 'Error in user delegation configuration : ownership role {$a} undefined';
$string['errornosuchuser'] = 'No such user';
$string['errors'] = 'Errors';
$string['errorupdateuser'] = 'Error on user update';
$string['fakemail'] = 'Fake mail';
$string['fakemail_help'] = '';
$string['fakemails'] = 'Fake mails count';
$string['fieldseparator'] = 'Field separator';
$string['fieldseparator'] = 'Fields separator';
$string['fieldseparator_help'] = '';
$string['fileencoding'] = 'File encoding';
$string['fileencoding_help'] = '';
$string['fileformat'] = 'File format';
$string['filencoding'] = 'File encoding';
$string['filterconfirmed_all'] = 'All users';
$string['filterconfirmed_confirmedonly'] = 'Confirmed users only';
$string['filterconfirmed_unconfirmedonly'] = 'Unconfirmed only';
$string['filterconfirmed_unconformedonly'] = 'Unconfirmed usrs only';
$string['groupadded'] = 'User added to group';
$string['groupcreated'] = 'Group {$a} created';
$string['grouptoassign'] = 'Group to assign to new users';
$string['importuser'] = 'Import user';
$string['importusers'] = 'Bulk import users';
$string['inputfile'] = 'Input file';
$string['institution'] = 'Institution';
$string['invalidfieldname_areyousure'] = 'Invalid field name {$a}';
$string['invalidmails'] = 'Invalid mails count';
$string['lastownerdeletes'] = 'Last owner deletes';
$string['licenseprovider'] = 'Pro License provider';
$string['licenseprovider_desc'] = 'Input here your provider key';
$string['licensekey'] = 'Pro license key';
$string['licensekey_desc'] = 'Input here the product license key you got from your provider';
$string['linenumber'] = 'Line {$a}';
$string['loadingcoursegroups'] = 'Loading course groups...';
$string['loadinggroups'] = 'Loading groups...  please wait';
$string['maceol'] = 'MAC Line endings';
$string['missingvalue'] = 'Value missing for field {$a->fieldname}';
$string['mnethostidnotexists'] = 'This MNET host ID does\'nt exist';
$string['mycourses'] = 'My courses';
$string['mydelegatedcourses'] = 'My delegated courses';
$string['myusers'] = 'My users';
$string['new_userdelegation'] = 'New User Delegation';
$string['newgroupname'] = 'Group to create with imported users';
$string['newuser'] = 'New User';
$string['newuseradded'] = 'New user added';
$string['noassign'] = '-- no course assignment --';
$string['nogroups'] = 'No groups in course';
$string['nogroupswaitcourseslection'] = '-- No groups or waits course selection --';
$string['nomail'] = 'No Mail';
$string['nomail_help'] = 'Usually in Moodle, users SHOULD have a valid mail. In case this is NOT possible, you may mention this token into the email field of your file to let unmailed users pass.';
$string['nomailplaceholder'] = 'No Mail placeholder';
$string['noownedcourses'] = 'Not owning any course where I can manage users.';
$string['noownedusers'] = 'There are currently no users on your behalf.';
$string['nostudents'] = 'There are currently no students.';
$string['noteachers'] = 'There are currently no teachers.';
$string['onlyalphanum'] = 'Alphanum only';
$string['pipe'] = '|';
$string['plugindist'] = 'Plugin distribution';
$string['pluginname'] = 'User Administration Subdelegation';
$string['semicolon'] = ';';
$string['skipthisline'] = 'Skip this line';
$string['tab'] = 'TAB';
$string['teachers'] = 'Teachers';
$string['totalcourses'] = 'Total courses';
$string['traininggroup'] = 'Training group';
$string['manager'] = 'Group manager';
$string['trainee'] = 'Trainee {no}';
$string['traineerow'] = 'Trainee defined as Firstname / Lastname / Email. (Only full lines are processed)';
$string['truncatefield'] = 'Value truncated. Too long for this field.';
$string['unassignedusers'] = 'Unassigned Users';
$string['unixeol'] = 'UNIX Line endings';
$string['unmanaged'] = '(unmanaged)';
$string['uploadfile'] = 'Upload a file';
$string['uploadusers'] = 'Upload Users';
$string['user_delegation'] = 'User Administration Subdelegation';
$string['userbulkcreated'] = 'User {$a->username} account created for {$a->firstname} {$a->lastname}';
$string['userbulkexists'] = 'User {$a->firstname} {$a->lastname} was existing as {$a->username}. Skipped.';
$string['useraccountupdated'] = 'User account updated';
$string['useradded'] = 'Added to your behalves.';
$string['userenrolled'] = 'User enrolled in course {$a}';
$string['userexists'] = 'User exists';
$string['usermanagementoptions'] = 'User management options';
$string['username'] = 'Username : {$a}';
$string['usernotaddederror'] = 'User could not be created';
$string['usernotaddedregistered'] = 'User could not be assigned to you';
$string['usernotupdatederror'] = 'User could not be updatd';
$string['usersupdated'] = 'Users updated';
$string['uservalid'] = 'Valid ... Redirecting';
$string['validatinguser'] = 'Validating User.... Please wait';
$string['viewmycourses'] = 'View my courses';
$string['viewmyusers'] = 'View my users';

$string['uploadusers_help'] = '
<p>If you need import you owned users using a text file, it should be formated according to the following:</p>

<ul>
<li>File should be encoded in UTF-8 (default) or ISO 8859-1 (latin1).</li>
<li>The first line of the file has field names in lowercase, no spaces, in same order of data</li>
<li>Each line has one record.</li>
<li>Field values are separated using a semi-column (default) (or other accepted delimiter).</li>
<li>You may have blank lines or # comments lines, but do not repeat the field names line.</li>
</ul>

<p><b>Mandatory fields</b>: the following fields MUST be present and defined for all users.</p>

<p><code>firstname</code>, <code>lastname</code>, <code>email</code> for a user addition or <code>username</code> for an update</p>

<p><b>Optional fields</b>: those fields are optional. If not defined in the file, some of them will receive a default value from the Moodle site central settings.</p>

<p><code>city</code> (uppercase), <code>institution</code>, <code>department</code>, <code>country</code> (FR, UK), <code>lang</code> (fr,en,es,...) <!-- auth, ajax, timezone, idnumber, icq, --> phone1, phone2, address, url, description, <!-- mailformat, maildisplay, htmleditor, autosubscribe, emailstop --></p>

<p><b>Custom profile fields</b> : these fields are supported, replace xxxxx by the custom field shortname. The Moodle administrator should have published instructions for you about those field options.</p>

    <code>profile_field_xxxxx</code>

<p><b>Special fields</b> : Those fields may help you to delete or suspend users. Note that you cannot delete nor suspend users that 
are shared with other mentors.</p>

    <code>deleted</code>, <code>suspended</code>

<p><b>Enrol fields</b>: No enrol fields nor role information his supported by this user import service.</p>

<p>Use 0 and 1 for respectively "false" and "true" states of a boolean field.</p>
';

$string['createpassword_help'] = '
If you choose to let Moodle create passwords, you will NOT have communication of those.
Moodle will send directly passwords to users on base of the declared email in the import file. User emails thus need to be valid emails.
';

$string['plugindist_desc'] = '<p>This plugin is the community version and is published for anyone to use as is and check the plugin\'s
core application. A "pro" version of this plugin exists and is distributed under conditions to feed the life cycle, upgrade, documentation
and improvement effort.</p>
<p>Please contact one of our distributors to get "Pro" version support.</p>
<ul><li><a href="http://www.activeprolearn.com/plugin.php?plugin=block_use_stats&lang=en">ActiveProLearn SAS</a></li>
<li><a href="http://www.edunao.com">Edunao SAS</a></li></ul>';
