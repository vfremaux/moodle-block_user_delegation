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

require("../../../config.php");

$id = optional_param('id', SITEID, PARAM_INT);
require_course_login($id);

$lastmodified = filemtime("uploaduser.php");
$lifetime = 1800;

// Commenting this out since it's creating problems
// where solution seem to be hard to find...
// http://moodle.org/mod/forum/discuss.php?d=34376
//if ( function_exists('ob_gzhandler') ) {
//    ob_start("ob_gzhandler");
//}

header("Content-type: application/x-javascript; charset: utf-8");  // Correct MIME type
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmodified) . " GMT");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT");
header("Cache-control: max_age = $lifetime");
header("Pragma: ");
?>
$(document).ready(function() {

    load_course_groups();

    $('#id_coursetoassign').change(function() {
        load_course_groups();
    });

    function load_course_groups() {
       var course_id = $('#id_coursetoassign').val();
       var skey = $("[name='sesskey']").val();

       if (course_id == '' || course_id == null || course_id == 0) {
            //   console.log("No course selected");
            $('#fitem_id_newgroupname').hide();
       } else if(course_id == -1) {
           alert("<?php echo get_string('addnewgroup', 'block_user_delegation') ?>");
       } else {
              $('#id_grouptoassign').empty();
              $('#id_grouptoassign').append("<option><?php echo get_string('loadinggroups', 'block_user_delegation') ?></option>");
              $.getJSON('<?php echo $CFG->wwwroot ?>/blocks/user_delegation/serverside/service.php',{action:'GetCourseGroups',sesskey:skey,cid:course_id},function(data){

                if (data.result == false) {
                    $('#id_grouptoassign').empty();
                    $('#id_grouptoassign').append("<option><?php echo get_string('nogroups', 'block_user_delegation') ?></option>");
                } else {
                    $('#id_grouptoassign').empty();
                    $.each(data.result,function(i,obj){
                    $('#id_grouptoassign').append("<option value='"+obj.id+"'>"+obj.name+"</option>");
                    });
                }

                $('#id_grouptoassign').append("<option value='-1'><b><?php echo get_string('addnewgroup', 'block_user_delegation') ?></b></option>");

                $('#id_grouptoassign').on('change',function() {
                    var selected_value = $('#id_grouptoassign option:selected').val();
                    if (selected_value == -1) {
                        $('#fitem_id_newgroupname').show();
                    } else {
                        $('#fitem_id_newgroupname').hide();
                    }
                });
            });
        }
    }
});
