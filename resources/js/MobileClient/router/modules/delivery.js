import { defineAsyncComponent } from 'vue';

const DeliveryMain = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/DeliverymanDashboard.vue'));
const AvailableShops = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/AvailableShops.vue'));
const DeliveryCalculator = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/DeliveryCalculator.vue'));


export default [
    { path: '/delivery', redirect: { name: 'DeliverymanDashboard' } },
    {
        path: '/delivery/delivery-main',
        name: 'DeliverymanDashboard',
        component: DeliveryMain,
    },
    {
        path: '/delivery/shops',
        name: 'AvailableShops',
        component: AvailableShops,
    },

    {
        path: '/delivery/calc',
        name: 'DeliveryCalculator',
        component: DeliveryCalculator,
    },


];
