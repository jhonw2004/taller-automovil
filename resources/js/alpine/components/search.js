/**
 * Alpine.data('searchForm') — envuelve el bloque de búsqueda (filtros + mapa + lista) y dispara
 * la primera consulta al montar. El propio filtrado vive en Alpine.store('search'); este
 * componente solo orquesta el ciclo de vida de la sección (no duplica estado).
 */
export default function searchForm() {
    return {
        init() {
            this.$store.search.search();
        },
    };
}
