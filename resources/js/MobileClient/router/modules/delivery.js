import { defineAsyncComponent } from 'vue';

const DeliveryMain = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/DeliverymanDashboard.vue'));
const AvailableShops = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/AvailableShops.vue'));
const DeliveryCalculator = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/DeliveryCalculator.vue'));
const AdminDeliverymen = defineAsyncComponent(() => import('@/MobileClient/Pages/Delivery/AdminDeliverymen.vue'));


export default [
    { path: '/delivery', redirect: { name: 'DeliverymanDashboard' } },
    {
        path: '/delivery/delivery-main',
        name: 'DeliverymanDashboard',
        component: DeliveryMain,
        roles: ['super_admin', "senior_delivery", 'admin','deliveryman']
    },
    {
        path: '/delivery/shops',
        name: 'AvailableShops',
        component: AvailableShops,
        roles: ['super_admin', "senior_delivery", 'admin','deliveryman']
    },
    {
        path: '/admin/deliverymen',
        name: 'AdminDeliverymen',
        component: AdminDeliverymen,
        meta: {
            requiresAuth: true,
            roles: ['super_admin', "senior_delivery", 'admin'] // Только админы могут заходить сюда
        }
    },

    {
        path: '/delivery/calc',
        name: 'DeliveryCalculator',
        component: DeliveryCalculator,
        roles: ['super_admin', "senior_delivery", 'deliveryman', 'admin']
    },


];
