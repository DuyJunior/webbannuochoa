import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerUrl from 'leaflet/dist/images/marker-icon.png';
import markerRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

export function createDeliveryMap(root, messages) {
    const canvas = root.querySelector('[data-location-map]');
    const caption = root.querySelector('[data-location-map-caption]');
    const link = root.querySelector('[data-location-map-link]');
    const map = L.map(canvas, { scrollWheelZoom: false, attributionControl: true, fadeAnimation: false }).setView([21.03, 105.851], 15);
    // Retry a failed visible image once; preserve normal browser tile caching.
    const MapTiles = L.TileLayer.extend({
        createTile(coords, done) {
            const tile = document.createElement('img');
            tile.alt = '';
            tile.setAttribute('role', 'presentation');
            let retried = false;
            tile.onload = () => done(null, tile);
            tile.onerror = error => {
                if (!retried) {
                    retried = true;
                    setTimeout(() => {
                        if (tile.isConnected) tile.src = this.getTileUrl(coords);
                        else done(error, tile);
                    }, 1000);
                } else done(error, tile);
            };
            tile.src = this.getTileUrl(coords);
            return tile;
        },
    });
    const tiles = new MapTiles('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, keepBuffer: 0, updateWhenIdle: true,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(map);
    const retry = root.querySelector('[data-location-map-retry]');
    const failed = new Set();
    tiles.on('tileerror', event => failed.add(event.tile));
    tiles.on('tileload', event => failed.delete(event.tile));
    tiles.on('tileunload', event => failed.delete(event.tile));
    tiles.on('load', () => { retry.hidden = failed.size === 0; });
    retry.addEventListener('click', () => { failed.clear(); retry.hidden = true; tiles.redraw(); });
    const icon = L.icon({ iconUrl: markerUrl, iconRetinaUrl: markerRetinaUrl, shadowUrl,
        iconSize: [25, 41], iconAnchor: [12, 41], shadowSize: [41, 41], popupAnchor: [1, -34] });
    let marker, accuracyCircle;
    new ResizeObserver(() => map.invalidateSize({ pan: false })).observe(canvas);
    return {
        showPosition(position) {
            const { latitude: lat, longitude: lon, accuracy } = position.coords;
            if (!Number.isFinite(lat) || !Number.isFinite(lon) || Math.abs(lat) > 85 || Math.abs(lon) > 180) return;
            if (marker) map.removeLayer(marker);
            if (accuracyCircle) map.removeLayer(accuracyCircle);
            marker = L.marker([lat, lon], { icon, title: messages.mapDevice, alt: messages.mapDevice }).addTo(map);
            const radius = Number.isFinite(accuracy) && accuracy > 0 ? accuracy : 50;
            accuracyCircle = L.circle([lat, lon], { radius, color: '#476b91', weight: 1, fillOpacity: .08 }).addTo(map);
            map.fitBounds(accuracyCircle.getBounds(), { maxZoom: 16, padding: [30, 30], animate: false });
            caption.textContent = messages.mapPosition.replace(':meters', String(Math.ceil(radius)));
            link.href = `https://www.openstreetmap.org/?mlat=${lat.toFixed(5)}&mlon=${lon.toFixed(5)}#map=16/${lat.toFixed(5)}/${lon.toFixed(5)}`;
        },
    };
}
