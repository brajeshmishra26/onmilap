/* Simple Random Video Chat Client (ticket-based) */
(function(){
  const apiURL = (typeof api_request_url !== 'undefined' && api_request_url) ? api_request_url : ($('base').eq(0).attr('href')||'./') + 'web_request/';
  let pollId = null, inChannel = false;
  console.log('[random_simple] loaded. apiURL=', apiURL);

  async function api(action){
    const fd = new FormData(); fd.append('add','random_simple'); fd.append('action',action);
    if (typeof user_csrf_token !== 'undefined' && user_csrf_token) fd.append('csrf_token', user_csrf_token);
    const r = await fetch(apiURL, { method:'POST', body: fd });
    try { return await r.json(); } catch { return {error:true, message:'Invalid JSON'}; }
  }

  async function ensureUI(){
    $('.main .middle > .video_chat_interface').removeClass('d-none').removeAttr('group_id').removeAttr('user_id');
    $('.main .middle > .video_chat_interface').removeClass('audio_only_chat');
  }

  async function start(){
    console.log('[random_simple] start() invoked');
    await ensureUI();
    // Local preview (best-effort)
    try {
      if (typeof AgoraRTC!=='undefined'){
        window.preferredCamId = await getPreferredCamera();
        const vt = await AgoraRTC.createCameraVideoTrack({cameraId: preferredCamId});
        let el = document.querySelector('#video-chat-grid .video-window.local-participant-window');
        if (!el){ el = document.createElement('div'); el.className='video-window local-participant-window'; el.innerHTML='<span class="participant_name">You</span><span class="vc_timer">00:00</span><span class="onmilap_overlay">onMilap</span>'; document.getElementById('video-chat-grid').appendChild(el);} 
        vt.play(el); window.localTracks = window.localTracks||{}; window.localTracks.videoTrack = vt;
      }
    } catch {}

    const res = await api('find');
    console.log('[random_simple] find result:', res);
    if (res && res.matched && res.channel && res.token){ return join(res); }
    pollId = setInterval(async ()=>{
      const s = await api('status');
      console.log('[random_simple] status:', s);
      if (s && s.matched && s.channel && s.token){ clearInterval(pollId); pollId=null; join(s); }
    }, 2000);
  }

  async function join(bundle){
    try {
      await ensureUI();
      if (!window.agora_video_client && typeof AgoraRTC!=='undefined'){
        window.agora_video_client = AgoraRTC.createClient({mode:'rtc', codec:'vp8'});
        agora_video_client.on('user-published', handleUserPublished);
        agora_video_client.on('user-unpublished', handleUserUnpublished);
        agora_video_client.on('user-left', handleUserLeft);
      }
      window.options = window.options || {}; options.appid=bundle.app_id; options.channel=bundle.channel; options.token=bundle.token; options.uid=null;

      const micOk = await checkMicrophonePermission(); window.preferredMicId = await getPreferredMicrophone();
      let audioTrack = null; if (micOk && typeof AgoraRTC!=='undefined'){ audioTrack = await AgoraRTC.createMicrophoneAudioTrack({encoderConfig:'high_quality_stereo',AEC:true,AGC:true,ANS:true,microphoneId: preferredMicId}); }

      await agora_video_client.join(options.appid, options.channel, options.token||null, null);
      if (!document.querySelector('#video-chat-grid .video-window.local-participant-window')){
        const el = document.createElement('div'); el.className='video-window local-participant-window'; el.innerHTML='<span class="participant_name">You</span><span class="vc_timer">00:00</span><span class="onmilap_overlay">onMilap</span>'; document.getElementById('video-chat-grid').appendChild(el);
      }
      if (window.localTracks && window.localTracks.videoTrack){ window.localTracks.videoTrack.play(document.querySelector('#video-chat-grid .video-window.local-participant-window')); await agora_video_client.publish([window.localTracks.videoTrack].concat(audioTrack?[audioTrack]:[])); }
      else if (audioTrack){ await agora_video_client.publish([audioTrack]); }

      window.isVideoChatActive = true; inChannel = true; if (!window.vc_callStartAt){ window.vc_callStartAt=Date.now(); if (typeof vc_startTimerIfNeeded==='function') vc_startTimerIfNeeded(); }
      $('.random_chat_modal').addClass('d-none');
    } catch(e){ console.error('random_simple join error', e); }
  }

  // Take over the Random Chat button(s) to use the simple flow
  // Bind by id and by data-action for resilience
  $(document)
    .off('click','#btnRandomChat')
    .on('click','#btnRandomChat', function(e){
      e.preventDefault();
      console.log('[random_simple] #btnRandomChat click');
      start();
    })
    .on('click','[data-action="random-chat"]', function(e){
      e.preventDefault();
      console.log('[random_simple] [data-action="random-chat"] click');
      start();
    });

  // Log presence of trigger elements once DOM is ready (debug aid)
  $(function(){
    console.log('[random_simple] triggers present:', {
      byId: $('#btnRandomChat').length,
      byAttr: $('[data-action="random-chat"]').length
    });
  });

  // Expose manual starter for debugging
  window.randomSimpleStart = start;
})();
