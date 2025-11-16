<?php
if (!Registry::load('appearance')->display_chat_alone) {
    $middle_column_class = "col-md-7 col-lg-9 middle page_column";
} else {
    $middle_column_class = "display_chat_alone col-md-12 middle page_column";
}
?>

<div class="<?php echo $middle_column_class; ?>" column="second">

    <div class="video_preview d-none">
        <span class="icons">
            <span class="close_player">
                <svg class="close_window_icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.354 4.646L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z" />
                </svg>
            </span>
        </span>
        <div>
        </div>
    </div>

    <div class="iframe_window d-none">
        <span class="icons">
            <span class="close_iframe_window">
                <svg class="close_window_icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.354 4.646L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z" />
                </svg>
            </span>
        </span>
        <div>
        </div>
    </div>

    <div class="group_headers d-none">
        <span class="icons">
            <span class="close_group_header">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.354 4.646L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z" />
                </svg>
            </span>
        </span>
        <div class="header_content"></div>
    </div>

    <div class="video_chat_interface d-none">
        <div class="video_chat_settings_box d-none">
            <div class="d-flex justify-center justify-content-center align-items-center h-100">
                <div class="card shadow-sm rounded-4 mx-auto p-2 position-relative">
                    <button type="button" class="btn-close position-absolute btn-close-white"></button>
                    <div class="card-body">
                        <div class="mb-3 mt-3" class="select_mic_box">
                            <label for="select_microphone" class="form-label"><?php echo Registry::load('strings')->select_microphone ?></label>
                            <select id="select_microphone" class="form-select select_microphone">
                            </select>
                        </div>
                        <div class="select_camera_box">
                            <label for="select_camera" class="form-label"><?php echo Registry::load('strings')->select_camera ?></label>
                            <select id="select_camera" class="form-select select_camera">
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="video_chat_container" style="position:relative;">
            <div class="video_chat_full_view" id="video-chat-full-view"></div>
            <div class="video_chat_grid" id="video-chat-grid"></div>
            <div class="audio_chat_grid d-none" id="audio-chat-grid"></div>
            <!-- Loading overlay for candidate preview join -->
            <div class="preview_loading d-none" id="preview-loading" style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:40;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);">
                <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                    <div style="width:28px;height:28px;border:3px solid #fff;border-right-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
                    <div style="color:#fff;font-size:14px;">Loading preview…</div>
                </div>
            </div>
            <style>
                @keyframes spin { from { transform: rotate(0deg);} to { transform: rotate(360deg);} }
            </style>
            <!-- Random Browse Controls: Prev / Match / Next / Leave -->
            <div class="random_browse_controls d-none" id="random-browse-controls" style="position:absolute; left:0; bottom:60px; width:100%; z-index:30; padding:10px; text-align:center; gap:8px; display:flex; justify-content:center; align-items:center; flex-wrap:wrap;">
                <button type="button" class="btn btn-sm btn-outline-light" data-action="random-prev">Prev</button>
                <button type="button" class="btn btn-sm btn-primary" data-action="random-match">Match</button>
                <button type="button" class="btn btn-sm btn-outline-light" data-action="random-next">Next</button>
                <button type="button" class="btn btn-sm btn-danger" data-action="random-leave">Leave</button>
            </div>
            <style>
                /* Small onMilap badge on each video window */
                .video-window { position: relative; overflow: hidden; }
                .video-window video, .video-window canvas { position: relative; z-index: 1; }
                /* Watermark overlay over each camera window */
                .video-window .onmilap_overlay {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-18deg);
                    color: #fff;
                    opacity: 0.12;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    font-size: clamp(14px, 2.5vw, 24px);
                    line-height: 1;
                    z-index: 2;
                    pointer-events: none;
                    user-select: none;
                    white-space: nowrap;
                }
                /* Call duration timer at top-left of each tile */
                .video-window .vc_timer {
                    position: absolute;
                    top: 6px;
                    left: 6px;
                    z-index: 3;
                    background: rgba(0,0,0,0.55);
                    color: #fff;
                    font-weight: 600;
                    font-size: 11px;
                    line-height: 1;
                    padding: 4px 6px;
                    border-radius: 4px;
                    pointer-events: none;
                    user-select: none;
                }
            </style>
            <div class="icons">
                ...existing code...
            </div>
            <div class="icons">
                <span class="leave_video_call">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-phone-off">
                        <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-3.33-2.67m-2.67-3.34a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path>
                        <line x1="23" y1="1" x2="1" y2="23"></line>
                    </svg>
                </span>

                <?php

                if (Registry::load('settings')->screen_sharing !== 'disable') {
                    ?>
                    <span class="toggle_screen_share">
                        <svg class="stop_screen_share d-none" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 3H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2v-3" />
                            <path d="M8 21h8" />
                            <path d="M12 17v4" />
                            <path d="M22 3l-5 5" />
                            <path d="M17 3l5 5" />
                        </svg>
                        <svg class="share_user_screen" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 3H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2v-3" />
                            <path d="M8 21h8" />
                            <path d="M12 17v4" />
                            <path d="M17 8l5-5" />
                            <path d="M17 3h5v5" />
                        </svg>

                    </span>
                    <?php
                }
                ?>
                <span class="toggle_video_call_mic">
                    <svg class="mic_muted d-none" version="1.1" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 1024 1024">
                        <path fill="currentColor" d="M640 920h-88v-92.352c33.728-3.616 64.608-11.68 93.472-23.648l-2.24 0.832c14.4-6.24 24.288-20.352 24.288-36.768 0-22.080-17.888-39.968-39.968-39.968-5.568 0-10.88 1.152-15.712 3.2l0.256-0.096c-29.824 12.992-64.576 20.544-101.088 20.544-53.44 0-103.072-16.16-144.32-43.872l0.928 0.576c-83.904-63.36-137.6-162.944-137.6-275.072 0-9.056 0.352-18.016 1.024-26.912l-0.064 1.184c0-22.080-17.92-40-40-40s-40 17.92-40 40v0c-0.576 8.16-0.896 17.664-0.896 27.232 0 139.36 67.776 262.88 172.128 339.392l1.184 0.832c42.144 28.16 92.608 47.328 146.976 53.632l1.568 0.16v91.072h-88c-22.080 0-40 17.92-40 40s17.92 40 40 40v0h256c22.080 0 40-17.92 40-40s-17.92-40-40-40v0zM512 712c22.080 0 40-17.92 40-40s-17.92-40-40-40v0c-83.904-0.096-151.904-68.096-152-152v0c0-22.080-17.92-40-40-40s-40 17.92-40 40v0c0.128 128.064 103.936 231.872 232 232v0zM988.32 931.68l-219.136-219.136c63.648-76.672 102.304-176.096 102.304-284.544 0-7.168-0.16-14.272-0.512-21.344l0.032 0.992c0-22.080-17.92-40-40-40s-40 17.92-40 40v0c0.288 5.568 0.48 12.064 0.48 18.624 0 86.944-29.76 166.912-79.648 230.304l0.608-0.8-25.024-25.024c35.104-40.096 56.544-92.928 56.608-150.752v-224.032c0-0.16 0-0.384 0-0.576 0-127.808-103.616-231.424-231.424-231.424-117.6 0-214.688 87.712-229.472 201.248l-0.128 1.152-190.72-190.688c-7.232-7.2-17.184-11.616-28.192-11.616-22.080 0-40 17.92-40 40 0 11.008 4.448 20.96 11.616 28.192l895.968 896.032c7.232 7.232 17.248 11.712 28.288 11.712 22.112 0 40.032-17.92 40.032-40.032 0-11.040-4.48-21.056-11.712-28.288v0zM360 256c0-83.936 68.064-152 152-152s152 68.064 152 152v0 224c0 0.096 0 0.192 0 0.288 0 35.744-12.416 68.608-33.184 94.464l0.224-0.288-271.040-271.040z"></path>
                    </svg>

                    <svg class="mic_not_muted" version="1.1" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 1024 1024">
                        <path fill="currentColor" d="M876.894 405.937c0-22.442-18.214-40.656-40.656-40.656s-40.656 18.214-40.656 40.656v0c0 240.227-147.499 349.672-284.59 349.672s-284.59-109.445-284.59-349.672c0-22.442-18.214-40.656-40.656-40.656s-40.656 18.214-40.656 40.656v0c-0.26 5.789-0.423 12.554-0.423 19.385 0 115.56 42.835 221.102 113.478 301.633l-0.455-0.52c54.544 58.089 128.83 97.086 212.093 106.29l1.529 0.13v93.801h-89.443c-22.442 0-40.656 18.214-40.656 40.656s18.214 40.656 40.656 40.656v0h260.197c22.442 0 40.656-18.214 40.656-40.656s-18.214-40.656-40.656-40.656v0h-89.443v-92.663c167.209-20.848 324.238-166.851 324.238-428.089zM512 715.279c130.163-0.13 235.673-105.64 235.803-235.803v-227.672c0-130.229-105.575-235.803-235.803-235.803s-235.803 105.575-235.803 235.803v0 227.672c0.13 130.163 105.64 235.673 235.803 235.803v0zM357.508 251.803c0-85.312 69.18-154.492 154.492-154.492s154.492 69.18 154.492 154.492v0 227.672c0 85.312-69.18 154.492-154.492 154.492s-154.492-69.18-154.492-154.492v0z"></path>
                    </svg>

                </span>

                <span class="toggle_video_call_camera">
                    <svg class="cam_not_disabled" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.3944 7C18.9574 7 19.2389 7 19.4711 7.05628C20.199 7.2327 20.7673 7.801 20.9437 8.52887C21 8.76107 21 9.04256 21 9.60555L21 16C21 17.8856 21 18.8284 20.4142 19.4142C19.8284 20 18.8856 20 17 20L16 20L8 20L7 20C5.11438 20 4.17157 20 3.58579 19.4142C3 18.8284 3 17.8856 3 16L3 9.60555C3 9.04256 3 8.76107 3.05628 8.52887C3.23271 7.801 3.80101 7.2327 4.52887 7.05628C4.76107 7 5.04257 7 5.60555 7V7C5.92098 7 6.07869 7 6.2261 6.9779C6.68235 6.90952 7.10092 6.6855 7.4109 6.34382C7.51105 6.23342 7.59853 6.1022 7.7735 5.83975L8 5.5C8.39637 4.90544 8.59456 4.60816 8.86549 4.40367C9.03094 4.27879 9.2148 4.18039 9.41048 4.112C9.73092 4 10.0882 4 10.8028 4L13.1972 4C13.9118 4 14.2691 4 14.5895 4.112C14.7852 4.18039 14.9691 4.27879 15.1345 4.40367C15.4054 4.60816 15.6036 4.90544 16 5.5L16.2265 5.83975C16.4015 6.1022 16.4889 6.23342 16.5891 6.34382C16.8991 6.6855 17.3177 6.90952 17.7739 6.9779C17.9213 7 18.079 7 18.3944 7V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path>
                        <path d="M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke="currentColor" stroke-width="2"></path>
                    </svg>

                    <svg class="cam_disabled d-none" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 20L16 20L8 20L7 20C5.11438 20 4.17157 20 3.58579 19.4142C3 18.8284 3 17.8856 3 16L3 9C3 7.89543 3.89543 7 5 7V7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path>
                        <path d="M9.91501 10.8429C9.35081 11.3884 9 12.1532 9 12.9999C9 14.6568 10.3431 15.9999 12 15.9999C13.0435 15.9999 13.9625 15.4672 14.5 14.6588" stroke="currentColor" stroke-width="2"></path>
                        <path d="M3 5L21 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M21 16L21 9.60555C21 9.04256 21 8.76107 20.9437 8.52887C20.7673 7.801 20.199 7.2327 19.4711 7.05628C19.2389 7 18.9574 7 18.3944 7V7C18.079 7 17.9213 7 17.7739 6.9779C17.3177 6.90952 16.8991 6.6855 16.5891 6.34382C16.4889 6.23342 16.4015 6.1022 16.2265 5.83975L16 5.5C15.6036 4.90544 15.4054 4.60816 15.1345 4.40367C14.9691 4.27879 14.7852 4.18039 14.5895 4.112C14.2691 4 13.9118 4 13.1972 4L9.90139 4C9.33825 4 8.81237 4.28144 8.5 4.75V4.75L8.25 5.125" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>

                </span>
                <span class="toggle_chat_window">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 10H16M8 14H16M21.0039 12C21.0039 16.9706 16.9745 21 12.0039 21C9.9675 21 3.00463 21 3.00463 21C3.00463 21 4.56382 17.2561 3.93982 16.0008C3.34076 14.7956 3.00391 13.4372 3.00391 12C3.00391 7.02944 7.03334 3 12.0039 3C16.9745 3 21.0039 7.02944 21.0039 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>

                <span class="toggle_video_chat_settings">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.48 18.5368H21M4.68 12L3 12.044M4.68 12C4.68 13.3255 5.75451 14.4 7.08 14.4C8.40548 14.4 9.48 13.3255 9.48 12C9.48 10.6745 8.40548 9.6 7.08 9.6C5.75451 9.6 4.68 10.6745 4.68 12ZM10.169 12.0441H21M12.801 5.55124L3 5.55124M21 5.55124H18.48M3 18.5368H12.801M17.88 18.6C17.88 19.9255 16.8055 21 15.48 21C14.1545 21 13.08 19.9255 13.08 18.6C13.08 17.2745 14.1545 16.2 15.48 16.2C16.8055 16.2 17.88 17.2745 17.88 18.6ZM17.88 5.4C17.88 6.72548 16.8055 7.8 15.48 7.8C14.1545 7.8 13.08 6.72548 13.08 5.4C13.08 4.07452 14.1545 3 15.48 3C16.8055 3 17.88 4.07452 17.88 5.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    </svg>
                </span>

                <?php
                if (Registry::load('settings')->view_before_joining_group_vc !== 'disable') {
                    ?>
                    <span class="join_video_chat_now auto_width">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 18L19 15M19 15L22 18M19 15V21M15.5 3.29076C16.9659 3.88415 18 5.32131 18 7C18 8.67869 16.9659 10.1159 15.5 10.7092M12 15H8C6.13623 15 5.20435 15 4.46927 15.3045C3.48915 15.7105 2.71046 16.4892 2.30448 17.4693C2 18.2044 2 19.1362 2 21M13.5 7C13.5 9.20914 11.7091 11 9.5 11C7.29086 11 5.5 9.20914 5.5 7C5.5 4.79086 7.29086 3 9.5 3C11.7091 3 13.5 4.79086 13.5 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span><?php echo Registry::load('strings')->join ?></span>
                    </span>
                    <?php
                } ?>

                <?php
                if (Registry::load('settings')->push_to_talk_feature !== 'disable') {
                    ?>
                    <span class="toggle_push_to_talk d-none" id="push_to_talk_icon" title="<?php echo Registry::load('strings')->push_to_talk ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                        <svg class="non_active" viewBox="0 0 470 470" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                            <g>
                                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g id="add" fill="currentColor" transform="translate(85.333333, 18.666667)">
                                        <path d="M206.413257,215.447893 L206.413257,130.666667 C206.413257,99.7915733 181.28835,74.6666667 150.413257,74.6666667 C119.538163,74.6666667 94.4132566,99.7915733 94.4132566,130.666667 L94.4132566,250.096427 C84.0473899,241.401173 71.1085099,236.468907 57.5200299,236.468907 C41.3429632,236.468907 25.8116566,243.323093 14.9470166,255.281493 C-6.06205009,278.304853 -4.69501009,313.828267 17.6045099,335.429973 C17.6330966,335.45856 17.6435499,335.48992 17.6721366,335.518507 L112,426.666667 L320,426.666667 L320,250.666667 L206.413257,215.447893 Z M277.333333,384 C277.333333,384 128.393387,384 128,384 C128,384 53.2621099,313.552 45.9912832,306.927147 C37.7177899,299.39072 37.1202432,286.573013 44.6579499,278.29696 C52.1969366,270.023467 65.0146432,269.4272 73.2894166,276.963627 C78.8286166,282.010453 115.241417,312 115.241417,312 L131.74659,303.46624 L131.74659,130.666667 C131.74659,120.356693 140.104563,112 150.413257,112 C160.72323,112 169.079923,120.356693 169.079923,130.666667 L169.079923,248.541653 L277.333333,282.666667 L277.333333,384 Z M24.1438166,163.708373 C21.3728299,153.1328 19.7465899,142.098987 19.7465899,130.666667 C19.7465899,58.6250667 78.3612032,7.10542736e-15 150.413257,7.10542736e-15 C222.46531,7.10542736e-15 281.079923,58.6250667 281.079923,130.666667 C281.079923,142.098987 279.453683,153.1328 276.682697,163.708373 L236.211443,149.69792 C237.574643,143.552 238.413257,137.218773 238.413257,130.666667 C238.413257,82.1431467 198.936777,42.6666667 150.413257,42.6666667 C101.889737,42.6666667 62.4132566,82.1431467 62.4132566,130.666667 C62.4132566,137.218773 63.2518699,143.552 64.6150699,149.69792 L24.1438166,163.708373 Z" id="Shape"></path>
                                    </g>
                                </g>
                            </g>
                        </svg>
                        <svg class="active" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="0.168">
                            <g id="SVGRepo_iconCarrier">
                                <path d="M18.3569 1.61412C18.57 1.25893 19.0307 1.14376 19.3859 1.35687L19 1.99999C19.3859 1.35687 19.3855 1.35667 19.3859 1.35687L19.3873 1.35771L19.3888 1.35866L19.3925 1.36091L19.4021 1.36683C19.4095 1.3714 19.4187 1.37724 19.4297 1.38436C19.4517 1.39859 19.4808 1.41797 19.516 1.4426C19.5863 1.49182 19.6813 1.56232 19.7926 1.65508C20.0147 1.84016 20.3052 2.11666 20.5945 2.49271C21.1773 3.25044 21.75 4.40734 21.75 5.99999C21.75 7.59265 21.1773 8.74954 20.5945 9.50727C20.3052 9.88332 20.0147 10.1598 19.7926 10.3449C19.6813 10.4377 19.5863 10.5082 19.516 10.5574C19.4808 10.582 19.4517 10.6014 19.4297 10.6156C19.4187 10.6227 19.4095 10.6286 19.4021 10.6332L19.3925 10.6391L19.3888 10.6413L19.3873 10.6423C19.3869 10.6425 19.3859 10.6431 19 9.99999L19.3859 10.6431C19.0307 10.8562 18.57 10.741 18.3569 10.3859C18.1447 10.0322 18.258 9.57386 18.6097 9.35958L18.6152 9.35604C18.6225 9.35133 18.6363 9.34219 18.6558 9.32854C18.6949 9.30119 18.7562 9.25607 18.8324 9.19258C18.9853 9.06516 19.1948 8.86666 19.4055 8.59271C19.8227 8.05044 20.25 7.20734 20.25 5.99999C20.25 4.79264 19.8227 3.94954 19.4055 3.40727C19.1948 3.13332 18.9853 2.93482 18.8324 2.80741C18.7562 2.74391 18.6949 2.69879 18.6558 2.67145C18.6363 2.6578 18.6225 2.64866 18.6152 2.64394L18.6097 2.64041C18.258 2.42613 18.1447 1.96781 18.3569 1.61412Z" fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 1.24999C7.37665 1.24999 5.25 3.37664 5.25 5.99999C5.25 8.62334 7.37665 10.75 10 10.75C12.6234 10.75 14.75 8.62334 14.75 5.99999C14.75 3.37664 12.6234 1.24999 10 1.24999ZM6.75 5.99999C6.75 4.20507 8.20507 2.74999 10 2.74999C11.7949 2.74999 13.25 4.20507 13.25 5.99999C13.25 7.79492 11.7949 9.24999 10 9.24999C8.20507 9.24999 6.75 7.79492 6.75 5.99999Z" fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 12.25C7.96067 12.25 6.07752 12.7207 4.67815 13.5204C3.3 14.3079 2.25 15.5101 2.25 17C2.25 18.4899 3.3 19.6921 4.67815 20.4796C6.07752 21.2792 7.96067 21.75 10 21.75C12.0393 21.75 13.9225 21.2792 15.3219 20.4796C16.7 19.6921 17.75 18.4899 17.75 17C17.75 15.5101 16.7 14.3079 15.3219 13.5204C13.9225 12.7207 12.0393 12.25 10 12.25ZM3.75 17C3.75 16.2807 4.26701 15.4829 5.42236 14.8227C6.55649 14.1747 8.17334 13.75 10 13.75C11.8267 13.75 13.4435 14.1747 14.5776 14.8227C15.733 15.4829 16.25 16.2807 16.25 17C16.25 17.7193 15.733 18.517 14.5776 19.1772C13.4435 19.8253 11.8267 20.25 10 20.25C8.17334 20.25 6.55649 19.8253 5.42236 19.1772C4.26701 18.517 3.75 17.7193 3.75 17Z" fill="currentColor"></path>
                                <path d="M17.3859 3.35687C17.0307 3.14376 16.57 3.25893 16.3569 3.61412L16.6051 4.63761L16.6129 4.64293C16.6246 4.65113 16.6468 4.66735 16.6761 4.69178C16.7353 4.74107 16.8198 4.82082 16.9055 4.93227C17.0727 5.14954 17.25 5.49264 17.25 5.99999C17.25 6.50734 17.0727 6.85044 16.9055 7.06771C16.8198 7.17916 16.7353 7.25891 16.6761 7.3082C16.6468 7.33263 16.6246 7.34885 16.6129 7.35705L16.6051 7.36237C16.257 7.57782 16.1456 8.0337 16.3569 8.38586C16.57 8.74105 17.0307 8.85622 17.3859 8.64311L17 7.99999C17.3859 8.64311 17.3855 8.64331 17.3859 8.64311L17.3872 8.6423L17.3887 8.64144L17.3918 8.63951L17.3993 8.63492L17.4185 8.6227C17.4332 8.61321 17.4515 8.60096 17.4731 8.5859C17.516 8.55582 17.572 8.51423 17.6364 8.46053C17.7647 8.35357 17.9302 8.19582 18.0945 7.98227C18.4273 7.54954 18.75 6.89264 18.75 5.99999C18.75 5.10734 18.4273 4.45044 18.0945 4.01771C17.9302 3.80416 17.7647 3.64641 17.6364 3.53945C17.572 3.48576 17.516 3.44416 17.4731 3.41408C17.4515 3.39902 17.4332 3.38678 17.4185 3.37728L17.3993 3.36507L17.3918 3.36047L17.3887 3.35855L17.3872 3.35768C17.3869 3.35748 17.3859 3.35687 17 3.99999L17.3859 3.35687Z" fill="currentColor"></path>
                            </g>
                        </svg>
                    </span>
                    <?php
                } ?>

            </div>
        </div>
    </div>

    <div class="confirm_box d-none animate__animated animate__flipInX">
        <div class="error">
            <span class="message"><?php echo(Registry::load('strings')->error) ?> : <span></span></span>
        </div>
        <div class="content">
            <span class="text"></span>
            <span class="btn cancel" column="second"><span></span></span>
            <span class="btn submit"><span></span></span>
        </div>
    </div>

    <div class="content">

        <div class="welcome_screen">
            <?php include 'layouts/chat_page/welcome_screen.php'; ?>
        </div>

        <div class="membership_info d-none">
            <?php include 'layouts/chat_page/membership_info.php'; ?>
        </div>

        <div class="statistics d-none">
            <?php include 'layouts/chat_page/statistics.php'; ?>
        </div>

        <div class="custom_page d-none">
            <?php include 'layouts/chat_page/custom_page.php'; ?>
        </div>

        <div class="chatbox d-none boundary">
            <?php include 'layouts/chat_page/chatbox.php'; ?>
        </div>
    </div>


</div>