/**
 * Alpine.data('favoritoToggle') — icono de corazón lleno/vacío en el perfil público y en el
 * grid "Mis Favoritos" del dashboard (006-resenas-favoritos). `esFavoritoInicial` viene del
 * render server-side para evitar un fetch extra solo para pintar el estado inicial.
 */
export default function favoritoToggle(tallerId, esFavoritoInicial = false) {
    return {
        tallerId,
        esFavorito: esFavoritoInicial,
        loading: false,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async toggle() {
            this.loading = true;

            const url = this.esFavorito ? '/api/favoritos/delete' : '/api/favoritos';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ taller_id: this.tallerId }),
                });

                if (!response.ok) {
                    throw new Error('No se pudo actualizar tus favoritos.');
                }

                this.esFavorito = !this.esFavorito;
                this.$store.ui.addToast(
                    this.esFavorito ? 'Agregado a favoritos.' : 'Quitado de favoritos.',
                    'success',
                );
            } catch (error) {
                this.$store.ui.addToast(error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}
