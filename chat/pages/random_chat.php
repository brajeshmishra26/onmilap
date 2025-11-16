<?php
require_once 'include/load.php';

// Only allow logged-in users
if (!Registry::load('current_user')->logged_in) {
    header('Location: '.Registry::load('config')->site_url);
    exit;
}

include 'layouts/chat_page/header.php';
include 'layouts/chat_page/topmenu.php';
include 'layouts/chat_page/side_navigation.php';
include 'layouts/chat_page/middle.php';

?>
<div class="random_chat_modal d-none">
  <div class="random_chat_box">
    <div class="rc_header">
      <strong>Random Video Chat</strong>
      <button class="rc_close">×</button>
    </div>
    <div class="rc_body">
      <div class="rc_status">Looking for a match…</div>
      <div class="rc_controls d-none">
        <button class="rc_next">Next</button>
        <button class="rc_end">End</button>
      </div>
    </div>
  </div>
  <style>
    .random_chat_modal{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center}
    .random_chat_box{background:#fff;color:#333;width:92%;max-width:520px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.25)}
    body.dark_mode .random_chat_box{background:#232327;color:#fff}
    .rc_header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid rgba(0,0,0,.08)}
    .rc_body{padding:16px}
    .rc_controls button{margin-right:8px}
    .rc_close{background:transparent;border:0;color:inherit;font-size:20px;line-height:1;cursor:pointer}
  </style>
</div>

<?php include 'layouts/chat_page/footer.php'; ?>
<script src="assets/js/chat_page/random_chat.js?v=1"></script>
