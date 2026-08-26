import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'

// 🆕 1. Импортируем наши Layout-ы
import AuthLayout from './Layouts/AuthLayout.vue'
import AdminLayout from './Layouts/AdminLayout.vue'

const appName = import.meta.env.VITE_APP_NAME || 'Админ-панель'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    // 🆕 2. Модифицируем resolve, чтобы автоматически назначать Layout
    resolve: (name) => {
        return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')).then((module) => {
            const page = module.default

            // Если это страница логина, используем AuthLayout
            if (name === 'Auth/Login') {
                page.layout = AuthLayout
            } else {
                // Для всех остальных страниц (дашборд, тенанты, пользователи и т.д.) используем AdminLayout
                page.layout = AdminLayout
            }

            return page
        })
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())

        app.mount(el)
    },
    progress: {
        color: '#667eea',
    },
})
