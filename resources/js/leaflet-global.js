import L from 'leaflet';

// leaflet.markercluster extends the global `L`, so expose it before that plugin loads.
window.L = L;

export default L;
