import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // #181 v26.css — отдельный вход: вёрстка новой главной живёт вне Tailwind-темы
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/v26.css', 'resources/css/v26-chrome.css', 'resources/js/v26.js'],
            refresh: true,
        }),
    ],
});
