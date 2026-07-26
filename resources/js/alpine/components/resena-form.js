/**
 * Alpine.data('resenaForm') — formulario "Escribir reseña"/"Mi reseña" del perfil público
 * (006-resenas-favoritos). `resenaInicial` viene del render server-side (la reseña propia del
 * usuario si ya existe, `null` si no). Usa `Alpine.store('ui').addToast()` (016) para feedback.
 */
export default function resenaForm(tallerId, resenaInicial = null) {
    return {
        tallerId,
        resenaId: resenaInicial?.id ?? null,
        calificacion: resenaInicial?.calificacion ?? 0,
        comentario: resenaInicial?.comentario ?? '',
        editing: !resenaInicial,
        loading: false,
        error: null,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        editar() {
            this.editing = true;
        },

        async guardar() {
            if (this.calificacion < 1) {
                this.error = 'Selecciona una calificación de 1 a 5 estrellas.';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/api/resenas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        taller_id: this.tallerId,
                        calificacion: this.calificacion,
                        comentario: this.comentario || null,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.error ?? 'No se pudo guardar tu reseña.');
                }

                this.resenaId = payload.data.id;
                this.editing = false;
                this.$store.ui.addToast('Reseña guardada.', 'success');
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async eliminar() {
            if (!this.resenaId) return;

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(`/api/resenas/${this.resenaId}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo eliminar tu reseña.');
                }

                this.resenaId = null;
                this.calificacion = 0;
                this.comentario = '';
                this.editing = true;
                this.$store.ui.addToast('Reseña eliminada.', 'info');
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
