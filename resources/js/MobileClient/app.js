
import 'bootstrap/dist/css/bootstrap.min.css'

import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap

import '@fortawesome/fontawesome-free/css/all.min.css'

import '@/MobileClient/bootstrap';
import '../../css/MobileClient/app.css';
import Preloader from './Modules/preloader.js'

import NotificationPlugin from '@/MobileClient/Components/Notifications/notificationPlugin.js';

import moment from 'moment'
import Vue3TouchEvents from "vue3-touch-events";
import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../../vendor/tightenco/ziggy/dist';
import { createPinia, setActivePinia } from 'pinia' // 👈 Добавляем setActivePinia
import router from './router'
import { registerSW } from '@/MobileClient/sw/register.js'
import ProductInfo from './Modules/products.js'
import './plugins/axiosQueue.js'

import VueLazyLoad from 'vue3-lazyload'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const tenant = window.Tenant || null


createInertiaApp({
    title: (title) => `${tenant?.name || ''} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // 1. Сначала создаем и подключаем Pinia (до всех остальных плагинов)
        const pinia = createPinia();
        app.use(pinia);

        // 2. Подключаем Inertia plugin и роутер
        app.use(plugin)
            .use(router);

        // 3. Остальные плагины
        app.use(ZiggyVue)
            .use(NotificationPlugin, { position: 'top-right' })
            .use(Vue3TouchEvents)
            .use(VueLazyLoad, {
                loading: '../pwa-lazy.jpg',
                error: '../pwa-lazy.jpg'
            });

        // 4. Глобальные свойства и директивы
        app.directive('can', {
            mounted(el, binding) {
                const user = window.TenantUser || {};
                const value = binding.value;
                let hasAccess = false;

                if (typeof value === 'string' || Array.isArray(value)) {
                    const rolesToCheck = Array.isArray(value) ? value : [value];
                    hasAccess = rolesToCheck.some(role => user.role_names?.includes(role));
                } else if (typeof value === 'object' && value.permission) {
                    const permsToCheck = Array.isArray(value.permission) ? value.permission : [value.permission];
                    hasAccess = user.role_names?.includes('super_admin')
                        ? true
                        : permsToCheck.some(perm => user.permission_names?.includes(perm));
                }

                if (!hasAccess) {
                    el.parentNode?.removeChild(el);
                }
            }
        });

        app.config.globalProperties.$filters = {
            local: (date) => moment(date).format("YYYY-MM-DDThh:mm"),
            timeAgo: (date) => moment(date).fromNow(),
            current: (date) => moment(date).format("YYYY-MM-DD"),
            currentFull: (date) => moment(date).format("YYYY-MM-DD HH:mm:ss"),
        };

        app.config.globalProperties.$tenant = {
            ...window.Tenant,
            auth: window.TenantAuth || null,
            user: window.TenantUser || null
        };
        app.config.globalProperties.$productInfo = ProductInfo;
        app.config.globalProperties.$preloader = Preloader;

        // 5. Монтируем приложение в самом конце
        return app.mount(el);
    },
    progress: { color: '#4B5563' },
});

if (import.meta.env.PROD) { // Только в production!
    registerSW();
}
