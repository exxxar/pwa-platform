import { createRouter, createWebHashHistory,createWebHistory } from 'vue-router'
import { useTenantAuthStore } from '@/MobileClient/stores/tenantAuth.js'
import { defineAsyncComponent } from 'vue'


const Menu = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Menu.vue'))
const Profile = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Profile.vue'))
const Catalog = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Catalog.vue'))
const Chat = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Chat.vue'))
const Cart = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/ShopCart.vue'))
const Contacts = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Contacts.vue'))
const Cashback = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/CashBack.vue'))
const Orders = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Orders.vue'))
const FeedBack = defineAsyncComponent(() => import('@/MobileClient/Pages/FeedBack.vue'))
const TableBooking = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Bookings.vue'))
const WheelClassic = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Wheel.vue'))
const Coffee = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/Coffee.vue'))
const NotFound = defineAsyncComponent(() => import('@/MobileClient/Pages/NotFound.vue'))
const PromoCode = defineAsyncComponent(() => import('@/MobileClient/Pages/Shop/PromoCode.vue'))

const AdminShop = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Shop.vue'))
const AdminInvoice = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Invoice.vue'))
const AdminPartners = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/Partners.vue'))
const AdminStories = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/StoryManager.vue'))
const AdminShopLanding = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/ShopLandingSettings.vue'))

// ADMIN
const Settings = defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/TenantSettings.vue'))

const routes = [
    // Redirect с корня на меню
    { path: '/', redirect: '/menu' },

    // Публичные маршруты (доступны без авторизации)
    {
        path: '/menu',
        name: 'Menu',
        component: Menu,
        meta: { public: true }
    },
    {
        path: '/coffee',
        name: 'Coffee',
        component: Coffee,
        meta: { public: true }
    },
    {
        path: '/wheel-classic',
        name: 'WheelClassic',
        component: WheelClassic,
        meta: { public: true }
    },
    {
        path: '/contacts',
        name: 'Contacts',
        component: Contacts,
        meta: { public: true }
    },
    {
        path: '/booking',
        name: 'TableBooking',
        component: TableBooking,
        meta: { public: true }
    },
    {
        path: '/feedback',
        name: 'FeedBack',
        component: FeedBack,
        meta: { public: true }
    },
    {
        path: '/catalog',
        name: 'Catalog',
        component: Catalog,
        meta: { public: true }
    },

    // Приватные маршруты (требуют авторизации)
    {
        path: '/profile',
        name: 'Profile',
        component: Profile,
        meta: { auth: true }
    },
    {
        path: '/promo',
        name: 'PromoCode',
        component: PromoCode,
        meta: { auth: true }
    },
    {
        path: '/orders',
        name: 'Orders',
        component: Orders,
        meta: { auth: true }
    },
    {
        path: '/cashback',
        name: 'Cashback',
        component: Cashback,
        meta: { auth: true }
    },
    {
        path: '/chat',
        name: 'Chat',
        component: Chat,
        meta: { auth: true }
    },
    {
        path: '/games',
        name: 'GamesCatalog',
        component: () => import('@/MobileClient/Components/Games/GamesCatalog.vue'),
    },
    {
        path: '/games/card-prizes',
        name: 'PrizeCardGame',
        component: () => import('@/MobileClient/Components/Games/PrizeCardGame.vue'),
    },
    {
        path: '/games/slot-machine',
        name: 'SlotMachineGame',
        component: () => import('@/MobileClient/Components/Games/SlotMachineGame.vue'),
        meta: { title: 'Слот-машина' }
    },
    {
        path: '/games/daily-bonus',
        name: 'DailyBonusGame',
        component: () => import('@/MobileClient/Components/Games/DailyBonusGame.vue'),
        meta: { title: 'Ежедневный бонус' }
    },
    {
        path: '/games/quiz',
        name: 'QuizGame',
        component: () => import('@/MobileClient/Components/Games/QuizGame.vue'),
        meta: { title: 'Викторина' }
    },
    {
        path: '/games/scratch-card',
        name: 'ScratchCardGame',
        component: () => import('@/MobileClient/Components/Games/ScratchCardGame.vue'),
        meta: { title: 'Скретч-карта' }
    },
    {
        path: '/games/wheel',
        name: 'WheelOfFortune',
        component: () => import('@/MobileClient/Components/Games/WheelOfFortuneClassic.vue'),
    },
    {
        path: '/games/cards',
        name: 'CashbackCardGame',
        component: () => import('@/MobileClient/Components/Games/CashbackCardGame.vue'),
    },
    {
        path: '/games/cards',
        name: 'CashbackCardGame',
        component: () => import('@/MobileClient/Components/Games/CashbackCardGame.vue'),
    },
    {
        path: '/food-calculators',
        name: 'FoodCalculators',
        component: () => import('@/MobileClient/Components/Food/Calculators/FoodCalculators.vue'),
        meta: { title: 'Калькуляторы еды' },
    },
    {
        path: '/grocery-order',
        name: 'GroceryOrder',
        component: () => import('@/MobileClient/Pages/Shop/GroceryOrder.vue'), // Укажи правильный путь
        meta: {
            title: 'Заказ продуктов',
            hideBottomMenu: true,
            requiresAuth: true // Если нужна авторизация
        }
    },
    {
        path: '/food-calculators/burger',
        name: 'BurgerCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/BurgerCalculator.vue'),
        meta: { title: 'Собери свой бургер',   hideBottomMenu: true, }
    },
    {
        path: '/food-calculators/pizza',
        name: 'PizzaCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/PizzaCalculator.vue'),
        meta: { title: 'Калькулятор пиццы',  hideBottomMenu: true },
    },
    {
        path: '/food-calculators/coffee',
        name: 'CoffeeCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/CoffeeCalculator.vue'),
        meta: { title: 'Калькулятор кофе',  hideBottomMenu: true },
    },
    {
        path: '/food-calculators/waffles',
        name: 'WafflesCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/WafflesCalculator.vue'),
        meta: { title: 'Гонконгские вафли',  hideBottomMenu: true },
    },
    {
        path: '/food-calculators/sushi',
        name: 'SushiCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/SushiCalculator.vue'),
        meta: { title: 'Суши и роллы',  hideBottomMenu: true },
    },
    {
        path: '/food-calculators/pancakes',
        name: 'PancakesCalculator',
        component: () => import('@/MobileClient/Components/Food/Calculators/PancakesCalculator.vue'),
        meta: { title: 'Блинчики',  hideBottomMenu: true  },
    },
    {
        path: '/cashback-shop',
        name: 'CashbackShop',
        component: () => import('@/MobileClient/Pages/Shop/CashbackShop.vue'),
        meta: { title: 'Магазин бонусов',  hideBottomMenu: true },
    },
    {
        path: '/cart',
        name: 'Cart',
        component: Cart,
        meta: { hideBottomMenu: true }
    },

    // Админка (если нужна)
    {
        path: '/settings',
        name: 'Settings',
        component: Settings,
        meta: { auth: true, role: 'admin' }
    },
// В массив routes добавь:
    {
        path: '/taplink/:slug?', // ? делает slug опциональным, если нужно использовать текущий tenant
        name: 'TapLink',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/TapLink.vue')),
        meta: { public: true } // Важно: доступно без авторизации!
    },
    {
        path: '/auth',
        name: 'Auth',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/AuthPage.vue')),
        meta: {
            public: true,
            guestOnly: true // Кастомное мета-поле для проверки в глобальном guard
        }
    },
    {
        path: '/about',
        name: 'AboutDeveloper',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/AboutDeveloper.vue')),
        meta: { public: true }
    },
    {
        path: '/admin/tap-links',
        name: 'TapLinkAdmin',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/Admin/TapLinkAdmin.vue')),
        meta: { auth: true, role: 'admin' } // Доступ только админу/владельцу
    },

    {
        path: '/admin/shop-landing',
        name: 'AdminShopLanding',
        component:AdminShopLanding,
        meta: { auth: true, /*role: 'admin' */} // Доступ только админу/владельцу
    },

    {
        path: '/admin/stories',
        name: 'AdminStories',
        component:AdminStories,
        meta: { auth: true, /*role: 'admin' */} // Доступ только админу/владельцу
    },
    {
        path: '/admin/invoice',
        name: 'AdminInvoice',
        component:AdminInvoice,
        meta: { auth: true, /*role: 'admin' */} // Доступ только админу/владельцу
    },
    {
        path: '/admin/partners',
        name: 'AdminPartners',
        component:AdminPartners,
        meta: { auth: true, /*role: 'admin' */} // Доступ только админу/владельцу
    },
    {
        path: '/privacy-policy',
        name: 'PrivacyPolicy',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/PrivacyPolicy.vue')),
        meta: { public: true }
    },
    {
        path: '/terms-of-service',
        name: 'TermsOfService',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/PrivacyPolicy.vue')), // Можно использовать тот же компонент с другим текстом
        meta: { public: true }
    },
    {
        path: '/cookie-policy',
        name: 'CookiePolicy',
        component: defineAsyncComponent(() => import('@/MobileClient/Pages/PrivacyPolicy.vue')), // Или создать отдельный
        meta: { public: true }
    },
    {
        path: '/admin/shop',
        name: 'AdminShop',
        component: AdminShop,
    },

    // 404 страница
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFound,
        meta: { public: true }
    }
]

const router = createRouter({
    history: createWebHashHistory('/pwa'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        }
        return { top: 0, left: 0 }
    }
})

// Глобальный guard
router.beforeEach((to, from, next) => {
    const auth = useTenantAuthStore()

    // Публичные страницы доступны всем
    if (to.meta.public) {
        return next()
    }

    // Проверка авторизации
    if (to.meta.auth && !auth.isAuthenticated) {
        return next('/menu') // Редирект на меню вместо /login
    }

    // Проверка роли
    if (to.meta.role && !auth.hasRole(to.meta.role)) {
        return next('/menu')
    }

    next()
})

export default router
