<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit();
}
require "fns/realtime/websocket.php";