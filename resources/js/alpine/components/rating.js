/**
 * Alpine.data('starRating') — modo interactivo de `<x-marketplace.star-rating readonly="false">`.
 * Sin uso todavía en 005 (la escritura de reseñas es 006-resenas-favoritos); se implementa ahora
 * porque el componente Blade define ambas variantes desde 016-ui-design-system/plan.md.
 */
export default function starRating(initial = 0) {
    return {
        rating: initial,
        hovered: 0,

        set(value) {
            this.rating = value;
        },

        hover(value) {
            this.hovered = value;
        },
    };
}
