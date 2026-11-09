import L from './leaflet-global';
import 'leaflet.markercluster';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import '../css/map.css';

const INDONESIA_CENTER = [-2.5, 118];
const INDONESIA_ZOOM = 5;

export const STATUS_STYLES = {
    safe: { label: 'Aman', color: '#059669', rank: 0 },
    watch: { label: 'Waspada', color: '#f59e0b', rank: 1 },
    threatened: { label: 'Terancam', color: '#dc2626', rank: 2 },
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

// A cluster takes the colour of the most threatened site inside it,
// so a red site never hides behind a green cluster.
function clusterIcon(cluster) {
    const worst = cluster
        .getAllChildMarkers()
        .map((marker) => STATUS_STYLES[marker.options.status] ?? STATUS_STYLES.safe)
        .reduce((a, b) => (b.rank > a.rank ? b : a), STATUS_STYLES.safe);

    const count = cluster.getChildCount();
    const size = count < 10 ? 36 : count < 50 ? 44 : 52;

    return L.divIcon({
        className: 'site-cluster',
        html: `<span class="site-cluster__bubble" style="--pin-color: ${worst.color}">${count}</span>`,
        iconSize: [size, size],
    });
}

function renderMarkers(map, sites) {
    const layer = L.markerClusterGroup({
        iconCreateFunction: clusterIcon,
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 50,
    });

    sites.forEach((site) => {
        L.marker([site.lat, site.lng], {
            icon: markerIcon(site.status),
            status: site.status,
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
