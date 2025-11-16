<?php
if ($layout_variable['successful']) {
    $layout_variable['body_class'] = 'success_page';
} else {
    $layout_variable['body_class'] = 'error_page';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $layout_variable['title'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no shrink-to-fit=no">
    <link href="<?php echo Registry::load('config')->site_url.'assets/css/common/transaction_status.css' ?>" rel="stylesheet">
    <link rel="preload" href="<?php echo Registry::load('config')->site_url.'assets/fonts/inter/font.css' ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">

</head>

<body translate="no" class="<?php echo $layout_variable['body_class'] ?>">
    <div id='card'>
        <div id='upper-side'>

            <?php if ($layout_variable['successful']) {
                ?>
                <svg width="80px" height="80px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="none" stroke="#FFFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                    <path d="m14.25 8.75c-.5 2.5-2.3849 4.85363-5.03069 5.37991-2.64578.5263-5.33066-.7044-6.65903-3.0523-1.32837-2.34784-1.00043-5.28307.81336-7.27989 1.81379-1.99683 4.87636-2.54771 7.37636-1.54771" />
                    <polyline points="5.75 7.75,8.25 10.25,14.25 3.75" />
                </svg>
                <?php
            } else {
                ?>
                <svg width="80px" height="80px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="9" stroke="#FFF" stroke-width="2" />
                    <path d="M18 18L6 6" stroke="#FFF" stroke-width="2" />
                </svg>
                <?php
            } ?>
            <h3 id='status'>
                <?php echo $layout_variable['status'] ?>
            </h3>
        </div>
        <div id='lower-side'>
            <p id='message'>
                <?php echo $layout_variable['description'] ?>
            </p>
            <a href="<?php echo Registry::load('config')->site_url ?>wallet/" id="contBtn"><?php echo $layout_variable['button'] ?></a>
        </div>
    </div>



</body>

</html>