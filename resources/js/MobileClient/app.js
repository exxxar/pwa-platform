
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
import { createPinia } from 'pinia'
import router from './router'
import { registerSW } from '@/MobileClient/sw/register.js'
import ProductInfo from './Modules/products.js'

import VueLazyLoad from 'vue3-lazyload'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {

        const app = createApp({render: () => h(App, props)});

        app.config.globalProperties.$filters = {
            local(date){
                return moment(date).format("YYYY-MM-DDThh:mm")
            },
            timeAgo(date) {
                return moment(date).fromNow()
            },
            current(date) {
                return moment(date).format("YYYY-MM-DD")
            },
            currentFull(date) {
                return moment(date).format("YYYY-MM-DD HH:mm:ss")
            },
        }

        app.config.globalProperties.$tenant = {
            ...window.Tenant,
            auth:  window.TenantAuth || null,
            user: window.TenantUser || null
        }
        app.config.globalProperties.$productInfo = ProductInfo
        app.config.globalProperties.$preloader = Preloader

        return app
            .use(NotificationPlugin, {
                position: 'top-right', // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
            })
            .use(plugin)
            .use(createPinia())
            .use(router)
            .use(ZiggyVue)
            .use(Vue3TouchEvents)
            .use(VueLazyLoad,
                {
                    loading: '../pwa-lazy.jpg',
                    error: '../pwa-lazy.jpg'
                })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

if (import.meta.env.PROD) { // Только в production!
    registerSW();
}
