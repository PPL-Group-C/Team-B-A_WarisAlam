// Labels and helpers shared by the map, popup and site list.

export const STATUS_STYLES = {
    safe: { label: 'Aman', color: '#059669', rank: 0 },
    watch: { label: 'Waspada', color: '#f59e0b', rank: 1 },
    threatened: { label: 'Terancam', color: '#dc2626', rank: 2 },
};

export const TYPE_LABELS = {
    cultural: 'Cagar Budaya',
    natural: 'Cagar Alam',
};

export function statusStyle(status) {
    return STATUS_STYLES[status] ?? STATUS_STYLES.safe;
}

export function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}
