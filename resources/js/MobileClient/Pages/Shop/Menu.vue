<template>
    <div class="home-page pb-5">

        <!-- Disabled alert -->
        <div v-if="isDisabled" class="alert alert-danger mb-3 mx-2 sticky-top">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <p class="mb-0">{{ disabledText }}</p>
            </div>
        </div>

        <!-- ===== HERO СЕКЦИЯ ===== -->
        <div class="hero-section">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-greeting">
                    <div class="greeting-text">
                        <small class="greeting-label">Добро пожаловать 👋</small>
                        <h2 class="greeting-name">{{ self?.name || 'Гость' }}</h2>
                    </div>
                    <div class="hero-avatar">
                        <img v-if="self?.avatar" :src="self.avatar" alt="">
                        <i v-else class="fa-solid fa-user"></i>
                    </div>
                </div>

                <!-- Мини-статистика -->
                <div class="hero-stats">
                    <button class="stat-pill" @click="goTo('Cashback')">
                        <i class="fa-solid fa-coins"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.cashBack?.amount || 0 }} ₽</span>
                            <span class="stat-label">Баланс</span>
                        </div>
                    </button>
                    <button class="stat-pill" @click="goTo('Orders')">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.order_count || 0 }}</span>
                            <span class="stat-label">Заказов</span>
                        </div>
                    </button>
                    <button class="stat-pill" @click="goTo('Friends')">
                        <i class="fa-solid fa-user-group"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.friends_count || 0 }}</span>
                            <span class="stat-label">Друзей</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stories -->
        <div v-if="storiesStore.stories?.length" class="px-2 mt-3">
            <StoryList :stories="storiesStore.stories"/>
        </div>

        <StoreStatusBanner
            :is-work="isWork"
            :schedule="tenant?.settings?.schedule"
        />

        <div class="container px-2">

            <!-- ===== НАСТРОЙКИ ТЕМЫ (Компактный блок) ===== -->
            <div class="theme-settings-card mb-4">
                <button
                    class="theme-toggle-header"
                    @click="showThemeSettings = !showThemeSettings"
                >
                    <div class="d-flex align-items-center gap-2">
                        <div class="theme-icon-mini">
                            <i class="fa-solid fa-palette"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold small">Настройки оформления</div>
                            <small class="text-muted" style="font-size: 0.7rem;">Тема, цвет, режим</small>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"
                       :class="{ 'rotated': showThemeSettings }"></i>
                </button>

                <transition name="slide-down">
                    <div v-show="showThemeSettings" class="theme-settings-body">
                        <ThemeToggle class="mb-2"/>
                        <ThemeColorPicker class="mb-2"/>
                        <ThemeSchemePicker/>
                    </div>
                </transition>
            </div>

            <!-- ===== СЕКЦИЯ: СЕРВИСЫ ===== -->
            <div class="section-header">
                <div class="section-icon">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <div>
                    <h6 class="section-title">Наши сервисы</h6>
                    <p class="section-subtitle">Быстрый доступ к функциям</p>
                </div>
            </div>

            <div
                class="services-grid"
                @touchstart="onTouchStart"
                @touchend="onTouchEnd"
            >
                <div
                    v-for="item in visibleMenuItems"
                    :key="item.key"
                >
                    <MainMenuItem
                        :route="item.route"
                        :default-image="item.img"
                        :default-text="item.text"
                        :item="item"
                    >
                        <template #counter v-if="item.key === 'basket'">
                            <span class="counter badge bg-primary">
                                {{ basketStore.cartTotalCount || 0 }}
                            </span>
                        </template>

                        <template #counter v-if="item.key === 'chat'">
                            <span class="counter badge bg-primary">
                                {{ chatStore?.unreadCount || 0 }}
                            </span>
                        </template>
                    </MainMenuItem>
                </div>
            </div>



            <!-- ===== КОЛЕСО ФОРТУНЫ (Wow-карточка) ===== -->
            <div class="fortune-card mt-4" @click="goTo('WheelClassic')">
                <div class="fortune-background"></div>
                <div class="fortune-content">
                    <div class="fortune-icon-wrapper">
                        <div class="fortune-icon">
                            <i class="fa-solid fa-dice"></i>
                        </div>
                        <div class="fortune-sparkle sparkle-1">✨</div>
                        <div class="fortune-sparkle sparkle-2">✨</div>
                        <div class="fortune-sparkle sparkle-3">✨</div>
                    </div>
                    <div class="fortune-text">
                        <h5 class="fortune-title">Колесо Фортуны</h5>
                        <p class="fortune-description">
                            Крути колесо и получай бонусы, скидки и призы!
                        </p>
                    </div>
                    <div class="fortune-action">
                        <span>Испытать удачу</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>

            <!-- ===== СЕКЦИЯ: БОНУСЫ ===== -->
            <div class="section-header mt-4">
                <div class="section-icon bonus-icon">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div>
                    <h6 class="section-title">Система бонусов</h6>
                    <p class="section-subtitle">Зарабатывай и получай награды</p>
                </div>
            </div>

            <Carousel v-bind="carouselConfig" class="bonus-carousel">
                <Slide v-for="item in entertainment" :key="item.key">
                    <div class="bonus-slide">
                        <button
                            class="bonus-card"
                            @click="goTo(item.route)"
                            :disabled="!item.route"
                        >
                            <div class="bonus-image-wrapper">
                                <img :src="'/images/shop/' + item.img" :alt="item.default">
                            </div>
                            <div class="bonus-info">
                                <div class="bonus-title">{{ item.title || item.default }}</div>
                                <div class="bonus-action">
                                    <span>Открыть</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                        </button>
                    </div>
                </Slide>

                <template #addons>
                    <Navigation />
                </template>
            </Carousel>

            <OrderPeriscope></OrderPeriscope>

            <!-- ===== АДМИНКА (только для админов) ===== -->
            <template v-if="!isAdmin">

                <AppDivider
                    :icon="'fa-solid fa-screwdriver-wrench'"
                    :text="'Панель администратора'"></AppDivider>


                <div class="services-grid admin-grid">
                    <div
                        v-for="item in visibleAdminItems"
                        :key="item.key"
                    >
                        <MainMenuItem
                            :route="item.route"
                            :default-image="item.img"
                            :default-text="item.text"
                            :item="item"
                        />
                    </div>
                </div>
            </template>

        </div>
    </div>
</template>

<script>
import ThemeToggle from '@/MobileClient/Components/ThemeToggle.vue';
import ThemeSchemePicker from '@/MobileClient/Components/ThemeSchemePicker.vue';
import ThemeColorPicker from "@/MobileClient/Components/ThemeColorPicker.vue";
import MainMenuItem from "@/MobileClient/Components/Shop/MainMenuItem.vue";
import StoryList from "@/MobileClient/Components/Shop/Stories/StoryList.vue";
import 'vue3-carousel/dist/carousel.css';
import {Carousel, Slide, Navigation} from 'vue3-carousel';
import {useStoriesStore} from "@/MobileClient/stores/Shop/stories.js";
import {useBasketStore} from "@/MobileClient/stores/Shop/basket.js";

import StoreStatusBanner from '@/MobileClient/Components/StoreStatusBanner.vue';
import AppDivider from "@/MobileClient/Components/AppDivider.vue";

import OrderPeriscope from "@/MobileClient/Components/Shop/OrderPeriscope.vue";
export default {
    name: "HomePage",

    components: {
        AppDivider,
        ThemeToggle,
        OrderPeriscope,
        ThemeColorPicker,
        ThemeSchemePicker,
        MainMenuItem,
        StoryList,
        Carousel,
        Slide,
        Navigation,
        StoreStatusBanner,
    },

    setup() {
        const storiesStore = useStoriesStore();
        const basketStore = useBasketStore();

        return {
            storiesStore,
            basketStore,
        };
    },

    data() {
        return {
            showThemeSettings: false,
            carouselConfig: {
                itemsToShow: 1.5,
                wrapAround: true,
                snapAlign: 'start',
            },
            script_data: {
                is_disabled: false,
                disabled_text: '',
            },
            touchStartX: 0,
            touchEndX: 0,
            menuItems: [
                {key: 'shop', route: 'Catalog', text: 'Магазин', img: 'shop.png'},
                {key: 'basket', route: 'Cart', text: 'Корзина', img: 'basket.png'},
                {key: 'profile', route: 'Profile', text: 'Профиль', img: 'profile.png'},
                {key: 'booking', route: 'TableBooking', text: 'Бронь столика', img: 'tables.png', condition: 'can_use_booking'},
                {key: 'history', route: 'Orders', text: 'История', img: 'history.png'},
                {key: 'chat', route: 'Chat', text: 'Чат', img: 'chat.png'},
                {key: 'events', route: 'WheelClassic', text: 'Розыгрыши', img: 'events.png'},
                {key: 'about', route: 'Contacts', text: 'Контакты', img: 'contacts.png'},
            ],
            adminMenuItems: [
                {key: 'send_invoice', route: 'AdminInvoice', text: 'Счет на оплату', img: 'clients.png'},
                {key: 'products_manage', route: 'AdminShop', text: 'Управление товарами', img: 'products.png'},
                {key: 'shop_settings', modal: 'script-setting-editor', text: 'Настройка магазина', img: 'settings.png'},
                {key: 'partners', route: 'AdminPartners', text: 'Работа с партнерами', img: 'partners.png'},
                {key: 'stories_manage', route: 'Stories', text: 'Управление историями', img: 'stories.png'},
                {key: 'tables_manage', route: 'TablesManager', text: 'Управление столиками', img: 'tables.png', condition: 'need_table_list'},
                {key: 'clients', route: 'ClientsV2', text: 'Управление клиентами', img: 'clients.png'},
                {key: 'utm', route: 'LinkManagerV2', text: 'UTM-метки', img: 'utm.png'},
                {key: 'mailing', route: 'MailingV2', text: 'Управление рассылками', img: 'mail.png'},
                {key: 'admin_orders', route: 'AdminOrdersV2', text: 'Управление заказами', img: 'orders.png'},
                {key: 'promo', route: 'PromoCodesV2', text: 'Управление промокодами', img: 'promo.png'},
                {key: 'statistic', route: 'StatisticV2', text: 'Статистика', img: 'statistic.png'},
            ],
            entertainment: [
                {key: 'wheel_of_fortune_btn', img: 'fortune.png', default: 'Колесо фортуны', route: 'WheelClassic'},
                {key: 'coffee_bonus_btn', img: 'coffee.png', default: 'Больше кофе', route: 'Coffee'},
                {key: 'social_quest_btn', img: 'social-quest.png', default: 'Квесты', route: 'Quests'},
            ],
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
        isAdmin() {
            return this.self?.role === 'admin' || this.self?.is_admin === true;
        },
        isDisabled() {
            return this.script_data?.is_disabled === true;
        },
        disabledText() {
            return this.script_data?.disabled_text || 'Сервис временно недоступен';
        },
        preparedMenuItems() {
            const icons = this.settings?.icons || [];
            const map = Object.fromEntries(icons.map(i => [i.slug, i]));
            return this.menuItems.map(item => {
                const fromSettings = map[item.key] || {};
                return {
                    ...item,
                    text: fromSettings.title || item.text,
                    img: fromSettings.image || item.img,
                    is_visible: fromSettings.is_visible ?? true,
                    ...fromSettings,
                };
            });
        },
        visibleMenuItems() {
            return this.preparedMenuItems.filter(item => {
                if (item.is_visible === false) return false;
                return this.checkCondition(item);
            });
        },
        visibleAdminItems() {
            return this.adminMenuItems.filter(item => this.checkCondition(item));
        },
        isWork() {
            if (!this.settings?.schedule) return true;
            if (!window.isCorrectSchedule?.(this.settings.schedule)) return false;
            return this.settings?.is_work ?? true;
        },
    },

    mounted() {
        this.loadStories();
        this.loadScriptData();
    },

    methods: {
        async loadStories() {
            try {
                await this.storiesStore.loadStories();
            } catch (e) {
                console.error('Ошибка загрузки stories:', e);
            }
        },
        async loadScriptData() {
            // TODO: Замени на реальный API-запрос
        },
        goTo(name) {
            if (!name) return;
            this.$router.push({name});
        },
        onTouchStart(e) {
            this.touchStartX = e.changedTouches[0].screenX;
        },
        onTouchEnd(e) {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        },
        handleSwipe() {
            const diff = this.touchStartX - this.touchEndX;
            const threshold = 50;
            if (Math.abs(diff) > threshold) {
                if (diff > 0) this.goTo('Cart');
                else this.goTo('Catalog');
            }
        },
        checkCondition(item) {
            if (!item.condition) return true;
            const parts = item.condition.split('.');
            let value = this;
            for (const part of parts) {
                value = value?.[part];
                if (value === undefined) return false;
            }
            return !!value;
        },
    },
};
</script>

<style scoped>
.home-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.hero-section {
    position: relative;
    padding: 24px 16px 20px;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        var(--bs-primary) 0%,
        var(--bs-primary-hover, var(--bs-primary)) 100%
    );
    opacity: 0.95;
}

.hero-background::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    filter: blur(40px);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-greeting {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.greeting-label {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.85rem;
    display: block;
    margin-bottom: 4px;
}

.greeting-name {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    overflow: hidden;
}

.hero-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Мини-статистика */
.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    overflow-x: auto;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 14px;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.stat-pill:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

.stat-pill i {
    font-size: 1.2rem;
    opacity: 0.9;
}

.stat-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.stat-value {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.1;
}

.stat-label {
    font-size: 0.65rem;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ==========================================
   НАСТРОЙКИ ТЕМЫ (Компактный блок)
   ========================================== */
.theme-settings-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.theme-toggle-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--bs-body-color);
    transition: background 0.2s ease;
}

.theme-toggle-header:hover {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.theme-icon-mini {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}

.toggle-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
    font-size: 0.85rem;
}

.toggle-arrow.rotated {
    transform: rotate(180deg);
}

.theme-settings-body {
    padding: 0 16px 16px;
}

/* Анимация раскрытия */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 1000px;
}

/* ==========================================
   ЗАГОЛОВКИ СЕКЦИЙ
   ========================================== */
.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding: 0 4px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
    flex-shrink: 0;
}

.section-icon.bonus-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.25);
}

.section-icon.admin-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.25);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   СЕТКА СЕРВИСОВ
   ========================================== */
.services-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

@media (max-width: 576px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
    }
}

/* ==========================================
   КОЛЕСО ФОРТУНЫ (Wow-карточка)
   ========================================== */
.fortune-card {
    position: relative;
    padding: 20px;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 8px 24px rgba(118, 75, 162, 0.3);
}

.fortune-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(118, 75, 162, 0.4);
}

.fortune-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.fortune-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
    color: white;
}

.fortune-icon-wrapper {
    position: relative;
    flex-shrink: 0;
}

.fortune-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.fortune-sparkle {
    position: absolute;
    font-size: 0.8rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 {
    top: -4px;
    right: -4px;
    animation-delay: 0s;
}

.sparkle-2 {
    bottom: -4px;
    left: -4px;
    animation-delay: 0.7s;
}

.sparkle-3 {
    top: 50%;
    right: -8px;
    animation-delay: 1.4s;
}

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1); }
}

.fortune-text {
    flex: 1;
    min-width: 0;
}

.fortune-title {
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0 0 4px 0;
    color: white;
}

.fortune-description {
    margin: 0;
    font-size: 0.8rem;
    opacity: 0.9;
    line-height: 1.4;
}

.fortune-action {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
}

/* ==========================================
   БОНУСЫ КАРУСЕЛЬ
   ========================================== */
.bonus-carousel {
    margin: 0 -8px;
}

.bonus-slide {
    padding: 0 6px;
}

.bonus-card {
    width: 100%;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
    color: var(--bs-body-color);
    padding: 0;
    display: block;
}

.bonus-card:hover:not(:disabled) {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: var(--bs-primary);
}

.bonus-card:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.bonus-image-wrapper {
    width: 100%;
    aspect-ratio: 16/10;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.1) 0%, rgba(var(--bs-primary-rgb), 0.05) 100%);
}

.bonus-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.bonus-card:hover .bonus-image-wrapper img {
    transform: scale(1.05);
}

.bonus-info {
    padding: 12px;
}

.bonus-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.bonus-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 0.75rem;
    color: var(--bs-primary);
    font-weight: 600;
}

.bonus-action i {
    transition: transform 0.2s ease;
}

.bonus-card:hover .bonus-action i {
    transform: translateX(4px);
}

/* Навигация карусели */
:deep(.carousel__prev),
:deep(.carousel__next) {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    color: var(--bs-primary);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .greeting-name {
        font-size: 1.3rem;
    }

    .hero-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }

    .stat-pill {
        padding: 8px 10px;
    }

    .stat-value {
        font-size: 0.85rem;
    }

    .fortune-content {
        flex-wrap: wrap;
    }

    .fortune-action {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
    }
}

/* Плавные переходы для всей страницы */
:deep(*) {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
</style>
