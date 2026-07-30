/**
 * Alpine.store('search') — estado de filtros + resultados de la búsqueda de talleres
 * (005-marketplace-busqueda-perfil). Usado tanto por la página `/search` (dedicada) como por el
 * bloque de búsqueda embebido en el home (`/`), que comparten el mismo componente Blade
 * `<x-marketplace.search-filters>` + `<x-marketplace.map>`.
 */
export function registerSearchStore(Alpine) {
    Alpine.store('search', {
        query: '',
        category: '',
        lat: null,
        lon: null,
        radius: 10,
        minRating: 0,
        openNow: false,
        sort: 'cercania',
        results: [],
        total: 0,
        loading: false,
        error: null,
        hasSearched: false,
        selected: null,

        select(tallerId) {
            this.selected = this.selected === tallerId ? null : tallerId;
        },

        selectedTaller() {
            return this.results.find((taller) => taller.id === this.selected) ?? null;
        },

        async search() {
            this.loading = true;
            this.error = null;

            const params = new URLSearchParams();
            if (this.query) params.set('q', this.query);
            if (this.category) params.set('categoria', this.category);
            if (this.lat !== null && this.lon !== null) {
                params.set('lat', this.lat);
                params.set('lon', this.lon);
                params.set('radio', this.radius);
            }
            if (this.minRating > 0) params.set('min_calificacion', this.minRating);
            if (this.openNow) params.set('open_now', '1');
            if (this.sort) params.set('sort', this.sort);

            try {
                const response = await fetch(`/api/talleres/search?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('No se pudo completar la búsqueda.');
                }

                const payload = await response.json();
                this.results = payload.data ?? [];
                this.total = payload.meta?.total ?? this.results.length;

                if (this.selected !== null && ! this.results.some((taller) => taller.id === this.selected)) {
                    this.selected = null;
                }
            } catch (error) {
                this.error = error.message ?? 'Ocurrió un error al buscar talleres.';
                this.results = [];
                this.total = 0;
            } finally {
                this.loading = false;
                this.hasSearched = true;
            }
        },

        useMyLocation() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition((position) => {
                this.lat = position.coords.latitude;
                this.lon = position.coords.longitude;
                this.search();
                window.dispatchEvent(
                    new CustomEvent('search:located', {
                        detail: { lat: this.lat, lon: this.lon },
                    }),
                );
            });
        },
    });

    Alpine.store('ui', {
        sidebarOpen: false,
        modalOpen: null,
        toasts: [],

        addToast(message, type = 'info') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            }, 4000);
        },
    });
}
