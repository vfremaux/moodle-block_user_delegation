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

require('../../../config.php');

$id = optional_param('id', SITEID, PARAM_INT);


// Security.

require_course_login($id);

$lastmodified = filemtime("user_edit.php");
$lifetime = 1800;

// Commenting this out since it's creating problems
// where solution seem to be hard to find...
// http://moodle.org/mod/forum/discuss.php?d=34376
// if ( function_exists('ob_gzhandler') ) {
// ob_start("ob_gzhandler");
// }

header("Content-type: application/x-javascript; charset: utf-8");  // Correct MIME type
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmodified) . " GMT");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT");
header("Cache-control: max_age = $lifetime");
header("Pragma: ");
?>

$(document).ready(function() {

    $('#id_submitbutton').click(function(event) {

        event.preventDefault();

        var uid = $('#id').val();

        if (uid != -1) {
            $('#mform1').submit();
        } else {
            $(this).val("<?php print_string('validatinguser', 'block_user_delegation') ?>.'"); 
            var email = $('#id_email').val();
            var firstname = $('#id_firstname').val();
            var lastname = $('#id_lastname').val();
            check_user_exist(email,firstname,lastname);
        }
    });

    function check_user_exist(email,firstname,lastname) {
        var skey= $('input[name=sesskey]').val();

        $.getJSON('serverside/service.php',{action:'CheckUserExist',sesskey:skey,f_name:firstname,l_name:lastname,e:email},function(data){
            if (data.result==0) {
                $('#id_submitbutton').val("<?php print_string('uservalid', 'block_user_delegation') ?>");
                $('#mform1').submit();
            } else {
                $('#id_submitbutton').val("<?php print_string('userexists', 'block_user_delegation') ?>");
                $.each(data.users,function(i,obj){
                $('#exisiting_users').append('<div>Opps, it looks like that the user is already exists in our database, please choose one of the following:</div>' );
                $('#exisiting_users').append(
                    '<div class="info-exist-user-cont">'+
                    '<?php echo $OUTPUT->pix_icon('user', '', 'block_user_delegation') ?> ' +
                    obj.firstname+" "+obj.lastname+
                    '&nbsp;&nbsp;&nbsp;&nbsp;'+
                    '<a class="addtoaccount" uid="'+obj.id+'" href="#">+ <?php print_string('attachtome', 'block_user_delegation') ?></a>'+
                    '</div>');
            });

            $("#exisiting_users a").live("click", function(event) {
                event.preventDefault();
                var power_uid = $('input[name=power_uid]').val();
                var fellow_uid= $(this).attr('uid');
                $.getJSON('serverside/service.php',{action:'AttachUser',sesskey:skey,puid:power_uid,fuid:fellow_uid},function(data) {
                        if (data.result == 1) {
                            alert("<?php print_string('useradded', 'block_user_delegation') ?>");
                            window.location="myusers.php?course="+$('input[name=course]').val();
                        }
                    });
                });

            $('#exisiting_users').show();
        }
    });
}

});
