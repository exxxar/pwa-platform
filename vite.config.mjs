import { defineConfig, loadEnv } from 'vite' // 🆕 Добавили loadEnv
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import fs from 'fs'
import path from 'path'

// 🆕 Оборачиваем в функцию, чтобы получить доступ к mode (development/production)
export default defineConfig(({ mode }) => {
    // Загружаем переменные окружения из .env файла
    // Третий аргумент '' означает, что мы загружаем ВСЕ переменные, а не только с префиксом VITE_
    const env = loadEnv(mode, process.cwd(), '')

    return {
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
                 //   'resources/js/AdminPanel/app.js',
                  //  'resources/css/AdminPanel/app.css',
                    'resources/css/MobileClient/app.css',
                ],
                ssr: [
                   // 'resources/js/AdminPanel/ssr.js',
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
               // 'AdminPanel@': path.resolve(__dirname, './resources/js/AdminPanel'),
                'Mobile@': path.resolve(__dirname, './resources/js/MobileClient'),
            },
        },
        build: {
            chunkSizeWarningLimit: 1000,
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

        // 🆕 ДОБАВЛЕНО: Внедрение версии приложения в глобальные константы
        define: {
            // JSON.stringify обязателен, так как define делает простую текстовую замену
            'import.meta.env.VITE_APP_VERSION': JSON.stringify(env.APP_VERSION || '1.0.0'),
        },
    }
})
