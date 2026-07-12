{{-- ══════════════════════════════════════════════════════════════════
     Widget d'appel vocal in-app (Agora) — Panel Admin / Support
     Chargé globalement dans admin/layouts/app.blade.php : sonne sur
     N'IMPORTE QUELLE page du panel admin, pas seulement la messagerie.

     Mode "call center" : jusqu'à 10 appels simultanés PAR catégorie
     (client / chauffeur / société) peuvent sonner en même temps — chacun
     apparaît comme une carte dans la file d'attente ci-dessous. N'importe
     quel admin connecté, sur n'importe quelle machine, peut décrocher
     n'importe laquelle de ces cartes (file partagée). Dès qu'un admin
     décroche, la carte disparaît chez tous les autres admins ("call.taken").
     Un admin ne peut être que sur UN SEUL appel actif à la fois : les
     boutons "Répondre" des autres cartes sont désactivés tant qu'il est en
     ligne.
     ══════════════════════════════════════════════════════════════════ --}}

<style>
    @keyframes tt-pulse-blue { 0%{box-shadow:0 0 0 0 rgba(29,161,242,.55);} 70%{box-shadow:0 0 0 14px rgba(29,161,242,0);} 100%{box-shadow:0 0 0 0 rgba(29,161,242,0);} }
    @keyframes tt-pop-in     { from{transform:translateY(16px);opacity:0;} to{transform:translateY(0);opacity:1;} }
    @keyframes tt-blink      { 0%,100%{opacity:1;} 50%{opacity:.25;} }
    .tt-call-card, #tt-call-active { animation: tt-pop-in .25s ease-out; font-family:'Inter',sans-serif; }
    .tt-avatar { width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#1DA1F2,#0d6ba8);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;flex-shrink:0; }
    .tt-avatar.tt-ringing { animation: tt-pulse-blue 1.4s infinite; }
    .tt-live-dot { width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;animation:tt-blink 1.4s infinite; }
    .tt-ctrl-btn { width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.1);color:#fff;border:none;cursor:pointer;font-size:16px;transition:.15s; }
    .tt-ctrl-btn:hover { background:rgba(255,255,255,.2); }
    .tt-ctrl-btn.tt-on { background:#1DA1F2; }
    .tt-hangup-btn { width:50px;height:50px;border-radius:50%;background:#D13212;color:#fff;border:none;cursor:pointer;font-size:18px;box-shadow:0 4px 14px rgba(209,50,18,.45);transition:.15s; }
    .tt-hangup-btn:hover { background:#b82a0f; transform:scale(1.06); }
    .tt-answer-btn  { flex:1;background:#1E8449;color:#fff;border:none;border-radius:8px;padding:9px;font-weight:700;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:.15s; }
    .tt-answer-btn:hover { background:#196b3a; }
    .tt-decline-btn { flex:1;background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:9px;font-weight:700;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:.15s; }
    .tt-decline-btn:hover { background:rgba(209,50,18,.25); border-color:rgba(209,50,18,.5); }
</style>

<div id="tt-call-queue" style="display:none;position:fixed;top:16px;right:16px;z-index:9998;flex-direction:column;gap:10px;max-width:300px"></div>

<div id="tt-call-active" style="display:none;position:fixed;bottom:16px;right:16px;z-index:9998;background:linear-gradient(160deg,#132030,#0B141C);border:1px solid rgba(29,161,242,.35);border-radius:16px;padding:16px 20px;box-shadow:0 12px 40px rgba(0,0,0,.5);color:#fff;min-width:250px">
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
    const pendingCalls = new Map(); // callId -> { callerName, queueType }
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

    const QUEUE_LABELS = { client: '📞 Appel Client', chauffeur: '📞 Appel Chauffeur', societe: '📞 Appel Société' };

    function ring() {
        if (ringInterval) return; // déjà en train de sonner
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
        if (speakerBtn) { speakerBtn.textContent = '🔈'; speakerBtn.classList.remove('tt-on'); }
        const muteBtn = document.getElementById('tt-call-mute-btn');
        if (muteBtn) { muteBtn.textContent = '🎙️'; muteBtn.classList.remove('tt-on'); }
    }

    // ── Enregistrement : mixe le micro local + tout l'audio distant reçu via
    // l'API Web Audio dans un seul flux, enregistré en webm/opus. Aucun
    // service tiers requis (pas d'Agora Cloud Recording) — l'enregistrement
    // se fait dans le navigateur puis est uploadé au raccroché.
    function startRecording() {
        try {
            recordAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            recordDest = recordAudioCtx.createMediaStreamDestination();
            if (localAudioTrack?.getMediaStreamTrack) {
                const localStream = new MediaStream([localAudioTrack.getMediaStreamTrack()]);
                recordAudioCtx.createMediaStreamSource(localStream).connect(recordDest);
            }
            recordedChunks = [];
            mediaRecorder = new MediaRecorder(recordDest.stream, { mimeType: 'audio/webm' });
            mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size > 0) recordedChunks.push(e.data); };
            mediaRecorder.start(1000);
        } catch (e) { console.warn('Enregistrement indisponible', e); mediaRecorder = null; }
    }

    function attachRemoteToRecording(audioTrack) {
        if (!recordAudioCtx || !recordDest || !audioTrack?.getMediaStreamTrack) return;
        try {
            const remoteStream = new MediaStream([audioTrack.getMediaStreamTrack()]);
            recordAudioCtx.createMediaStreamSource(remoteStream).connect(recordDest);
        } catch (e) {}
    }

    function stopRecordingAndUpload(callId) {
        if (!mediaRecorder || mediaRecorder.state === 'inactive') { cleanupRecording(); return Promise.resolve(); }
        return new Promise((resolve) => {
            mediaRecorder.onstop = async () => {
                try {
                    const blob = new Blob(recordedChunks, { type: 'audio/webm' });
                    if (blob.size > 2000) {
                        const fd = new FormData();
                        fd.append('recording', blob, `call_${callId}.webm`);
                        await fetch(`/admin/calls/${callId}/recording`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                            body: fd,
                        });
                    }
                } catch (e) { console.warn('Upload enregistrement échoué', e); }
                cleanupRecording();
                resolve();
            };
            try { mediaRecorder.stop(); } catch (e) { cleanupRecording(); resolve(); }
        });
    }

    function cleanupRecording() {
        mediaRecorder = null; recordedChunks = [];
        try { recordAudioCtx?.close(); } catch (e) {}
        recordAudioCtx = null; recordDest = null;
    }

    function refreshQueueInteractivity() {
        const busy = currentCallId !== null;
        document.querySelectorAll('#tt-call-queue .tt-call-answer').forEach(btn => {
            btn.disabled = busy;
            btn.style.opacity = busy ? '.4' : '1';
            btn.style.cursor = busy ? 'not-allowed' : 'pointer';
        });
    }

    function renderCard(callId, callerName, queueType) {
        const box = document.createElement('div');
        box.className = 'tt-call-card';
        box.id = 'tt-call-card-' + callId;
        box.style = 'background:linear-gradient(160deg,#132030,#0B141C);border:1px solid rgba(29,161,242,.4);border-radius:14px;padding:14px 16px;box-shadow:0 10px 34px rgba(0,0,0,.45);color:#fff;font-family:"Inter",sans-serif';
        const initial = (callerName || '?').trim().charAt(0).toUpperCase() || '?';
        box.innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div class="tt-avatar tt-ringing">${initial}</div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:10px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.04em;font-weight:600">${QUEUE_LABELS[queueType] || '📞 Appel entrant'}</div>
                    <div style="font-size:14.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${callerName || 'Appel entrant'}</div>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:8px">
                <button class="tt-call-answer tt-answer-btn">📞 Répondre</button>
                <button class="tt-call-decline tt-decline-btn">✕ Refuser</button>
            </div>
            <div style="font-size:9.5px;color:rgba(255,255,255,.4);text-align:center">🔴 Cet appel sera enregistré</div>`;
        box.querySelector('.tt-call-answer').addEventListener('click', () => window.TTCall.accept(callId));
        box.querySelector('.tt-call-decline').addEventListener('click', () => window.TTCall.decline(callId));
        document.getElementById('tt-call-queue').appendChild(box);
        refreshQueueInteractivity();
    }

    function removeFromQueue(callId) {
        pendingCalls.delete(callId);
        document.getElementById('tt-call-card-' + callId)?.remove();
        if (pendingCalls.size === 0) {
            stopRing();
            document.getElementById('tt-call-queue').style.display = 'none';
        }
    }

    // ── Filet de secours "raccroché des deux côtés" ─────────────────────────
    // Le Pusher event "call.ended" doit normalement raccrocher instantanément
    // l'autre partie, mais s'il échoue silencieusement (identifiants Pusher
    // invalides, etc. — même symptôme que la sonnerie, voir pollPending), le
    // widget de la partie qui n'a PAS raccroché restait bloqué "en ligne"
    // indéfiniment. On interroge le statut réel de l'appel en secours.
    function startStatusPolling(callId) {
        stopStatusPolling();
        statusPollInterval = setInterval(async () => {
            if (!currentCallId) { stopStatusPolling(); return; }
            try {
                const res = await fetch(`/admin/calls/${callId}/status`, { headers: headers() });
                const data = await res.json();
                if (data.success && (data.status === 'ended' || data.status === 'missed')) {
                    endCallLocally(callId);
                }
            } catch (e) {}
        }, 4000);
    }
    function stopStatusPolling() {
        if (statusPollInterval) { clearInterval(statusPollInterval); statusPollInterval = null; }
    }

    /** Termine l'appel côté navigateur (audio + timer + panel + upload de
     *  l'enregistrement), que ce soit parce que NOUS raccrochons ou parce que
     *  l'AUTRE partie a raccroché (Pusher ou filet de secours). */
    async function endCallLocally(id) {
        if (id !== currentCallId) return;
        currentCallId = null;
        stopStatusPolling();
        stopTimer();
        document.getElementById('tt-call-active').style.display = 'none';
        await stopRecordingAndUpload(id);
        await leaveChannel();
        refreshQueueInteractivity();
    }

    window.TTCall = {
        showIncoming(callId, callerName, queueType) {
            callId = parseInt(callId);
            if (pendingCalls.has(callId)) return; // déjà affiché (reconnexion Pusher, etc.)
            pendingCalls.set(callId, { callerName, queueType });
            document.getElementById('tt-call-queue').style.display = 'flex';
            renderCard(callId, callerName, queueType);
            ring();
        },

        async accept(callId) {
            callId = parseInt(callId);
            if (currentCallId) return; // déjà en ligne — bouton normalement désactivé
            const info = pendingCalls.get(callId);
            removeFromQueue(callId);
            try {
                const res = await fetch(`/admin/calls/${callId}/answer`, { method: 'POST', headers: headers() });
                const data = await res.json();
                if (!data.success) {
                    if (data.message) alert(data.message); // ex: "pris par un collègue"
                    return;
                }
                currentCallId = callId;
                if (data.agora) await joinChannel(data.agora);
                document.getElementById('tt-call-active-label').textContent = info?.callerName || 'Appel en cours';
                document.getElementById('tt-call-avatar').textContent = (info?.callerName || '?').trim().charAt(0).toUpperCase() || '?';
                document.getElementById('tt-call-active').style.display = 'block';
                startTimer();
                startStatusPolling(callId);
                refreshQueueInteractivity();
            } catch (e) { console.warn('call answer error', e); }
        },

        async decline(callId) {
            callId = parseInt(callId);
            removeFromQueue(callId);
            try { await fetch(`/admin/calls/${callId}/missed`, { method: 'POST', headers: headers() }); } catch (e) {}
        },

        /** Appeler un client, un chauffeur ou une société depuis une page admin. */
        async startCall(targetType, targetId) {
            if (currentCallId) { alert('Terminez votre appel en cours avant d\'en démarrer un autre.'); return; }
            try {
                const res = await fetch('/admin/calls/initiate', {
                    method: 'POST', headers: headers(),
                    body: JSON.stringify({ target_type: targetType, target_id: targetId }),
                });
                const data = await res.json();
                if (!data.success) { alert(data.message || 'Appel impossible.'); return; }
                currentCallId = data.call.id;
                if (data.agora) await joinChannel(data.agora);
                document.getElementById('tt-call-active-label').textContent = 'Appel en cours';
                document.getElementById('tt-call-avatar').textContent = '🎙️';
                document.getElementById('tt-call-active').style.display = 'block';
                startTimer();
                startStatusPolling(currentCallId);
                refreshQueueInteractivity();
            } catch (e) { console.warn('startCall error', e); alert('Erreur réseau.'); }
        },

        async hangup() {
            if (!currentCallId) return;
            const id = currentCallId;
            await endCallLocally(id);
            try { await fetch(`/admin/calls/${id}/end`, { method: 'POST', headers: headers() }); } catch (e) {}
        },

        async toggleMute() {
            if (!localAudioTrack) return;
            const muted = !localAudioTrack.muted;
            await localAudioTrack.setMuted(muted);
            const btn = document.getElementById('tt-call-mute-btn');
            if (btn) { btn.textContent = muted ? '🔇' : '🎙️'; btn.classList.toggle('tt-on', muted); }
        },

        toggleSpeaker() {
            speakerOn = !speakerOn;
            remoteAudioTracks.forEach(t => t.setVolume(speakerOn ? 200 : 100));
            const btn = document.getElementById('tt-call-speaker-btn');
            if (btn) { btn.textContent = speakerOn ? '🔊' : '🔈'; btn.classList.toggle('tt-on', speakerOn); }
        },
    };

    // Souscription globale — sonne quelle que soit la page admin ouverte.
    try {
        const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
            cluster: '{{ env("PUSHER_APP_CLUSTER", "eu") }}', forceTLS: true,
        });
        const channel = pusher.subscribe('admin-support');

        channel.bind('call.incoming', function (data) {
            window.TTCall.showIncoming(data.call_id, data.caller_name, data.queue_type);
        });

        // Un collègue (autre admin/machine) vient de décrocher cet appel :
        // on le retire de notre propre file d'attente s'il y était encore.
        channel.bind('call.taken', function (data) {
            const id = parseInt(data.call_id);
            if (pendingCalls.has(id)) removeFromQueue(id);
        });

        channel.bind('call.ended', function (data) {
            const id = parseInt(data.call_id);
            if (pendingCalls.has(id)) removeFromQueue(id);
            endCallLocally(id);
        });
    } catch (e) { console.warn('Pusher init (call widget) failed', e); }

    // ✅ Filet de sécurité : le push Pusher ci-dessus doit normalement faire
    // sonner l'admin instantanément, mais si le push échoue silencieusement
    // (ex: identifiants Pusher invalides côté serveur — le seul symptôme
    // visible était alors "rien ne se passe, aucun panel, aucun journal"),
    // on interroge périodiquement le serveur pour rattraper les appels en
    // attente que le temps réel n'aurait pas signalés.
    async function pollPending() {
        try {
            const res = await fetch('/admin/calls/pending', { headers: headers() });
            const data = await res.json();
            if (!data.success) return;

            const seenIds = new Set(data.calls.map(c => parseInt(c.call_id)));

            data.calls.forEach(c => {
                window.TTCall.showIncoming(c.call_id, c.caller_name, c.queue_type);
            });

            // Un appel qu'on affichait n'est plus "en attente" côté serveur
            // (pris par un collègue, expiré, annulé) : on le retire.
            pendingCalls.forEach((info, id) => {
                if (!seenIds.has(id)) removeFromQueue(id);
            });
        } catch (e) { /* silencieux : simple filet de secours */ }
    }
    pollPending();
    setInterval(pollPending, 4000);

    // ✅ Si l'admin ferme/quitte l'onglet en plein appel (répondu ou pas
    // encore), on prévient le serveur pour ne pas laisser l'appel "actif"
    // bloquer indéfiniment les tentatives suivantes entre les deux parties.
    window.addEventListener('pagehide', function () {
        if (currentCallId) {
            try {
                fetch(`/admin/calls/${currentCallId}/end`, { method: 'POST', headers: headers(), keepalive: true });
            } catch (e) {}
        }
    });
})();
</script>
