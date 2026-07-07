{{-- ══════════════════════════════════════════════════════════════════
     Widget d'appel vocal in-app (Agora) — Panel Admin / Support
     Chargé globalement dans admin/layouts/app.blade.php : sonne sur
     N'IMPORTE QUELLE page du panel admin, pas seulement la messagerie.
     File d'attente partagée : n'importe quel admin connecté peut décrocher.
     ══════════════════════════════════════════════════════════════════ --}}

<div id="tt-call-incoming" style="display:none;position:fixed;top:16px;right:16px;z-index:9998;background:#0F1923;border:1px solid rgba(29,161,242,.4);border-radius:10px;padding:16px 18px;box-shadow:0 8px 32px rgba(0,0,0,.4);min-width:260px;color:#fff;font-family:'Inter',sans-serif">
    <div style="font-size:12px;color:rgba(255,255,255,.5);margin-bottom:4px">📞 Appel entrant</div>
    <div id="tt-call-caller-name" style="font-size:15px;font-weight:700;margin-bottom:12px">—</div>
    <div style="display:flex;gap:8px">
        <button onclick="TTCall.accept()" style="flex:1;background:#1E8449;color:#fff;border:none;border-radius:6px;padding:8px;font-weight:600;cursor:pointer">Répondre</button>
        <button onclick="TTCall.decline()" style="flex:1;background:#D13212;color:#fff;border:none;border-radius:6px;padding:8px;font-weight:600;cursor:pointer">Refuser</button>
    </div>
</div>

<div id="tt-call-active" style="display:none;position:fixed;bottom:16px;right:16px;z-index:9998;background:#0F1923;border:1px solid rgba(29,161,242,.4);border-radius:10px;padding:14px 18px;box-shadow:0 8px 32px rgba(0,0,0,.4);color:#fff;font-family:'Inter',sans-serif;display:flex;align-items:center;gap:12px">
    <span style="font-size:13px;font-weight:600">🎙️ Appel en cours</span>
    <button id="tt-call-mute-btn" onclick="TTCall.toggleMute()" style="background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer">🎙️</button>
    <button onclick="TTCall.hangup()" style="background:#D13212;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;font-weight:600">Raccrocher</button>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.22.2/AgoraRTC_N-production.min.js"></script>
<script>
(function () {
    let client = null, localAudioTrack = null, currentCallId = null, ringInterval = null;

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
        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'audio') user.audioTrack.play();
        });
    }

    async function leaveChannel() {
        try { localAudioTrack?.close(); } catch (e) {}
        try { await client?.leave(); } catch (e) {}
        client = null; localAudioTrack = null;
    }

    window.TTCall = {
        showIncoming(callId, callerName) {
            currentCallId = callId;
            document.getElementById('tt-call-caller-name').textContent = callerName || 'Appel entrant';
            document.getElementById('tt-call-incoming').style.display = 'block';
            ring();
        },

        async accept() {
            stopRing();
            document.getElementById('tt-call-incoming').style.display = 'none';
            try {
                const res = await fetch(`/admin/calls/${currentCallId}/answer`, { method: 'POST', headers: headers() });
                const data = await res.json();
                if (data.success && data.agora) await joinChannel(data.agora);
            } catch (e) { console.warn('call answer error', e); }
            document.getElementById('tt-call-active').style.display = 'flex';
        },

        async decline() {
            stopRing();
            document.getElementById('tt-call-incoming').style.display = 'none';
            const id = currentCallId;
            currentCallId = null;
            try { await fetch(`/admin/calls/${id}/missed`, { method: 'POST', headers: headers() }); } catch (e) {}
        },

        /** Appeler un client, un chauffeur ou une société depuis une page admin. */
        async startCall(targetType, targetId) {
            try {
                const res = await fetch('/admin/calls/initiate', {
                    method: 'POST', headers: headers(),
                    body: JSON.stringify({ target_type: targetType, target_id: targetId }),
                });
                const data = await res.json();
                if (!data.success) { alert(data.message || 'Appel impossible.'); return; }
                currentCallId = data.call.id;
                if (data.agora) await joinChannel(data.agora);
                document.getElementById('tt-call-active').style.display = 'flex';
            } catch (e) { console.warn('startCall error', e); alert('Erreur réseau.'); }
        },

        async hangup() {
            if (!currentCallId) return;
            const id = currentCallId;
            currentCallId = null;
            await leaveChannel();
            document.getElementById('tt-call-active').style.display = 'none';
            try { await fetch(`/admin/calls/${id}/end`, { method: 'POST', headers: headers() }); } catch (e) {}
        },

        async toggleMute() {
            if (!localAudioTrack) return;
            const muted = !localAudioTrack.muted;
            await localAudioTrack.setMuted(muted);
            document.getElementById('tt-call-mute-btn').textContent = muted ? '🔇' : '🎙️';
        },
    };

    // Souscription globale — sonne quelle que soit la page admin ouverte.
    try {
        const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
            cluster: '{{ env("PUSHER_APP_CLUSTER", "eu") }}', forceTLS: true,
        });
        const channel = pusher.subscribe('admin-support');

        channel.bind('call.incoming', function (data) {
            window.TTCall.showIncoming(data.call_id, (data.caller_name || 'Appel entrant') + (data.caller_type ? '' : ''));
        });

        channel.bind('call.ended', function (data) {
            if (String(data.call_id) === String(currentCallId)) {
                stopRing();
                document.getElementById('tt-call-incoming').style.display = 'none';
                document.getElementById('tt-call-active').style.display = 'none';
                leaveChannel();
                currentCallId = null;
            }
        });
    } catch (e) { console.warn('Pusher init (call widget) failed', e); }
})();
</script>
