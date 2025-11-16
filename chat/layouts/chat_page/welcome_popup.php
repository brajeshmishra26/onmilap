
<!-- Welcome Popup Modal -->
<style>
  :root {
    --popup-bg-light: #fff;
    --popup-bg-dark: #232327ff;
    --popup-text-light: #333;
    --popup-text-dark: #fff;
    --popup-desc-light: #555;
    --popup-desc-dark: #ccc;
    --popup-btn-bg: #007bff;
    --popup-btn-text: #fff;
  }
  body.dark_mode #welcomePopup .welcome-modal {
    background: var(--popup-bg-dark);
    color: var(--popup-text-dark);
  }
  body.dark_mode #welcomePopup .welcome-modal h2 {
    color: var(--popup-text-dark);
  }
  body.dark_mode #welcomePopup .welcome-modal p {
    color: var(--popup-desc-dark);
  }
  #welcomePopup {
    display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh;
    background:rgba(0,0,0,0.5); justify-content:center; align-items:center;
  }
  #welcomePopup .welcome-modal {
    background: var(--popup-bg-light);
    border-radius:8px; max-width:400px; width:90%; padding:32px 24px; text-align:center;
    box-shadow:0 2px 16px rgba(0,0,0,0.2); position:relative;
    color: var(--popup-text-light);
    transition: background 0.2s, color 0.2s;
  }
  #welcomePopup .welcome-modal h2 {
    margin-bottom:16px; color: var(--popup-text-light);
  }
  #welcomePopup .welcome-modal p {
    margin-bottom:24px; color: var(--popup-desc-light);
  }
  #welcomePopup .welcome-modal .button-row { display:flex; gap:12px; justify-content:center; }
  #welcomePopup .welcome-modal button {
    padding:8px 20px; color:var(--popup-btn-text);
    border:none; border-radius:4px; cursor:pointer;
  }
  #welcomePopup .welcome-modal button.primary { background:var(--popup-btn-bg); }
  #welcomePopup .welcome-modal button.secondary {
    background:transparent; color:var(--popup-btn-bg);
    border:1px solid var(--popup-btn-bg);
  }
</style>
<div id="welcomePopup" class="modal" tabindex="-1" role="dialog">
  <div class="welcome-modal">
    <h2>Welcome to onMilap!</h2>
    <p>We're glad to have you here. Enjoy seamless real-time chat and video features!</p>
    <div class="button-row">
      <button id="btnChat" class="primary" data-action="chat">Chat</button>
      <button id="btnRandomChat" class="secondary" data-action="random-chat">Random Chat</button>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Show popup on each login/page load for logged-in users
    var popup = document.getElementById('welcomePopup');
    popup.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    function closeWelcomePopup() {
      popup.style.display = 'none';
      document.body.style.overflow = '';
    }

    // Basic close handlers; actions can be wired elsewhere via data-action
    var btnChat = document.getElementById('btnChat');
    var btnRandom = document.getElementById('btnRandomChat');
    if (btnChat) btnChat.addEventListener('click', closeWelcomePopup);
    if (btnRandom) btnRandom.addEventListener('click', closeWelcomePopup);
  });
</script>
