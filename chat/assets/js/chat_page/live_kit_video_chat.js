var live_kit_video_client = null;
var isVideoChatActive = false;
video_chat_available = true;
var localTracks = [];
var localParticipantContainer;
var audio_only_chat = false;
var preferredMicId;
var preferredCamId;
var currentCamId;
var currentMicId;
var mic_mute_on_load = false;
let psh_tkLastTapTime = 0;
let psh_tkToggleState = false;
var mute_users_during_call = false;
var lk_callStartAt = null;

var videochat_GridContainer = $('#video-chat-grid');
var video_chat_formData = new FormData();

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .toggle_chat_window", function(e) {
    $('.main .middle > .video_chat_interface').toggleClass('show_chat_window');
});

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .leave_video_call", function(e) {
    if (isVideoChatActive) {
        exit_video_chat();
    }
});

$("body").on("click", ".main .chatbox .join_video_call", function(e) {

    if ($('.call_notification').length > 0) {
        if (!$('.call_notification .call_ringtone')[0].paused) {
            $('.call_notification .call_ringtone')[0].pause();
            $('.call_notification .call_ringtone')[0].currentTime = 0;
        }
    }

    video_chat_formData = new FormData();
    video_chat_formData.append('add', 'video_chat');

    $('.main .middle>.video_chat_interface').removeAttr('group_id');
    $('.main .middle>.video_chat_interface').removeAttr('user_id');

    if ($(".main .chatbox").attr('group_id') !== undefined) {
        video_chat_formData.append('group_id', $(".main .chatbox").attr('group_id'));
        current_video_chat_type = 'group';
        current_video_chat_id = $(".main .chatbox").attr('group_id');
        $('.main .middle>.video_chat_interface').attr('group_id', current_video_chat_id);
    } else if ($(".main .chatbox").attr('user_id') !== undefined) {
        video_chat_formData.append('user_id', $(".main .chatbox").attr('user_id'));
        current_video_chat_type = 'private_chat';
        current_video_chat_id = $(".main .chatbox").attr('user_id');
        $('.main .middle>.video_chat_interface').attr('user_id', current_video_chat_id);
    } else {
        console.log('Error : Failed to fetch conversation info');
        return;
    }

    $('.main .video_chat_container>.icons>span.toggle_video_call_mic').show();
    $('.main .video_chat_container>.icons>span.join_video_chat_now').hide();
    mic_mute_on_load = false;

    if (system_variable('push_to_talk_feature') === 'enable') {

        if ($(".main .chatbox").attr('group_id') !== undefined) {
            $('.toggle_push_to_talk').removeClass('active d-none');
            $('.main .video_chat_container>.icons>span.toggle_video_call_mic').hide();
            mic_mute_on_load = true;
        } else {
            $('.toggle_push_to_talk').addClass('d-none');
            $('.main .video_chat_container>.icons>span.toggle_video_call_mic').show();
        }
    }

    if ($(this).attr('audio_only') !== undefined && $(this).attr('audio_only') === 'yes') {
        video_chat_formData.append('audio_only', true);
        $('.main .video_chat_container>.icons>span.toggle_video_call_camera').hide();
        $('.main .video_chat_container>.icons>span.toggle_screen_share').hide();
    } else {
        $('.main .video_chat_container>.icons>span.toggle_video_call_camera').show();
        $('.main .video_chat_container>.icons>span.toggle_screen_share').show();
    }

    if (user_csrf_token !== null) {
        video_chat_formData.append('csrf_token', user_csrf_token);
    }

    if (user_login_session_id !== null && user_access_code !== null && user_session_time_stamp !== null) {
        video_chat_formData.append('login_session_id', user_login_session_id);
        video_chat_formData.append('access_code', user_access_code);
        video_chat_formData.append('session_time_stamp', user_session_time_stamp);
    }

    $('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid').html('');
    $('.video_chat_container > .video_chat_full_view').html('');
    $('.main .middle > .video_chat_interface').removeClass('d-none');
    $('.call_notification').addClass('d-none');

    if ($(this).attr('audio_only') !== undefined && $(this).attr('audio_only') === 'yes') {
        $('.main .middle > .video_chat_interface').addClass('audio_only_chat');
        audio_only_chat = true;
    } else {
        audio_only_chat = false;
        $('.main .middle > .video_chat_interface').removeClass('audio_only_chat');
    }

    if (isVideoChatActive) {
        exit_video_chat();
    } else {

        if ($('.main .chatbox').attr('user_id') !== undefined) {
            current_video_caller_id = $('.main .chatbox').attr('user_id');
        }

        initilazing_video_chat();
        create_video_chat();
    }
});

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .toggle_video_call_camera", function(e) {

    if (!live_kit_video_client) {
        return;
    }

    if (!isVideoChatActive) {
        return;
    }

    localTracks.forEach(function (track) {
        if (track.kind === 'video') {

            if (track.isMuted) {
                track.unmute();
            } else {
                track.mute();
            }
        }
    });


    if ($(this).find('.cam_disabled').hasClass('d-none')) {
        live_kit_video_client.localParticipant.setCameraEnabled(false);
    } else {
        live_kit_video_client.localParticipant.setCameraEnabled(true);
    }

    if ($(this).find('.cam_disabled').hasClass('d-none')) {
        $(this).find('.cam_not_disabled').addClass('d-none');
        $(this).find('.cam_disabled').removeClass('d-none');
    } else {
        $(this).find('.cam_disabled').addClass('d-none');
        $(this).find('.cam_not_disabled').removeClass('d-none');
    }

});

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .toggle_video_call_mic", function(e) {

    if (!live_kit_video_client) {
        return;
    }

    if (!isVideoChatActive) {
        return;
    }

    localTracks.forEach(function (track) {
        if (track.kind === 'audio') {

            if (track.isMuted) {
                track.unmute();
            } else {
                track.mute();
            }
        }
    });

    if ($(this).find('.mic_muted').hasClass('d-none')) {
        live_kit_video_client.localParticipant.setMicrophoneEnabled(false);
    } else {
        live_kit_video_client.localParticipant.setMicrophoneEnabled(true);
    }

    if ($(this).find('.mic_muted').hasClass('d-none')) {
        $(this).find('.mic_not_muted').addClass('d-none');
        $(this).find('.mic_muted').removeClass('d-none');
    } else {
        $(this).find('.mic_muted').addClass('d-none');
        $(this).find('.mic_not_muted').removeClass('d-none');
    }

});

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .toggle_screen_share", function(e) {

    if (!live_kit_video_client) {
        return;
    }

    if (!isVideoChatActive) {
        return;
    }

    if ($(this).find('.share_user_screen').hasClass('d-none')) {
        stop_share_device_screen();
        $(this).find('.stop_screen_share').addClass('d-none');
        $(this).find('.share_user_screen').removeClass('d-none');
        $('.main .video_chat_container>.icons>span.toggle_video_call_camera').show();
    } else {
        share_device_screen();
        $(this).find('.share_user_screen').addClass('d-none');
        $('.main .video_chat_container>.icons>span.toggle_video_call_camera').hide();
        $(this).find('.stop_screen_share ').removeClass('d-none');
    }

});


function exit_video_chat() {

    total_vc_users = live_kit_video_client?.remoteParticipants?.size || 0;

    $('.main .middle > .video_chat_interface').addClass('d-none');

    $('.toggle_push_to_talk').removeClass('active');
    $('.toggle_push_to_talk').addClass('d-none');

    $('.toggle_video_call_mic .mic_muted').addClass('d-none');
    $('.toggle_video_call_mic .mic_not_muted').removeClass('d-none');

    $('.toggle_screen_share .stop_screen_share').addClass('d-none');
    $('.toggle_screen_share .share_user_screen').removeClass('d-none');

    $('.toggle_video_call_camera .cam_disabled').addClass('d-none');
    $('.toggle_video_call_camera .cam_not_disabled').removeClass('d-none');

    leaveChannel();
    stop_update_video_chat_status();
    current_video_chat_type = current_video_chat_id = null;
}

function leaveChannel() {

    if (!isVideoChatActive) {
        return;
    }

    if (live_kit_video_client) {
        live_kit_video_client.disconnect();
        live_kit_video_client = null;
    }
    localTracks.forEach(function (track) {
        track.stop();
    });
    localTracks = [];
    if (localParticipantContainer !== undefined && localParticipantContainer) {
        localParticipantContainer.remove();
    }

    videochat_GridContainer.innerHTML = '';
    $('.video_chat_container > .video_chat_full_view').html('');

    var sessionSeconds = null;
    if (lk_callStartAt) {
        sessionSeconds = Math.round((Date.now() - lk_callStartAt) / 1000);
    }
    lk_callStartAt = null;

    isVideoChatActive = false;

    if (window.SubscriptionUsageTracker && typeof SubscriptionUsageTracker.stopAndReport === 'function') {
        SubscriptionUsageTracker.stopAndReport({
            provider: 'livekit',
            session_seconds: sessionSeconds
        });
    }
}

async function checkWebcamAndPermission() {
    try {
        var devices = await navigator.mediaDevices.enumerateDevices();
        var hasWebcam = devices.some(device => device.kind === 'videoinput');

        if (!hasWebcam) {
            return false;
        }

        try {
            var stream = await navigator.mediaDevices.getUserMedia({
                video: true
            });
            stream.getTracks().forEach(track => track.stop());
            return true;
        } catch (error) {
            return false;
        }
    } catch (error) {
        return false;
    }
}


async function checkMicrophonePermission() {
    try {
        var devices = await navigator.mediaDevices.enumerateDevices();
        var hasMicrophone = devices.some(device => device.kind === 'audioinput');

        if (!hasMicrophone) {
            return false;
        }

        try {
            var stream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });

            stream.getTracks().forEach(track => track.stop());
            return true;
        } catch (error) {
            return false;
        }
    } catch (error) {
        return false;
    }
}



function add_livekit_participant(participant, track) {

    if (track.kind === 'video' || track.kind === 'audio') {

        var participant_identifier = participant.identity;

        if ($('.participant-container[user_identity="'+participant_identifier+'"]').length > 0) {

            if (track.kind === 'video' && $('.participant-container[user_identity="'+participant_identifier+'"] > video').length > 0) {
                $('.participant-container[user_identity="'+participant_identifier+'"]').find('video').remove();
            }
            var mediaElement = track.attach();
            $('.participant-container[user_identity="'+participant_identifier+'"]').append(mediaElement);
        } else {

            var participantContainer = $('<div></div>')
            .addClass('participant-container')
            .attr({
                "user_identity": participant_identifier,
                "user_sid": participant.sid
            });

            var mediaElement = track.attach();
            participantContainer.append(mediaElement);

            videochat_GridContainer.append(participantContainer);

            fetch_user_info(participant.identity).then(function (userData) {
                var participantUsername = userData.username;

                var group_attribute = '';

                if ($(".main .chatbox").attr('group_id') !== undefined) {
                    group_attribute = 'data-group_identifier="'+$(".main .chatbox").attr('group_id')+'"';
                }

                $('.participant-container[user_identity="'+participant_identifier+'"]').append('<span class="mute_symbol"></span><span '+group_attribute+' class="participant_name get_info" user_id="'+participant.identity+'">@' + participantUsername + '</span>');

                if (audio_only_chat) {
                    $('.participant-container[user_identity="'+participant_identifier+'"]').append('<span class="participant_img"><img src="'+userData.image+'"/></span>');
                }
            }).catch(function (error) {
                console.log(error);
                $('.participant-container[user_identity="'+participant_identifier+'"]').append('<span class="participant_name">@'+participant.identity+'</span>');
            });

            var vc_options_svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 1024 1024"> <path fill="currentColor" d="M983.162 385.156l-111.835-13.828c-1.838-4.702-3.768-9.364-5.784-13.986l69.296-88.855c14.454-18.534 12.824-44.928-3.798-61.546l-114.055-113.991c-16.616-16.608-43.004-18.228-61.526-3.786l-88.823 69.268c-4.624-2.016-9.288-3.948-13.988-5.788l-13.83-111.807c-2.886-23.32-22.696-40.83-46.194-40.83h-161.273c-23.496 0-43.312 17.51-46.194 40.832l-13.828 111.807c-4.698 1.838-9.362 3.768-13.976 5.78l-88.829-69.284c-18.522-14.444-44.916-12.828-61.536 3.788l-114.041 114.017c-16.62 16.614-18.246 43.012-3.792 61.542l69.288 88.833c-2.024 4.624-3.956 9.294-5.8 14.004l-111.811 13.828c-23.318 2.888-40.832 22.698-40.832 46.196v161.279c0 23.5 17.516 43.316 40.842 46.194l111.801 13.806c1.838 4.704 3.768 9.372 5.788 13.988l-69.282 88.833c-14.448 18.528-12.822 44.92 3.788 61.534l114.041 114.061c16.614 16.62 43.014 18.242 61.546 3.792l88.833-69.296c4.612 2.014 9.276 3.948 13.974 5.782l13.828 111.841c2.882 23.32 22.696 40.832 46.194 40.832h161.273c23.496 0 43.31-17.51 46.194-40.832l13.83-111.839c4.702-1.84 9.364-3.77 13.986-5.784l88.849 69.3c18.532 14.45 44.926 12.828 61.542-3.796l114.029-114.061c16.614-16.616 18.234-43.008 3.786-61.536l-69.282-88.817c2.024-4.626 3.956-9.294 5.796-14.004l111.801-13.806c23.322-2.88 40.842-22.696 40.842-46.194v-161.279c-0.006-23.496-17.52-43.306-40.838-46.192zM930.905 551.477l-99.727 12.316c-18.302 2.262-33.544 15.112-38.862 32.772-5.626 18.674-13.12 36.774-22.276 53.806-8.734 16.248-7.050 36.126 4.294 50.67l61.796 79.226-55.848 55.864-79.242-61.81c-14.538-11.336-34.404-13.026-50.654-4.3-17.026 9.144-35.13 16.638-53.81 22.268-17.654 5.322-30.5 20.558-32.762 38.854l-12.338 99.765h-78.984l-12.334-99.765c-2.262-18.292-15.102-33.526-32.75-38.85-18.734-5.65-36.834-13.142-53.798-22.264-16.244-8.738-36.122-7.054-50.672 4.294l-79.23 61.804-55.854-55.87 61.794-79.236c11.342-14.546 13.026-34.412 4.294-50.656-9.136-17.002-16.63-35.108-22.274-53.822-5.324-17.65-20.564-30.494-38.86-32.752l-99.721-12.312v-78.984l99.731-12.334c18.292-2.262 33.526-15.102 38.846-32.75 5.648-18.712 13.144-36.812 22.286-53.8 8.748-16.248 7.066-36.134-4.286-50.684l-61.806-79.24 55.848-55.836 79.24 61.804c14.546 11.344 34.418 13.026 50.67 4.292 16.986-9.13 35.082-16.62 53.792-22.264 17.65-5.324 30.494-20.558 32.756-38.854l12.332-99.733h78.988l12.338 99.737c2.266 18.296 15.114 33.534 32.768 38.854 18.656 5.622 36.758 13.114 53.804 22.27 16.244 8.728 36.11 7.044 50.65-4.298l79.232-61.788 55.854 55.824-61.794 79.236c-11.332 14.534-13.026 34.382-4.316 50.622 9.16 17.080 16.656 35.198 22.276 53.846 5.322 17.65 20.558 30.496 38.854 32.762l99.755 12.338v78.978z"> </path> <path fill="currentColor" d="M511.991 310.304c-111.211 0-201.689 90.487-201.689 201.711 0 111.205 90.477 201.677 201.689 201.677 111.217 0 201.703-90.471 201.703-201.677 0-111.223-90.485-201.711-201.703-201.711zM511.991 620.602c-59.882 0-108.599-48.712-108.599-108.587 0-59.894 48.718-108.621 108.599-108.621 59.888 0 108.611 48.726 108.611 108.621 0 59.876-48.724 108.587-108.611 108.587z"> </path> </svg>';

            var lk_player_options = '<li class="mute_remote_user vc_mute_user" user_id="'+participant.identity+'">'+language_string('mute')+'</li>';
            lk_player_options += '<li class="mute_remote_user vc_unmute_user d-none" unmute="true" user_id="'+participant.identity+'">'+language_string('unmute')+'</li>';
            lk_player_options = '<div class="vc_options"><div class="dropdown_button">'+vc_options_svg+'<ul class="dropdown_list">'+lk_player_options+'</ul></div></div>'
            if (mute_users_during_call) {
                $('.participant-container[user_identity="'+participant_identifier+'"]').append(lk_player_options);
            }
        }
    }
}


async function share_device_screen() {

    try {
        var screen_share_tracks = await live_kit_video_client.localParticipant.createScreenTracks({
            audio: true,
        });

        screen_share_tracks.forEach((track) => {
            live_kit_video_client.localParticipant.publishTrack(track);
        });

        if ($('.main .middle > .video_chat_interface > .video_chat_container').hasClass('full_view_container')) {
            if ($('.video_chat_container.full_view_container > .video_chat_full_view > div').length > 0) {
                $('.video_chat_container.full_view_container > .video_chat_full_view > div').appendTo('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid');
            }

            $('.main .middle > .video_chat_interface > .video_chat_container').removeClass('full_view_container');
        }

        localParticipantContainer.find('video').remove();
        var localVideoElement = screen_share_tracks.find(track => track.kind === 'video').attach();
        localParticipantContainer.append(localVideoElement);
    } catch (error) {
        console.error('Error sharing screen share:', error);

        $('.main .video_chat_container .toggle_screen_share .stop_screen_share').addClass('d-none');
        $('.main .video_chat_container .toggle_screen_share .share_user_screen').removeClass('d-none');
        $('.main .video_chat_container>.icons>span.toggle_video_call_camera').show();
    }
}

async function stop_share_device_screen() {

    try {
        live_kit_video_client.localParticipant.setScreenShareEnabled(false);
    } catch (error) {
        console.error('Error stopping screen share:', error);
    }

    if ($('.main .middle > .video_chat_interface > .video_chat_container').hasClass('full_view_container')) {
        if ($('.video_chat_container.full_view_container > .video_chat_full_view > div').length > 0) {
            $('.video_chat_container.full_view_container > .video_chat_full_view > div').appendTo('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid');
        }

        $('.main .middle > .video_chat_interface > .video_chat_container').removeClass('full_view_container');
    }

    var cam_permissionGranted = await checkWebcamAndPermission();

    var mic_permissionGranted = await checkMicrophonePermission();

    var local_video_tracks = await live_kit_video_client.localParticipant.createTracks({
        audio: mic_permissionGranted, video: cam_permissionGranted
    });

    local_video_tracks.forEach((track) => {
        live_kit_video_client.localParticipant.publishTrack(track);
    });

    localParticipantContainer.find('video').remove();

    if (cam_permissionGranted) {
        var localVideoElement = local_video_tracks.find(track => track.kind === 'video').attach();
        localParticipantContainer.append(localVideoElement);
    }
}

async function getPreferredCamera() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const cameras = devices.filter(device => device.kind === "videoinput");

    if (cameras.length === 0) {
        console.log("No cameras found!");
        return null;
    }

    if (currentCamId) {
        const matchedCam = cameras.find(cam => cam.deviceId === currentCamId);
        if (matchedCam) {
            console.log(`Using currentCamId: ${matchedCam.label} (ID: ${matchedCam.deviceId})`);
            return matchedCam.deviceId;
        }
    }

    let preferredCam = cameras.find(cam =>
        cam.label.toLowerCase().includes("front") ||
        cam.label.toLowerCase().includes("default")
    );

    if (!preferredCam) {
        console.log("No 'front' or 'default' camera found. Using fallback camera.");
        preferredCam = cameras[0];
    }

    console.log(`Selected Camera: ${preferredCam.label} (ID: ${preferredCam.deviceId})`);
    return preferredCam.deviceId;
}

$(document).ready(function () {

    $('.toggle_push_to_talk')
    .on('mousedown touchstart', function (e) {
        if (isVideoChatActive && !psh_tkToggleState) {
            $(this).addClass('active');

            if (live_kit_video_client && isVideoChatActive) {
                live_kit_video_client.localParticipant.setMicrophoneEnabled(true);
            }
        }

        // Handle double-tap toggle
        if (e.type === 'touchstart') {
            const psh_tkCurrentTime = new Date().getTime();
            const psh_tkTapLength = psh_tkCurrentTime - psh_tkLastTapTime;

            if (psh_tkTapLength < 300 && psh_tkTapLength > 0) {
                psh_tkToggleState = !psh_tkToggleState;
                $(this).toggleClass('active', psh_tkToggleState);

                if (live_kit_video_client && isVideoChatActive) {
                    live_kit_video_client.localParticipant.setMicrophoneEnabled(psh_tkToggleState);
                }
            }

            psh_tkLastTapTime = psh_tkCurrentTime;
        }
    })
    .on('dblclick', function () {
        if (isVideoChatActive) {
            psh_tkToggleState = !psh_tkToggleState;
            $(this).toggleClass('active', psh_tkToggleState);

            if (live_kit_video_client) {
                live_kit_video_client.localParticipant.setMicrophoneEnabled(psh_tkToggleState);
            }
        }
    })
    .on('mouseup mouseleave touchend touchcancel', function () {
        if (isVideoChatActive && !psh_tkToggleState) {
            $(this).removeClass('active');

            if (live_kit_video_client && isVideoChatActive) {
                live_kit_video_client.localParticipant.setMicrophoneEnabled(false);
            }
        }
    });
});


async function create_video_chat() {
    try {

        var mic_permissionGranted = await checkMicrophonePermission();

        var video_cam_enabled = false;
        var audio_only_chat = false;
        var publish_local_media = true;

        var videoChatData = await fetchVideoChatData();

        if (videoChatData.alert_message) {
            alert(videoChatData.alert_message);
            exit_video_chat();
            return;
        }

        var {
            token, channel: roomName, live_kit_url, subscriber_only, audio_only
        } = videoChatData;

        if (subscriber_only) {
            hideElementsForSubscriberMode();
            publish_local_media = false;
        }

        if (videoChatData.can_join_later !== undefined) {
            $('.main .video_chat_container>.icons>span.join_video_chat_now').show();
        }

        if (videoChatData.mute_users_during_call !== undefined) {
            mute_users_during_call = true;
        } else {
            mute_users_during_call = false;
        }

        live_kit_video_client = new LivekitClient.Room({
            adaptiveStream: true,
            autoSubscribe: true,
        });

        audio_only_chat = Boolean(audio_only);

        var skip_vc_camera_confirm = false;

        if ($('.main .chatbox .join_video_call').hasClass('join_video_chat_now')) {
            $('.main .chatbox .join_video_call').removeClass('join_video_chat_now');
        } else if (system_variable('view_before_joining_group_vc') === 'enable') {
            skip_vc_camera_confirm = true;
        }

        if (audio_only_chat) {
            var cam_permissionGranted = false;
        } else if (!skip_vc_camera_confirm && current_video_chat_type === 'group' && system_variable('confirm_camera_use') === 'enable') {
            var enableCamera = confirm(language_string('enable_camera_prompt'));
            if (enableCamera) {
                var cam_permissionGranted = await checkWebcamAndPermission();
            } else {
                var cam_permissionGranted = false;
                $('.main .video_chat_container>.icons>span.toggle_video_call_camera').hide();
            }
        } else {
            var cam_permissionGranted = await checkWebcamAndPermission();
        }



        video_cam_enabled = audio_only ? false: cam_permissionGranted;

        await live_kit_video_client.prepareConnection(live_kit_url, token);
        await live_kit_video_client.connect(live_kit_url, token);

        console.log(`Connected to Room: ${roomName}`);

        isVideoChatActive = true;
        lk_callStartAt = Date.now();
        if (window.SubscriptionUsageTracker && typeof SubscriptionUsageTracker.start === 'function') {
            SubscriptionUsageTracker.start({
                provider: 'livekit',
                media: audio_only_chat ? 'audio' : 'video'
            });
        }

        update_video_chat_status();

        update_wsocket_data();

        clearCallNotifications();

        live_kit_video_client.remoteParticipants.forEach(participant => {
            participant.trackPublications.forEach(publication => {
                if (publication.track) {
                    add_livekit_participant(participant, publication.track);
                }
            });
        });

        live_kit_video_client.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => {
            add_livekit_participant(participant, track);
        });

        if (publish_local_media) {
            await setupLocalParticipant(
                live_kit_video_client,
                mic_permissionGranted,
                video_cam_enabled,
                audio_only_chat
            );
        }

        if (publish_local_media && live_kit_video_client && isVideoChatActive) {
            if (mic_mute_on_load) {

                if (system_variable('push_to_talk_feature') === 'enable') {
                    const pt_tooltipInstance = bootstrap.Tooltip.getInstance(document.getElementById('push_to_talk_icon'));
                    if (pt_tooltipInstance) {
                        pt_tooltipInstance.show();
                    }
                }

                live_kit_video_client.localParticipant.setMicrophoneEnabled(false);
            } else {
                live_kit_video_client.localParticipant.setMicrophoneEnabled(true);
            }
        }

        live_kit_video_client.on('participantDisconnected', (disconnectedParticipant) => {
            console.log(`Participant ${disconnectedParticipant.identity} has disconnected`);
            removeDisconnectedParticipant(disconnectedParticipant.sid);
        });



        live_kit_video_client.on('trackMuted', (track, publication) => {
            var participant_identifier = publication.participantInfo.identity;
            $('.participant-container[user_identity="'+participant_identifier+'"]').find('span.mute_symbol').addClass('muted');
            console.log(`muted their ${track.kind} track`);
        });

        live_kit_video_client.on('trackUnmuted', (track, publication) => {
            var participant_identifier = publication.participantInfo.identity;
            $('.participant-container[user_identity="'+participant_identifier+'"]').find('span.mute_symbol').removeClass('muted');
            console.log(`unmuted their ${track.kind} track`);
        });

        live_kit_video_client.on('activeSpeakersChanged', (speakers) => {
            $('.video_chat_grid > div').removeClass('talking');

            speakers.forEach((speaker) => {
                const participantId = speaker.sid;
                if (current_logged_user_id && current_logged_user_id == speaker.identity) {
                    $('.video_chat_grid > div.local-participant-window').addClass('talking');
                } else {
                    $(`.video_chat_grid > div[user_sid='${participantId}']`).addClass('talking');
                }
            });
        });

    } catch (error) {
        console.error('Error during video chat initialization:', error);
        exit_video_chat();
    }
}

$("body").on("click", ".main .middle > .video_chat_interface > .video_chat_container .join_video_chat_now", function(e) {

    if (!live_kit_video_client) {
        return;
    }

    if (!isVideoChatActive) {
        return;
    }

    exit_video_chat();

    if ($(".main .chatbox").attr('group_id') !== undefined && $('.main .middle>.video_chat_interface').attr('group_id') !== undefined) {
        if ($(".main .chatbox").attr('group_id') != $('.main .middle>.video_chat_interface').attr('group_id')) {
            alert(language_string('open_chat_to_join_vc'));
            return;
        }
    } else {
        alert(language_string('open_chat_to_join_vc'));
        return;
    }

    setTimeout(function () {
        if (audio_only_chat) {
            $('.main .chatbox .join_video_call').eq(0).addClass('join_video_chat_now').trigger('click');
        } else {
            $('.main .chatbox .join_video_call').eq(1).addClass('join_video_chat_now').trigger('click');
        }
    }, 800);
});

function vc_remote_mute_user(action, group_id) {

    if (!live_kit_video_client) {
        return;
    }

    if (!isVideoChatActive) {
        return;
    }

    if (action == 'unmute_remote_user') {

        if (system_variable('push_to_talk_feature') === 'enable') {
            if ($('.main .middle>.video_chat_interface').attr('group_id') !== undefined) {
                $('.main .video_chat_container>.icons>span.toggle_push_to_talk').removeClass('d-none');
            }
        } else {
            live_kit_video_client.localParticipant.setMicrophoneEnabled(true);
            $('.main .video_chat_container>.icons>span.toggle_video_call_mic').show();
        }
    } else if (action == 'mute_remote_user') {

        live_kit_video_client.localParticipant.setMicrophoneEnabled(false);

        $('.main .video_chat_container>.icons>span.toggle_push_to_talk').removeClass('active');
        $('.main .video_chat_container>.icons>span.toggle_video_call_mic').hide();
        $('.main .video_chat_container>.icons>span.toggle_push_to_talk').addClass('d-none');
    }
}



async function fetchVideoChatData() {
    try {

        if ($('.main .chatbox .join_video_call').hasClass('join_video_chat_now')) {
            video_chat_formData.append('join_video_chat_now', true);
        }

        var response = await fetch(api_request_url, {
            method: 'POST',
            body: video_chat_formData,
        });

        if (!response.ok) {
            console.error(`Error: ${response.status} - ${response.statusText}`);
            throw new Error(`Failed to fetch video chat data. Status: ${response.status}`);
        }

        var data = await response.json();
        return data;

    } catch (error) {
        console.error('Error fetching video chat data:', error);
        throw error;
    }
}

function hideElementsForSubscriberMode() {
    $('.main .video_chat_container>.icons>span.toggle_video_call_camera').hide();
    $('.main .video_chat_container>.icons>span.toggle_screen_share').hide();
    $('.main .video_chat_container>.icons>span.toggle_video_call_mic').hide();

    $('.toggle_push_to_talk').addClass('d-none');
}

function clearCallNotifications() {
    if (call_notification_timeout_id) {
        clearTimeout(call_notification_timeout_id);
    }
    $('.call_notification').attr('current_call_id', 0);
}

async function getPreferredMicrophone() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const microphones = devices.filter(device => device.kind === "audioinput");

    if (microphones.length === 0) {
        console.log("No microphones found!");
        return null;
    }

    if (currentMicId) {
        const matchedMic = microphones.find(mic => mic.deviceId === currentMicId);
        if (matchedMic) {
            console.log(`Using currentMicId: ${matchedMic.label} (ID: ${matchedMic.deviceId})`);
            return matchedMic.deviceId;
        }
    }

    let preferredMic = microphones.find(mic =>
        mic.label.toLowerCase().includes("communications")
    );

    if (!preferredMic) {
        console.log("No 'Communications' microphone found. Using default microphone.");
        preferredMic = microphones[0];
    }

    console.log(`Selected Microphone: ${preferredMic.label} (ID: ${preferredMic.deviceId})`);
    return preferredMic.deviceId;
}


async function get_mic_list() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const audioDevices = devices.filter(device => device.kind === 'audioinput');

        const micSelect = document.querySelector('.video_chat_settings_box .card select.select_microphone');
        micSelect.innerHTML = '';

        if (audioDevices.length === 0) {
            console.warn("No audio input devices found.");
            return;
        }

        let currentDeviceId = null;
        if (localTracks && preferredMicId) {
            currentDeviceId = preferredMicId;
        }

        audioDevices.forEach((device, index) => {
            const option = document.createElement("option");
            option.value = device.deviceId;
            option.textContent = device.label || `Microphone ${index + 1}`;

            if (device.deviceId === currentDeviceId) {
                option.selected = true;
            }

            micSelect.appendChild(option);
        });

    } catch (error) {
        console.error("Error getting microphone list:", error);
    }
}

async function get_cam_list() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');

        const camSelect = document.querySelector('.video_chat_settings_box .card .select_camera');
        if (!camSelect) {
            console.warn("Camera select element not found.");
            return;
        }

        camSelect.innerHTML = '';

        if (videoDevices.length === 0) {
            console.warn("No video input devices found.");
            return;
        }

        let currentDeviceId = null;
        if (localTracks && preferredCamId) {
            currentDeviceId = preferredCamId;
        }

        $('.video_chat_settings_box .card .select_camera_box').show();

        videoDevices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.textContent = device.label || `Camera ${index + 1}`;

            if (device.deviceId === currentDeviceId) {
                option.selected = true;
            }

            camSelect.appendChild(option);
        });

    } catch (error) {
        console.error("Error getting camera list:", error);
    }
}

$("body").on('change', '.video_chat_settings_box .card .select_camera', async function (e) {
    const selectedCamId = $(this).val();

    try {
        const oldVideoTrack = localTracks.find(track => track.kind === 'video');
        const videoPub = live_kit_video_client.localParticipant.getTrackPublications()
            .find(p => p.track && p.track.kind === 'video');

        if (videoPub) {
            await live_kit_video_client.localParticipant.unpublishTrack(videoPub.track);
        }

        if (oldVideoTrack) {
            oldVideoTrack.stop();
            localTracks = localTracks.filter(track => track.kind !== 'video');
        }

        const newTrack = await LivekitClient.createLocalVideoTrack({
            deviceId: { exact: selectedCamId }
        });

        await live_kit_video_client.localParticipant.publishTrack(newTrack);
        localTracks.push(newTrack);

        $('.participant-container.local-participant-window video').remove();
        const newVideoEl = newTrack.attach();
        $('.participant-container.local-participant-window').append(newVideoEl);

        currentCamId = selectedCamId;
        console.log("Camera switched to:", selectedCamId);
    } catch (error) {
        console.error("Failed to switch camera:", error);
    }
});


$("body").on('change', '.video_chat_settings_box .card .select_microphone', async function(e) {
    const selectedMicId = $(this).val();

    try {
        const oldAudioTrack = localTracks.find(track => track.kind === 'audio');
        const audioPub = live_kit_video_client.localParticipant.getTrackPublications()
            .find(p => p.track && p.track.kind === 'audio');

        if (audioPub) {
            await live_kit_video_client.localParticipant.unpublishTrack(audioPub.track);
        }

        if (oldAudioTrack) {
            oldAudioTrack.stop();
            localTracks = localTracks.filter(track => track.kind !== 'audio');
        }

        const newAudioTrack = await LivekitClient.createLocalAudioTrack({
            deviceId: { exact: selectedMicId },
            noiseSuppression: true,
            echoCancellation: true,
            autoGainControl: true
        });

        await live_kit_video_client.localParticipant.publishTrack(newAudioTrack);
        localTracks.push(newAudioTrack);

        currentMicId = selectedMicId;
        console.log("Mic switched to:", selectedMicId);
    } catch (error) {
        console.error("Failed to switch Mic:", error);
    }
});


async function setupLocalParticipant(client, micEnabled, videoEnabled, audioOnly) {
    try {

        if (micEnabled) {
            preferredMicId = await getPreferredMicrophone();
            await get_mic_list();
        }

        if (videoEnabled) {
            preferredCamId = await getPreferredCamera();
            await get_cam_list();
        } else {
            $('.video_chat_settings_box .card .select_camera_box').hide();
        }

        localTracks = await LivekitClient.createLocalTracks({
            audio: micEnabled ? {
                noiseSuppression: true,
                echoCancellation: true,
                autoGainControl: true,
                deviceId: preferredMicId
            }: false,
            video: videoEnabled ? {
                deviceId: preferredCamId
            }: false
        });
        localParticipantContainer = $('<div></div>').addClass('participant-container');

        if (videoEnabled) {
            var localVideoElement = localTracks.find(track => track.kind === 'video').attach();
            localParticipantContainer.append(localVideoElement);
        } else if (audioOnly) {
            localParticipantContainer.append(
                `<span class="participant_img"><img src="${$('.logged_in_user_avatar').attr('src')}" /></span>`
            );
        }

        var groupAttr = $(".main .chatbox").attr('group_id')
        ? `data-group_identifier="${$(".main .chatbox").attr('group_id')}"`: '';

        localParticipantContainer
        .addClass('identity You local-participant-window')
        .append(`<span ${groupAttr} class="participant_name get_info" user_id="${$('.logged_in_user_id').text()}">You</span>`);

        client.localParticipant.setMicrophoneEnabled(micEnabled);
        client.localParticipant.setCameraEnabled(videoEnabled);

        videochat_GridContainer.append(localParticipantContainer);
    } catch (error) {
        console.error('Error setting up local participant:', error);
        throw error;
    }
}

function removeDisconnectedParticipant(participantSid) {
    $('.participant-container').each(function () {
        if ($(this).attr('user_sid') === participantSid) {
            if ($(this).parent().hasClass('video_chat_full_view')) {
                $('.main .middle > .video_chat_interface > .video_chat_container').removeClass('full_view_container');
            }

            $(this).remove();
        }
    });

    var total_vclients = $('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid > div').length;

    if (total_vclients === 0 && $('.main .middle > .video_chat_interface > .video_chat_container').hasClass('full_view_container')) {
        if ($('.video_chat_container.full_view_container > .video_chat_full_view > div').length > 0) {
            $('.video_chat_container.full_view_container > .video_chat_full_view > div').appendTo('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid');
        }

        $('.main .middle > .video_chat_interface > .video_chat_container').removeClass('full_view_container');
    }
}