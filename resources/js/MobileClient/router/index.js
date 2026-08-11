import { createRouter, createWebHashHistory } from 'vue-router';
import { defineAsyncComponent } from 'vue';
import { useTenantAuthStore } from '@/MobileClient/stores/tenantAuth.js';

// Импортируем группы маршрутов
import publicRoutes from './modules/public';
import authRoutes from './modules/auth';
import shopRoutes from './modules/shop';
import gamesRoutes from './modules/games';
import adminRoutes from './modules/admin';
import legalRoutes from './modules/legal';


// 404
const NotFound = defineAsyncComponent(() => import('@/MobileClient/Pages/NotFound.vue'));

// Объединяем все маршруты
const routes = [
    ...publicRoutes,
    ...authRoutes,
    ...shopRoutes,
    ...gamesRoutes,
    ...adminRoutes,
    ...legalRoutes,


    // 404 (всегда в конце)
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFound,
        meta: { public: true }
    }
];

// Создание роутера
const router = createRouter({
    history: createWebHashHistory('/pwa'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        return { top: 0, left: 0, behavior: 'smooth' };
    }
});

// ==========================================
// 🔐 ГЛОБАЛЬНЫЙ GUARD
// ==========================================
router.beforeEach((to, from, next) => {
    const authStore = useTenantAuthStore();

    // 1. Публичные страницы доступны всем
    if (to.meta.public) {
        if (to.meta.guestOnly && authStore.isAuthenticated) {
            return next({ name: 'Profile' });
        }
        return next();
    }

    // 2. Проверка авторизации
    if (to.meta.auth && !authStore.isAuthenticated) {
        return next({ name: 'Menu' });
    }

    // 3. Проверка ролей
    if (to.meta.roles && Array.isArray(to.meta.roles)) {
        if (!authStore.hasAnyRole(to.meta.roles)) {
            console.warn(`[Router Guard] Доступ запрещен: нет подходящей роли. Требуются: ${to.meta.roles.join(', ')}`);
            return next({ name: 'Menu' });
        }
    }

    // 4. Проверка разрешения
    if (to.meta.permission) {
        if (!authStore.hasPermission(to.meta.permission)) {
            console.warn(`[Router Guard] Доступ запрещен: нет разрешения '${to.meta.permission}'`);
            return next({ name: 'Menu' });
        }
    }

    // 5. Анимации переходов для чата
    if (to.name === 'ChatRoom' && from.name === 'ChatList') {
        to.meta.transition = 'slide-left';
    } else if (to.name === 'ChatList' && from.name === 'ChatRoom') {
        to.meta.transition = 'slide-right';
    } else {
        to.meta.transition = 'fade';
    }

    next();
});

export default router;
