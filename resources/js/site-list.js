// "Katalog Situs Terpantau" sidebar: lists the sites currently shown on the map.

import { TYPE_LABELS, escapeHtml, statusStyle } from './map-shared';

const SORTERS = {
    risk: (a, b) => b.site.threat_score - a.site.threat_score || a.site.name.localeCompare(b.site.name, 'id'),
    name: (a, b) => a.site.name.localeCompare(b.site.name, 'id'),
    nearest: (a, b) => a.distance - b.distance,
};

function formatDistance(meters) {
    const km = meters / 1000;

    return km < 10 ? `${km.toFixed(1)} km` : `${Math.round(km).toLocaleString('id-ID')} km`;
}

function cardHtml(site, distance) {
    const style = statusStyle(site.status);
    const score = Number(site.threat_score ?? 0).toFixed(1);
    const location = [site.city, site.province].filter(Boolean).map(escapeHtml).join(', ');

    return `
        <button type="button" class="site-card" style="--pin-color: ${style.color}">
            <span class="site-card__top">
                <span class="site-card__badge">${escapeHtml(site.category ?? TYPE_LABELS[site.type])}</span>
                ${distance === null ? '' : `<span class="site-card__distance">${formatDistance(distance)}</span>`}
            </span>
            <span class="site-card__status">${style.label} &bull; Skor ${score}</span>
            <span class="site-card__name">${escapeHtml(site.name)}</span>
            <span class="site-card__meta">${escapeHtml(TYPE_LABELS[site.type] ?? site.type)} &middot; ${location}</span>
        </button>
    `;
}

export function createSiteList({ map, layer }) {
    const list = document.getElementById('site-list');
    const empty = document.getElementById('site-list-empty');
    const sortGroup = document.getElementById('site-sort');
    const note = document.getElementById('site-sort-note');

    if (!list) {
        return { update() {} };
    }

    let visible = [];
    let sortKey = 'risk';
    let origin = null;

    const render = () => {
        const rows = visible.map((marker) => ({
            marker,
            site: marker.options.site,
            distance: origin ? map.distance(origin, marker.getLatLng()) : null,
        }));

        rows.sort(SORTERS[sortKey]);

        list.replaceChildren(...rows.map(({ marker, site, distance }) => {
            const item = document.createElement('li');
            item.innerHTML = cardHtml(site, sortKey === 'nearest' ? distance : null);
            item.querySelector('button').addEventListener('click', () => {
                layer.zoomToShowLayer(marker, () => marker.fire('click'));
            });

            return item;
        }));

        empty.classList.toggle('hidden', rows.length > 0);
    };

    const setNote = (text) => {
        note.textContent = text ?? '';
        note.classList.toggle('hidden', !text);
    };

    const locate = () => new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve(null);

            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => resolve([position.coords.latitude, position.coords.longitude]),
            () => resolve(null),
            { timeout: 8000, maximumAge: 300000 },
        );
    });

    const selectSort = async (key) => {
        sortKey = key;
        sortGroup.querySelectorAll('[data-sort]').forEach((button) => {
            button.setAttribute('aria-checked', String(button.dataset.sort === key));
        });

        setNote(null);

        if (key === 'nearest') {
            setNote('Mencari lokasi Anda…');
            const position = await locate();
            origin = position ?? map.getCenter();
            setNote(position ? null : 'Lokasi tidak diizinkan, jarak dihitung dari tengah peta.');
        }

        render();
    };

    sortGroup?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort]');
        if (button) {
            selectSort(button.dataset.sort);
        }
    });

    return {
        update(markers) {
            visible = markers;
            render();
        },
    };
}
