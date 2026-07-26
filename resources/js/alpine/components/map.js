import L from 'leaflet';

// Iconos default de Leaflet dependen de rutas relativas que Vite no resuelve solo — se
// referencian explícitamente con `new URL(...)` para que el bundler los incluya en el build.
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

function addTileLayer(map, onLoad) {
    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    });

    tileLayer.once('load', onLoad);
    tileLayer.addTo(map);
}

/**
 * Alpine.data('map') — inicializa Leaflet y sincroniza marcadores con
 * `$store.search.results` (005-marketplace-busqueda-perfil/plan.md).
 */
export default function map(center, zoom) {
    return {
        map: null,
        markers: [],
        loadingTiles: true,

        init() {
            this.map = L.map(this.$refs.container).setView([center.lat, center.lon], zoom);
            addTileLayer(this.map, () => {
                this.loadingTiles = false;
            });

            this.$watch('$store.search.results', (results) => this.updateMarkers(results));

            window.addEventListener('search:located', (event) => {
                this.map.setView([event.detail.lat, event.detail.lon], 14);
            });
        },

        updateMarkers(results) {
            this.markers.forEach((marker) => marker.remove());
            this.markers = [];

            results.forEach((taller) => {
                if (taller.lat === null || taller.lon === null) return;

                const marker = L.marker([taller.lat, taller.lon])
                    .addTo(this.map)
                    .bindPopup(`<strong>${taller.nombre}</strong><br>${taller.direccion ?? ''}`);

                this.markers.push(marker);
            });
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
            this.map = L.map(this.$refs.container).setView([center.lat, center.lon], 16);
            addTileLayer(this.map, () => {
                this.loadingTiles = false;
            });

            L.marker([center.lat, center.lon]).addTo(this.map).bindPopup(popupText);
        },
    };
}
