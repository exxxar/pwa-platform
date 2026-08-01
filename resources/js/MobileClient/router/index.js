import { createRouter, createWebHashHistory } from 'vue-router';
import { defineAsyncComponent } from 'vue';
import { useTenantAuthStore } from '@/MobileClient/stores/tenantAuth.js';

// ==========================================
// 1. ИМПОРТЫ КОМПОНЕНТОВ (Сгруппированы)
// ==========================================

// --- Основные / Публичные ---
const Menu = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Menu.vue'));
const Catalog = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Catalog.vue'));
const Contacts = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Contacts.vue'));
const FeedBack = defineAsyncComponent(() => import('@/MobileClient/Pages/FeedBack.vue'));
const AboutDeveloper = defineAsyncComponent(() => import('@/MobileClient/Pages/AboutDeveloper.vue'));
const AuthPage = defineAsyncComponent(() => import('@/MobileClient/Pages/AuthPage.vue'));
const TapLink = defineAsyncComponent(() => import('@/MobileClient/Pages/TapLink.vue'));
const ReferralsPage = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ReferralsPage.vue'));

// --- Пользователь (Требуют авторизации) ---
const Profile = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Profile.vue'));
const Orders = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Orders.vue'));
const Cashback = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CashBack.vue'));
const CashbackShop = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CashbackShop.vue'));
const ChatRoom = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Chat.vue'));
const ChatList = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/DialogList.vue'));
const PromoCode = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/PromoCode.vue'));
const Achievements = defineAsyncComponent(() => import('@/MobileClient/Pages/AchievementsPage.vue'));
const ShopCart = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ShopCart.vue'));
const TableBooking = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Bookings.vue'));
const WheelClassic = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Wheel.vue'));
const Coffee = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Coffee.vue'));
const GroceryOrder = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/GroceryOrder.vue'));

// --- Игры и Калькуляторы ---
const GamesCatalog = defineAsyncComponent(() => import('@/MobileClient/Components/Games/GamesCatalog.vue'));
const PrizeCardGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/PrizeCardGame.vue'));
const SlotMachineGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/SlotMachineGame.vue'));
const DailyBonusGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/DailyBonusGame.vue'));
const QuizGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/QuizGame.vue'));
const ScratchCardGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/ScratchCardGame.vue'));
const WheelOfFortune = defineAsyncComponent(() => import('@/MobileClient/Components/Games/WheelOfFortuneClassic.vue'));
const CashbackCardGame = defineAsyncComponent(() => import('@/MobileClient/Components/Games/CashbackCardGame.vue'));

const FoodCalculators = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/FoodCalculators.vue'));
const BurgerCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/BurgerCalculator.vue'));
const PizzaCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/PizzaCalculator.vue'));
const CoffeeCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/CoffeeCalculator.vue'));
const WafflesCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/WafflesCalculator.vue'));
const SushiCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/SushiCalculator.vue'));
const PancakesCalculator = defineAsyncComponent(() => import('@/MobileClient/Components/Food/Calculators/PancakesCalculator.vue'));
const CalculatorPage = defineAsyncComponent(() => import('@/MobileClient/Pages/CalculatorPage.vue'));

// --- Админ-панель ---
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
const TransactionsAdmin = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/TransactionsAdmin.vue')); // Укажите ваш реальный путь
const AdminTablesManager = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/Tables/TablesManager.vue')); // Укажите ваш реальный путь
const AdminTableDetails = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/Tables/Table.vue')); // Укажите ваш реальный путь
const AdminTableSettings = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/Tables/TableSettings.vue')); // Укажите ваш реальный путь
const AdminUserDetails = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/UserDetails.vue')); // Укажите ваш реальный путь
const WheelAdmin = defineAsyncComponent(() =>import('@/MobileClient/Pages/Admin/WheelAdmin.vue')); // Укажите ваш реальный путь

// --- Юридические страницы и 404 ---
const PrivacyPolicy = defineAsyncComponent(() => import('@/MobileClient/Pages/PrivacyPolicy.vue'));
const NotFound = defineAsyncComponent(() => import('@/MobileClient/Pages/NotFound.vue'));


// ==========================================
// 2. КОНФИГУРАЦИЯ МАРШРУТОВ
// ==========================================

const routes = [
    // --- РЕДИРЕКТЫ ---
    { path: '/', redirect: { name: 'Catalog' } },

    // ==========================================
    // ПУБЛИЧНЫЕ МАРШРУТЫ (Доступны всем)
    // ==========================================
    { path: '/menu', name: 'Menu', component: Menu, meta: { public: true } },
    { path: '/catalog', name: 'Catalog', component: Catalog, meta: { public: true } },
    { path: '/contacts', name: 'Contacts', component: Contacts, meta: { public: true } },
    { path: '/feedback', name: 'FeedBack', component: FeedBack, meta: { public: true } },
    { path: '/booking', name: 'TableBooking', component: TableBooking, meta: { public: true } },
    { path: '/wheel-classic', name: 'WheelClassic', component: WheelClassic, meta: { public: true } },
    { path: '/coffee', name: 'Coffee', component: Coffee, meta: { public: true } },
    { path: '/referral', name: 'ReferralsPage', component: ReferralsPage, meta: { public: true } },
    { path: '/about', name: 'AboutDeveloper', component: AboutDeveloper, meta: { public: true, hideBottomMenu: true } },
    { path: '/taplink/:slug?', name: 'TapLink', component: TapLink, meta: { public: true } },
    { path: '/auth', name: 'Auth', component: AuthPage, meta: { public: true } },

    // Юридические
    { path: '/privacy-policy', name: 'PrivacyPolicy', component: PrivacyPolicy, meta: { public: true } },
    { path: '/terms-of-service', name: 'TermsOfService', component: PrivacyPolicy, meta: { public: true } },
    { path: '/cookie-policy', name: 'CookiePolicy', component: PrivacyPolicy, meta: { public: true } },

    // ==========================================
    // МАРШРУТЫ ПОЛЬЗОВАТЕЛЯ (Требуют авторизации)
    // ==========================================
    { path: '/profile', name: 'Profile', component: Profile, meta: { auth: true } },
    { path: '/orders', name: 'Orders', component: Orders, meta: { auth: true } },
    { path: '/cashback', name: 'Cashback', component: Cashback, meta: { auth: true } },
    { path: '/cashback-shop', name: 'CashbackShop', component: CashbackShop, meta: { auth: true, hideBottomMenu: true } },
    { path: '/promo', name: 'PromoCode', component: PromoCode, meta: { auth: true } },
    { path: '/achievements', name: 'Achievements', component: Achievements, meta: { auth: true, title: 'Мои достижения' } },
    {
        path: '/chat',
        name: 'ChatList',
        component: ChatList,
        meta: { auth: true }
    },
    {
        path: '/chat/:id',
        name: 'ChatRoom',
        component: ChatRoom,
        meta: { auth: true, hideBottomMenu: true, hideFooter: true }
    },

    {
        path: '/admin/users/:id',
        name: 'AdminUserDetails',
        component: AdminUserDetails, // Укажите правильный путь
        meta: { auth: true}
    },

    { path: '/cart', name: 'Cart', component: ShopCart, meta: { hideBottomMenu: true } },
    { path: '/grocery-order', name: 'GroceryOrder', component: GroceryOrder, meta: { auth: true, hideBottomMenu: true, title: 'Заказ продуктов' } },
    { path: '/calculator', name: 'Calculator', component: CalculatorPage, meta: { auth: true, hideBottomMenu: true, title: 'Калькулятор стоимости' } },

    // Игры (можно сделать public: true, если разрешено гостям, но обычно требуют auth)
    { path: '/games', name: 'GamesCatalog', component: GamesCatalog, meta: { auth: true } },
    { path: '/games/card-prizes', name: 'PrizeCardGame', component: PrizeCardGame, meta: { auth: true } },
    { path: '/games/slot-machine', name: 'SlotMachineGame', component: SlotMachineGame, meta: { auth: true, title: 'Слот-машина' } },
    { path: '/games/daily-bonus', name: 'DailyBonusGame', component: DailyBonusGame, meta: { auth: true, title: 'Ежедневный бонус' } },
    { path: '/games/quiz', name: 'QuizGame', component: QuizGame, meta: { auth: true, title: 'Викторина' } },
    { path: '/games/scratch-card', name: 'ScratchCardGame', component: ScratchCardGame, meta: { auth: true, title: 'Скретч-карта' } },
    { path: '/games/wheel', name: 'WheelOfFortune', component: WheelOfFortune, meta: { auth: true } },
    { path: '/games/cards', name: 'CashbackCardGame', component: CashbackCardGame, meta: { auth: true } },

    // Калькуляторы еды
    { path: '/food-calculators', name: 'FoodCalculators', component: FoodCalculators, meta: { auth: true, title: 'Калькуляторы еды', hideBottomMenu: true } },
    { path: '/food-calculators/burger', name: 'BurgerCalculator', component: BurgerCalculator, meta: { auth: true, title: 'Собери свой бургер', hideBottomMenu: true } },
    { path: '/food-calculators/pizza', name: 'PizzaCalculator', component: PizzaCalculator, meta: { auth: true, title: 'Калькулятор пиццы', hideBottomMenu: true } },
    { path: '/food-calculators/coffee', name: 'CoffeeCalculator', component: CoffeeCalculator, meta: { auth: true, title: 'Калькулятор кофе', hideBottomMenu: true } },
    { path: '/food-calculators/waffles', name: 'WafflesCalculator', component: WafflesCalculator, meta: { auth: true, title: 'Гонконгские вафли', hideBottomMenu: true } },
    { path: '/food-calculators/sushi', name: 'SushiCalculator', component: SushiCalculator, meta: { auth: true, title: 'Суши и роллы', hideBottomMenu: true } },
    { path: '/food-calculators/pancakes', name: 'PancakesCalculator', component: PancakesCalculator, meta: { auth: true, title: 'Блинчики', hideBottomMenu: true } },

    // ==========================================
    // АДМИН-ПАНЕЛЬ (Требуют auth + roles/permissions)
    // ==========================================
    {
        path: '/admin/tenant', name: 'AdminTenant', component: AdminTenant,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/achievements',
        name: 'AdminAchievements',
        component: AdminAchievements,
        meta: { requiresAuth: true }
    },

    {
        path: '/admin/transactions',
        name: 'AdminTransactions',
        component: TransactionsAdmin,
        meta: {
            requiresAuth: true,
            isAdmin: true // Или ваша проверка на роль админа
        }
    },
    {
        path: '/admin/wheel', name: 'WheelAdmin',component: WheelAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }

    },
    {
        path: '/admin/tables', name: 'AdminTablesManager',component: AdminTablesManager,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }

    },
    {
        path: '/admin/table-settings', name: 'AdminTableSettings',component: AdminTableSettings,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }

    },
    {
        path: '/admin/tables/:tableId', name: 'AdminTableDetails', component: AdminTableDetails,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },

    {
        path: '/admin/roles', name: 'AdminRoles', component: AdminRoles,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_settings' }
    },
    {
        path: '/admin/shop', name: 'AdminShop', component: AdminShop,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_products' }
    },
    {
        path: '/admin/clients', name: 'AdminClients', component: AdminClients,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_users' }
    },
    {
        path: '/admin/orders', name: 'AdminOrders', component: AdminOrders,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_orders' }
    },

    {
        path: '/admin/orders/:id',
        name: 'AdminOrderDetails',
        component: AdminOrderDetails,
        meta: {  auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'manage_orders'}
    },

    {
        path: '/admin/partners', name: 'AdminPartners', component: AdminPartners,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_partners' }
    },
    {
        path: '/admin/stories', name: 'AdminStories', component: AdminStories,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_stories' }
    },
    {
        path: '/admin/promo-codes', name: 'AdminPromoCodes', component: AdminPromoCodes,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_promos' }
    },
    {
        path: '/admin/statistic', name: 'AdminStatistic', component: AdminStatistic,
        meta: { auth: true, roles: ['admin', 'super_admin', 'worker'], permission: 'view_statistics' }
    },
    {
        path: '/admin/kanban', name: 'AdminKanban', component: AdminKanban,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_crm' }
    },
    {
        path: '/admin/shop-landing', name: 'AdminShopLanding', component: AdminShopLanding,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_landing' }
    },
    {
        path: '/admin/invoice', name: 'AdminInvoice', component: AdminInvoice,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_invoices' }
    },
    {
        path: '/admin/broadcast', name: 'AdminBroadcastsPage', component: AdminBroadcastsPage,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_broadcasts' }
    },
    {
        path: '/admin/broadcast/create', name: 'AdminBroadcastCreate', component: AdminBroadcastCreate,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_broadcasts' }
    },
    {
        path: '/admin/tap-links', name: 'TapLinkAdmin', component: TapLinkAdmin,
        meta: { auth: true, roles: ['admin', 'super_admin'], permission: 'manage_taplink' }
    },

    // ==========================================
    // 404 (Всегда в конце)
    // ==========================================
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFound,
        meta: { public: true }
    }
];

// ==========================================
// 3. ИНИЦИАЛИЗАЦИЯ И GUARD
// ==========================================

const router = createRouter({
    history: createWebHashHistory('/pwa'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        return { top: 0, left: 0, behavior: 'smooth' };
    }
});

router.beforeEach((to, from, next) => {
    const authStore = useTenantAuthStore()

    // 1. Публичные страницы доступны всем
    if (to.meta.public) {
        // Если пользователь уже вошел, но пытается зайти на страницу только для гостей (например, /auth)
        if (to.meta.guestOnly && authStore.isAuthenticated) {
            return next({ name: 'Profile' }) // или 'Menu'
        }
        return next()
    }

    // 2. Проверка авторизации
    if (to.meta.auth && !authStore.isAuthenticated) {
        return next({ name: 'Menu' })
    }

    // 3. Проверка ролей (используем готовый метод стора hasAnyRole)
    if (to.meta.roles && Array.isArray(to.meta.roles)) {
        if (!authStore.hasAnyRole(to.meta.roles)) {
            console.warn(`[Router Guard] Доступ запрещен: нет подходящей роли. Требуются: ${to.meta.roles.join(', ')}`)
            return next({ name: 'Menu' }) // В идеале здесь должен быть редирект на страницу 403 Forbidden
        }
    }

    // 4. Проверка конкретного разрешения (permission)
    if (to.meta.permission) {
        // Метод hasPermission в сторе уже проверяет, является ли пользователь super_admin!
        if (!authStore.hasPermission(to.meta.permission)) {
            console.warn(`[Router Guard] Доступ запрещен: нет разрешения '${to.meta.permission}'`)
            return next({ name: 'Menu' })
        }
    }

    if (to.name === 'ChatRoom' && from.name === 'ChatList') {
        to.meta.transition = 'slide-left'; // Заходим в чат
    } else if (to.name === 'ChatList' && from.name === 'ChatRoom') {
        to.meta.transition = 'slide-right'; // Возвращаемся в список
    } else {
        to.meta.transition = 'fade'; // Остальные переходы
    }

    // Все проверки пройдены
    next()
})

export default router;
