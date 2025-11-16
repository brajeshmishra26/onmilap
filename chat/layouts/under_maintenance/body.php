<body class="d-flex h-100 text-center under_mainteance_bg">

    <div class="under_mainteance error_page_container d-flex w-100 h-100 p-3 mx-auto flex-column">

        <main class="px-3 mt-auto mb-auto">
            <div class="image">
                <img src="<?php echo Registry::load('config')->site_url ?>assets/files/backgrounds/under_mainteance.png" />
            </div>
            <h1 class="title"><?php echo Registry::load('strings')->under_mainteance_title; ?></h1>
            <div class="description">
                <p>
                    <?php echo Registry::load('strings')->under_mainteance_description; ?>
                </p>
            </div>
            <div class="button">
                <a href="<?php echo Registry::load('config')->site_url ?>"><?php echo Registry::load('strings')->under_mainteance_button; ?></a>
            </div>
        </main>
    </div>
</body>