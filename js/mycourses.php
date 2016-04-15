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

require_once("../../../config.php");

$id = optional_param('id', SITEID, PARAM_INT);

// Security.

require_course_login($id);

$lastmodified = filemtime("mycourses.php");
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
$(document).ready(function(){

    $('.courseteachers-btn').click(function(event) {
       event.preventDefault();
       var id = $(this).attr('id');

       if ($('#cteacherscont-'+id).css('display') == 'none') {
           $('#cteacherscont-'+id).css('display','block');
       } else {
           $('#cteacherscont-'+id).css('display','none');
       }
    });

    $('.coursestudents-btn').click(function(event) {
        event.preventDefault();
        var id = $(this).attr('id');

       if ($('#cstudentscont-'+id).css('display')=='none') {
           $('#cstudentscont-'+id).css('display','block');
       } else {
           $('#cstudentscont-'+id).css('display','none');
       }
    });

});

