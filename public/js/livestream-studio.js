(() => {
    const root = document.getElementById('live-studio');
    if (!root) return;

    const { Room, RoomEvent, Track } = window.LivekitClient || {};
    const video = document.getElementById('live-studio-video');
    const placeholder = document.getElementById('live-studio-placeholder');
    const status = document.getElementById('live-studio-status');
    const pill = document.getElementById('live-studio-pill');
    const start = document.getElementById('live-studio-start');
    const mic = document.getElementById('live-studio-mic');
    const camera = document.getElementById('live-studio-camera');
    const end = document.getElementById('live-studio-end');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let room = null;
    let heartbeat = null;
    let heartbeatBusy = false;
    let missedHeartbeats = 0;
    let onAir = false;

    const setStatus = (message, error = false) => {
        status.textContent = message;
        status.classList.toggle('live-studio-error', error);
    };
    const post = async (url) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(result.message || `Không thể kết nối (${response.status}).`);
            error.status = response.status;
            throw error;
        }
        return result;
    };
    const reset = async () => {
        clearInterval(heartbeat);
        heartbeat = null;
        heartbeatBusy = false;
        missedHeartbeats = 0;
        onAir = false;
        const oldRoom = room;
        room = null;
        if (oldRoom) await oldRoom.disconnect();
        video.srcObject = null;
        placeholder.hidden = false;
        pill.textContent = 'Chưa lên sóng';
        pill.classList.remove('live-studio-pill--on');
        start.disabled = root.dataset.ended === '1' || root.dataset.configured !== '1';
        mic.disabled = camera.disabled = end.disabled = true;
    };
    const checkHeartbeat = async () => {
        if (heartbeatBusy || !onAir) return;
        heartbeatBusy = true;
        try {
            await post(root.dataset.heartbeatUrl);
            missedHeartbeats = 0;
            setStatus('Bạn đang phát trực tiếp. Khách có thể xem ngay trên website.');
        } catch (error) {
            missedHeartbeats++;
            if ([401, 403, 409, 419].includes(error.status) || missedHeartbeats >= 3) {
                await reset();
                setStatus('Buổi phát đã dừng hoặc không còn quyền phát. Kiểm tra lại rồi bắt đầu lại từ studio.', true);
            } else {
                setStatus('Đang thử nối lại trạng thái buổi phát...', true);
            }
        } finally {
            heartbeatBusy = false;
        }
    };

    start.addEventListener('click', async () => {
        if (!Room) return setStatus('Không tải được thư viện video. Hãy tải lại trang.', true);
        if (!navigator.mediaDevices?.getUserMedia) {
            return setStatus('Trình duyệt không cho phép camera. Hãy dùng HTTPS hoặc localhost và kiểm tra quyền camera/micro.', true);
        }
        start.disabled = true;
        setStatus('Đang kết nối máy chủ video và xin quyền camera/micro...');
        try {
            const access = await post(root.dataset.tokenUrl);
            room = new Room({ adaptiveStream: true, dynacast: true });
            room.on(RoomEvent.Reconnecting, () => setStatus('Đường truyền đang gián đoạn, hệ thống đang kết nối lại...'));
            room.on(RoomEvent.Reconnected, () => setStatus('Đã kết nối lại. Bạn đang phát trực tiếp.'));
            room.on(RoomEvent.Disconnected, () => {
                if (onAir) {
                    reset().then(() => setStatus('Kết nối video đã mất. Bạn có thể bấm bắt đầu phát để kết nối lại.', true));
                }
            });
            await room.connect(access.server_url, access.participant_token);
            await room.localParticipant.enableCameraAndMicrophone();
            const publication = room.localParticipant.getTrackPublication(Track.Source.Camera);
            if (!publication?.track) throw new Error('Camera chưa sẵn sàng. Kiểm tra quyền truy cập của trình duyệt.');
            publication.track.attach(video);
            placeholder.hidden = true;
            await post(root.dataset.beginUrl);
            onAir = true;
            pill.textContent = '● Đang phát trực tiếp';
            pill.classList.add('live-studio-pill--on');
            setStatus('Bạn đang phát trực tiếp. Khách có thể xem ngay trên website.');
            mic.disabled = camera.disabled = end.disabled = false;
            heartbeat = setInterval(checkHeartbeat, 10000);
        } catch (error) {
            await reset();
            const connectionFailed = /could not establish signal connection|failed to fetch/i.test(error.message || '');
            setStatus(connectionFailed
                ? 'Không kết nối được máy chủ video LiveKit. Kiểm tra LiveKit đang chạy rồi thử lại.'
                : (error.message || 'Không thể bắt đầu buổi live.'), true);
        }
    });

    mic.addEventListener('click', async () => {
        if (!room) return;
        try {
            mic.disabled = true;
            const enabled = room.localParticipant.isMicrophoneEnabled;
            await room.localParticipant.setMicrophoneEnabled(!enabled);
            mic.textContent = `Micro: ${enabled ? 'tắt' : 'bật'}`;
        } catch (error) {
            setStatus(error.message || 'Không thể đổi trạng thái micro.', true);
        } finally {
            mic.disabled = !onAir;
        }
    });
    camera.addEventListener('click', async () => {
        if (!room) return;
        try {
            camera.disabled = true;
            const enabled = room.localParticipant.isCameraEnabled;
            await room.localParticipant.setCameraEnabled(!enabled);
            if (!enabled) {
                const publication = room.localParticipant.getTrackPublication(Track.Source.Camera);
                publication?.track?.attach(video);
            }
            placeholder.hidden = !enabled;
            camera.textContent = `Camera: ${enabled ? 'tắt' : 'bật'}`;
        } catch (error) {
            setStatus(error.message || 'Không thể đổi trạng thái camera.', true);
        } finally {
            camera.disabled = !onAir;
        }
    });
    end.addEventListener('click', async () => {
        if (!confirm('Kết thúc buổi phát trực tiếp này?')) return;
        end.disabled = true;
        try {
            await post(root.dataset.finishUrl);
            root.dataset.ended = '1';
            await reset();
            setStatus('Buổi phát đã kết thúc. Tạo buổi mới để phát tiếp.');
        } catch (error) {
            end.disabled = false;
            setStatus(error.message || 'Không thể kết thúc buổi phát.', true);
        }
    });
})();
