<script src="<?php echo Registry::load('config')->site_url.'assets/js/combined_js_chat_page_'.$js_file_cache_timestamp.'.js'; ?>"></script>

<?php
if (Registry::load('settings')->video_chat === 'agora') {
    ?>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.23.3.js"></script>
    <script src="<?php echo Registry::load('config')->site_url.'assets/js/chat_page/agora_video_chat.js'.$cache_timestamp; ?>"></script>
    <?php
} else if (Registry::load('settings')->video_chat === 'live_kit') {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
    <script src="<?php echo Registry::load('config')->site_url.'assets/js/chat_page/live_kit_video_chat.js'.$cache_timestamp; ?>"></script>
    <?php
}
?>

<?php
if (Registry::load('settings')->adblock_detector === 'enable') {
    ?>
    <script src="<?php echo Registry::load('config')->site_url.'assets/js/common/ad_block_detector.js'.$cache_timestamp; ?>"></script>
    <?php
}
if (isset(Registry::load('settings')->fingerprint_module) && Registry::load('settings')->fingerprint_module !== 'disable') {
    ?>
    <script src="<?php echo Registry::load('config')->site_url.'assets/thirdparty/thumbmarkjs/script.js'.$cache_timestamp; ?>"></script>
    <?php
}
?>

<?php
include 'fns/global/service_workers.php';
include 'layouts/chat_page/web_push_service_scripts.php';
?>

<?php
if (Registry::load('settings')->progressive_web_application === 'enable') {
    include 'layouts/landing_page/pwa_install.php';
    ?>
    <?php

}

if (Registry::load('current_user')->logged_in) {
    if (Registry::load('settings')->chat_page_boxed_layout === 'enable') {

        $bg_image = get_img_url(['from' => 'site_users/backgrounds', 'image' => Registry::load('current_user')->profile_bg_image, 'replace_with_default' => false]);

        if (!empty($bg_image)) {
            ?>
            <style>
                body,body.dark_mode {
                    background: url('<?php echo $bg_image; ?>');
                    background-size: cover;
                    background-position: center;
                }
            </style>
            <?php
        }
    }
}

?>
<?php
include 'layouts/chat_page/google_analytics.php';
include 'assets/headers_footers/chat_page/footer.php';

// Load Random Chat client (non-invasive)
?>
<!-- Random Chat browsing flow -->
<!-- <script src="<?php echo Registry::load('config')->site_url.'assets/js/chat_page/random_simple.js'.$cache_timestamp; ?>"></script> -->
<script src="<?php echo Registry::load('config')->site_url.'assets/js/chat_page/random_browse.js'.$cache_timestamp; ?>"></script>
<?php


if (isset(DB::connect()->pdo)) {
    DB::connect()->pdo = null;
}

DB::closeConnection();
?>
</html>