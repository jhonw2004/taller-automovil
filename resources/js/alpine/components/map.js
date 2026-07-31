import L from 'leaflet';

// Iconos default de Leaflet dependen de rutas relativas que Vite no resuelve solo — se
// referencian explícitamente con `new URL(...)` para que el bundler los incluya en el build.
// Solo los usa `singleMap()` (perfil público) — `map()` usa su propio `divIcon` (019-mapa-busqueda-ux).
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const PIN_PATH = 'M13 0C6.1 0 0.5 5.6 0.5 12.5c0 9.4 11 20.7 11.5 21.2.3.3.7.3 1 0 .5-.5 11.5-11.8 11.5-21.2C24.5 5.6 18.9 0 13 0z';

/**
 * Marcador propio de taller (019-mapa-busqueda-ux): reemplaza el pin azul por defecto de
 * Leaflet por un `divIcon` con la paleta de `016-ui-design-system`. `selected` distingue
 * visualmente el marcador activo (card flotante abierta).
 */
function tallerIcon(selected = false) {
    const size = selected ? 40 : 30;
    const color = selected ? '#ff5a00' : '#18181b';
    const height = Math.round(size * (34 / 26));

    return L.divIcon({
        className: 'taller-marker',
        html: `
            <svg width="${size}" height="${height}" viewBox="0 0 26 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="${PIN_PATH}" fill="${color}" stroke="white" stroke-width="1.5"/>
                <circle cx="12.5" cy="13" r="5" fill="white"/>
            </svg>
        `,
        iconSize: [size, height],
        iconAnchor: [size / 2, height],
        popupAnchor: [0, -height * 0.95],
    });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char]);
}

/** HTML del contenido de la card flotante (popup de Leaflet en desktop/tablet). */
function popoverHtml(taller) {
    const categorias = (taller.categorias ?? []).slice(0, 3);
    const categoriasHtml = categorias
        .map((categoria) => `<span class="taller-popover-badge">${escapeHtml(categoria)}</span>`)
        .join('');

    const estadoHtml = taller.abierto_ahora
        ? '<span class="taller-popover-estado taller-popover-estado--abierto">Abierto ahora</span>'
        : '<span class="taller-popover-estado taller-popover-estado--cerrado">Cerrado</span>';

    const direccionHtml = taller.direccion
        ? `<p class="taller-popover-direccion">${escapeHtml(taller.direccion)}</p>`
        : '';

    const distanciaHtml = taller.distancia_km !== null && taller.distancia_km !== undefined
        ? `<span class="taller-popover-distancia">${escapeHtml(taller.distancia_km)} km</span>`
        : '';

    return `
        <div class="taller-popover-card">
            <button type="button" class="taller-popover-close" data-close-popover aria-label="Cerrar">&times;</button>
            <p class="taller-popover-nombre">${escapeHtml(taller.nombre)}</p>
            ${categorias.length ? `<div class="taller-popover-badges">${categoriasHtml}</div>` : ''}
            <div class="taller-popover-meta">
                <span>&#9733; ${Number(taller.calificacion_promedio ?? 0).toFixed(1)} (${escapeHtml(taller.cantidad_resenas ?? 0)})</span>
                ${estadoHtml}
            </div>
            ${direccionHtml}
            <div class="taller-popover-footer">
                ${distanciaHtml}
                <a href="/talleres/${escapeHtml(taller.slug)}" class="taller-popover-link">Ver perfil completo &rarr;</a>
            </div>
        </div>
    `;
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 767px)').matches;
}

function addTileLayer(map, onLoad) {
    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        maxZoom: 19,
    });

    tileLayer.once('load', onLoad);
    tileLayer.addTo(map);

    // Atribución propia, sin el autocrédito "Leaflet" (`prefix: false`) y con estilo discreto
    // definido en `resources/css/app.css` (`.leaflet-control-attribution`) — 019-mapa-busqueda-ux.
    L.control.attribution({ prefix: false, position: 'bottomright' }).addTo(map);
}

/**
 * Alpine.data('map') — inicializa Leaflet y sincroniza marcadores con
 * `$store.search.results` (005-marketplace-busqueda-perfil/plan.md). Marcadores propios, card
 * flotante al seleccionar un taller y controles de zoom/ubicación propios (019-mapa-busqueda-ux).
 */
export default function map(center, zoom) {
    return {
        map: null,
        markersById: {},
        loadingTiles: true,
        zoomLevel: zoom,
        minZoom: 0,
        maxZoom: 19,

        init() {
            this.map = L.map(this.$refs.container, { zoomControl: false, attributionControl: false })
                .setView([center.lat, center.lon], zoom);

            this.minZoom = this.map.getMinZoom();
            this.maxZoom = this.map.getMaxZoom();
            this.map.on('zoomend', () => {
                this.zoomLevel = this.map.getZoom();
            });

            addTileLayer(this.map, () => {
                this.loadingTiles = false;
            });

            this.map.on('click', () => {
                this.$store.search.selected = null;
            });

            // Delegado en el contenedor del mapa (en vez de buscar el botón cada vez que se
            // abre un popup): el contenido del popup se recrea en cada `updateMarkers()`, así
            // que un listener atado a un nodo puntual queda huérfano tan pronto ese marcador se
            // reemplaza. La delegación funciona sin importar cuántas veces se recree el DOM.
            this.map.getContainer().addEventListener('click', (event) => {
                if (event.target.closest('[data-close-popover]')) {
                    this.$store.search.selected = null;
                }
            });

            this.$watch('$store.search.results', (results) => this.updateMarkers(results));
            this.$watch('$store.search.selected', (selectedId) => this.syncSelection(selectedId));

            window.addEventListener('search:located', (event) => {
                this.map.setView([event.detail.lat, event.detail.lon], 14);
            });

            window.addEventListener('taller:hover', (event) => this.highlightMarker(event.detail.id));
        },

        updateMarkers(results) {
            Object.values(this.markersById).forEach((marker) => marker.remove());
            this.markersById = {};

            results.forEach((taller) => {
                if (taller.lat === null || taller.lon === null) return;

                const marker = L.marker([taller.lat, taller.lon], {
                    icon: tallerIcon(taller.id === this.$store.search.selected),
                })
                    .addTo(this.map)
                    .bindPopup(popoverHtml(taller), {
                        className: 'taller-popover',
                        closeButton: false,
                        autoPan: true,
                        maxWidth: 320,
                    })
                    .on('click', () => this.$store.search.select(taller.id));

                this.markersById[taller.id] = marker;
            });

            if (this.$store.search.selected !== null) {
                this.syncSelection(this.$store.search.selected);
            }
        },

        syncSelection(selectedId) {
            Object.entries(this.markersById).forEach(([id, marker]) => {
                marker.setIcon(tallerIcon(Number(id) === selectedId));
            });

            if (selectedId === null) {
                Object.values(this.markersById).forEach((marker) => marker.closePopup());
                return;
            }

            const marker = this.markersById[selectedId];
            if (! marker) return;

            this.map.panTo(marker.getLatLng());

            if (! isMobileViewport()) {
                marker.openPopup();
            }
        },

        highlightMarker(tallerId) {
            Object.entries(this.markersById).forEach(([id, marker]) => {
                if (Number(id) === this.$store.search.selected) return;
                marker.setIcon(tallerIcon(Number(id) === tallerId));
            });
        },

        zoomIn() {
            this.map.zoomIn();
        },

        zoomOut() {
            this.map.zoomOut();
        },

        locate() {
            this.$store.search.useMyLocation();
        },
    };
}

/**
 * Alpine.data('singleMap') — un solo marcador fijo, usado en el perfil público del taller
 * (no depende de `$store.search`, a diferencia de `map()`).
 */
export function singleMap(center, popupText) {
    return {
        map: null,
        loadingTiles: true,

        init() {
            this.map = L.map(this.$refs.container, { zoomControl: false, attributionControl: false }).setView([center.lat, center.lon], 16);
            addTileLayer(this.map, () => {
                this.loadingTiles = false;
            });

            L.marker([center.lat, center.lon]).addTo(this.map).bindPopup(popupText);
        },
    };
}
