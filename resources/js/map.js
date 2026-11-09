import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/map.css';

const INDONESIA_CENTER = [-2.5, 118];
const INDONESIA_ZOOM = 5;

export const STATUS_STYLES = {
    safe: { label: 'Aman', color: '#059669' },
    watch: { label: 'Waspada', color: '#f59e0b' },
    threatened: { label: 'Terancam', color: '#dc2626' },
};

function show(element, message) {
    if (!element) {
        return;
    }
    if (message) {
        element.textContent = message;
    }
    element.classList.remove('hidden');
}

function hide(element) {
    element?.classList.add('hidden');
}

function markerIcon(status) {
    const style = STATUS_STYLES[status] ?? STATUS_STYLES.safe;

    return L.divIcon({
        className: 'site-marker',
        html: `<span class="site-marker__pin" style="--pin-color: ${style.color}"></span>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
    });
}

function renderMarkers(map, sites) {
    const layer = L.layerGroup();

    sites.forEach((site) => {
        L.marker([site.lat, site.lng], {
            icon: markerIcon(site.status),
            title: site.name,
            alt: `${site.name} (${STATUS_STYLES[site.status]?.label ?? site.status})`,
        })
            .bindTooltip(site.name, { direction: 'top', offset: [0, -10] })
            .addTo(layer);
    });

    return layer.addTo(map);
}

function renderSummary(sites) {
    const total = document.getElementById('summary-total');
    const atRisk = document.getElementById('summary-at-risk');

    if (total) {
        total.textContent = sites.length;
    }
    if (atRisk) {
        atRisk.textContent = sites.filter((site) => site.status !== 'safe').length;
    }
}

async function loadSites(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error(`Failed to load sites: ${response.status}`);
    }

    const payload = await response.json();

    return payload.data ?? [];
}

async function initMap() {
    const container = document.getElementById('site-map');
    if (!container) {
        return;
    }

    const errorBox = document.getElementById('map-error');
    const emptyBox = document.getElementById('map-empty');
    const loadingBox = document.getElementById('map-loading');

    let map;

    try {
        map = L.map(container, {
            zoomControl: false,
            minZoom: 4,
        }).setView(INDONESIA_CENTER, INDONESIA_ZOOM);

        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.control.scale({ position: 'bottomleft', imperial: false }).addTo(map);

        const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });

        tiles.on('tileerror', () => show(errorBox));
        tiles.addTo(map);

        window.warisAlamMap = map;
    } catch (error) {
        console.error(error);
        show(errorBox);
        return;
    }

    try {
        const sites = await loadSites(container.dataset.sitesUrl);

        renderSummary(sites);

        if (sites.length === 0) {
            show(emptyBox);
            return;
        }

        window.warisAlamSites = sites;
        window.warisAlamMarkers = renderMarkers(map, sites);
    } catch (error) {
        console.error(error);
        show(errorBox, 'Data situs gagal dimuat. Coba muat ulang halaman.');
    } finally {
        hide(loadingBox);
    }
}

document.addEventListener('DOMContentLoaded', initMap);
