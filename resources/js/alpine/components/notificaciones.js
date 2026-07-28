/**
 * Alpine.data('notificacionesPanel') — sección "Notificaciones" del dashboard marketplace
 * (014-notificaciones). `notificacionesIniciales` viene del render server-side (mismo criterio que
 * `favoritoToggle`: evita un fetch extra solo para pintar el estado inicial).
 */
export default function notificacionesPanel(notificacionesIniciales = []) {
    return {
        notificaciones: notificacionesIniciales,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async marcarLeida(id) {
            try {
                const response = await fetch(`/notificaciones/${id}/marcar-leida`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo marcar la notificación como leída.');
                }

                this.notificaciones = this.notificaciones.filter((n) => n.id !== id);
            } catch (error) {
                this.$store.ui.addToast(error.message, 'error');
            }
        },
    };
}
