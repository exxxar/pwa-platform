import { defineAsyncComponent } from 'vue';

const DeliveryMain = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/DeliverymanDashboard.vue'));
const AvailableShops = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/AvailableShops.vue'));


export default [
    {
        path: '/delivery/delivery-main',
        name: 'DeliverymanDashboard',
        component: DeliveryMain,
        meta: { auth: true, roles: ['admin', 'super_admin'] }
    },
    {
        path: '/delivery/shops',
        name: 'AvailableShops',
        component: AvailableShops,
        meta: { auth: true, roles: ['admin', 'super_admin'] }
    },


];
