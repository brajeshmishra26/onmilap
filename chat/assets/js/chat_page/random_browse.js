/* Random Browse UI: enter waiting, browse preview of waiting users (prev/next), and match */
(function(){
  const apiURL = (typeof api_request_url !== 'undefined' && api_request_url) ? api_request_url : ($('base').eq(0).attr('href')||'./') + 'web_request/';
  let previewClient = null, previewPublishClient = null, previewPlayingUserId = null, pollId = null, candidates = [], idx = 0, previewJoining = false, refreshId = null, matchedPending = false;
  let rbEarlyPreviewStarted = false;

  // Robust local preview mounting helper (handles some mobile browsers failing initial play)
  function ensureLocalPreviewVideo(container, attempt){
    attempt = attempt || 1;
    if (!container) return;
    try {
      const vid = container.querySelector('video');
      if (vid){
        vid.setAttribute('playsinline','');
        vid.muted = true; // ensure autoplay safe
        vid.style.width = '100%';
        vid.style.height = '100%';
        vid.style.objectFit = 'cover';
        vid.style.background = '#000';
        container.style.position = 'relative';
        if (!container.classList.contains('rb-preview-mounted')){
          container.classList.add('rb-preview-mounted');
        }
        if (vid.videoWidth && vid.videoHeight){
          // We have dimensions -> success
          return;
        }
      }
      // Retry play if track exists but element not yet rendering
      if (window.localTracks && window.localTracks.videoTrack){
        if (!vid){
          // If for some reason no video tag yet, call play again
          try { window.localTracks.videoTrack.play(container); } catch(_){}
        }
      }
    } catch(e) { /* ignore */ }
    if (attempt < 5){ // up to 5 attempts over ~1.5s
      setTimeout(()=>ensureLocalPreviewVideo(container, attempt+1), 350);
    } else {
      // Final attempt: force re-create track if still nothing visible (width/height 0)
      try {
        const vid = container.querySelector('video');
        if ( (!vid || !(vid.videoWidth && vid.videoHeight)) && window.AgoraRTC ){
          console.warn('Recreating camera track after failed preview display');
          window.localTracks.videoTrack && window.localTracks.videoTrack.close && window.localTracks.videoTrack.close();
          window.localTracks.videoTrack = null;
          AgoraRTC.createCameraVideoTrack(window.preferredCamId?{cameraId: window.preferredCamId}:{ }).then(t=>{ window.localTracks.videoTrack = t; t.play(container); ensureLocalPreviewVideo(container, attempt+1); });
        }
      } catch(_){ }
    }
  }

  async function api(action, extra){
    const fd = new FormData(); fd.append('add','random_browse'); fd.append('action',action);
    if (extra) { Object.entries(extra).forEach(([k,v])=>fd.append(k,v)); }
    if (typeof user_csrf_token !== 'undefined' && user_csrf_token) fd.append('csrf_token', user_csrf_token);
    const r = await fetch(apiURL, { method:'POST', body: fd });
    try { return await r.json(); } catch { return {error:true}; }
  }

  async function ensureUI(){
    $('.main .middle > .video_chat_interface').removeClass('d-none').removeAttr('group_id').removeAttr('user_id');
    $('.main .middle > .video_chat_interface').removeClass('audio_only_chat');
    
    // Add mobile back button if not already present
    if (!$('.video_chat_container .rb_go_back_icon').length) {
      $('<span class="rb_go_back_icon" style="display:none;position:absolute;top:10px;left:10px;z-index:50;background:rgba(0,0,0,0.5);border-radius:50%;padding:8px;cursor:pointer;"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16"><path fill="white" fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg></span>')
        .prependTo('.video_chat_container');
      
      // Show back arrow on mobile only
      if ($(window).width() < 780) {
        $('.rb_go_back_icon').show();
      }
      
      // Handle back button click - navigate to the first column (aside panel)
      $('.rb_go_back_icon').on('click', function() {
        if (typeof open_column === 'function') {
          open_column('first');
        } else {
          // Fallback if open_column not available
          $('.main .page_column').removeClass('visible');
          $('.main .page_column[column="first"]').addClass('visible').removeClass('d-none previous');
          $('.main .page_column[column="second"]').addClass('previous').removeClass('visible');
        }
      });
      
      // Handle window resize
      $(window).on('resize', function() {
        if ($(window).width() < 780) {
          $('.rb_go_back_icon').show();
        } else {
          $('.rb_go_back_icon').hide();
        }
      });
    }
    
    if (!document.querySelector('#video-chat-grid .video-window.local-participant-window')){
      const el = document.createElement('div'); el.className='video-window local-participant-window'; el.innerHTML='<span class="participant_name">You</span><span class="vc_timer">00:00</span><span class="onmilap_overlay">onMilap</span>'; document.getElementById('video-chat-grid').appendChild(el);
    }
    $('#random-browse-controls').removeClass('d-none');
    // Ensure loading overlay is hidden by default
    $('#preview-loading').addClass('d-none');
  }

  async function publishPreview(preview){
    try {
      if (typeof AgoraRTC==='undefined') return;
      // create local video track (reuse if exists)
      window.preferredCamId = typeof getPreferredCamera==='function' ? await getPreferredCamera() : undefined;
      if (!window.localTracks || !window.localTracks.videoTrack){
        const vt = await AgoraRTC.createCameraVideoTrack(window.preferredCamId?{cameraId: window.preferredCamId}:{ });
        window.localTracks = window.localTracks||{}; window.localTracks.videoTrack = vt;
      }
      // use a dedicated client for preview publishing to avoid interfering with main call client
      if (!previewPublishClient){ previewPublishClient = AgoraRTC.createClient({mode:'rtc', codec:'vp8'}); }
      await ensureUI();
      const gridLocal = document.querySelector('#video-chat-grid .video-window.local-participant-window');
      window.localTracks.videoTrack.play(gridLocal);
      // --- Mobile visibility fallback: force sizing & retry play if needed ---
      try {
        if (gridLocal){
          // Ensure the element is visible and has height (some mobile browsers collapse empty div before <video> attaches)
          gridLocal.style.minHeight = '230px';
          gridLocal.style.display = 'block';
          gridLocal.style.visibility = 'visible';
          // If on a small screen, enforce single column so local preview isn't squished next to placeholder
          if (window.matchMedia && window.matchMedia('(max-width: 780px)').matches){
            const grid = document.getElementById('video-chat-grid');
            if (grid){
              grid.style.display = 'grid';
              // Only force single column when not in active call to avoid breaking multi-user layout later
              if (!window.isVideoChatActive){ grid.style.gridTemplateColumns = '1fr'; }
            }
          }
          // Some devices (iOS Safari) occasionally need a delayed second play() after layout settles
          setTimeout(()=>{
            try {
              if (gridLocal && !gridLocal.querySelector('video')){
                window.localTracks && window.localTracks.videoTrack && window.localTracks.videoTrack.play(gridLocal);
              }
            } catch(_){}
          }, 600);
        }
      } catch(_){}
      // Attach state change logging & stats insight (help diagnose silent failures)
      try {
        if (window.localTracks && window.localTracks.videoTrack && !window.localTracks.videoTrack.__rb_logged){
          window.localTracks.videoTrack.on('player-state-changed', (state)=>{ try { console.log('[RB] local player state', state); } catch(_){} });
          window.localTracks.videoTrack.__rb_logged = true;
        }
      } catch(_){ }
      // Kick off robust ensure routine
      ensureLocalPreviewVideo(gridLocal, 1);
  await previewPublishClient.join(preview.app_id, preview.channel, preview.token||null, preview.uid || null);
      await previewPublishClient.publish([window.localTracks.videoTrack]);
    } catch (e) { console.error('publishPreview error', e); }
  }

  // Early preview to keep within user gesture context (avoid autoplay blocking on some Android browsers)
  function startEarlyLocalPreview(){
    if (rbEarlyPreviewStarted) return; rbEarlyPreviewStarted = true;
    try {
      ensureUI(); // not awaited
      // Kick off track creation immediately; don't await network yet
      if (typeof AgoraRTC==='undefined') return;
      const camPromise = (async()=>{
        try {
          if (!window.localTracks || !window.localTracks.videoTrack){
            window.preferredCamId = typeof getPreferredCamera==='function' ? await getPreferredCamera() : undefined;
            const vt = await AgoraRTC.createCameraVideoTrack(window.preferredCamId?{cameraId: window.preferredCamId}:{ });
            window.localTracks = window.localTracks||{}; window.localTracks.videoTrack = vt;
          }
          const gridLocal = document.querySelector('#video-chat-grid .video-window.local-participant-window');
          if (gridLocal){
            window.localTracks.videoTrack.play(gridLocal);
            gridLocal.style.minHeight='230px';
            gridLocal.style.background='#000';
            // Frame visibility forcing loop
            let attempts=0;
            const chk=()=>{
              attempts++;
              const vid = gridLocal.querySelector('video');
              if (vid && vid.videoWidth>0 && vid.videoHeight>0){ return; }
              if (attempts===2){ try { window.localTracks.videoTrack.setEnabled(false).then(()=>window.localTracks.videoTrack.setEnabled(true)); } catch(_){} }
              if (attempts<8) setTimeout(chk, 250);
            };
            setTimeout(chk, 250);
          }
        } catch(err){ console.warn('early preview error', err); }
      })();
    } catch(e){ /* ignore */ }
  }

  async function playCandidatePreview(){
    if (!candidates[idx] || previewJoining) return;
    const cand = candidates[idx];
    previewJoining = true;
    try {
      $('#preview-loading').removeClass('d-none');
      const tok = await api('watch_token', {user_id: cand.owner_user_id});
      if (!tok || !tok.success || !tok.preview || !tok.preview.app_id || !tok.preview.channel || !tok.preview.token) { return; }
      if (!previewClient && typeof AgoraRTC!=='undefined'){
        // Use LIVE mode with audience role to align with viewer token
        previewClient = AgoraRTC.createClient({mode:'live', codec:'vp8'});
        previewClient.on('user-published', async (user, mediaType)=>{
          await previewClient.subscribe(user, mediaType);
          if (mediaType==='video'){
            let el = document.querySelector('#video-chat-grid .video-window.candidate-preview-window');
            if (!el){ el = document.createElement('div'); el.className='video-window candidate-preview-window'; el.innerHTML='<span class="participant_name">Candidate</span>'; document.getElementById('video-chat-grid').appendChild(el); }
            user.videoTrack.play(el);
            $('#preview-loading').addClass('d-none');
          }
        });
      }
      if (previewClient){ try { await previewClient.leave(); } catch(e) { /* ignore */ } }
      try {
        // ensure audience role before join for LIVE mode
        try { await previewClient.setClientRole('audience'); } catch(_) {}
        await previewClient.join(tok.preview.app_id, tok.preview.channel, tok.preview.token||null, tok.preview.uid || null);
      } catch (e) {
        // Retry once on invalid/expired token
        const msg = (e && (''+e).toLowerCase()) || '';
        if (msg.includes('invalid token') || msg.includes('authorized failed')){
          const tok2 = await api('watch_token', {user_id: cand.owner_user_id});
          if (tok2 && tok2.success && tok2.preview){
            try { await previewClient.setClientRole('audience'); } catch(_) {}
            await previewClient.join(tok2.preview.app_id, tok2.preview.channel, tok2.preview.token||null, tok2.preview.uid || null);
          } else { throw e; }
        } else { throw e; }
      }
      previewPlayingUserId = cand.owner_user_id;
    } catch(e){ console.error('playCandidatePreview error', e); }
    finally { previewJoining = false; $('#preview-loading').addClass('d-none'); }
  }

  async function start(){
    // Start early preview BEFORE awaiting network (retain user gesture context)
    startEarlyLocalPreview();
    await ensureUI();
    document.body.classList.add('rb-active');
    
    // Handle mobile navigation - ensure proper column switching
    if ($(window).width() < 780) {
      // Use the existing open_column function to switch to middle panel view
      if (typeof open_column === 'function') {
        open_column('second');
      } else {
        // Fallback if open_column not available
        $('.main .page_column').removeClass('visible');
        $('.main .page_column[column="second"]').addClass('visible').removeClass('d-none');
        $('.main .page_column[column="first"]').addClass('previous').removeClass('visible');
      }
    }
    
    // Aggressive layout forcing for Samsung Android browsers
    try {
      const forceMobileReflow = ()=>{
        try { 
          // Multi-method reflow forcing
          window.dispatchEvent(new Event('resize')); 
          window.dispatchEvent(new Event('orientationchange'));
        } catch(_){}
        if (typeof window.adjustVideoGrid==='function') window.adjustVideoGrid();
        
        const videoInterface = document.querySelector('.main .middle > .video_chat_interface');
        const gridLocal = document.querySelector('#video-chat-grid .video-window.local-participant-window');
        const grid = document.getElementById('video-chat-grid');
        
        if (videoInterface){
          // Force display recalculation by temporarily hiding/showing
          videoInterface.style.display = 'none';
          videoInterface.offsetHeight; // force reflow
          videoInterface.style.display = 'block';
        }
        
        if (grid){
          // Force grid recalculation
          const origDisplay = grid.style.display;
          grid.style.display = 'none';
          grid.offsetHeight; // force reflow
          grid.style.display = 'grid';
          grid.style.gridTemplateColumns = '1fr';
        }
        
        if (gridLocal){
          gridLocal.style.minHeight='230px';
          gridLocal.style.height='230px';
          gridLocal.style.width='100%';
          gridLocal.style.transform='translateZ(0)'; // trigger GPU layer
          gridLocal.style.backfaceVisibility='hidden'; // another GPU hint
          // Force paint by toggling opacity
          gridLocal.style.opacity = '0.99';
          setTimeout(()=>{ if (gridLocal) gridLocal.style.opacity = '1'; }, 50);
        }
      };
      
      // Immediate + staggered reflows
      forceMobileReflow();
      setTimeout(forceMobileReflow, 100);
      setTimeout(forceMobileReflow, 350);
      setTimeout(forceMobileReflow, 800);
      setTimeout(forceMobileReflow, 1500);
      
      // Try viewport meta manipulation (some Samsung browsers respond to this)
      try {
        const viewport = document.querySelector('meta[name=viewport]');
        if (viewport){
          const origContent = viewport.content;
          viewport.content = 'width=device-width, initial-scale=1.01'; // tiny scale change
          setTimeout(()=>{ viewport.content = origContent; }, 200);
        }
      } catch(_){}
      
    } catch(_){}
    const enter = await api('enter');
    if (enter && enter.success && enter.preview){
      // publish (join + publish) now that preview already showing
      await publishPreview(enter.preview);
    } else {
      // If enter failed, allow retry but keep local preview visible
      rbEarlyPreviewStarted = false; // allow retry
    }
    // fetch waiting list and start with first candidate
    const res = await api('list');
    candidates = (res && res.success && Array.isArray(res.candidates)) ? res.candidates : [];
    idx = 0; if (candidates.length>0){ await playCandidatePreview(); }
    // poll match status for me
    clearInterval(pollId); pollId = setInterval(async ()=>{
      if (matchedPending) return; // already transitioning
      const s = await api('status');
      if (s && s.matched && s.channel && s.token){
        matchedPending = true;
        // Hide controls immediately so remote side doesn't keep navigating
        $('#random-browse-controls').addClass('d-none');
        // Stop refresh & further polling before joining
        clearInterval(refreshId); refreshId=null;
        clearInterval(pollId); pollId=null;
        await joinCall(s);
      }
    }, 2000);
    // auto-refresh candidate list every 5 seconds
    clearInterval(refreshId); refreshId = setInterval(refreshCandidates, 5000);
  }

  function isStaleCandidate(c){
    if (!c || !c.updated_on) return false; // if no timestamp, keep (server will purge)
    const updated = Date.parse(c.updated_on.replace(/-/g,'/')); // Safari-safe
    if (isNaN(updated)) return false;
    return (Date.now() - updated) > 130*1000; // >130s idle
  }

  async function refreshCandidates(){
    try {
      if (matchedPending || window.isVideoChatActive) { return; }
      const res = await api('list');
  let newList = (res && res.success && Array.isArray(res.candidates)) ? res.candidates : [];
  // client-side stale filtering as extra safety
  newList = newList.filter(c=>!isStaleCandidate(c));
      const currentId = previewPlayingUserId;
      // Update the global list
      candidates = newList;
      if (candidates.length === 0){
        // No candidates: stop preview client and show a minimal hint
        if (previewClient){ try { await previewClient.leave(); } catch {} previewClient=null; }
        previewPlayingUserId = null;
        let el = document.querySelector('#video-chat-grid .video-window.candidate-preview-window');
        if (!el){ el = document.createElement('div'); el.className='video-window candidate-preview-window'; document.getElementById('video-chat-grid').appendChild(el); }
        el.innerHTML = '<span class="participant_name">No one waiting yet</span>';
        return;
      }
      // If we have a current preview and it still exists, keep it
      const pos = currentId ? candidates.findIndex(c => c.owner_user_id === currentId) : -1;
      if (pos >= 0){ idx = pos; return; }
      // Otherwise, move to the first candidate and play preview
      idx = 0; await playCandidatePreview();
    } catch (e) { /* ignore refresh errors */ }
  }

  async function joinCall(bundle){
    try {
      // Stop auto-refresh during the full call
      clearInterval(refreshId); refreshId=null;
      await ensureUI();
      document.body.classList.add('rb-active');
      
      // Handle mobile navigation for video call
      if ($(window).width() < 780) {
        // Use the existing open_column function to ensure we're in the middle panel for video
        if (typeof open_column === 'function') {
          open_column('second');
        } else {
          // Fallback if open_column not available
          $('.main .page_column').removeClass('visible');
          $('.main .page_column[column="second"]').addClass('visible').removeClass('d-none');
          $('.main .page_column[column="first"]').addClass('previous').removeClass('visible');
        }
      }
      
      // Reflow again at call join to ensure layout adjusts when remote stream(s) will appear
      try { setTimeout(()=>window.dispatchEvent(new Event('resize')), 300); } catch(_){}
      // Clean up preview sessions to avoid client conflicts
      if (previewClient){ try{ await previewClient.leave(); }catch{} previewClient=null; }
      if (previewPublishClient){ try{ await previewPublishClient.leave(); }catch{} previewPublishClient=null; }
      // Remove any candidate preview/placeholder window (e.g., 'No one waiting yet')
      document.querySelectorAll('#video-chat-grid .candidate-preview-window').forEach(el=>{ try { el.remove(); } catch(_){} });
      if (!window.agora_video_client && typeof AgoraRTC!=='undefined'){
        window.agora_video_client = AgoraRTC.createClient({mode:'rtc', codec:'vp8'});
        agora_video_client.on('user-published', handleUserPublished);
        agora_video_client.on('user-unpublished', handleUserUnpublished);
        agora_video_client.on('user-left', handleUserLeft);
      }
      window.options = window.options || {}; options.appid=bundle.app_id; options.channel=bundle.channel; options.token=bundle.token; options.uid=null;
      const micOk = typeof checkMicrophonePermission==='function' ? await checkMicrophonePermission() : true;
      window.preferredMicId = typeof getPreferredMicrophone==='function' ? await getPreferredMicrophone() : undefined;
      let audioTrack = null; if (micOk && typeof AgoraRTC!=='undefined'){ audioTrack = await AgoraRTC.createMicrophoneAudioTrack(window.preferredMicId?{microphoneId: window.preferredMicId}:{encoderConfig:'high_quality_stereo',AEC:true,AGC:true,ANS:true}); }
      // Track created audio track so global leaveChannel() can stop/close it
      window.localTracks = window.localTracks || {};
      if (audioTrack && !window.localTracks.audioTrack){ window.localTracks.audioTrack = audioTrack; }
  await agora_video_client.join(options.appid, options.channel, options.token||null, (bundle && bundle.uid) ? bundle.uid : null);
      if (window.localTracks && window.localTracks.videoTrack){ await agora_video_client.publish([window.localTracks.videoTrack].concat(audioTrack?[audioTrack]:[])); }
      else if (audioTrack){ await agora_video_client.publish([audioTrack]); }
      window.isVideoChatActive = true; if (!window.vc_callStartAt){ window.vc_callStartAt=Date.now(); if (typeof vc_startTimerIfNeeded==='function') vc_startTimerIfNeeded(); }
      $('#random-browse-controls').addClass('d-none');
      if (typeof window.adjustVideoGrid==='function'){ window.adjustVideoGrid(); }
    } catch(e){ console.error('joinCall error', e); }
  }

  async function next(){ if (candidates.length===0) return; idx = (idx+1)%candidates.length; await playCandidatePreview(); }
  async function prev(){ if (candidates.length===0) return; idx = (idx-1+candidates.length)%candidates.length; await playCandidatePreview(); }
  async function match(){
    if (!candidates[idx] || matchedPending) return;
    matchedPending = true;
    // Hide controls right away for local user
    $('#random-browse-controls').addClass('d-none');
    clearInterval(refreshId); refreshId=null; // stop browsing
    const m = await api('match',{user_id: candidates[idx].owner_user_id});
    if (m && m.success && m.matched){
      await joinCall(m);
    } else {
      // If match failed, allow retry
      matchedPending = false;
      $('#random-browse-controls').removeClass('d-none');
    }
  }

  async function leave(){
    try{
      clearInterval(pollId); pollId=null;
      clearInterval(refreshId); refreshId=null;
      document.body.classList.remove('rb-active');
      // If in an active call, disconnect the Agora session
      if (window.isVideoChatActive){
        if (typeof window.leaveChannel === 'function'){
          await window.leaveChannel();
        } else if (typeof window.exit_video_chat === 'function'){
          await window.exit_video_chat();
        }
      }
      await api('leave');
      // Stop candidate preview client
      if (previewClient){ try{ await previewClient.leave(); }catch{} previewClient=null; previewPlayingUserId=null; }
      // Unpublish local preview
      if (previewPublishClient){ try{ await previewPublishClient.leave(); }catch{} previewPublishClient=null; }
      // keep local video track for quick restart, or stop it?
      // Hide controls
      $('#random-browse-controls').addClass('d-none');
      if (typeof window.adjustVideoGrid==='function'){ window.adjustVideoGrid(); }
      
      // Handle mobile navigation - reset to first column when leaving random chat
      if ($(window).width() < 780) {
        // Use the existing open_column function to switch back to aside panel view
        if (typeof open_column === 'function') {
          open_column('first');
        } else {
          // Fallback if open_column not available
          $('.main .page_column').removeClass('visible previous');
          $('.main .page_column[column="first"]').addClass('visible').removeClass('d-none');
        }
      }
    }catch(e){ console.error('leave error', e); }
  }

  // Bind Random Chat button and UI controls
  $(document)
    .off('click','#btnRandomChat')
    .on('click','#btnRandomChat', function(e){ e.preventDefault(); start(); })
    .on('click','[data-action="random-chat"]', function(e){ e.preventDefault(); start(); })
    .on('click','[data-action="random-prev"]', function(e){ e.preventDefault(); prev(); })
    .on('click','[data-action="random-next"]', function(e){ e.preventDefault(); next(); })
    .on('click','[data-action="random-match"]', function(e){ e.preventDefault(); match(); })
    .on('click','[data-action="random-leave"]', function(e){ e.preventDefault(); leave(); });

  // Expose for debugging
  window.randomBrowse = { start, next, prev, match, leave };

  // Graceful cleanup on page close/refresh
  window.addEventListener('beforeunload', function(){
    try {
      if (matchedPending) return; // already transitioning
      const data = new FormData();
      data.append('add','random_browse');
      // If in an active call, revert paired row back to waiting via status_fix.
      // Otherwise (only preview/browsing) delete the ticket via leave.
      data.append('action', window.isVideoChatActive ? 'status_fix' : 'leave');
      if (typeof user_csrf_token !== 'undefined' && user_csrf_token) data.append('csrf_token', user_csrf_token);
      if (navigator.sendBeacon){
        navigator.sendBeacon(apiURL, data);
      } else {
        fetch(apiURL, {method:'POST', body:data, keepalive:true});
      }
    } catch(e) { /* ignore */ }
  });

  // --- Dynamic grid collapse helper ---
  window.adjustVideoGrid = function(){
    const grid = document.getElementById('video-chat-grid'); if (!grid) return;
    // Count active participant windows (exclude candidate preview placeholders)
    const active = Array.from(grid.querySelectorAll('.video-window')).filter(el=>!el.classList.contains('candidate-preview-window'));
    if (window.isVideoChatActive){
      const count = active.length || 1;
      // Use CSS grid for flexible collapse
      grid.style.display='grid';
      if (count<=2){
        grid.style.gridTemplateColumns='repeat('+count+', 1fr)';
      } else {
        grid.style.gridTemplateColumns='';
      }
    } else {
      // Browsing mode: revert to default styling
      grid.style.gridTemplateColumns='';
    }
  };

  // Monkey-patch handleUserPublished to re-adjust layout after remote joins
  if (typeof window.handleUserPublished === 'function' && !window.handleUserPublished.__rb_patched){
    const orig = window.handleUserPublished;
    window.handleUserPublished = function(user, mediaType){
      try { orig(user, mediaType); } finally { if (window.isVideoChatActive && typeof window.adjustVideoGrid==='function'){ window.adjustVideoGrid(); } }
    };
    window.handleUserPublished.__rb_patched = true;
  }
})();
