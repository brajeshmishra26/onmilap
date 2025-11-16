$("body").on('click', '.api_request', function(e) {

    if (!$(this).hasClass('processing')) {
        $(this).addClass('processing');

        var form_data = output_message_field = null;

        if ($(this).attr('form_data') !== undefined) {
            form_data = $(this).attr('form_data');
            var data = new FormData($(form_data)[0]);
        } else {
            var data = $(this).data();
        }

        var element = $(this);
        var skip_hide_action = false;
        var column = 'first';

        if (element.attr('column') !== undefined) {
            column = element.attr('column');
        }

        if ($(this).attr('output_message_field') !== undefined) {
            output_message_field = $(this).attr('output_message_field');
            $(output_message_field).hide();
        }

        if ($(this).attr('loader') !== undefined) {
            $($(this).attr('loader')).show();
        }

        if ($(this).hasClass('change_site_color_scheme') && $(this).data('color_scheme') !== undefined) {
            if ($(this).data('color_scheme') === 'light_mode') {
                var css_variable_file = baseurl+'assets/css/common/css_variables.css';
            } else {
                var css_variable_file = baseurl+'assets/css/common/dark_mode_css_variables.css';
            }

            (async () => {
                try {
                    var css_file_response = await fetch(css_variable_file);

                    if (!css_file_response.ok) {
                        console.log('CSS File load failed: ' + css_file_response.statusText);
                    }
                } catch (error) {
                    console.log('Error loading file:', error);
                }
            })();
        }

        if ($(this).attr('pass_send_as_user_id') !== undefined) {
            if ($('.main .chatbox > .header > .switch_user').length > 0) {
                if ($('.main .chatbox > .header > .switch_user > .current_selected_user > .user_image > img').length > 0) {
                    var send_as_user_id = $('.main .chatbox > .header > .switch_user > .user_id > input').val();
                    if (send_as_user_id.length > 0 && send_as_user_id !== '0') {
                        data['send_as_user_id'] = send_as_user_id;
                    }
                }
            }
        }


        if (form_data !== null) {
            if (user_csrf_token !== null) {
                data.append("csrf_token", user_csrf_token);
            }

            if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
                data.append("login_session_id", user_login_session_id);
                data.append("access_code", user_access_code);
                data.append("session_time_stamp", user_session_time_stamp);
            }
        } else {
            if (user_csrf_token !== null) {
                data['csrf_token'] = user_csrf_token;
            }

            if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
                data["login_session_id"] = user_login_session_id;
                data["access_code"] = user_access_code;
                data["session_time_stamp"] = user_session_time_stamp;
            }
        }

        if ($(this).attr('multi_select') !== undefined) {

            if ($(this).attr('data-chat_messages') !== undefined) {

                if ($(".main .chatbox").attr('group_id') !== undefined) {
                    data['remove'] = "group_messages";
                } else if ($(".main .chatbox").attr('user_id') !== undefined) {
                    data['remove'] = "private_chat_messages";
                }

                data['message_id'] = new Array();
                $(".main .chatbox .selector.select_item > input:checked").each(function() {
                    data['message_id'].push($(this).val());
                });
            } else {
                var selected = new Array();
                $(".main .aside > .site_records > .records > .list > li > div > .selector.select_item > input:checked").each(function() {
                    selected.push($(this).val());
                });
                data[$(this).attr('multi_select')] = selected;
            }

        }

        if (form_data !== null) {
            var request_data = {
                url: api_request_url,
                dataType: 'text',
                cache: false,
                contentType: false,
                processData: false,
                async: true,
                data: data,
                type: 'post',
                success: function(data) {}
            };
        } else {
            var request_data = {
                type: 'POST',
                url: api_request_url,
                data: data,
                async: true,
                success: function(data) {}
            };
        }

        $.ajax(request_data).done(function(data) {
            if (data === '') {
                location.reload(true);
            } else if (isJSON(data)) {
                data = $.parseJSON(data);
                if (data.success) {

                    if (data.close_modal !== undefined) {
                        $(data.close_modal).modal('hide');
                    }

                    if (data.force_reload_aside !== undefined) {
                        $('.main .aside > .site_records > .current_record').attr('load', data.force_reload_aside);
                        $(".main .aside > .site_records > .current_record").removeClass('loading');
                        $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');

                        if (data.filter_data !== undefined) {
                            $(".main .aside > .site_records > .current_record > .title").attr('filter_data', data.filter_data);
                        }

                        $(".main .aside > .site_records > .current_record > .title").trigger('click');
                    }

                    if (data.reload !== undefined && $.isArray(data.reload)) {
                        if (jQuery.inArray($('.main .aside > .site_records > .current_record').attr('load'), data.reload) !== -1) {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');
                            $(".main .aside > .site_records > .current_record > .title").trigger('click');
                        }
                    } else if (data.todo == 'reload') {

                        if (data.reload !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === data.reload) {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $(".main .aside > .site_records > .current_record > .title > div").removeClass('dropdown_button');

                            if (data.filter_data !== undefined) {
                                $(".main .aside > .site_records > .current_record > .title").attr('filter_data', data.filter_data);
                            }

                            $(".main .aside > .site_records > .current_record > .title").trigger('click');
                        }

                    } else if (data.todo == 'refresh') {
                        window.location.href = baseurl;
                    } else if (data.todo == 'refresh_current_page') {
                        location.reload(true);
                    } else if (data.todo == 'consolelog' && data.log !== undefined) {
                        console.log(data.log);
                    } else if (data.todo == 'redirect') {

                        if (data.remove_login_session !== undefined) {
                            WebStorage('remove', 'login_session_id');
                            WebStorage('remove', 'access_code');
                            WebStorage('remove', 'session_time_stamp');
                            WebStorage('remove', 'remove_login_session');

                            setTimeout(function () {
                                window.location.href = data.redirect;
                            }, 2000);

                        } else {
                            window.location.href = data.redirect;
                        }
                    } else if (data.todo == 'update_message_reactions') {
                        if (data.update_data !== undefined) {
                            update_message_reactions(data.update_data);
                        }
                    } else if (data.todo == 'remove_messages') {

                        $(".main .chatbox > .header > .message_selection").find('input').prop('checked', false);

                        if (data.remove_data !== undefined) {
                            remove_messages(data.remove_data);
                        }
                    } else if (data.todo == 'load_conversation') {

                        if (data.reload_aside !== undefined && $('.main .aside > .site_records > .current_record').attr('load') === 'groups') {
                            $(".main .aside > .site_records > .current_record").removeClass('loading');
                            $('.main .aside > .site_records > .current_record').attr('disable_preloader', true);
                            load_aside($(".main .aside > .site_records > .current_record"));
                            $('.main .aside > .site_records > .current_record').removeAttr('disable_preloader');
                        }

                        var load_data = [];
                        load_data[data.identifier_type] = data.identifier;
                        load_conversation(load_data);

                    }
                    if (data.info_box !== undefined) {
                        get_info(data.info_box);
                    }
                    $('.main .page_column[column="'+column+'"] .confirm_box > .content > .btn.cancel').trigger('click');
                } else {
                    if (data.error_message_position === undefined) {
                        if (data.error_message !== undefined && output_message_field !== null) {
                            $(output_message_field).replace_text(data.error_message);
                            $(output_message_field).fadeIn();
                        } else if (data.error_message !== undefined) {
                            $('.main .page_column[column="'+column+'"] .confirm_box > .error > .message > span').replace_text(data.error_message);
                            $('.main .page_column[column="'+column+'"] .confirm_box > .error').fadeIn();
                            skip_hide_action = true;
                        }
                    }
                }
            } else {
                console.log('ERROR : ' + data);
            }
            if (element.attr('loader') !== undefined) {
                $(element.attr('loader')).hide();
            }

            element.removeClass('processing');

            if (element.attr('hide_window') !== undefined) {
                $(element.attr('hide_window')).hide();
            }

            if (element.attr('hide_element') !== undefined && !skip_hide_action) {
                $(element.attr('hide_element')).addClass('d-none');
            }

        }) .fail(function(qXHR, textStatus, errorThrown) {
            if (element.attr('loader') !== undefined) {
                $(element.attr('loader')).hide();
            }

            element.removeClass('processing');


            if (element.attr('hide_window') !== undefined) {
                $(element.attr('hide_window')).hide();
            }

            if (element.attr('hide_element') !== undefined) {
                $(element.attr('hide_element')).addClass('d-none');
            }

            console.log('ERROR : ' + errorThrown);
        });
    }
});