<?php if (Registry::load('settings')->add_to_home_screen_library === 'enable') {
    $ahs_js_url = 'https://cdn.jsdelivr.net/gh/philfung/add-to-homescreen@3.3/dist/';

    $ahs_js_url .= 'add-to-homescreen.min.js';

    ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/philfung/add-to-homescreen@3.3/dist/add-to-homescreen.min.css" />
    <script src="<?php echo $ahs_js_url; ?>"></script>

    <script>

        async function detectIncognitoMode() {
            if ('storage' in navigator && 'estimate' in navigator.storage && performance?.memory) {
                try {
                    const {
                        quota
                    } = await navigator.storage.estimate();
                    const jsHeapLimit = performance.memory.jsHeapSizeLimit;


                    if (quota < jsHeapLimit) {
                        return true;
                    } else {
                        return false;
                    }
                } catch (e) {
                    return false;
                }
            } else {
                return false;
            }
        }
        var lang_iso_code = '<?php echo Registry::load('strings')->iso_code; ?>';

        document.addEventListener('DOMContentLoaded', function () {
            window.AddToHomeScreenInstance = window.AddToHomeScreen({
                appName: '<?php echo Registry::load('settings')->pwa_name ?>',
                appNameDisplay: '<?php echo Registry::load('settings')->pwa_display ?>',
                appIconUrl: 'assets/files/defaults/pwa_icon-192x192.png',
                assetUrl: 'assets/files/defaults/',
                maxModalDisplayCount: -1
            });

            if (!isPWAInstalled()) {
                setTimeout(async function () {
                    const isIncognito = await detectIncognitoMode();

                    if (!isIncognito) {
                        showAddToHomeScreen();
                    }
                }, 5000);
            }
        });

        function showAddToHomeScreen() {
            const maxShowCount = 3;

            if (typeof(Storage) !== "undefined") {
                const lastShown = localStorage.getItem('lastAddToHomeScreenTime');
                const showCount = localStorage.getItem('addToHomeScreenShowCount') || 0;
                const currentTime = new Date().getTime();

                if (!lastShown || ((currentTime - lastShown) / 1000 > 3600)) {
                    localStorage.setItem('addToHomeScreenShowCount', 0);
                    localStorage.setItem('lastAddToHomeScreenTime', currentTime);
                }

                if (showCount < maxShowCount) {
                    window.AddToHomeScreenInstance.show(lang_iso_code);
                    localStorage.setItem('addToHomeScreenShowCount', parseInt(showCount) + 1);
                }

            } else {
                window.AddToHomeScreenInstance.show(lang_iso_code);
            }
        }
    </script>

    <?php
} else {
    ?>

    <script type="module">
        import 'https://cdn.jsdelivr.net/npm/@pwabuilder/pwainstall';
        const el = document.createElement('pwa-update');
        document.body.appendChild(el);
    </script>

    <?php
} ?>