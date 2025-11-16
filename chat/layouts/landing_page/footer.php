<script src="<?php echo Registry::load('config')->site_url.'assets/js/combined_js_landing_page_'.$js_file_cache_timestamp.'.js'; ?>"></script>
<?php

if (Registry::load('settings')->adblock_detector === 'enable') {
    ?>
    <script src="<?php echo Registry::load('config')->site_url.'assets/js/common/ad_block_detector.js'.$cache_timestamp; ?>"></script>
    <?php
}
?>
<?php
if (Registry::load('settings')->progressive_web_application === 'enable') {
    include 'layouts/landing_page/pwa_install.php';
    ?>
    <script>
        $(window).on('load', function() {
            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register(baseurl + "pwa-sw.js")
                .then((registration) => {
                    console.log('Service Worker registered with scope:', registration.scope);
                })
                .catch((error) => {
                    console.error('Service Worker registration failed:', error);
                });
            } else {
                console.warn('Service workers are not supported in this browser.');
            }
        });
    </script>

    <?php

}
if (isset($_REQUEST["login_session_id"]) && isset($_REQUEST["session_time_stamp"]) && isset($_REQUEST["access_code"])) {
    if (!Registry::load('current_user')->logged_in) {
        Registry::load('current_user')->remove_storage_login_access = true;
    }
}
if (isset(Registry::load('current_user')->remove_storage_login_access) && Registry::load('current_user')->remove_storage_login_access) {
    ?>
    <script>
        WebStorage('remove', 'login_session_id');
        WebStorage('remove', 'access_code');
        WebStorage('remove', 'session_time_stamp');
    </script>
    <?php
}
?>
<?php
include 'assets/headers_footers/landing_page/footer.php';
include 'layouts/landing_page/google_analytics.php';

if (isset(DB::connect()->pdo)) {
    DB::connect()->pdo = null;
}

DB::closeConnection();

?>
</html>