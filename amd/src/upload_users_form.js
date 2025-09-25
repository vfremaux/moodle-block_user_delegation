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

define(['jquery', 'core/log', 'core/config', 'core/str'], function($, log, cfg, str) {

    var upload_users_form = {

        strs: [],

        init: function() {

            $('#id_coursetoassign').change(function() {
                upload_users_form.load_course_groups();
            });

            // Fetch some strings.
            str.get_strings([{
                key: 'addnewgroup',
                component: 'block_user_delegation'
            }, {
                key: 'loadinggroups',
                component: 'block_user_delegation'
            }, {
                key: 'nogroups',
                component: 'block_user_delegation'
            }, {
                key: 'nogroupassign',
                component: 'block_user_delegation'
            }
            ]).then(function(results) {
                upload_users_form.strs['addnewgroup'] = results[0];
                upload_users_form.strs['loadinggroups'] = results[1];
                upload_users_form.strs['nogroups'] = results[2];
                upload_users_form.strs['nogroupassign'] = results[3];
            });

            this.load_course_groups();

            log.debug("AMD User delegation upload users initialized.");
        },

        load_course_groups: function () {

            var courseid = $('#id_coursetoassign').val();
            var skey = $("[name='sesskey']").val();

           if (courseid === '' || courseid === null || courseid === 0) {
                log.debug("No course selected");
                $('#fitem_id_newgroupname').hide();
                $('#id_grouptoassign').empty();
                $('#id_grouptoassign').append("<option>" + upload_users_form.strs['loadinggroups'] + "</option>");
           } else {
                $('#id_grouptoassign').empty();
                $('#id_grouptoassign').append("<option>" + upload_users_form.strs['nogroupassign'] + "</option>");

                var params = {
                    action: 'GetCourseGroups',
                    sesskey: skey,
                    cid: courseid
                };
                var coursesurl = cfg.wwwroot + "/blocks/user_delegation/serverside/service.php";
                $.getJSON(coursesurl, params, function(data) {
                    $('#id_grouptoassign').empty();
                    $('#id_grouptoassign').append("<option value='-1'><b>" +
                        upload_users_form.strs['addnewgroup'] + "</b></option>");
                    if (data.result == false) {
                        $('#id_grouptoassign').append("<option>" +
                            upload_users_form.strs['nogroups'] + "</option>");
                    } else {
                        $('#id_grouptoassign').append("<option value='0'><b>" +
                            upload_users_form.strs['nogroupassign'] + "</b></option>");
                        $.each(data.result, function(i, obj) {
                        $('#id_grouptoassign').append("<option value='" + obj.id + "'>" + obj.name + "</option>");
                        });
                    }

                    $('#id_grouptoassign').on('change', function() {
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
    };

    return upload_users_form;
});
