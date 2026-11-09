import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const INDONESIA_CENTER = [-2.5, 118];
const INDONESIA_ZOOM = 5;

function initMap() {
    const container = document.getElementById('site-map');
    if (!container) {
        return;
    }

    const errorBox = document.getElementById('map-error');

    try {
        const map = L.map(container, {
            zoomControl: false,
            minZoom: 4,
        }).setView(INDONESIA_CENTER, INDONESIA_ZOOM);

        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.control.scale({ position: 'bottomleft', imperial: false }).addTo(map);

        const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });

        tiles.on('tileerror', () => errorBox?.classList.remove('hidden'));
        tiles.addTo(map);

        window.warisAlamMap = map;
    } catch (error) {
        console.error(error);
        errorBox?.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', initMap);
