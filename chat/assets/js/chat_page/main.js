var baseurl = $('base').eq(0).attr('href');
var api_request_url = baseurl+'web_request/';
var realtime_request_url = baseurl+'realtime_request/';
var default_meta_title = decode_specialchars($("meta[name='default-title']").attr("content"));
var meta_title_timeout = null;
var user_typing_log_request = null;
var user_typing_log_timeout = null;
var users_typing_timeout = null;
var exit_video_chat_on_change = true;
var current_video_chat_type = null;
var current_video_chat_id = null;
var user_csrf_token = null;
var isVideoChatActive = false;
var search_on_change_of_input = false;
var user_login_session_id = WebStorage('get', 'login_session_id');
var user_access_code = WebStorage('get', 'access_code');
var user_session_time_stamp = WebStorage('get', 'session_time_stamp');
var remove_login_session = WebStorage('get', 'remove_login_session');
var blur_img_url = baseurl+'assets/files/defaults/image_thumb.jpg';
var blur_img_url = 'data:image/jpeg;base64,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';
var sw_registerations = [];
var viewerjs;
var current_logged_user_id;


$(document).ready(function() {
    if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
        if (system_variable('login_from_storage') === 'true') {
            $('body').addClass('d-none');
            var current_site_url = $(location).attr('href');

            var url_divider = (current_site_url.indexOf('?') !== -1) ? '&': '?';

            var user_login_register = current_site_url+url_divider;
            user_login_register = user_login_register+'login_session_id='+user_login_session_id+'&access_code='+user_access_code;
            user_login_register = user_login_register+'&session_time_stamp='+user_session_time_stamp;
            window.location.href = user_login_register;
        }
    }

    if ($('.logged_in_user_id').length > 0) {
        current_logged_user_id = $('.logged_in_user_id').text();
    }
});

if (system_variable('exit_call_when_switching') === 'no') {
    var exit_video_chat_on_change = false;
}

if (system_variable('search_on_change_of_input') === 'enable') {
    search_on_change_of_input = true;
}

if ($('meta[name="csrf-token"]').attr('content') !== undefined) {
    user_csrf_token = $('meta[name="csrf-token"]').attr('content');
}

var mobile_page_transitions = ['animate__backInUp', 'animate__zoomInUp', 'animate__rotateInUpLeft'];

var mobile_page_transition = 'animate__fadeInRightBig';




$('.main').on('click', function(e) {
    if (!$(e.target).parents('.switchuser').hasClass('switchuser')) {
        $('.main .panel > .textbox > .box > .switchuser > .uslist').hide();
    }
});

function addCssFile(url) {
    if ($('link[rel="stylesheet"][href="' + url + '"]').length === 0) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = url;

        document.head.appendChild(link);
    }
}


$('.main').on('click', function(e) {
    if ($(window).width() < 1200) {
        if (!$(e.target).parents('.side_navigation').hasClass('side_navigation')) {
            if ($('.main .chat_page_container').hasClass('show_navigation')) {
                toggle_side_navigation();
            }
        }
    }
});

function uniqueCodeHash(input) {
    const normalized = String(input).trim();

    let hash = 5381;
    for (let i = 0; i < normalized.length; i++) {
        hash = ((hash << 5) + hash) + normalized.charCodeAt(i);
        hash = hash >>> 0;
    }

    return hash.toString(36);
}

function unicodeHash(inputString) {
    const utf8Bytes = new TextEncoder().encode(inputString);
    let hash = 0;

    for (const byte of utf8Bytes) {
        hash += byte;
    }

    return hash;
}

function handleImageError(imageElement) {
    imageElement.src = blur_img_url;
}

$(document).ready(function() {
    $('body').on('contextmenu', 'img', function(e) {
        return false;
    });

    if ($('body').hasClass('right_click_disabled')) {
        document.addEventListener("contextmenu", (event) => {
            event.preventDefault();
        });
    }

    var force_ios_lockdown = false;

    if (force_ios_lockdown || isLockdown.isLockdownEnabled()) {
        var lockdown_stylesheet = $('<link>', {
            rel: 'stylesheet',
            type: 'text/css',
            href: baseurl+'assets/css/chat_page/lockdown_stylesheet.css'
        });

        $('head').append(lockdown_stylesheet);
    }
});


$("body").on('click', '.main .dropdown_button > .icon', function(evt) {
    if ($(window).width() > 767.98) {
        if ($(evt.target).parents('.dropdown_list').length == 0) {
            $(this).parent().find(".dropdown_list > ul > li").first().trigger("click");
        }
    }
});

function registerServiceWorker(update_worker) {
    var sw_location = baseurl+'service_worker.js';

    if (update_worker !== undefined) {
        var sw_location = baseurl+'service_worker.js?v='+update_worker;
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(sw_location)
        .then(function(registration) {
            if (update_worker !== undefined) {
                registration.update();
                console.log('Service worker updated successfully:', registration);
            } else {
                console.log('Service worker registered successfully:', registration);
            }
        })
        .catch(function(error) {
            console.log('Service worker registration failed:', error);
        });
    } else {
        console.log('Service workers are not supported.');
    }
}

function show_dropdown(element) {
    element.find(".dropdown_list").removeClass('reverse');
    element.find(".dropdown_list").show();

    var dropdown_box = {
        bottom: 552.6375122070312,
        height: 225,
        left: 641.4249877929688,
        right: 764.6749877929688,
        top: 327.63751220703125,
        width: 123.25,
        x: 641.4249877929688,
        y: 327.63751220703125,
    };

    if (element.find(".dropdown_list").length > 0) {
        dropdown_box = element.find(".dropdown_list").get(0).getBoundingClientRect();
    }

    var newtop = parseInt(element.find(".dropdown_list").height())-parseInt(dropdown_box.top);
    var isInViewport = (
        dropdown_box.top >= newtop &&
        dropdown_box.left >= 0 &&
        dropdown_box.bottom <= (element.parent('.boundary').innerHeight()) &&
        dropdown_box.right <= (element.parents('.boundary').innerWidth())
    );

    if (dropdown_box.top < newtop) {
        element.find(".dropdown_list").addClass('reverse');
    } else if (dropdown_box.bottom > (element.parent('.boundary').innerHeight())) {
        element.find(".dropdown_list").addClass('reverse');
    }
}


$("body").on('mouseenter', '.main .dropdown_button', function(e) {
    if ($(window).width() > 767.98) {
        //show_dropdown($(this))
    }
});

$("body").on('click', '.main .side_navigation .menu_items li', function(e) {
    if ($(window).width() < 767.98) {
        if (!$(this).hasClass('has_child')) {
            $('.main .chat_page_container').removeClass('show_navigation');
        }
    }
});


$(".main").on('click', function(e) {

    if (!$(e.target).hasClass('dropdown_button') && $(e.target).closest('.dropdown_button').length === 0) {
        $(".main .dropdown_list").hide();
        $('.main .toggle_message_toolbar > div > div.toggle_toolbar_button').removeClass('opened');
    }

    if (!$(e.target).parents().hasClass('switch_user')) {
        $('.main .chatbox > .header > .switch_user').removeClass('open');
    }

    if (!$(e.target).hasClass('site_record_item') && $(e.target).parents('.site_record_item').length == 0) {
        $(".main .aside > .site_records > .records > .list > li > div > .right > .options > span").hide();
    }

    if (!$(e.target).hasClass('side_navigation_footer') && $(e.target).parents('.side_navigation_footer').length == 0) {
        $(".main .side_navigation > .bottom.has_child").removeClass('show');
    }
});

$("body").on('click', '.main .dropdown_button', function(e) {
    var show_dropdown_list = true;

    if ($(this).hasClass('toggle_dropdown')) {
        if ($(this).find('.dropdown_list').is(':visible')) {
            show_dropdown_list = false;
        }
    }

    $(".main .dropdown_list").hide();

    if ($(e.target).hasClass('hide_onClick')) {
        show_dropdown_list = false;
    }

    if (show_dropdown_list) {
        show_dropdown($(this));
    }
});

function update_user_online_status(status) {

    var update_user_online_status = baseurl+system_variable('authentication_page_url_path')+'/user_online_status/';

    if (status !== undefined && status === 'offline') {
        var update_data = {
            offline: true,
        };
    } else {
        var update_data = {
            online: true,
        };
    }

    if (navigator.sendBeacon) {
        navigator.sendBeacon (update_user_online_status, JSON.stringify (update_data));
    }
}

window.addEventListener('beforeunload', function (e) {
    update_user_online_status('offline');
});

$(window).on("load", function() {
    update_user_online_status('online');
});

document.addEventListener("visibilitychange", function() {
    if ($(window).width() < 767.98) {
        if (document.visibilityState === 'hidden') {
            update_user_online_status('offline');
        } else if (document.visibilityState === 'visible') {
            update_user_online_status('online');
        }
    }
});

$("body").on('mouseenter', '.main .infotipbtn', function(e) {
    $(this).find(".infotip").show();
});

$("body").on('mouseleave', '.main .infotipbtn', function(e) {
    $(".main .infotip").hide();
});

$("html").on("dragover", function(e) {
    e.preventDefault();
    e.stopPropagation();
});

$("html").on("click", function(event) {
    if ($(event.target).attr('data-bs-toggle') === undefined || $(event.target).parent().hasClass('hide_tooltip_on_click')) {
        $('.tooltip').remove();
    }
});

$("html").on("drop", function(e) {
    e.preventDefault();
    e.stopPropagation();
});


$('.refresh_page').on('click', function() {

    var embed_url = system_variable('embed_url');

    if (embed_url.length > 0) {
        window.location.replace(embed_url);
    } else {
        location.reload(true);
    }
});

function dhbrowser_chk() {
    try {
        var canvas = document.createElement('canvas');
        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return true;
    } catch (e) {
        return true;
    }

    if (navigator.plugins.length === 0) {
        return true;
    }

    if (!navigator.mediaDevices) {
        return true;
    }

    if (navigator.webdriver) {
        return true;
    }

    var userAgent = navigator.userAgent;
    if (userAgent.includes('HeadlessChrome')) {
        return true;
    }

    if (screen.width < 320 || screen.height < 320) {
        return true;
    }

    return false;
}

$("body").on('focus', '.copy_to_clipboard', function(e) {
    var $this = $(this);
    $this.select();

    $this.keydown(function(event) {

        if (event.keyCode !== 17 && event.keyCode !== 67 && event.keyCode !== 91 && event.keyCode !== 67) {
            event.preventDefault();
        }

    });

    document.execCommand('copy');
});

jQuery(document).ready(function($) {

    if (window.history && window.history.pushState) {
        if ($(window).width() < 800) {
            $(window).on('popstate', function() {
                var hashLocation = location.hash;
                var load_blank_onexit = false;
                var hashSplit = hashLocation.split("#!/");
                var hashName = hashSplit[1];
                if (hashName !== '') {
                    var hash = window.location.hash;
                    if (hash === '') {
                        window.history.pushState('forward', null, './#');

                        if (load_blank_onexit && $('.main .aside').hasClass('visible')) {
                            window.open('about:blank', "_self");
                        } else {

                            var go_to_back_trigger = true;

                            if (!$('.main .middle > .video_preview').hasClass('d-none')) {
                                $('.main .middle > .video_preview').removeClass('fixed_draggable_layout');
                                $('.main .middle > .video_preview').addClass('d-none');
                                $('.main .middle > .video_preview > div').html('');
                                go_to_back_trigger = false;
                            }

                            if ($('.viewer-container.viewer-backdrop').length > 0) {
                                viewer.destroy();
                                go_to_back_trigger = false;
                            }

                            if (go_to_back_trigger) {
                                open_column('first', true);
                            }
                        }
                    }
                }
            });

            window.history.pushState('forward', null, './#');
        }
    }

});


$(window).on('load', function() {
    $('.preloader').fadeOut();
    $('body').removeClass('overflow-hidden');
    $('.site_sound_notification').addClass('d-none');

    var left_panel_content_on_page_load = $.trim($('.content_on_page_load > .left_panel_content_on_page_load').text());


    if ($('.main .side_navigation .force_trigger_onload').length > 0) {
        $('.main .side_navigation .force_trigger_onload').trigger('click');
    } else if (left_panel_content_on_page_load !== '') {
        left_panel_content_on_page_load = '.load_'+left_panel_content_on_page_load;
        $('.main .side_navigation '+left_panel_content_on_page_load).trigger('click');
    } else if ($('.main .side_navigation .load_groups').length > 0) {
        $('.main .side_navigation .load_groups').trigger('click');
    } else {
        $('.main .aside > .head > .icons > i.load_groups').trigger('click');
    }

    if ($(window).width() > 770.98) {
        var main_panel_content_on_page_load = $.trim($('.content_on_page_load > .main_panel_content_on_page_load').text());

        if (main_panel_content_on_page_load === 'statistics') {
            $('.main .side_navigation .load_statistics').trigger('click');
        } else if (main_panel_content_on_page_load === 'wallet') {
            $('.main .side_navigation .load_wallet_info').trigger('click');
        } else if (main_panel_content_on_page_load === 'membership') {
            $('.main .side_navigation .load_membership_info').trigger('click');
        }
    }


    if ($(window).width() > 1210) {
        if ($('.main .side_navigation').length > 0) {
            if (system_variable('show_side_navigation_on_load') === 'yes') {
                toggle_side_navigation();
            }
        }

    }


    var load_on_refresh = WebStorage('get', 'load_on_refresh');

    if (load_on_refresh !== undefined && load_on_refresh !== null) {
        load_on_refresh = JSON.parse(load_on_refresh);
    }

    if (load_on_refresh !== null && load_on_refresh.attributes !== undefined) {

        WebStorage('remove', 'load_on_refresh');

        var load_on_refresh_element = '<span ';

        $.each(load_on_refresh.attributes, function(attrkey, attrval) {
            load_on_refresh_element = load_on_refresh_element+attrkey+'="'+attrval+'" ';

        });

        load_on_refresh_element = load_on_refresh_element+'>on_refresh</span>';

        $('.load_on_refresh').html(load_on_refresh_element);
        $('.load_on_refresh > span').trigger('click');
    } else if ($('.on_site_load > span').length > 0) {

        if ($('.on_site_load > span').hasClass('load_profile_on_page_load')) {
            if ($(window).width() > 1210) {
                $('.on_site_load > span').trigger('click');
            }
        } else {
            $('.on_site_load > span').trigger('click');
        }

    }

    $('.main .aside > .storage_files_upload_status').addClass('d-none');
    $('.main').fadeIn();

    var after_load_js = baseurl+"assets/js/combined_js_chat_page_after_load_"+system_variable('js_file_cache_timestamp')+".js";
    $.getScript(after_load_js);

    $('.lazy').Lazy();

    try {
        if (window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches) {
            localStorage.setItem('pwaInstalled', 'true');
        }
        window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwaInstalled', 'true');
        });
    } catch (error) {
        console.error("An error occurred while checking PWA installation:", error);
    }

});

function isPWAInstalled() {
    try {
        const pwaStatus = localStorage.getItem('pwaInstalled');
        return pwaStatus === 'true';
    } catch (error) {
        return false;
    }
}


function is_touch_device() {
    return (
        "ontouchstart" in window ||
        navigator.MaxTouchPoints > 0 ||
        navigator.msMaxTouchPoints > 0
    );
}

function isJSON (data) {
    var IS_JSON = true;
    try
    {
        var json = $.parseJSON(data);
    }
    catch(err) {
        IS_JSON = false;
    }
    return IS_JSON;
}

function language_string(string_constant) {
    var string_value = '';

    if (string_constant !== undefined) {
        string_value = $('.language_strings > .string_'+string_constant).text();
    }

    return string_value;
}

function system_variable(variable, update_value) {

    if (update_value === undefined) {
        var result = '';

        if (variable !== undefined) {
            result = $('.system_variables > .variable_'+variable).text();
        }

        return result;
    } else {
        $('.system_variables > .variable_'+variable).text(update_value);
    }
}

function change_browser_title(title, set_timeout = 0) {
    if (title !== undefined) {
        title = $.trim(title);
        if (title.length > 0) {

            document.title = decode_specialchars(title);

            if (meta_title_timeout !== null) {
                clearTimeout(meta_title_timeout);
            }

            if (set_timeout == 0) {
                system_variable('current_title', title)
            } else {
                meta_title_timeout = setTimeout(function() {
                    meta_title_timeout = null;

                    var reset_title = system_variable('current_title');

                    if (reset_title.length < 0) {
                        reset_title = default_meta_title;
                    }

                    change_browser_title(reset_title);

                }, set_timeout);
            }
        }
    }
}

function timestamp_convertor(s) {
    var h = Math.floor(s/3600);
    var tms = "";
    s -= h*3600;
    var m = Math.floor(s/60);
    s -= m*60;
    s = Math.floor(s);
    if (h != 0) {
        tms = h+":"+(m < 10 ? '0'+m: m)+":"+(s < 10 ? '0'+s: s);
    } else {
        tms = (m < 10 ? '0'+m: m)+":"+(s < 10 ? '0'+s: s);
    }
    if (tms == 'NaN:NaN:NaN') {
        tms = "00:00";
    }
    return tms;
}

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    } else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

function isLocalStorageAvailable() {
    var test = 'test';
    try {
        localStorage.setItem(test, test);
        localStorage.removeItem(test);
        return true;
    } catch(e) {
        return false;
    }
}

function WebStorage(todo, name, value) {

    if (isLocalStorageAvailable() && typeof(Storage) !== "undefined") {
        if (todo == 'get') {
            value = localStorage.getItem(name);
            if (value) {
                return value;
            } else {
                return null;
            }
        } else if (todo == 'set') {
            localStorage.setItem(name, value);
        } else if (todo == 'remove') {
            localStorage.removeItem(name);
        } else if (todo == 'clear') {
            localStorage.clear();
        }
    } else {
        console.log('No Web Storage Support');
        return null;
    }
}

function RandomString (len) {
    var rdmString = "";
    for (; rdmString.length < len; rdmString += Math.random().toString(36).substr(2));
    return  rdmString.substr(0, len);
}

function abbreviateNumber(value) {
    var newValue = value;
    if (value >= 1000) {
        var suffixes = ["", "k", "m", "b", "t"];
        var suffixNum = Math.floor((""+value).length/3);
        var shortValue = '';
        for (var precision = 2; precision >= 1; precision--) {
            shortValue = parseFloat((suffixNum != 0 ? (value / Math.pow(1000, suffixNum)): value).toPrecision(precision));
            var dotLessShortValue = (shortValue + '').replace(/[^a-zA-Z 0-9]+/g, '');
            if (dotLessShortValue.length <= 2) {
                break;
            }
        }
        if (shortValue % 1 != 0)  shortValue = shortValue.toFixed(1);
        newValue = shortValue+suffixes[suffixNum];
    }
    return newValue;
}

$("body").on('click', '.open_link', function(e) {

    var web_address = '';

    if ($(this).attr('link') !== undefined) {
        web_address = $(this).attr('link');
    }

    if ($(this).attr('autosync') !== undefined) {
        if ($('.main .chatbox > .info_box > .open_link').is(":visible")) {
            if ($('.main .chatbox > .info_box > .open_link').attr('link') !== undefined) {
                web_address = $('.main .chatbox > .info_box > .open_link').attr('link');
            }
        }
    }

    if (web_address.length > 0) {

        if ($(this).attr('target') !== undefined) {
            window.open(web_address, $(this).attr('target')).focus();
        } else {
            window.location = web_address;
        }
    }

});


function on_image_load(image) {
    image.parentElement.classList.add('image_loaded');
}

$("body").on('click', '.go_to_previous_page', function(e) {

    if ($(window).width() < 780) {

        if (audio_message_preview !== undefined && audio_message_preview !== null) {
            audio_message_preview.pause();
        }

        if (video_preview !== undefined && video_preview !== null) {
            video_preview.pause();
        }

        $('.main .middle > .group_headers').addClass('d-none');
        $('.main .middle > .group_headers > .header_content').html('');

    }

    open_column('first', true);
});

function open_column(column, loadPrevious) {

    var animate = true;

    if ($(window).width() <= 991 && $(window).width() >= 770.98) {
        loadPrevious = false;
    }

    if (loadPrevious !== undefined && loadPrevious) {
        animate = false;
        if ($('.page_column.previous').length > 0) {

            var previous_column = $('.page_column.previous').attr('column');

            if ($('.page_column.previous').hasClass('d-none')) {
                previous_column = 'first';
            } else if (previous_column === 'third') {
                previous_column = 'first';
            }

            if (previous_column !== $('.page_column.visible').attr('column')) {
                column = previous_column;
            }
        }
    }

    var current_column = $('.page_column.visible');

    $('.page_column').removeClass('previous');
    $('.page_column').removeClass('animate__animated '+mobile_page_transition+' animate__faster');

    if (current_column.length === 0) {
        current_column = $('.page_column[column="first"]');
        $('.page_column[column="first"]').removeClass('d-none');
        $('.page_column[column="first"]').addClass('visible');
    }

    if (column !== undefined) {

        if ($(window).width() <= 991 && $(window).width() >= 770.98) {

            if (column === 'fourth') {
                $('.page_column[column="third"]').addClass('d-none');
                $('.page_column[column="first"]').addClass('d-none');
                $('.page_column[column="fourth"]').removeClass('d-none');
            } else if (column === 'first') {
                $('.page_column[column="third"]').addClass('d-none');
                $('.page_column[column="fourth"]').addClass('d-none');
                $('.page_column[column="first"]').removeClass('d-none');
            } else if (column === 'third') {
                $('.page_column[column="fourth"]').addClass('d-none');
                $('.page_column[column="first"]').addClass('d-none');
                $('.page_column[column="third"]').removeClass('d-none');
            }
        }

        if ($('.page_column.visible').attr('column') != column && animate) {
            $('.page_column[column="'+column+'"]').addClass('animate__animated '+mobile_page_transition+' animate__faster');
        }

        if (current_column.attr('column') === 'third' || current_column.attr('column') === 'fourth') {
            current_column = $('.page_column[column="first"]');
        }

        current_column.addClass('previous');
        $('.page_column').removeClass('visible');
        $('.page_column[column="'+column+'"]').addClass('visible').removeClass('previous');
    }

}

function open_module(moduleClass, parentClass, keepitOpen) {

    if (parentClass === undefined) {
        parentClass = 'body';
    }

    if (keepitOpen === undefined) {
        keepitOpen = false;
    }
    if ($(parentClass).find(moduleClass).hasClass('hidden')) {
        $(parentClass).find('.module').addClass('hidden');
        $(parentClass).find(moduleClass).removeClass('hidden');
    } else if (!keepitOpen) {
        $(parentClass).find('.module').addClass('hidden');
    }

}

function close_module(moduleClass, parentClass) {

    if (parentClass === undefined) {
        parentClass = 'body';
    }

    $(parentClass).find('.module').addClass('hidden');

}

function loader_content($type = 'list') {
    var content = '';
    if ($type == 'list') {
        for (let i = 0; i < 14; i++) {
            content = content+'<li><div><span class="left">';
            content = content+'<span class="img"></span>';
            content = content+'</span><span class="center">';
            content = content+'<span class="title"></span>';
            content = content+'<span class="subtitle"></span>';
            content = content+'</span><span class="right"></span>';
            content = content+'</div></li>';
        }
    }
    return content;
}


$('.no_form_submit').on('submit', function(event) {
    event.preventDefault();
});


$('body').on('click', '.openlink', function(e) {
    var url = $(this).attr("url");
    var pattern = /^((http|https|ftp):\/\/)/;
    if (!pattern.test(url)) {
        url = baseurl+url;
    }
    if ($(this).attr('newtab') == undefined) {
        window.location = url;
    } else {
        window.open(url, '_blank');
    }
    return false;
});

function randomColor(lum) {
    var randomColor = Math.floor(Math.random()*16777215).toString(16);
    randomColor = String(randomColor).replace(/[^0-9a-f]/gi, '');
    if (randomColor.length < 6) {
        randomColor = randomColor[0]+randomColor[0]+randomColor[1]+randomColor[1]+randomColor[2]+randomColor[2];
    }
    lum = lum || 0;
    var rgb = "#", c, i;
    for (i = 0; i < 3; i++) {
        c = parseInt(randomColor.substr(i*2, 2), 16);
        c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
        rgb += ("00"+c).substr(c.length);
    }
    return rgb;
}


$("body").on('click', '.toggle_side_navigation', function(e) {
    toggle_side_navigation();
});

function toggle_side_navigation() {

    $('.main .chat_page_container > .side_navigation').removeClass('animate__animated animate__slideInLeft animate__faster animate__slideInRight');

    if ($('.main .chat_page_container').hasClass('show_navigation')) {
        $('.main .chat_page_container').removeClass('show_navigation');
    } else {
        if ($(window).width() < 1200) {
            if ($('body').hasClass('ltr_language')) {
                $('.main .chat_page_container > .side_navigation').addClass('animate__animated animate__slideInLeft animate__faster');
            } else {
                $('.main .chat_page_container > .side_navigation').addClass('animate__animated animate__slideInRight animate__faster');
            }
        }
        $('.main .chat_page_container').addClass('show_navigation');
    }
}


$("body").on('click', '.download_file', function(e) {

    if (!$(this).hasClass('processing') && $(this).attr('download') !== undefined) {
        $(this).addClass('processing');

        var element = $(this);

        var data = {
            process: "download",
            validate: true,
            download: $(this).attr('download')
        };

        data = $.extend(data, $(this).data());

        if (user_csrf_token !== null) {
            data["csrf_token"] = user_csrf_token;
        }

        if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
            data["login_session_id"] = user_login_session_id;
            data["access_code"] = user_access_code;
            data["session_time_stamp"] = user_session_time_stamp;
        }

        $.ajax({
            type: 'POST',
            url: api_request_url,
            data: data,
            async: true,
            success: function(data) {}
        }).done(function(data) {
            if (isJSON(data)) {
                data = $.parseJSON(data);
                if (data.error != undefined) {
                    alert(decode_specialchars(data.error));
                } else if (data.download_link != undefined) {
                    window.location.href = data.download_link;
                }
            } else {
                console.log('ERROR : ' + data);
            }

            element.removeClass('processing');

        }) .fail(function(qXHR, textStatus, errorThrown) {
            element.removeClass('processing');
            console.log('ERROR : ' + errorThrown);
        });
    }
});


$("body").on('click', '.preview_image', function(e) {

    $('#preview_image').removeAttr('id');

    var index = $(this).parent().parent().index();
    var prev_btn = next_btn = navbar = 0;

    if ($(this).parents('.files').length > 0) {
        $(this).parent().parent().parent().attr('id', 'preview_image');
    } else {
        $(this).attr('id', 'preview_image');
    }

    if ($(this).parent().parent().parent().find('li').length > 1) {
        navbar = 1;
    }

    var image_data = {
        title: 0,
        navbar: navbar,
        toolbar: {
            zoomIn: {
                show: 1,
                size: 'large',
            },
            zoomOut: {
                show: 1,
                size: 'large',
            },
            oneToOne: 0,
            play: 0,
            prev: prev_btn,
            next: next_btn,
            rotateLeft: {
                show: 1,
                size: 'large',
            },
            reset: {
                show: 1,
                size: 'large',
            },
            rotateRight: {
                show: 1,
                size: 'large',
            },
            flipHorizontal: {
                show: 1,
                size: 'large',
            },
            flipVertical: {
                show: 1,
                size: 'large',
            },
        },
        hidden: function () {
            viewer.destroy();
        },
        url(image) {
            return image.getAttribute("original");
        },
    };

    if ($(this).attr('load_image') === undefined) {
        var viewerjs = viewer = new Viewer(document.getElementById('preview_image'), image_data);
    } else {

        image_data['url'] = 'src';

        var load_image = new Image();
        load_image.src = $(this).attr('load_image');
        var viewerjs = viewer = new Viewer(load_image, image_data);
    }


    viewer.view(index)
    viewer.show();
});


$("body").on('click', '.ask_confirmation', function(e) {

    var column = 'first';

    if ($(this).attr('column') === undefined || $(this).attr('column') === 'first') {
        $('.main .aside > .site_records > .records').addClass('blur');
        $('.main .aside > .site_records > .records > .list > li').removeClass('selected');
        $('.main .aside > .site_records > .tools').addClass('d-none');
    } else {
        column = $(this).attr('column');
    }

    var confirm_box = $('.main .page_column[column="'+column+'"] .confirm_box');

    var submit_button = '<span class="api_request">'+$(this).attr('submit_button')+'</span>';

    confirm_box.find('.content > .btn.submit').html(submit_button);

    confirm_box.find('.content > .btn.cancel > span').replace_text($(this).attr('cancel_button'));

    confirm_box.find('.content > .text').replace_text($(this).attr('confirmation'));

    $(this).parents('li').addClass('selected');

    $.each($(this).data(), function (name, value) {
        name = 'data-'+name;
        confirm_box.find('.content > .btn.submit > span').attr('column', column);
        confirm_box.find('.content > .btn.submit > span').attr(name, value);
    });

    if ($(this).attr('multi_select') !== undefined) {
        confirm_box.find('.content > .btn.submit > span').attr('multi_select', $(this).attr('multi_select'));
    }

    if (column === 'second') {
        confirm_box.find('.content > .btn.submit > span').attr('hide_element', '.middle .confirm_box');
    }

    confirm_box.find('.error').hide();
    confirm_box.removeClass('d-none');

    if (column === 'fourth') {
        $('.main .info_panel').animate({
            scrollTop: 0
        }, 500);
    }
});


$("body").on('click', '.main .side_navigation .menu_items > li.has_child,.main .side_navigation > .bottom.has_child', function(event) {
    if (!$(event.target).parent().parent().hasClass('child_menu')) {
        if ($(this).hasClass("show")) {
            $(this).removeClass("show")
        } else {
            $(this).addClass("show")
        }
    }
});

$("body").on('click', '.main .confirm_box > .content > .btn.cancel', function(e) {

    var column = 'first';

    if ($(this).attr('column') === undefined || $(this).attr('column') === 'first') {
        $('.main .aside > .site_records > .records').removeClass('blur');
        $('.main .aside > .site_records > .records > .list > li').removeClass('selected');
        $('.main .aside > .site_records > .tools').removeClass('d-none');
        $('.main .aside > .site_records > .records > .loader').hide();
    } else {
        column = $(this).attr('column');
    }

    var confirm_box = $('.main .page_column[column="'+column+'"] .confirm_box');

    confirm_box.find('.error').hide();
    confirm_box.addClass('d-none');

});



function typing_indicator(todo = 'log') {

    if (todo === undefined || todo === 'log') {
        if (!$('.main .chatbox').hasClass('logged_user_typing_status')) {

            $('.main .chatbox').addClass('logged_user_typing_status');

            var post_data = {
                update: 'typing_status',
            };

            if ($('.main .chatbox').attr('group_id') !== undefined) {
                post_data['group_id'] = $('.main .chatbox').attr('group_id');
            } else if ($('.main .chatbox').attr('user_id') !== undefined) {
                post_data['user_id'] = $('.main .chatbox').attr('user_id');
            }

            if ($('.main .chatbox > .header > .switch_user > .user_id > input').length > 0) {
                var send_as_user_id = $('.main .chatbox > .header > .switch_user > .user_id > input').val();

                if (send_as_user_id.length > 0 && send_as_user_id !== '0') {
                    post_data['send_as_user_id'] = send_as_user_id;
                }
            }

            if (user_csrf_token !== null) {
                post_data["csrf_token"] = user_csrf_token;
            }

            if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
                post_data["login_session_id"] = user_login_session_id;
                post_data["access_code"] = user_access_code;
                post_data["session_time_stamp"] = user_session_time_stamp;
            }

            user_typing_log_request = $.ajax({
                type: 'POST',
                url: api_request_url,
                data: post_data,
                async: true,
                beforeSend: function() {
                    if (user_typing_log_request !== null) {
                        user_typing_log_request.abort();
                        user_typing_log_request = null;
                    }
                },
                success: function(data) {}
            }).done(function(data) {
                $('.main .chatbox').addClass('logged_user_typing_status');
            }).fail(function(qXHR, textStatus, errorThrown) {
                $('.main .chatbox').removeClass('logged_user_typing_status');
            });

            if ($('.main .chatbox').hasClass('logged_user_typing_status')) {
                if (user_typing_log_timeout !== null) {
                    clearTimeout(user_typing_log_timeout);
                }

                user_typing_log_timeout = setTimeout(function() {
                    $('.main .chatbox').removeClass('logged_user_typing_status');
                    user_typing_log_timeout = null;
                }, 10000);

            }
        }
    } else if (todo === 'reset') {

        if (user_typing_log_timeout !== null) {
            clearTimeout(user_typing_log_timeout);
            user_typing_log_timeout = null;
        }

        whos_typing(null);

        $('.main .chatbox').removeClass('logged_user_typing_status');
    }
}


function whos_typing(user_data) {

    if (user_data !== undefined) {

        if (users_typing_timeout !== null) {
            clearTimeout(users_typing_timeout);
            users_typing_timeout = null;
        }

        if (user_data === null || user_data === '') {
            $('.main .chatbox > .header > .heading > .whos_typing').attr('last_logged_user_id', 0);
            $('.main .chatbox > .header > .heading > .whos_typing > ul').html('');
        } else {
            var users_typing = '';

            $.each(user_data, function(key, user) {
                users_typing += '<li>'+user+' '+language_string('is_typing')+'</li>';
            });

            $('.main .chatbox > .header > .heading > .whos_typing > ul').html(users_typing);
        }
    }

    if ($('.main .chatbox > .header > .heading > .whos_typing > ul').length > 0) {
        if ($('.main .chatbox > .header > .heading > .whos_typing > ul > li.active').length === 0) {
            $('.main .chatbox > .header > .heading > .whos_typing > ul > li:first-child').addClass('active');
        } else {

            var $active = $('.main .chatbox > .header > .heading > .whos_typing > ul > li.active');

            if ($active.next().length > 0) {
                var $next = $active.next();
            } else {
                var $next = $('.main .chatbox > .header > .heading > .whos_typing > ul > li:first-child');
            }

            $next.addClass('active');

            if ($('.main .chatbox > .header > .heading > .whos_typing > ul > li').length > 1) {
                $active.removeClass('active');
            }

        }

        if (users_typing_timeout !== null) {
            clearTimeout(users_typing_timeout);
        }

        if ($('.main .chatbox > .header > .heading > .whos_typing > ul > li').length > 1) {
            users_typing_timeout = setTimeout(function() {
                whos_typing();
                users_typing_timeout = null;
            }, 2000);
        }
    }
}