import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // The `fonts` option used to download Instrument Sans (400/500/600,
            // ~77 KB across four files) which @theme never referenced, so nothing
            // was ever rendered with it. app.css loads Plus Jakarta Sans and
            // IBM Plex Mono instead — see the preconnect hints in the layouts.
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
