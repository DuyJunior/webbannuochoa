(() => {
    const key = 'ha-thu-follow-live';
    const root = document.getElementById('live-follow');
    const followedId = sessionStorage.getItem(key);
    if (!root || !/^\d+$/.test(followedId || '')) return;

    const video = document.getElementById('live-follow-video');
    const audio = document.getElementById('live-follow-audio');
    const youtube = document.getElementById('live-follow-youtube');
    const message = document.getElementById('live-follow-message');
    const sound = document.getElementById('live-follow-sound');
    const title = document.getElementById('live-follow-title');
    let room = null;
    let connecting = false;

    const stop = () => {
        sessionStorage.removeItem(key);
        root.hidden = true;
        youtube.src = 'about:blank';
        room?.disconnect();
        room = null;
    };
    document.getElementById('live-follow-close').addEventListener('click', stop);
    window.addEventListener('pagehide', () => room?.disconnect());

    const loadLibrary = () => new Promise((resolve, reject) => {
        if (window.LivekitClient) return resolve(window.LivekitClient);
        const script = document.createElement('script');
        script.src = root.dataset.libraryUrl;
        script.onload = () => window.LivekitClient ? resolve(window.LivekitClient) : reject(new Error('Không tải được trình phát.'));
        script.onerror = () => reject(new Error('Không tải được trình phát.'));
        document.head.append(script);
    });

    const connect = async (state) => {
        if (connecting || room) return;
        connecting = true;
        try {
            const { Room, RoomEvent, Track } = await loadLibrary();
            const response = await fetch(state.viewer_token_url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, Accept: 'application/json' },
            });
            const access = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(access.message || 'Không thể xem buổi live.');

            room = new Room({ adaptiveStream: true });
            const attach = (track) => {
                if (track.kind === Track.Kind.Video) {
                    track.attach(video);
                    video.hidden = false;
                    message.hidden = true;
                } else if (track.kind === Track.Kind.Audio) {
                    track.attach(audio);
                    audio.play().catch(() => { sound.hidden = false; });
                }
            };
            room.on(RoomEvent.TrackSubscribed, attach);
            room.on(RoomEvent.TrackUnsubscribed, (track) => {
                track.detach();
                if (track.kind === Track.Kind.Video) { video.hidden = true; message.hidden = false; message.textContent = 'Đang chờ camera kết nối lại...'; }
            });
            room.on(RoomEvent.Disconnected, () => {
                room = null;
                video.hidden = true;
                message.hidden = false;
                message.textContent = 'Mất kết nối. Đang thử kết nối lại...';
            });
            await room.connect(access.server_url, access.participant_token);
            for (const participant of room.remoteParticipants.values()) {
                for (const publication of participant.trackPublications.values()) {
                    if (publication.track) attach(publication.track);
                }
            }
        } catch (error) {
            room?.disconnect();
            room = null;
            message.hidden = false;
            message.textContent = error.message || 'Không thể kết nối buổi live. Đang thử lại...';
        } finally {
            connecting = false;
        }
    };

    sound.addEventListener('click', () => audio.play().then(() => { sound.hidden = true; }).catch(() => {}));
    const refresh = async () => {
        try {
            const response = await fetch(root.dataset.stateUrl, { cache: 'no-store' });
            if (!response.ok) return;
            const state = await response.json();
            if (!state.on_air || String(state.livestream_id) !== followedId) return stop();
            root.hidden = false;
            title.textContent = state.title || 'Soopi đang livestream';
            if (state.source === 'youtube') {
                if (youtube.hidden) {
                    youtube.src = state.embed_url + '?autoplay=1&mute=1';
                    youtube.hidden = false;
                    message.hidden = true;
                }
            } else {
                connect(state);
            }
        } catch (_) {
            // Keep the player visible during a brief network interruption.
        }
    };
    refresh();
    setInterval(refresh, 15000);
})();
