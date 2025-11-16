/* Random Video Chat Client */
(function(){
  // Use global api_request_url defined in combined_js_chat_page_*.js
  const apiURL = (typeof api_request_url !== 'undefined' && api_request_url) ? api_request_url : ($('base').eq(0).attr('href')||'./') + 'web_request/';

  let rcState = {
    polling: null,
    joined: false,
    inChannel: false,
    lastBundle: null,
    previewing: false,
    previewTrack: null,
    candidates: [],
    cursor: 0,
    listInterval: null
  };

  // Inject modal markup if not present
  function ensureModal(){
    if ($('.random_chat_modal').length) return;
    const html = `
    <div class="random_chat_modal d-none">
      <div class="random_chat_box">
        <div class="rc_header">
          <strong>Random Video Chat</strong>
          <button class="rc_close">×</button>
        </div>
        <div class="rc_body">
          <div class="rc_status">Looking for a match…</div>
          <div class="rc_browse d-none">
            <div class="rc_candidate">Waiting user: <span class="rc_peer">—</span></div>
            <div class="rc_browse_controls">
              <button class="rc_prev">Prev</button>
              <button class="rc_match">Match</button>
              <button class="rc_nextcand">Next</button>
            </div>
          </div>
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
        .rc_browse_controls button{margin-right:6px}
        .rc_close{background:transparent;border:0;color:inherit;font-size:20px;line-height:1;cursor:pointer}
      </style>
    </div>`;
    $('body').append(html);
  }

  async function rcApi(action){
    const fd = new FormData();
    fd.append('add','random_chat');
    fd.append('action', action);
    if (typeof user_csrf_token !== 'undefined' && user_csrf_token) {
      fd.append('csrf_token', user_csrf_token);
    }
    try {
      const r = await fetch(apiURL, { method: 'POST', body: fd });
      if (!r.ok) {
        return { error: true, status: r.status, message: 'Request failed: '+r.status };
      }
      const json = await r.json().catch(()=>({ error:true, message:'Invalid server response'}));
      return json;
    } catch (e) {
      return { error: true, message: e && e.message ? e.message : 'Network error' };
    }
  }

  function openModal(){
    ensureModal();
    $('.random_chat_modal').removeClass('d-none');
    $('.random_chat_modal .rc_status').html('Looking for a match…<br><small style="opacity:.7">Tip: open another browser/incognito & login as a second user to test.</small>');
    $('.random_chat_modal .rc_controls').addClass('d-none');
    $('.random_chat_modal .rc_browse').removeClass('d-none');
    $('.random_chat_modal .rc_browse .rc_peer').text('No waiting users');
    toggleBrowseButtons(false);
  }
  function closeModal(){
    $('.random_chat_modal').addClass('d-none');
  }

  async function ensureVideoInterface(){
    // Show video UI container without tying to a specific chatbox
    $('.main .middle > .video_chat_interface').removeClass('d-none').removeAttr('group_id').removeAttr('user_id');
    $('.main .middle > .video_chat_interface').removeClass('audio_only_chat');
    // Keep existing grid content if preview is active; otherwise reset
    if (!rcState.previewing && !rcState.inChannel) {
      $('.main .middle > .video_chat_interface > .video_chat_container > .video_chat_grid').html('');
      $('.video_chat_container > .video_chat_full_view').html('');
    }
  }

  // Start local camera preview (no channel join)
  async function startLocalPreview(){
    try {
      await ensureVideoInterface();
      if (rcState.previewing) return;

      // Don’t recreate tile if it exists
      let grid = document.getElementById('video-chat-grid');
      if (!grid) return;

      const camOk = await checkWebcamAndPermission();
      if (!camOk || typeof AgoraRTC === 'undefined') {
        console.warn('Camera not available or Agora not loaded');
        return;
      }
      window.preferredCamId = await getPreferredCamera();

      // Create preview track only (no audio yet)
      rcState.previewTrack = await AgoraRTC.createCameraVideoTrack({ cameraId: preferredCamId });

      // Create local tile if missing
      let localEl = document.querySelector('#video-chat-grid .video-window.local-participant-window');
      if (!localEl) {
        localEl = document.createElement('div');
        localEl.className = 'video-window local-participant-window';
        const name = document.createElement('span'); name.className='participant_name'; name.textContent='You';
        const t = document.createElement('span'); t.className='vc_timer'; t.textContent='00:00'; // timer will start on connect
        const wm = document.createElement('span'); wm.className='onmilap_overlay'; wm.textContent='onMilap';
        localEl.appendChild(name); localEl.appendChild(t); localEl.appendChild(wm);
        grid.appendChild(localEl);
      }

      // Play preview
      rcState.previewTrack.play(localEl);
      rcState.previewing = true;
      console.log('RandomChat: local preview started');
    } catch (e) {
      console.error('Failed to start local preview', e);
    }
  }

  async function stopLocalPreview(){
    try {
      if (rcState.previewTrack) {
        rcState.previewTrack.stop();
        rcState.previewTrack.close();
        rcState.previewTrack = null;
      }
      rcState.previewing = false;
      // Do not clear tile if we might immediately join; tile will be reused
    } catch (e) {
      console.warn('Error stopping preview', e);
    }
  }

  async function joinBundle(bundle){
    // Use a minimal join flow leveraging AgoraRTC directly, without disturbing existing join flow state.
    try {
      await ensureVideoInterface();
      if (typeof AgoraRTC === 'undefined') {
        console.error('AgoraRTC not loaded');
        return;
      }

      // Reuse existing global client if available
      // Client is initialized in agora_video_chat.js; do not create a duplicate

      // Set options used by existing handlers
      window.options = window.options || {};
      options.appid = bundle.app_id;
      options.channel = bundle.channel;
      options.token = bundle.token;
      options.uid = null;

      // Publish using existing joinChannel helper if present
      if (typeof joinChannel === 'function') {
        // Prepare formData flags so existing flow doesn't require chatbox attributes
        window.video_chat_formData = new FormData();
        video_chat_formData.append('add','video_chat');
        // Simulate a private one-to-one without specifying user; server will only validate token gen when called by random_chat
        if (typeof user_csrf_token !== 'undefined' && user_csrf_token) {
          video_chat_formData.append('csrf_token', user_csrf_token);
        }
      }

      // Directly join using code from joinChannel, but without requesting a new token
      const camOk = await checkWebcamAndPermission();
      const micOk = await checkMicrophonePermission();
      window.preferredMicId = await getPreferredMicrophone();

      // Reuse preview video track if available; else create
      let audioTrack = null, videoTrack = null;
      if (rcState.previewTrack) {
        videoTrack = rcState.previewTrack;
        rcState.previewTrack = null; // hand it over
      } else if (camOk) {
        window.preferredCamId = await getPreferredCamera();
        videoTrack = await AgoraRTC.createCameraVideoTrack({ cameraId: preferredCamId });
      }
      if (micOk) {
        audioTrack = await AgoraRTC.createMicrophoneAudioTrack({ encoderConfig: 'high_quality_stereo', AEC:true, AGC:true, ANS:true, microphoneId: preferredMicId });
      }

  const creds = await agora_video_client.join(options.appid, options.channel, options.token || null, null);
      window.localTracks = window.localTracks || {videoTrack:null,audioTrack:null};
      localTracks.audioTrack = audioTrack; localTracks.videoTrack = videoTrack;

      const grid = document.getElementById('video-chat-grid');
      let localEl = document.querySelector('#video-chat-grid .video-window.local-participant-window');
      if (!localEl) {
        localEl = document.createElement('div');
        localEl.className = 'video-window local-participant-window';
        const name = document.createElement('span'); name.className='participant_name'; name.textContent='You';
        const t = document.createElement('span'); t.className='vc_timer'; t.textContent='00:00';
        const wm = document.createElement('span'); wm.className='onmilap_overlay'; wm.textContent='onMilap';
        localEl.appendChild(name); localEl.appendChild(t); localEl.appendChild(wm);
        grid.appendChild(localEl);
      }
      if (videoTrack) videoTrack.play(localEl);
      if (videoTrack && audioTrack) { await agora_video_client.publish([videoTrack,audioTrack]); }
      else if (audioTrack) { await agora_video_client.publish([audioTrack]); }
      else if (videoTrack) { await agora_video_client.publish([videoTrack]); }

      // Start timer if available
      if (typeof vc_callStartAt === 'undefined' || !vc_callStartAt) { window.vc_callStartAt = Date.now(); if (typeof vc_startTimerIfNeeded==='function') vc_startTimerIfNeeded(); }

      // Mark video chat active so standard exit flow works
      window.isVideoChatActive = true;

      rcState.inChannel = true;
      // Stop preview state flag if still set
      rcState.previewing = false;
      $('.random_chat_modal .rc_status').text('Connected');
      $('.random_chat_modal .rc_controls').removeClass('d-none');
      // Hide modal so it doesn't overlay the video
      setTimeout(function(){
        $('.random_chat_modal').addClass('d-none');
      }, 200);
    } catch (e) {
      console.error('RandomChat join failed', e);
    }
  }

  async function startQueue(){
    openModal();
    console.log('RandomChat: calling join action...');
    const res = await rcApi('join');
    console.log('RandomChat: join result:', res);
    if (res && res.error) {
      $('.random_chat_modal .rc_status').text('Unable to start random chat: '+(res.message||'Unknown error'));
      return;
    }
    rcState.joined = true;
    // Start local camera preview while waiting
    startLocalPreview();

    // Load initial candidate list for manual browse
    const list = await rcApi('list');
    if (list && list.success) {
      rcState.candidates = Array.isArray(list.candidates) ? list.candidates : [];
      rcState.cursor = 0;
      updateBrowseUI();
      console.log('RandomChat candidates (init):', rcState.candidates);
    } else if (list && list.error) {
      $('.random_chat_modal .rc_status').text('Error loading candidates: '+(list.message||list.error_key||'unknown'));
    }
    if (res && res.matched && res.channel && res.token) {
      rcState.lastBundle = res;
      joinBundle(res);
      return;
    }
    // poll for status every 2s
    if (rcState.polling) clearInterval(rcState.polling);
    rcState.polling = setInterval(async ()=>{
      const s = await rcApi('status');
      if (s && s.error) {
        clearInterval(rcState.polling); rcState.polling=null;
        $('.random_chat_modal .rc_status').text('Error while matching: '+(s.message||'Unknown error'));
        return;
      }
      if (s && s.matched && s.channel && s.token) {
        clearInterval(rcState.polling); rcState.polling = null;
        rcState.lastBundle = s;
        joinBundle(s);
      }
    }, 2000);

    // refresh candidate list every 3s
    if (rcState.listInterval) clearInterval(rcState.listInterval);
    rcState.listInterval = setInterval(refreshCandidates, 3000);
  }

  function toggleBrowseButtons(enabled){
    const dis = !enabled;
    $('.random_chat_modal .rc_browse .rc_prev').prop('disabled', dis);
    $('.random_chat_modal .rc_browse .rc_nextcand').prop('disabled', dis);
    $('.random_chat_modal .rc_browse .rc_match').prop('disabled', dis);
  }

  function updateBrowseUI(){
    $('.random_chat_modal .rc_browse').removeClass('d-none');
    if (!rcState.candidates || rcState.candidates.length === 0) {
      $('.random_chat_modal .rc_browse .rc_peer').text('No waiting users');
      toggleBrowseButtons(false);
      return;
    }
    const idx = ((rcState.cursor % rcState.candidates.length)+rcState.candidates.length)%rcState.candidates.length;
    const uid = rcState.candidates[idx];
    $('.random_chat_modal .rc_browse .rc_peer').text('#'+uid);
    toggleBrowseButtons(true);
  }

  async function refreshCandidates(){
    const list = await rcApi('list');
    if (list && list.success) {
      rcState.candidates = Array.isArray(list.candidates) ? list.candidates : [];
      if (rcState.cursor >= rcState.candidates.length) rcState.cursor = 0;
      updateBrowseUI();
      console.log('RandomChat candidates (refresh):', rcState.candidates);
    } else if (list && list.error) {
      $('.random_chat_modal .rc_status').text('Error loading candidates: '+(list.message||list.error_key||'unknown'));
    }
  }

  async function nextMatch(){
    // Leave current and search again
    if (rcState.inChannel && typeof exit_video_chat === 'function') {
      await exit_video_chat();
    }
    rcState.inChannel = false;
    await rcApi('next');
    startQueue();
  }

  async function endChat(){
    if (rcState.polling) { clearInterval(rcState.polling); rcState.polling=null; }
    if (rcState.listInterval) { clearInterval(rcState.listInterval); rcState.listInterval=null; }
    await rcApi('leave');
    if (rcState.inChannel && typeof exit_video_chat === 'function') {
      await exit_video_chat();
    }
    // Stop local preview if running
    await stopLocalPreview();
    rcState.inChannel = false; rcState.joined=false; rcState.lastBundle=null;
    closeModal();
  }

  // Wire buttons in random chat modal
  $(document).on('click','.random_chat_modal .rc_close', endChat);
  $(document).on('click','.random_chat_modal .rc_end', endChat);
  $(document).on('click','.random_chat_modal .rc_next', nextMatch);
  $(document).on('click','.random_chat_modal .rc_prev', function(){ if (rcState.candidates.length){ rcState.cursor = (rcState.cursor-1+rcState.candidates.length)%rcState.candidates.length; updateBrowseUI(); }});
  $(document).on('click','.random_chat_modal .rc_nextcand', function(){ if (rcState.candidates.length){ rcState.cursor = (rcState.cursor+1)%rcState.candidates.length; updateBrowseUI(); }});
  $(document).on('click','.random_chat_modal .rc_match', async function(){
    if (!rcState.candidates.length) { return; }
    const uid = rcState.candidates[rcState.cursor % rcState.candidates.length];
    const fd = new FormData();
    fd.append('add','random_chat'); fd.append('action','match_with'); fd.append('peer_id', uid);
    if (typeof user_csrf_token !== 'undefined' && user_csrf_token) fd.append('csrf_token', user_csrf_token);
    try {
      const r = await fetch(apiURL, { method:'POST', body: fd });
      const j = await r.json();
      if (j && j.matched && j.channel && j.token) {
        rcState.lastBundle = j;
        joinBundle(j);
      } else {
        // Refresh list if match didn’t succeed (peer might have changed)
        refreshCandidates();
      }
    } catch(e) { refreshCandidates(); }
  });

  // Wire Welcome popup Random Chat button
  $(document).on('click','#btnRandomChat', function(){
    startQueue();
  });

})();
