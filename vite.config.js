import { fileURLToPath } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const path = (relative) => fileURLToPath(new URL(relative, import.meta.url));

// Builds the workbench's front end. Nothing here ships with the package.
export default defineConfig({
    plugins: [
        laravel({
            input: ['workbench/resources/js/app.js'],
            // The workbench runs on Testbench's Laravel app, so its public folder is in vendor.
            publicDirectory: 'vendor/orchestra/testbench-core/laravel/public',
            refresh: ['workbench/resources/views/**', 'workbench/routes/**'],
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        // Pages import the components from the same paths as the README, without publishing them.
        alias: [
            { find: '@/vendor/tags', replacement: path('./resources/js') },
            { find: '@', replacement: path('./workbench/resources/js') },
        ],
    },
});
