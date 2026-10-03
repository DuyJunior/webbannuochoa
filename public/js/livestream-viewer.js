(() => {
    const root = document.getElementById('live-viewer');
    if (!root) return;

    const { Room, RoomEvent, Track } = window.LivekitClient || {};
    const button = document.getElementById('live-viewer-join');
    const message = document.getElementById('live-viewer-message');
    const video = document.getElementById('live-viewer-video');
    const audio = document.getElementById('live-viewer-audio');
    let room = null;

    const attachTrack = (track) => {
        if (track.kind === Track.Kind.Video) {
            track.attach(video);
            video.hidden = false;
            message.textContent = '';
        } else if (track.kind === Track.Kind.Audio) {
            track.attach(audio);
            audio.play().catch(() => {
                message.textContent = (window.soopiT || (text => text))("Nhấn vào video để bật tiếng.");
            });
        }
    };
    const clearPlayer = async () => {
        const oldRoom = room;
        room = null;
        if (oldRoom) await oldRoom.disconnect();
        video.srcObject = null;
        audio.srcObject = null;
        video.hidden = true;
        button.hidden = false;
        button.disabled = false;
    };

    button.addEventListener('click', async () => {
        if (!Room) {
            message.textContent = (window.soopiT || (text => text))("Không tải được trình phát. Vui lòng tải lại trang.");
            return;
        }
        button.disabled = true;
        message.textContent = (window.soopiT || (text => text))("Đang kết nối buổi phát...");
        try {
            const response = await fetch(root.dataset.tokenUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    Accept: 'application/json',
                },
            });
            const access = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(access.message || (window.soopiT || (text => text))("Buổi phát hiện chưa sẵn sàng."));

            room = new Room({ adaptiveStream: true });
            room.on(RoomEvent.TrackSubscribed, attachTrack);
            room.on(RoomEvent.TrackUnsubscribed, (track) => {
                track.detach();
                if (track.kind === Track.Kind.Video) {
                    video.hidden = true;
                    message.textContent = (window.soopiT || (text => text))("Nhân viên đang kết nối lại camera...");
                }
            });
            room.on(RoomEvent.Reconnecting, () => {
                message.textContent = (window.soopiT || (text => text))("Đường truyền đang gián đoạn, đang kết nối lại...");
            });
            room.on(RoomEvent.Reconnected, () => {
                message.textContent = video.hidden ? (window.soopiT || (text => text))("Đang chờ hình ảnh từ nhân viên...") : '';
            });
            room.on(RoomEvent.Disconnected, () => {
                document.dispatchEvent(new Event('ha-thu-live-viewer-left'));
                if (!room) return;
                clearPlayer().then(() => {
                    message.textContent = (window.soopiT || (text => text))("Kết nối bị gián đoạn. Hãy thử xem lại.");
                });
            });
            await room.connect(access.server_url, access.participant_token);
            document.dispatchEvent(new Event('ha-thu-live-viewer-joined'));
            button.hidden = true;
            message.textContent = video.hidden ? (window.soopiT || (text => text))("Đang chờ hình ảnh từ nhân viên...") : '';
            for (const participant of room.remoteParticipants.values()) {
                for (const publication of participant.trackPublications.values()) {
                    if (publication.track) attachTrack(publication.track);
                }
            }
        } catch (error) {
            await clearPlayer();
            message.textContent = error.message || (window.soopiT || (text => text))("Không thể kết nối buổi phát.");
        }
    });
    video.addEventListener('click', () => audio.play().catch(() => {}));
    if (sessionStorage.getItem('ha-thu-follow-live') === document.getElementById('ht-live-page')?.dataset.currentId) {
        button.click();
    }
    window.addEventListener('pagehide', () => room?.disconnect());
})();
