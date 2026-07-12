{{-- ══════════════════════════════════════════════════════════════════
     Widget d'appel vocal in-app (Agora) — Panel Société
     Chargé globalement dans company/layouts/app.blade.php : sonne sur
     N'IMPORTE QUELLE page du panel société (appel client entrant ou appel
     du support entrant), et permet d'appeler le support.
     ══════════════════════════════════════════════════════════════════ --}}

<style>
    @keyframes tt-pulse-orange { 0%{box-shadow:0 0 0 0 rgba(236,114,17,.55);} 70%{box-shadow:0 0 0 14px rgba(236,114,17,0);} 100%{box-shadow:0 0 0 0 rgba(236,114,17,0);} }
    @keyframes tt-pop-in       { from{transform:translateY(16px);opacity:0;} to{transform:translateY(0);opacity:1;} }
    @keyframes tt-blink        { 0%,100%{opacity:1;} 50%{opacity:.25;} }
    #tt-call-incoming, #tt-call-active { animation: tt-pop-in .25s ease-out; }
    .tt-avatar { width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#ec7211,#b85a0d);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;flex-shrink:0; }
    .tt-avatar.tt-ringing { animation: tt-pulse-orange 1.4s infinite; }
    .tt-live-dot { width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;animation:tt-blink 1.4s infinite; }
    .tt-ctrl-btn { width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.12);color:#fff;border:none;cursor:pointer;font-size:16px;transition:.15s; }
    .tt-ctrl-btn:hover { background:rgba(255,255,255,.22); }
    .tt-ctrl-btn.tt-on { background:#ec7211; }
    .tt-hangup-btn { width:50px;height:50px;border-radius:50%;background:#D13212;color:#fff;border:none;cursor:pointer;font-size:18px;box-shadow:0 4px 14px rgba(209,50,18,.45);transition:.15s; }
    .tt-hangup-btn:hover { background:#b82a0f; transform:scale(1.06); }
    .tt-answer-btn  { flex:1;background:#1E8449;color:#fff;border:none;border-radius:8px;padding:9px;font-weight:700;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:.15s; }
    .tt-answer-btn:hover { background:#196b3a; }
    .tt-decline-btn { flex:1;background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:8px;padding:9px;font-weight:700;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:.15s; }
    .tt-decline-btn:hover { background:rgba(209,50,18,.25); border-color:rgba(209,50,18,.5); }
</style>

<div id="tt-call-incoming" style="display:none;position:fixed;top:16px;right:16px;z-index:9998;background:linear-gradient(160deg,#20242a,#15181d);border:1px solid rgba(236,114,17,.5);border-radius:16px;padding:16px 18px;box-shadow:0 12px 40px rgba(0,0,0,.45);min-width:260px;color:#fff;font-family:inherit">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <div class="tt-avatar tt-ringing" id="tt-call-caller-avatar">?</div>
        <div style="flex:1;min-width:0">
            <div style="font-size:10px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.04em;font-weight:600">📞 Appel entrant</div>
            <div id="tt-call-caller-name" style="font-size:15px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">—</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:8px">
        <button onclick="TTCall.accept()" class="tt-answer-btn">📞 Répondre</button>
        <button onclick="TTCall.decline()" class="tt-decline-btn">✕ Refuser</button>
    </div>
    <div style="font-size:9.5px;color:rgba(255,255,255,.4);text-align:center">🔴 Cet appel sera enregistré</div>
</div>

<div id="tt-call-active" style="display:none;position:fixed;bottom:16px;right:16px;z-index:9998;background:linear-gradient(160deg,#20242a,#15181d);border:1px solid rgba(236,114,17,.5);border-radius:16px;padding:16px 20px;box-shadow:0 12px 40px rgba(0,0,0,.45);color:#fff;font-family:inherit;min-width:250px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <div class="tt-avatar" id="tt-call-avatar">🎙️</div>
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:6px;font-size:10px;color:#22c55e;font-weight:700;text-transform:uppercase;letter-spacing:.04em">
                <span class="tt-live-dot"></span> En ligne
            </div>
            <div id="tt-call-active-label" style="font-size:14.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px">Appel en cours</div>
        </div>
        <div id="tt-call-timer" style="font-size:14px;font-weight:600;color:rgba(255,255,255,.75);font-variant-numeric:tabular-nums">00:00</div>
    </div>
    <div style="display:flex;align-items:center;justify-content:center;gap:16px">
        <button id="tt-call-mute-btn" class="tt-ctrl-btn" onclick="TTCall.toggleMute()" title="Muet">🎙️</button>
        <button onclick="TTCall.hangup()" class="tt-hangup-btn" title="Raccrocher"><span style="display:inline-block;transform:rotate(135deg)">📞</span></button>
        <button id="tt-call-speaker-btn" class="tt-ctrl-btn" onclick="TTCall.toggleSpeaker()" title="Haut-parleur">🔈</button>
    </div>
    <div style="display:flex;align-items:center;gap:5px;justify-content:center;margin-top:12px;font-size:10.5px;color:rgba(255,255,255,.45)">
        <span style="width:6px;height:6px;border-radius:50%;background:#D13212;display:inline-block"></span>
        Cet appel est enregistré
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.22.2/AgoraRTC_N-production.min.js"></script>
<script>
(function () {
    let client = null, localAudioTrack = null, currentCallId = null, ringInterval = null;
    let remoteAudioTracks = [], speakerOn = false;
    let callStartTime = null, timerInterval = null;
    let statusPollInterval = null;
    // ── Enregistrement d'appel (micro + audio distant mixés côté navigateur) ──
    let mediaRecorder = null, recordedChunks = [], recordAudioCtx = null, recordDest = null;

    function startTimer() {
        callStartTime = Date.now();
        updateTimerDisplay();
        timerInterval = setInterval(updateTimerDisplay, 1000);
    }
    function stopTimer() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        callStartTime = null;
        const el = document.getElementById('tt-call-timer');
        if (el) el.textContent = '00:00';
    }
    function updateTimerDisplay() {
        if (!callStartTime) return;
        const secs = Math.floor((Date.now() - callStartTime) / 1000);
        const m = String(Math.floor(secs / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        const el = document.getElementById('tt-call-timer');
        if (el) el.textContent = `${m}:${s}`;
    }

    function ring() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            function beep() {
                const o = ctx.createOscillator(), g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.type = 'sine'; o.frequency.setValueAtTime(880, ctx.currentTime);
                g.gain.setValueAtTime(0.35, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.4);
            }
            beep();
            ringInterval = setInterval(beep, 1200);
        } catch (e) {}
    }
    function stopRing() { if (ringInterval) { clearInterval(ringInterval); ringInterval = null; } }

    function headers() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        };
    }

    async function joinChannel(agora) {
        client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
        await client.join(agora.app_id, agora.channel, agora.token, agora.uid);
        localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        await client.publish([localAudioTrack]);
        startRecording();
        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'audio') {
                user.audioTrack.play();
                user.audioTrack.setVolume(speakerOn ? 200 : 100);
                remoteAudioTracks.push(user.audioTrack);
                attachRemoteToRecording(user.audioTrack);
            }
        });
        client.on('user-unpublished', (user) => {
            remoteAudioTracks = remoteAudioTracks.filter(t => t !== user.audioTrack);
        });
    }

    async function leaveChannel() {
        try { localAudioTrack?.close(); } catch (e) {}
        try { await client?.leave(); } catch (e) {}
        client = null; localAudioTrack = null;
        remoteAudioTracks = []; speakerOn = false;
        const speakerBtn = document.getElementById('tt-call-speaker-btn');
        if (speakerBtn) { speakerBtn.textContent = '🔈'; speakerBtn.classList.remove('tt-on