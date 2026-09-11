<template>
    <div
        class="offcanvas offcanvas-start app-sidebar"
        tabindex="-1"
        id="sidebar-menu"
        aria-labelledby="sidebarMenuLabel"
    >
        <!-- ===== ШАПКА С ПРОФИЛЕМ ===== -->
        <div class="sidebar-header">
            <!-- ... (код шапки без изменений) ... -->

            <!-- 🆕 Показываем статистику ТОЛЬКО клиентам -->
            <div v-if="resolvedUserType === 'client'" class="user-stats">
                <div class="stat-item" @click="goTo('Cashback')">
                    <i class="fa-solid fa-coins"></i>
                    <div class="stat-info">
                        <span class="stat-value">{{ self?.cashback_balance || 0 }} ₽</span>
                        <span class="stat-label">Баланс</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item" @click="goTo('Orders')">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <div class="stat-info">
                        <span class="stat-value">{{ self?.orders_count || 0 }}</span>
                        <span class="stat-label">Заказов</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ТЕЛО МЕНЮ ===== -->
        <div class="sidebar-body">
            <div class="sidebar-section">
                <!-- 🆕 Динамический заголовок секции -->
                <div class="section-label">
                    {{ resolvedUserType === 'delivery' ? 'Доставка' : resolvedUserType === 'agent' ? 'Агенту' : 'Навигация' }}
                </div>

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

                        <span v-if="item.badge && item.badge() > 0" class="nav-badge">
                            {{ item.badge() > 99 ? '99+' : item.badge() }}
                        </span>

                        <i class="fa-solid fa-chevron-right nav-arrow"></i>
                    </button>
                </nav>
            </div>

            <!-- 🆕 Контакты и CTA показываем ТОЛЬКО клиентам -->
            <div v-if="resolvedUserType === 'client' && hasContacts" class="sidebar-section">
                <div class="section-label">Контакты</div>
                <!-- ... (код контактов без изменений) ... -->
            </div>

            <div v-if="resolvedUserType === 'client' && settings?.manager?.link" class="sidebar-section">
                <!-- ... (код CTA менеджера без изменений) ... -->
            </div>
        </div>

        <!-- ===== ФУТЕР МЕНЮ ===== -->
        <div class="sidebar-footer">
            <!-- ... (код футера без изменений) ... -->
        </div>
    </div>
</template>

<script>
import { useChat } from '@/MobileClient/Composables/useChat.js';
import { useBasket } from "@/MobileClient/composables/useBasket.js";

export default {
    name: "AppSidebar",

    // 🆕 1. Добавляем пропс для явной передачи типа
    props: {
        roleType: {
            type: String,
            default: null, // null означает "определить автоматически"
            validator: (value) => value === null || ['client', 'delivery', 'agent'].includes(value)
        }
    },

    setup() {
        const basket = useBasket();
        const chat = useChat();
        return { basket, chat };
    },

    data() {
        return {
            isEmpty: this.basket?.isEmpty || true,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
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

        cartTotalCount() {
            return this.basket?.cartTotalCount || 0;
        },

        totalUnread() {
            return this.chat?.totalUnread?.value || 0;
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

        currentYear() {
            return new Date().getFullYear();
        },

        // 🆕 2. Умное определение типа: приоритет у пропса, затем автоматическая проверка
        resolvedUserType() {
            // Если пропс передан явно, используем его
            if (this.roleType) {
                return this.roleType;
            }

            // Иначе определяем автоматически по ролям (старая логика)
            if (!this.self) return 'client';
            const roles = this.self.role_names || [];
            if (roles.includes('deliveryman') || roles.includes('senior_delivery')) {
                return 'delivery';
            }
            if (roles.includes('agent') || roles.includes('senior_agent')) {
                return 'agent';
            }
            return 'client';
        },

        // 🆕 3. Формирование меню на основе resolvedUserType
        sidebarItems() {
            const type = this.resolvedUserType;

            // --- МЕНЮ ДЛЯ ДОСТАВКИ ---
            if (type === 'delivery') {
                return [
                    { route: 'DeliverymanDashboard', title: 'Панель доставки', icon: 'fa-solid fa-motorcycle' },
                    { route: 'AvailableShops', title: 'Доступные магазины', icon: 'fa-solid fa-store' },
                    { route: 'Orders', title: 'Мои доставки', icon: 'fa-solid fa-bag-shopping' },
                    { route: 'Profile', title: 'Профиль', icon: 'fa-solid fa-user' },
                ];
            }

            // --- МЕНЮ ДЛЯ АГЕНТА ---
            if (type === 'agent') {
                return [
                    { route: 'AgentDashboard', title: 'Панель агента', icon: 'fa-solid fa-briefcase' },
                    { route: 'AgentTenants', title: 'Мои приложения', icon: 'fa-solid fa-building' },
                    { route: 'AgentFinance', title: 'Финансы', icon: 'fa-solid fa-wallet' },
                    { route: 'Profile', title: 'Профиль', icon: 'fa-solid fa-user' },
                ];
            }

            // --- МЕНЮ ДЛЯ КЛИЕНТА (по умолчанию) ---
            const menuItems = this.settings?.menu_items || {};
            const menuConfig = {
                catalog: { route: 'Catalog', defaultTitle: 'Каталог товаров', defaultIcon: 'fa-solid fa-store' },
                grocery_order: { route: 'GroceryOrder', defaultTitle: 'Заказать продукты', defaultIcon: 'fa-solid fa-leaf' },
                food_calculator: { route: 'FoodCalculators', defaultTitle: 'Собери сам', defaultIcon: 'fa-brands fa-hive' },
                cart: { route: 'Cart', defaultTitle: 'Корзина', defaultIcon: 'fa-solid fa-cart-shopping', badge: () => this.cartTotalCount },
                orders: { route: 'Orders', defaultTitle: 'Мои заказы', defaultIcon: 'fa-solid fa-bag-shopping' },
                cashback: { route: 'Cashback', defaultTitle: 'Мои бонусы', defaultIcon: 'fa-solid fa-coins' },
                games: { route: 'GamesCatalog', defaultTitle: 'Бонус-игры', defaultIcon: 'fa-solid fa-dice' },
                cashback_shop: { route: 'CashbackShop', defaultTitle: 'Магазин бонусов', defaultIcon: 'fa-solid fa-shirt' },
                chat: { route: 'ChatList', defaultTitle: 'Сообщения', defaultIcon: 'fa-solid fa-comments', badge: () => this.totalUnread },
                feedback: { route: 'FeedBack', defaultTitle: 'Обратная связь', defaultIcon: 'fa-solid fa-comment-dots' },
            };

            const homeItem = { route: 'Menu', title: 'Главная', icon: 'fa-solid fa-house' };

            const dynamicItems = Object.entries(menuConfig)
                .filter(([key]) => {
                    const item = menuItems[key];
                    return item && item.is_visible !== false;
                })
                .map(([key, config]) => {
                    const item = menuItems[key] || {};
                    return {
                        route: config.route,
                        title: item.title || config.defaultTitle,
                        icon: item.icon || config.defaultIcon,
                        badge: config.badge,
                    };
                })
                .sort((a, b) => {
                    const keyA = Object.keys(menuConfig).find(k => menuConfig[k].route === a.route);
                    const keyB = Object.keys(menuConfig).find(k => menuConfig[k].route === b.route);
                    const orderA = menuItems[keyA]?.order ?? 999;
                    const orderB = menuItems[keyB]?.order ?? 999;
                    return orderA - orderB;
                });

            return [homeItem, ...dynamicItems, { route: 'Profile', title: 'Профиль', icon: 'fa-solid fa-user' }];
        }
    },

    mounted() {
        if (this.chat?.loadUnreadCount) {
            this.chat.loadUnreadCount();
        }
    },

    methods: {
        goTo(routeName) {
            if (!routeName) return;
            this.$router.push({ name: routeName }).catch(() => {});
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
