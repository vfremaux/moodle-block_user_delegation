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

define(['jquery', 'core/log', 'core/config', 'core/str', 'core/icon_system_standard'], function($, log, cfg, str, icons) {

    var user_delegation_check = {

        strs: [],

        init: function() {
            $('#id_submitbutton').bind('click', this.check_user);

            // Fetch some strings.
            str.get_strings([{
                key: 'validatinguser',
                component: 'block_user_delegation'
            }, {
                key: 'uservalid',
                component: 'block_user_delegation'
            }, {
                key: 'userexists',
                component: 'block_user_delegation'
            }, {
                key: 'checkuserinvite',
                component: 'block_user_delegation'
            }, {
                key: 'attachtome',
                component: 'block_user_delegation'
            }, {
                key: 'useradded',
                component: 'block_user_delegation'
            }
            ]).then(function(results) {
                this.strs['validatinguser'] = results[0];
                this.strs['uservalid'] = results[1];
                this.strs['userexists'] = results[2];
                this.strs['checkuserinvite'] = results[3];
                this.strs['atachtome'] = results[4];
                this.strs['useradded'] = results[5];
            });
        },

        check_user: function(e) {

            var that = $(this);
            e.preventDefault();

            var uid = $('#id').val();
            var userform = that.closest('form');

            if (uid != -1) {
                // Submit the form.
                userform.submit();
            } else {
                $(this).val(user_delegation_check.strs['validatinguser'] + '.');
                var email = $('#id_email').val();
                var firstname = $('#id_firstname').val();
                var lastname = $('#id_lastname').val();
                user_delegation_check.check_user_exist(email,firstname,lastname, userform);
            }
        },

        /**
         * Checks if a user exists and propose attachement options.
         * @param {String} email
         * @param {String} firstname
         * @param {String} lastname
         * @param {String} userform
         */
        check_user_exist: function(email, firstname, lastname, userform) {

            var skey = $('input[name=sesskey]').val();

            var checkurl = cfg.wwwroot + '/blocks/user_delegation/serverside/service.php';
            var params = {
                    action: 'CheckUserExist',
                    sesskey: skey,
                    f_name: firstname,
                    l_name: lastname,
                    e: email
            };

            $.getJSON(checkurl, params, function(data) {

                if (data.result == 0) {
                    // User is not colliding with an exiting user.
                    $('#id_submitbutton').val(user_delegation_check.strs['uservalid']);
                    userform.submit();
                } else {
                    // User IS colliding with an exiting user.
                    $('#id_submitbutton').val(user_delegation_check.strs['userexists']);

                    $.each(data.users, function(i, obj) {
                        $('#existing_users').append('<div>' +
                            user_delegation_check.strs['usercheckinvite'] + '</div>');

                        $('#existing_users').append(
                            '<div class="info-exist-user-cont">' +
                            icons.renderIcon('user', 'block_user_delegation', '', 'core/pix_icon') +
                            obj.firstname + " " + obj.lastname +
                            '&nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<a class="addtoaccount" uid="' + obj.id + '" href="#"> '+
                                user_delegation_check.strs['attachtome'] + '</a>' +
                            '</div>'
                        );
                    });

                    $("#existing_users a").live("click", function(event) {
                        event.preventDefault();
                        var power_uid = $('input[name=power_uid]').val();
                        var fellow_uid = $(this).attr('uid');
                        var params = {
                                action: 'AttachUser',
                                sesskey: skey,
                                puid: power_uid,
                                fuid: fellow_uid
                        };
                        $.getJSON(checkurl, params, function(data) {
                                if (data.result == 1) {
                                    alert(user_delegation_check.strs['useradded']);
                                    window.location = cfg.wwwroot +
                                        "/block/userdelegation/myusers.php?course=" +
                                            $('input[name=course]').val();
                                }
                            });
                        });

                    $('#existing_users').show();
                }

            });
        },
    }; // End of user_delegation_check;

    return user_delegation_check;

});
