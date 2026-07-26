import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // Tipografía Cosmica/DM Sans (016-ui-design-system) servida vía @fontsource/dm-sans,
            // importado directamente en resources/css/app.css — no vía bunny fonts (esa era la
            // fuente Instrument Sans del skeleton de Laravel, ya reemplazada).
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/erp/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
