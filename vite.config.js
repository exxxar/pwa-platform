import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import fs from 'fs'
import path from 'path'

export default defineConfig({
/*    server: {
        host: 'pwa-platform.test',
        port: 5173,
        https: {
            key: fs.readFileSync('./certs/pwa-platform-key.pem'),
            cert: fs.readFileSync('./certs/pwa-platform.pem'),
        },
        cors: true,
        hmr: {
            host: 'pwa-platform.test'
        }
    },*/


    css: {
        preprocessorOptions: {

            scss: {
                api: 'modern-compiler', // или 'modern'
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/MobileClient/app.js',
                'resources/css/AdminPanel/app.css',
                'resources/css/MobileClient/app.css',
            ],
            ssr: [
                'resources/js/AdminPanel/ssr.js',
                'resources/js/MobileClient/ssr.js',
            ],

            refresh: true,
        }),
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
        alias: {
            'AdminPanel@':path.resolve(__dirname, './resources/js/AdminPanel'),
            'Mobile@': path.resolve(__dirname, './resources/js/MobileClient'),
        },
    },
    build: {
        rollupOptions: {
            onwarn(warning, warn) {
                // Игнорируем предупреждения об eval из node_modules
                if (warning.code === 'EVAL' && warning.id?.includes('node_modules')) {
                    return;
                }
                warn(warning);
            },
        },
    },
})
