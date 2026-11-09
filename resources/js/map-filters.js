// Client-side filtering for the site map (FR-10).
// All site data is loaded once; filters only decide which markers are shown.

import L from './leaflet-global';

const SEARCH_DEBOUNCE = 150;

function normalize(value) {
    return (value ?? '').toString().trim().toLowerCase();
}

export function readFilters(form) {
    return {
        search: normalize(form.querySelector('#filter-search')?.value),
        type: form.querySelector('#filter-type')?.value ?? '',
        province: form.querySelector('#filter-province')?.value ?? '',
        status: form.querySelector('#filter-status')?.value ?? '',
    };
}

export function hasActiveFilters(filters) {
    return Object.values(filters).some(Boolean);
}

// Every active filter must match (AND logic). Empty filters are ignored.
export function matchesFilters(site, filters) {
    return (!filters.search || normalize(site.name).includes(filters.search))
        && (!filters.type || site.type === filters.type)
        && (!filters.province || site.province === filters.province)
        && (!filters.status || site.status === filters.status);
}

export function setupFilters({ form, map, layer, markers, onChange, resetView }) {
    if (!form) {
        return;
    }

    let searchTimer = null;

    const apply = () => {
        const filters = readFilters(form);
        const visible = markers.filter((marker) => matchesFilters(marker.options.site, filters));

        // Rebuild the cluster group so cluster counts and colours match the result.
        map.closePopup();
        layer.clearLayers();
        layer.addLayers(visible);

        if (visible.length > 0 && hasActiveFilters(filters)) {
            map.fitBounds(L.latLngBounds(visible.map((marker) => marker.getLatLng())), {
                padding: [40, 40],
                maxZoom: 8,
            });
        } else if (!hasActiveFilters(filters)) {
            resetView();
        }

        onChange(visible, markers.length, filters);
    };

    form.querySelector('#filter-search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(apply, SEARCH_DEBOUNCE);
    });

    form.querySelectorAll('select').forEach((select) => select.addEventListener('change', apply));

    // The reset event fires before the inputs are cleared, so wait one tick.
    form.addEventListener('reset', () => setTimeout(apply));

    onChange(markers, markers.length, readFilters(form));
}
