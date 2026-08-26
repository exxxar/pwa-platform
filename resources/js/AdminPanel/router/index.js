import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useNotifications } from '../composables/useNotifications'

// ==========================================
// 📄 LAYOUTS
// ==========================================

const AdminLayout = () => import('../layouts/AdminLayout.vue')
const AuthLayout = () => import('../layouts/AuthLayout.vue')

// ==========================================
// 🔐 AUTH PAGES
// ==========================================

const Login = () => import('../pages/Auth/Login.vue')

// ==========================================
// 📊 DASHBOARD
// ==========================================

const Dashboard = () => import('../pages/Dashboard/Index.vue')

// ==========================================
// 🌐 GLOBAL PAGES
// ==========================================

// Тенанты
const TenantsIndex = () => import('../pages/Tenants/Index.vue')
const TenantsCreate = () => import('../pages/Tenants/Create.vue')
const TenantsShow = () => import('../pages/Tenants/Show.vue')
const TenantsEdit = () => import('../pages/Tenants/Edit.vue')

// Глобальные админы
const AdminUsersIndex = () => import('../pages/AdminUsers/Index.vue')
const AdminUsersCreate = () => import('../pages/AdminUsers/Create.vue')
const AdminUsersEdit = () => import('../pages/AdminUsers/Edit.vue')

// Роли
const RolesIndex = () => import('../pages/Roles/Index.vue')
const RolesCreate = () => import('../pages/Roles/Create.vue')
const RolesEdit = () => import('../pages/Roles/Edit.vue')

// Разрешения
const PermissionsIndex = () => import('../pages/Permissions/Index.vue')
const PermissionsCreate = () => import('../pages/Permissions/Create.vue')
const PermissionsEdit = () => import('../pages/Permissions/Edit.vue')

// Системные настройки
const SettingsIndex = () => import('../pages/Settings/Index.vue')

// ==========================================
// 🏢 TENANT DATA PAGES
// ==========================================

// Пользователи тенантов
const TenantUsersIndex = () => import('../pages/TenantUsers/Index.vue')
const TenantUsersShow = () => import('../pages/TenantUsers/Show.vue')
const TenantUsersEdit = () => import('../pages/TenantUsers/Edit.vue')

// Заказы
const OrdersIndex = () => import('../pages/Orders/Index.vue')
const OrdersShow = () => import('../pages/Orders/Show.vue')

// Товары
const ProductsIndex = () => import('../pages/Products/Index.vue')
const ProductsCreate = () => import('../pages/Products/Create.vue')
const ProductsEdit = () => import('../pages/Products/Edit.vue')

// Транзакции
const TransactionsIndex = () => import('../pages/Transactions/Index.vue')
const TransactionsShow = () => import('../pages/Transactions/Show.vue')

// Диалоги
const DialogsIndex = () => import('../pages/Dialogs/Index.vue')
const DialogsShow = () => import('../pages/Dialogs/Show.vue')

// Кэшбэк
const CashbackIndex = () => import('../pages/Cashback/Index.vue')

// Рефералы
const ReferralsIndex = () => import('../pages/Referrals/Index.vue')
const ReferralsChain = () => import('../pages/Referrals/Chain.vue')
const ReferralsStats = () => import('../pages/Referrals/Stats.vue')

// ==========================================
// 📊 REPORTS & EXPORTS
// ==========================================

const ReportsDashboard = () => import('../pages/Reports/Dashboard.vue')
const ReportsTenantStats = () => import('../pages/Reports/TenantStats.vue')

// ==========================================
// ❌ ERROR PAGES
// ==========================================

const NotFound = () => import('../pages/Errors/404.vue')
const Forbidden = () => import('../pages/Errors/403.vue')

// ==========================================
// 🛡️ ROUTE GUARDS
// ==========================================

/**
 * Guard для проверки авторизации
 */
const requireAuth = (to, from, next) => {
    const authStore = useAuthStore()

    if (!authStore.isAuthenticated) {
        next({
            name: 'admin.login',
            query: { redirect: to.fullPath }
        })
    } else {
        next()
    }
}

/**
 * Guard для проверки наличия разрешения
 */
const requirePermission = (permission) => {
    return (to, from, next) => {
        const authStore = useAuthStore()

        if (!authStore.isAuthenticated) {
            next({ name: 'admin.login' })
            return
        }

        if (!authStore.hasPermission(permission)) {
            const notifications = useNotifications()
            notifications.error('У вас нет прав для доступа к этой странице')
            next({ name: 'admin.forbidden' })
            return
        }

        next()
    }
}

/**
 * Guard для проверки наличия роли
 */
const requireRole = (role) => {
    return (to, from, next) => {
        const authStore = useAuthStore()

        if (!authStore.isAuthenticated) {
            next({ name: 'admin.login' })
            return
        }

        if (!authStore.hasRole(role)) {
            const notifications = useNotifications()
            notifications.error('У вас нет прав для доступа к этой странице')
            next({ name: 'admin.forbidden' })
            return
        }

        next()
    }
}

// ==========================================
// 🛣️ ROUTES
// ==========================================

const routes = [
    // --- AUTH ROUTES ---
    {
        path: '/admin/login',
        component: AuthLayout,
        children: [
            {
                path: '',
                name: 'admin.login',
                component: Login,
                meta: {
                    title: 'Вход в систему',
                    guest: true,
                },
            },
        ],
    },

    // --- ADMIN ROUTES (требуют авторизации) ---
    {
        path: '/admin',
        component: AdminLayout,
        beforeEnter: requireAuth,
        children: [
            // Dashboard
            {
                path: '',
                name: 'admin.dashboard',
                component: Dashboard,
                meta: {
                    title: 'Главная',
                    icon: 'dashboard',
                    breadcrumb: 'Главная',
                },
            },

            // --- GLOBAL: TENANTS ---
            {
                path: 'tenants',
                name: 'admin.tenants.index',
                component: TenantsIndex,
                beforeEnter: requirePermission('tenants.view'),
                meta: {
                    title: 'Тенанты',
                    icon: 'building',
                    breadcrumb: 'Тенанты',
                    permission: 'tenants.view',
                },
            },
            {
                path: 'tenants/create',
                name: 'admin.tenants.create',
                component: TenantsCreate,
                beforeEnter: requirePermission('tenants.create'),
                meta: {
                    title: 'Создать тенанта',
                    breadcrumb: 'Создать',
                    parent: 'admin.tenants.index',
                    permission: 'tenants.create',
                },
            },
            {
                path: 'tenants/:id',
                name: 'admin.tenants.show',
                component: TenantsShow,
                beforeEnter: requirePermission('tenants.view'),
                meta: {
                    title: 'Тенант',
                    breadcrumb: 'Просмотр',
                    parent: 'admin.tenants.index',
                    permission: 'tenants.view',
                },
            },
            {
                path: 'tenants/:id/edit',
                name: 'admin.tenants.edit',
                component: TenantsEdit,
                beforeEnter: requirePermission('tenants.update'),
                meta: {
                    title: 'Редактировать тенанта',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.tenants.show',
                    permission: 'tenants.update',
                },
            },

            // --- GLOBAL: ADMIN USERS ---
            {
                path: 'admin-users',
                name: 'admin.admin-users.index',
                component: AdminUsersIndex,
                beforeEnter: requirePermission('admin_users.view'),
                meta: {
                    title: 'Администраторы',
                    icon: 'users',
                    breadcrumb: 'Администраторы',
                    permission: 'admin_users.view',
                },
            },
            {
                path: 'admin-users/create',
                name: 'admin.admin-users.create',
                component: AdminUsersCreate,
                beforeEnter: requirePermission('admin_users.create'),
                meta: {
                    title: 'Создать администратора',
                    breadcrumb: 'Создать',
                    parent: 'admin.admin-users.index',
                    permission: 'admin_users.create',
                },
            },
            {
                path: 'admin-users/:id/edit',
                name: 'admin.admin-users.edit',
                component: AdminUsersEdit,
                beforeEnter: requirePermission('admin_users.update'),
                meta: {
                    title: 'Редактировать администратора',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.admin-users.index',
                    permission: 'admin_users.update',
                },
            },

            // --- GLOBAL: ROLES ---
            {
                path: 'roles',
                name: 'admin.roles.index',
                component: RolesIndex,
                beforeEnter: requirePermission('roles.view'),
                meta: {
                    title: 'Роли',
                    icon: 'shield',
                    breadcrumb: 'Роли',
                    permission: 'roles.view',
                },
            },
            {
                path: 'roles/create',
                name: 'admin.roles.create',
                component: RolesCreate,
                beforeEnter: requirePermission('roles.create'),
                meta: {
                    title: 'Создать роль',
                    breadcrumb: 'Создать',
                    parent: 'admin.roles.index',
                    permission: 'roles.create',
                },
            },
            {
                path: 'roles/:id/edit',
                name: 'admin.roles.edit',
                component: RolesEdit,
                beforeEnter: requirePermission('roles.update'),
                meta: {
                    title: 'Редактировать роль',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.roles.index',
                    permission: 'roles.update',
                },
            },

            // --- GLOBAL: PERMISSIONS ---
            {
                path: 'permissions',
                name: 'admin.permissions.index',
                component: PermissionsIndex,
                beforeEnter: requirePermission('permissions.view'),
                meta: {
                    title: 'Разрешения',
                    icon: 'key',
                    breadcrumb: 'Разрешения',
                    permission: 'permissions.view',
                },
            },
            {
                path: 'permissions/create',
                name: 'admin.permissions.create',
                component: PermissionsCreate,
                beforeEnter: requirePermission('permissions.create'),
                meta: {
                    title: 'Создать разрешение',
                    breadcrumb: 'Создать',
                    parent: 'admin.permissions.index',
                    permission: 'permissions.create',
                },
            },
            {
                path: 'permissions/:id/edit',
                name: 'admin.permissions.edit',
                component: PermissionsEdit,
                beforeEnter: requirePermission('permissions.update'),
                meta: {
                    title: 'Редактировать разрешение',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.permissions.index',
                    permission: 'permissions.update',
                },
            },

            // --- GLOBAL: SETTINGS ---
            {
                path: 'settings',
                name: 'admin.settings.index',
                component: SettingsIndex,
                beforeEnter: requirePermission('settings.view'),
                meta: {
                    title: 'Настройки',
                    icon: 'settings',
                    breadcrumb: 'Настройки',
                    permission: 'settings.view',
                },
            },

            // --- TENANT DATA: USERS ---
            {
                path: 'tenant-users',
                name: 'admin.tenant-users.index',
                component: TenantUsersIndex,
                beforeEnter: requirePermission('tenant_users.view'),
                meta: {
                    title: 'Пользователи',
                    icon: 'user',
                    breadcrumb: 'Пользователи',
                    permission: 'tenant_users.view',
                },
            },
            {
                path: 'tenant-users/:id',
                name: 'admin.tenant-users.show',
                component: TenantUsersShow,
                beforeEnter: requirePermission('tenant_users.view'),
                meta: {
                    title: 'Пользователь',
                    breadcrumb: 'Просмотр',
                    parent: 'admin.tenant-users.index',
                    permission: 'tenant_users.view',
                },
            },
            {
                path: 'tenant-users/:id/edit',
                name: 'admin.tenant-users.edit',
                component: TenantUsersEdit,
                beforeEnter: requirePermission('tenant_users.update'),
                meta: {
                    title: 'Редактировать пользователя',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.tenant-users.show',
                    permission: 'tenant_users.update',
                },
            },

            // --- TENANT DATA: ORDERS ---
            {
                path: 'orders',
                name: 'admin.orders.index',
                component: OrdersIndex,
                beforeEnter: requirePermission('orders.view'),
                meta: {
                    title: 'Заказы',
                    icon: 'shopping-cart',
                    breadcrumb: 'Заказы',
                    permission: 'orders.view',
                },
            },
            {
                path: 'orders/:id',
                name: 'admin.orders.show',
                component: OrdersShow,
                beforeEnter: requirePermission('orders.view'),
                meta: {
                    title: 'Заказ',
                    breadcrumb: 'Просмотр',
                    parent: 'admin.orders.index',
                    permission: 'orders.view',
                },
            },

            // --- TENANT DATA: PRODUCTS ---
            {
                path: 'products',
                name: 'admin.products.index',
                component: ProductsIndex,
                beforeEnter: requirePermission('products.view'),
                meta: {
                    title: 'Товары',
                    icon: 'package',
                    breadcrumb: 'Товары',
                    permission: 'products.view',
                },
            },
            {
                path: 'products/create',
                name: 'admin.products.create',
                component: ProductsCreate,
                beforeEnter: requirePermission('products.create'),
                meta: {
                    title: 'Создать товар',
                    breadcrumb: 'Создать',
                    parent: 'admin.products.index',
                    permission: 'products.create',
                },
            },
            {
                path: 'products/:id/edit',
                name: 'admin.products.edit',
                component: ProductsEdit,
                beforeEnter: requirePermission('products.update'),
                meta: {
                    title: 'Редактировать товар',
                    breadcrumb: 'Редактировать',
                    parent: 'admin.products.index',
                    permission: 'products.update',
                },
            },

            // --- TENANT DATA: TRANSACTIONS ---
            {
                path: 'transactions',
                name: 'admin.transactions.index',
                component: TransactionsIndex,
                beforeEnter: requirePermission('transactions.view'),
                meta: {
                    title: 'Транзакции',
                    icon: 'credit-card',
                    breadcrumb: 'Транзакции',
                    permission: 'transactions.view',
                },
            },
            {
                path: 'transactions/:id',
                name: 'admin.transactions.show',
                component: TransactionsShow,
                beforeEnter: requirePermission('transactions.view'),
                meta: {
                    title: 'Транзакция',
                    breadcrumb: 'Просмотр',
                    parent: 'admin.transactions.index',
                    permission: 'transactions.view',
                },
            },

            // --- TENANT DATA: DIALOGS ---
            {
                path: 'dialogs',
                name: 'admin.dialogs.index',
                component: DialogsIndex,
                beforeEnter: requirePermission('dialogs.view'),
                meta: {
                    title: 'Диалоги',
                    icon: 'message-circle',
                    breadcrumb: 'Диалоги',
                    permission: 'dialogs.view',
                },
            },
            {
                path: 'dialogs/:id',
                name: 'admin.dialogs.show',
                component: DialogsShow,
                beforeEnter: requirePermission('dialogs.view'),
                meta: {
                    title: 'Диалог',
                    breadcrumb: 'Просмотр',
                    parent: 'admin.dialogs.index',
                    permission: 'dialogs.view',
                },
            },

            // --- TENANT DATA: CASHBACK ---
            {
                path: 'cashback',
                name: 'admin.cashback.index',
                component: CashbackIndex,
                beforeEnter: requirePermission('cashback.view'),
                meta: {
                    title: 'Кэшбэк',
                    icon: 'gift',
                    breadcrumb: 'Кэшбэк',
                    permission: 'cashback.view',
                },
            },

            // --- TENANT DATA: REFERRALS ---
            {
                path: 'referrals',
                name: 'admin.referrals.index',
                component: ReferralsIndex,
                beforeEnter: requirePermission('referrals.view'),
                meta: {
                    title: 'Рефералы',
                    icon: 'users',
                    breadcrumb: 'Рефералы',
                    permission: 'referrals.view',
                },
            },
            {
                path: 'referrals/chain/:userId',
                name: 'admin.referrals.chain',
                component: ReferralsChain,
                beforeEnter: requirePermission('referrals.view'),
                meta: {
                    title: 'Цепочка рефералов',
                    breadcrumb: 'Цепочка',
                    parent: 'admin.referrals.index',
                    permission: 'referrals.view',
                },
            },
            {
                path: 'referrals/stats',
                name: 'admin.referrals.stats',
                component: ReferralsStats,
                beforeEnter: requirePermission('referrals.view'),
                meta: {
                    title: 'Статистика рефералов',
                    breadcrumb: 'Статистика',
                    parent: 'admin.referrals.index',
                    permission: 'referrals.view',
                },
            },

            // --- REPORTS ---
            {
                path: 'reports/dashboard',
                name: 'admin.reports.dashboard',
                component: ReportsDashboard,
                beforeEnter: requirePermission('reports.view'),
                meta: {
                    title: 'Отчеты',
                    icon: 'bar-chart',
                    breadcrumb: 'Отчеты',
                    permission: 'reports.view',
                },
            },
            {
                path: 'reports/tenant/:tenantId',
                name: 'admin.reports.tenant-stats',
                component: ReportsTenantStats,
                beforeEnter: requirePermission('reports.view'),
                meta: {
                    title: 'Статистика тенанта',
                    breadcrumb: 'Статистика',
                    parent: 'admin.reports.dashboard',
                    permission: 'reports.view',
                },
            },

            // --- ERROR PAGES ---
            {
                path: '403',
                name: 'admin.forbidden',
                component: Forbidden,
                meta: {
                    title: 'Доступ запрещен',
                },
            },
        ],
    },

    // --- 404 ---
    {
        path: '/:pathMatch(.*)*',
        name: 'admin.not-found',
        component: NotFound,
        meta: {
            title: 'Страница не найдена',
        },
    },
]

// ==========================================
// 🚀 CREATE ROUTER
// ==========================================

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        } else {
            return { top: 0 }
        }
    },
})

// ==========================================
// 🛡️ GLOBAL NAVIGATION GUARD
// ==========================================

router.beforeEach((to, from, next) => {
    // Устанавливаем title страницы
    document.title = to.meta.title
        ? `${to.meta.title} | Админ-панель`
        : 'Админ-панель'

    // Если пользователь авторизован и пытается зайти на guest страницу
    const authStore = useAuthStore()
    if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'admin.dashboard' })
        return
    }

    next()
})

export default router
