<template>
    <div
        class="offcanvas offcanvas-start app-sidebar"
        tabindex="-1"
        id="sidebar-menu"
        aria-labelledby="sidebarMenuLabel"
    >
        <!-- ===== ШАПКА С ПРОФИЛЕМ ===== -->
        <div class="sidebar-header">
            <div class="sidebar-header-bg"></div>

            <div class="sidebar-header-content">
                <!-- Кнопка закрытия -->
                <button
                    type="button"
                    class="sidebar-close-btn"
                    data-bs-dismiss="offcanvas"
                    aria-label="Закрыть"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <!-- Профиль пользователя -->
                <div class="user-profile">
                    <div
                        @click="goTo('Profile')"
                        class="user-avatar">

                        <img v-if="self?.avatar" :src="self.avatar" alt="">
                        <i v-else class="fa-solid fa-user"></i>

                        <!-- Индикатор онлайн -->
                        <div class="online-indicator"></div>
                    </div>

                    <div class="user-info">

                        <h6 class="user-name">{{ self?.name || 'Гость' }}</h6>
                        <p class="user-phone">{{ self?.phone || 'Телефон не указан' }}</p>
                    </div>
                </div>

                <!-- Мини-статистика -->
                <div class="user-stats">
                    <div class="stat-item" @click="goTo('Cashback')">
                        <i class="fa-solid fa-coins"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.cashBack?.amount || 0 }} ₽</span>
                            <span class="stat-label">Баланс</span>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item" @click="goTo('Orders')">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.order_count || 0 }}</span>
                            <span class="stat-label">Заказов</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ТЕЛО МЕНЮ ===== -->
        <div class="sidebar-body">

            <!-- Навигация -->
            <div class="sidebar-section">
                <div class="section-label">Навигация</div>

                <nav class="sidebar-nav">
                    <button
                        v-for="item in sidebarItems"
                        :key="item.route"
                        class="nav-item"
                        :class="{ 'active': $route.name === item.route }"
                        @click="goTo(item.route)"
                        data-bs-dismiss="offcanvas"
                    >
                        <div class="nav-icon">
                            <i :class="item.icon"></i>
                        </div>
                        <span class="nav-title">{{ item.title }}</span>

                        <!-- Бейдж (например, для корзины) -->
                        <span v-if="item.badge && item.badge() > 0" class="nav-badge">
                            {{ item.badge() > 99 ? '99+' : item.badge() }}
                        </span>

                        <i class="fa-solid fa-chevron-right nav-arrow"></i>
                    </button>
                </nav>
            </div>

            <!-- Контакты -->
            <div v-if="hasContacts" class="sidebar-section">
                <div class="section-label">Контакты</div>

                <div class="contacts-list">
                    <!-- Телефон -->
                    <a
                        v-if="settings?.phones?.length > 0"
                        :href="'tel:' + settings.phones[0]"
                        class="contact-item"
                    >
                        <div class="contact-icon phone-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Телефон</span>
                            <span class="contact-value">{{ settings.phones[0] }}</span>
                        </div>
                    </a>

                    <!-- Email -->
                    <a
                        v-if="settings?.email"
                        :href="'mailto:' + settings.email"
                        class="contact-item"
                    >
                        <div class="contact-icon email-icon">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Почта</span>
                            <span class="contact-value">{{ settings.email }}</span>
                        </div>
                    </a>

                    <!-- Сайт -->
                    <a
                        v-if="links.site"
                        :href="links.site"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="contact-item"
                    >
                        <div class="contact-icon site-icon">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Сайт</span>
                            <span class="contact-value">{{ links.site }}</span>
                        </div>
                    </a>

                    <!-- Соцсети -->
                    <div v-if="links.inst || links.vk" class="social-links">
                        <a
                            v-if="links.inst"
                            :href="'https://instagram.com/' + links.inst"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn instagram"
                        >
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a
                            v-if="links.vk"
                            :href="'https://vk.com/' + links.vk"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn vk"
                        >
                            <i class="fa-brands fa-vk"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CTA: Связь с менеджером -->
            <div v-if="settings?.manager?.link" class="sidebar-section">
                <a
                    :href="settings.manager.link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="manager-cta"
                >
                    <div class="manager-cta-icon">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div class="manager-cta-content">
                        <div class="manager-cta-title">Нужна помощь?</div>
                        <div class="manager-cta-desc">
                            {{ settings.manager.title || 'Связаться с менеджером' }}
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-right manager-cta-arrow"></i>
                </a>
            </div>

        </div>

        <!-- ===== ФУТЕР МЕНЮ ===== -->
        <div class="sidebar-footer">
            <div class="footer-links">
                <router-link to="/about" class="footer-link">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    О платформе
                </router-link>
                <router-link to="/privacy-policy" class="footer-link">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    Конфиденциальность
                </router-link>
            </div>
            <div class="footer-version">
                {{ tenant?.name || 'Магазин' }} © {{ currentYear }}
            </div>
        </div>

    </div>
</template>

<script>
import { useChat } from '@/MobileClient/Composables/useChat.js';
import {useBasket} from "@/MobileClient/composables/useBasket.js";

export default {
    name: "AppSidebar",

    setup(){
        const basket = useBasket();
        const chat = useChat();

        return {basket, chat}
    },
    data(){


        return {
            isEmpty: this.basket.isEmpty,
        }
    },
    computed: {
        tenant() {
            return window.Tenant || null;
        },
        cartTotalCount(){
            return this.basket.cartTotalCount || 0
        },
        self() {

            return window.TenantUser || null;
        },

        settings() {
            return this.tenant?.settings || null;
        },

        links() {
            const links = this.settings?.links || {};
            return {
                inst: links.inst || null,
                vk: links.vk || null,
                site: links.site || null,
            };
        },
        totalUnread() {
            return this.chat.totalUnread.value;
        },
        hasContacts() {
            return (
                this.settings?.phones?.length > 0 ||
                this.settings?.email ||
                this.links.site ||
                this.links.inst ||
                this.links.vk
            );
        },

        sidebarItems() {
            const menuItems = this.settings?.menu_items || {};

            // 🆕 Маппинг: ключ из menu_items → конфигурация по умолчанию
            // route, badge — фиксированные, title/icon — берутся из настроек (с фоллбэком)
            const menuConfig = {
                catalog: {
                    route: 'Catalog',
                    defaultTitle: 'Каталог товаров',
                    defaultIcon: 'fa-solid fa-store',
                },
                grocery_order: {
                    route: 'GroceryOrder',
                    defaultTitle: 'Заказать продукты',
                    defaultIcon: 'fa-solid fa-leaf',
                },
                food_calculator: {
                    route: 'FoodCalculators',
                    defaultTitle: 'Собери сам',
                    defaultIcon: 'fa-brands fa-hive',
                },
                cart: {
                    route: 'Cart',
                    defaultTitle: 'Корзина',
                    defaultIcon: 'fa-solid fa-cart-shopping',
                    badge: () => this.cartTotalCount,
                },
                orders: {
                    route: 'Orders',
                    defaultTitle: 'Мои заказы',
                    defaultIcon: 'fa-solid fa-bag-shopping',
                },
                cashback: {
                    route: 'Cashback',
                    defaultTitle: 'Мои бонусы',
                    defaultIcon: 'fa-solid fa-coins',
                },
                games: {
                    route: 'GamesCatalog',
                    defaultTitle: 'Бонус-игры',
                    defaultIcon: 'fa-solid fa-dice',
                },
                cashback_shop: {
                    route: 'CashbackShop',
                    defaultTitle: 'Магазин бонусов',
                    defaultIcon: 'fa-solid fa-shirt',
                },
                profile: {
                    route: 'Profile',
                    defaultTitle: 'Профиль',
                    defaultIcon: 'fa-solid fa-user',
                },
                chat: {
                    route: 'ChatList',
                    defaultTitle: 'Сообщения',
                    defaultIcon: 'fa-solid fa-comments',
                    badge: () => this.totalUnread,
                },
                feedback: {
                    route: 'FeedBack',
                    defaultTitle: 'Обратная связь',
                    defaultIcon: 'fa-solid fa-comment-dots',
                },
            };

            // 🆕 1. Фиксированный первый пункт — "Главная" (всегда виден)
            const homeItem = {
                route: 'Menu',
                title: 'Главная',
                icon: 'fa-solid fa-house',
            };

            // 🆕 2. Динамические пункты из настроек
            const dynamicItems = Object.entries(menuConfig)
                // Фильтруем только те, что есть в настройках и включены
                .filter(([key]) => {
                    const item = menuItems[key];
                    return item && item.is_visible !== false;
                })
                // Преобразуем в итоговый формат
                .map(([key, config]) => {
                    const item = menuItems[key] || {};
                    return {
                        route: config.route,
                        title: item.title || config.defaultTitle,
                        icon: item.icon || config.defaultIcon,
                        badge: config.badge, // badge-функция, если есть
                    };
                })
                // 🆕 3. Сортируем по order из настроек (если order не задан — в конец)
                .sort((a, b) => {
                    const keyA = Object.keys(menuConfig).find(k => menuConfig[k].route === a.route);
                    const keyB = Object.keys(menuConfig).find(k => menuConfig[k].route === b.route);
                    const orderA = menuItems[keyA]?.order ?? 999;
                    const orderB = menuItems[keyB]?.order ?? 999;
                    return orderA - orderB;
                });

            // 🆕 4. Собираем итоговый массив: Главная + отсортированные пункты
            return [homeItem, ...dynamicItems];
        },





        currentYear() {
            return new Date().getFullYear();
        },
    },
    mounted() {
        this.chat.loadUnreadCount()
    },
    methods: {
        goTo(routeName) {
            if (!routeName) return;
            this.$router.push({ name: routeName });
        },
    },
};
</script>

<style scoped>
/* ==========================================
   КОНТЕЙНЕР SIDEBAR
   ========================================== */
.app-sidebar {
    width: 320px !important;
    max-width: 85vw;
    background: var(--bs-body-bg);
    border-right: 1px solid var(--bs-border-color);
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.08);
}

/* ==========================================
   ШАПКА С ПРОФИЛЕМ
   ========================================== */
.sidebar-header {
    position: relative;
    padding: 24px 20px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    flex-shrink: 0;
}

.sidebar-header-bg {
    position: absolute;
    top: -30%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.sidebar-header-content {
    position: relative;
    z-index: 1;
}

.sidebar-close-btn {
    position: absolute;
    top: 0px;
    right: 0px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
}

.sidebar-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Профиль */
.user-profile {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.user-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    position: relative;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #22c55e;
    border: 2px solid var(--bs-primary);
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    margin: 0 0 4px 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-phone {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.85;
}

/* Мини-статистика */
.user-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 14px;
}

.stat-item {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.stat-item:hover {
    opacity: 0.8;
}

.stat-item i {
    font-size: 1.2rem;
    opacity: 0.9;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.1;
}

.stat-label {
    font-size: 0.7rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-divider {
    width: 1px;
    height: 32px;
    background: rgba(255, 255, 255, 0.3);
}

/* ==========================================
   ТЕЛО МЕНЮ
   ========================================== */
.sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 0;
    -webkit-overflow-scrolling: touch;
}

.sidebar-body::-webkit-scrollbar {
    width: 4px;
}

.sidebar-body::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

/* Секции */
.sidebar-section {
    margin-bottom: 24px;
    padding: 0 16px;
}

.section-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding: 0 12px;
}

/* ==========================================
   НАВИГАЦИЯ
   ========================================== */
.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    color: var(--bs-body-color);
    width: 100%;
    position: relative;
}

.nav-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.nav-item.active {
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.1) 0%, rgba(var(--bs-primary-rgb), 0.05) 100%);
    color: var(--bs-primary);
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: var(--bs-primary);
    border-radius: 0 3px 3px 0;
}

.nav-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.nav-item.active .nav-icon {
    background: var(--bs-primary);
    color: white;
}

.nav-item:hover .nav-icon {
    transform: scale(1.05);
}

.nav-title {
    flex: 1;
    font-weight: 500;
    font-size: 0.95rem;
}

.nav-badge {
    padding: 2px 8px;
    background: var(--bs-primary);
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

.nav-arrow {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.2s ease;
}

.nav-item:hover .nav-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ==========================================
   КОНТАКТЫ
   ========================================== */
.contacts-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    text-decoration: none;
    color: var(--bs-body-color);
    transition: all 0.2s ease;
}

.contact-item:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.02);
    transform: translateX(4px);
}

.contact-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.phone-icon {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.email-icon {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.site-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.contact-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.contact-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.contact-value {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Соцсети */
.social-links {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.social-btn {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.2s ease;
    text-decoration: none;
}

.social-btn.instagram {
    background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-btn.vk {
    background: linear-gradient(135deg, #4a76a8 0%, #3d6290 100%);
}

.social-btn:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* ==========================================
   CTA: МЕНЕДЖЕР
   ========================================== */
.manager-cta {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 16px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.manager-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
    color: white;
}

.manager-cta-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.manager-cta-content {
    flex: 1;
}

.manager-cta-title {
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 2px;
}

.manager-cta-desc {
    font-size: 0.8rem;
    opacity: 0.9;
}

.manager-cta-arrow {
    font-size: 0.9rem;
    opacity: 0.8;
    transition: transform 0.2s ease;
}

.manager-cta:hover .manager-cta-arrow {
    transform: translateX(4px);
}

/* ==========================================
   ФУТЕР МЕНЮ
   ========================================== */
.sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    flex-shrink: 0;
}

.footer-links {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.footer-link {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-link:hover {
    color: var(--bs-primary);
}

.footer-version {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    opacity: 0.7;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .app-sidebar {
        width: 280px !important;
    }

    .user-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }

    .user-name {
        font-size: 1rem;
    }

    .nav-item {
        padding: 10px;
    }

    .nav-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
}
</style>
