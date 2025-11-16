<?php
include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';

$redirect = true;

if (Registry::load('current_user')->logged_in) {
    if (Registry::load('config')->csrf_token) {
        $redirect = false;
    }
}

if ($redirect) {
    redirect();
}


if (isset($_GET['user_tkn'])) {
    $width = 300;
    $height = 60;
    $image = imagecreatetruecolor($width, $height);

    $background_color = imagecolorallocate($image, 22, 27, 34);
    $text_color = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width, $height, $background_color);

    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $text = substr(str_shuffle($characters), 0, 6);

    update_user_csrf_token(['force_request' => true, 'token_code' => $text]);

    $font_size = 35;
    $font_path = 'assets/thirdparty/fonts/vademecum.regular.ttf';
    $bbox = imagettfbbox($font_size, 0, $font_path, $text);
    $x = (int) (($width - ($bbox[2] - $bbox[0])) / 2);
    $y = (int) (($height + ($bbox[1] - $bbox[7])) / 2);
    imagettftext($image, $font_size, 0, $x, $y, $text_color, $font_path, $text);

    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Registry::load('strings')->browser_check_title ?></title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #0d1117;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        .container {
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            color: white;
            width: 90%;
            border: 1px solid #30363d;
            border-radius: 10px;
            background-color: #161b22;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            border: 1px solid #30363d;
            border-radius: 5px;
            background-color: #0d1117;
            color: #c9d1d9;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="submit"] {
            margin-top: 10px;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #3498db;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 20px;
            background-color: #9b59b6;
            color: white;
            display: block;
            width: 100%;
        }
        h2 {
            font-size: 18px;
            margin-bottom: 0px;
            margin-top: 20px;
        }
        input[type="submit"]:hover {}

        .message {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #333;
        }
    </style>
</head>
<body>

    <?php
    $form_url = Registry::load('config')->site_url;
    if (isset($_GET['redirect'])) {
        $_GET['redirect'] = htmlspecialchars($_GET['redirect']);
        $form_url .= $_GET['redirect'];
    }
    ?>

    <div class="container">
        <img src="<?php echo Registry::load('config')->site_url; ?>browser_check/?user_tkn=true" />
        <h2><?php echo Registry::load('strings')->token_check_input_text ?></h2>
        <form id="captchaForm" method="GET" action="<?php echo $form_url ?>">
            <input type="text" name="user_token" placeholder="<?php echo Registry::load('strings')->token_check_input_text ?>" required>
            <input type="submit" value="<?php echo Registry::load('strings')->submit ?>">
        </form>
        <div class="message" id="message"></div>
    </div>

</body>
</html>