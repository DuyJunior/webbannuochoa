import '../css/admin-shop-location.css';

const editor = document.querySelector('[data-location-editor]');
if (editor) {
    const lat = editor.querySelector('[name=latitude]'), lng = editor.querySelector('[name=longitude]');
    const preview = editor.querySelector('[data-location-preview]'), status = editor.querySelector('[data-editor-status]');
    const canvas = editor.querySelector('[data-pin-map]'), enable = editor.querySelector('[data-enable-pin]');
    const name = editor.querySelector('[name=name]');
    let map, marker;
    const valid = () => lat.value !== '' && lng.value !== '' && lat.checkValidity() && lng.checkValidity();
    function refresh() {
        if (!valid()) { status.textContent = 'Vĩ độ phải từ -85 đến 85; kinh độ từ -180 đến 180.'; return; }
        const pair = [Number(lat.value), Number(lng.value)];
        preview.src = `https://maps.google.com/maps?q=${pair.join(',')}&z=16&output=embed`;
        if (map) { marker.setLatLng(pair); map.panTo(pair); }
        status.textContent = 'Đã cập nhật bản xem trước. Bấm Lưu vị trí để công khai thay đổi.';
    }
    lat.addEventListener('change', refresh); lng.addEventListener('change', refresh);
    name.addEventListener('input', () => { editor.querySelector('[data-preview-name]').textContent = name.value; });
    editor.querySelector('[data-apply-coordinates]').addEventListener('click', () => {
        const match = editor.querySelector('[data-coordinate-pair]').value.trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
        if (!match || Math.abs(Number(match[1])) > 85 || Math.abs(Number(match[2])) > 180) {
            status.textContent = 'Dán đúng cặp tọa độ, ví dụ: 21.0205, 105.76393 (không phải đường link).'; return;
        }
        lat.value = match[1]; lng.value = match[2]; refresh();
    });
    enable.addEventListener('click', async () => {
        if (!valid()) { refresh(); return; }
        enable.disabled = true;
        let timer;
        const fail = () => {
            clearTimeout(timer); map?.remove(); map = null; marker = null; canvas.hidden = true; preview.hidden = false;
            enable.disabled = false;
            status.textContent = 'Không tải được nền bản đồ tương tác. Hãy dán tọa độ từ Google Maps; bạn vẫn lưu được vị trí bình thường.';
        };
        try {
            const { default: L } = await import('leaflet');
            await import('leaflet/dist/leaflet.css');
            canvas.hidden = false; preview.hidden = true;
            map = L.map(canvas, { scrollWheelZoom: false }).setView([Number(lat.value), Number(lng.value)], 16);
            const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19,
                attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>' });
            // Keep the coordinate form usable if the user's network blocks tiles.
            timer = setTimeout(fail, 12000);
            tiles.once('tileload', () => clearTimeout(timer));
            tiles.once('tileerror', () => setTimeout(() => { if (map) fail(); }, 0));
            tiles.addTo(map);
            marker = L.marker([Number(lat.value), Number(lng.value)], { draggable:true,
                title:'Kéo ghim vị trí shop', icon:L.divIcon({className:'sl-admin-pin',html:'S',iconSize:[36,36],iconAnchor:[18,18]}) }).addTo(map);
            function select(point) { lat.value = point.lat.toFixed(7); lng.value = point.lng.toFixed(7); refresh(); }
            marker.on('dragend', () => select(marker.getLatLng()));
            map.on('click', event => select(event.latlng));
            status.textContent = 'Nhấp vào bản đồ hoặc kéo ghim. Thay đổi chưa lưu.';
        } catch { fail(); }
    });
    // Validation errors may return old inputs different from the saved preview.
    if (valid()) refresh();
}
