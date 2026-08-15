import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        // Bind to all interfaces so the dev server is reachable from the host
        // through Docker's published port (see docker-compose `5174:5174`).
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        // The browser (on the host) connects to the HMR socket here.
        hmr: {
            host: 'localhost',
        },
        // Bind-mounted source on Docker needs polling for reliable file watching.
        watch: {
            usePolling: true,
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
