// Trackpad-friendly map navigation:
// - two-finger swipe (plain wheel) pans the map
// - pinch on a trackpad (sent by browsers as ctrl + wheel) or Ctrl + scroll zooms
// Leaflet's own scroll-wheel zoom must be disabled for this to take over.

const LINE_HEIGHT = 16;
const PAGE_HEIGHT = 800;
const ZOOM_SENSITIVITY = 0.01;

function toPixels(delta, mode) {
    if (mode === 1) {
        return delta * LINE_HEIGHT;
    }
    if (mode === 2) {
        return delta * PAGE_HEIGHT;
    }

    return delta;
}

export function enableTrackpadGestures(map) {
    const container = map.getContainer();

    container.addEventListener('wheel', (event) => {
        event.preventDefault();

        const dx = toPixels(event.deltaX, event.deltaMode);
        const dy = toPixels(event.deltaY, event.deltaMode);

        if (event.ctrlKey || event.metaKey) {
            const zoom = map.getZoom() - dy * ZOOM_SENSITIVITY;
            const point = map.mouseEventToContainerPoint(event);
            const clamped = Math.min(Math.max(zoom, map.getMinZoom()), map.getMaxZoom());

            map.setZoomAround(point, clamped, { animate: false });

            return;
        }

        map.panBy([dx, dy], { animate: false });
    }, { passive: false });
}
