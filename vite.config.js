import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/gift-experience.js', 'resources/css/store-refinements.css', 'resources/css/store-navigation.css', 'resources/css/app.css', 'resources/css/boutique.css', 'resources/css/admin-boutique.css', 'resources/css/admin-polish.css', 'resources/css/home-bloom.css', 'resources/css/store-atelier.css', 'resources/css/store-interiors.css', 'resources/css/home-couture.css', 'resources/css/home-reference.css', 'resources/js/app.js', 'resources/js/admin-boutique.js', 'resources/js/home-bloom.js'],
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
