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

const TYPE_LABELS = {
    cultural: 'Cagar Budaya',
    natural: 'Cagar Alam',
};

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

function popupContent(site) {
    const style = STATUS_STYLES[site.status] ?? STATUS_STYLES.safe;
    const score = Number(site.threat_score ?? 0).toFixed(1);
    const location = [site.city, site.province].filter(Boolean).map(escapeHtml).join(', ');

    return `
        <article class="site-popup">
            <header class="site-popup__cover site-popup__cover--${escapeHtml(site.type)}">
                <span class="site-popup__badge">${escapeHtml(site.category ?? TYPE_LABELS[site.type])}</span>
                <span class="site-popup__status" style="--pin-color: ${style.color}">${style.label} &bull; Skor ${score}</span>
            </header>
            <div class="site-popup__body">
                <h3 class="site-popup__title">${escapeHtml(site.name)}</h3>
                <p class="site-popup__meta">${escapeHtml(TYPE_LABELS[site.type] ?? site.type)} &middot; ${location}</p>
                <a class="site-popup__link" href="${escapeHtml(site.detail_url)}">Lihat Detail Situs &rarr;</a>
            </div>
        </article>
    `;
}

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
            .bindPopup(() => popupContent(site), {
                className: 'site-popup-wrapper',
                maxWidth: 300,
                minWidth: 260,
                offset: [0, -6],
            })
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

function populateProvinceOptions(sites) {
    const select = document.getElementById('filter-province');
    if (!select) {
        return;
    }

    const provinces = [...new Set(sites.map((site) => site.province).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'id'));

    select.options[0].textContent = `Semua Provinsi (${provinces.length})`;

    provinces.forEach((province) => select.add(new Option(province, province)));
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
        populateProvinceOptions(sites);

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
