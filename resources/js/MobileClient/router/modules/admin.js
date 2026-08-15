import { defineAsyncComponent } from 'vue';

const AdminShop = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Shop.vue'));
const AdminAchievements = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/AchievementsAdmin.vue'));
const AdminClients = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Clients.vue'));
const AdminOrders = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Orders.vue'));
const AdminOrderDetails = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/OrderDetails.vue'));
const AdminPartners = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Partners.vue'));
const AdminStories = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/StoryManager.vue'));
const AdminPromoCodes = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Promocodes.vue'));
const AdminStatistic = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Statistic.vue'));
const AdminTenant = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/TenantSettings.vue'));
const AdminKanban = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/KanbanSettings.vue'));
const AdminShopLanding = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/ShopLandingSettings.vue'));
const AdminInvoice = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Invoice.vue'));
const AdminBroadcastsPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/BroadcastsPage.vue'));
const AdminBroadcastCreate = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/BroadcastCreate.vue'));
const TapLinkAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/TapLinkAdmin.vue'));
const AdminRoles = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/AdminRoles.vue'));
const TransactionsAdmin = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/TransactionsAdmin.vue'));
const AdminTablesManager = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Tables/TablesManager.vue'));
const AdminTableDetails = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Tables/Table.vue'));
const AdminTableSettings = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Tables/TableSettings.vue'));
const AdminUserDetails = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/UserDetails.vue'));
const AdminCoffeeScanner = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/CoffeeScanner.vue'));
const AdminCashbackScanner = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/CashbackScanner.vue'));

export default [
    {
        path: '/admin/tenant',
        name: 'AdminTenant',
        component: AdminTenant,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/achievements',
        name: 'AdminAchievements',
        component: AdminAchievements,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/cashback-scanner',
        name: 'CashbackScanner',
        component: AdminCashbackScanner,
        meta: { requiresAuth: true, roles: ['admin', 'super_admin'] }
    },
    {
        path: '/admin/coffee-scanner',
        name: 'CoffeeScanner',
        component: AdminCoffeeScanner,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/transactions',
        name: 'AdminTransactions',
        component: TransactionsAdmin,
        meta: { requiresAuth: true, isAdmin: true }
    },
    {
        path: '/admin/tables',
        name: 'AdminTablesManager',
        component: AdminTablesManager,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/table-settings',
        name: 'AdminTableSettings',
        component: AdminTableSettings,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/tables/:tableId',
        name: 'AdminTableDetails',
        component: AdminTableDetails,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/roles',
        name: 'AdminRoles',
        component: AdminRoles,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/shop',
        name: 'AdminShop',
        component: AdminShop,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_products' }
    },
    {
        path: '/admin/clients',
        name: 'AdminClients',
        component: AdminClients,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_users' }
    },
    {
        path: '/admin/orders',
        name: 'AdminOrders',
        component: AdminOrders,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_orders' }
    },
    {
        path: '/admin/orders/:id',
        name: 'AdminOrderDetails',
        component: AdminOrderDetails,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_orders' }
    },
    {
        path: '/admin/users/:id',
        name: 'AdminUserDetails',
        component: AdminUserDetails,
        meta: { auth: true }
    },
    {
        path: '/admin/partners',
        name: 'AdminPartners',
        component: AdminPartners,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_partners' }
    },
    {
        path: '/admin/stories',
        name: 'AdminStories',
        component: AdminStories,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_stories' }
    },
    {
        path: '/admin/promo-codes',
        name: 'AdminPromoCodes',
        component: AdminPromoCodes,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_promos' }
    },
    {
        path: '/admin/statistic',
        name: 'AdminStatistic',
        component: AdminStatistic,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'view_statistics' }
    },
    {
        path: '/admin/kanban',
        name: 'AdminKanban',
        component: AdminKanban,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_crm' }
    },
    {
        path: '/admin/shop-landing',
        name: 'AdminShopLanding',
        component: AdminShopLanding,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_landing' }
    },
    {
        path: '/admin/invoice',
        name: 'AdminInvoice',
        component: AdminInvoice,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_invoices' }
    },
    {
        path: '/admin/broadcast',
        name: 'AdminBroadcastsPage',
        component: AdminBroadcastsPage,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_broadcasts' }
    },
    {
        path: '/admin/broadcast/create',
        name: 'AdminBroadcastCreate',
        component: AdminBroadcastCreate,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_broadcasts' }
    },
    {
        path: '/admin/tap-links',
        name: 'TapLinkAdmin',
        component: TapLinkAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_taplink' }
    },
];
