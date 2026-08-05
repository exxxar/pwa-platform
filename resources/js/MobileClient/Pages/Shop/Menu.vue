<template>
    <div class="home-page pb-5">



        <!-- ===== HERO СЕКЦИЯ ===== -->
        <div class="hero-section">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-greeting">
                    <div class="greeting-text">
                        <small class="greeting-label">Добро пожаловать 👋</small>
                        <h2 class="greeting-name">{{ self?.name || 'Гость' }}</h2>
                    </div>
                    <div
                        @click="goTo('Profile')"
                        class="hero-avatar">
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
                    <button class="stat-pill" @click="goTo('ReferralsPage')">
                        <i class="fa-solid fa-user-group"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.friends_count || 0 }}</span>
                            <span class="stat-label">Друзей</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="storiesStore.stories?.length || isAdmin" class="px-2 mt-3">
            <StoryList
                :stories="storiesStore.stories"
                :is-admin="isAdmin"
                @create-story="showCreateStoryModal = true"
            />
        </div>

        <StoreStatusBanner
            :is-work="isWork"
            :schedule="tenant?.settings?.company?.schedule"
        />

        <div class="container py-2 px-2">


            <!-- 🆕 Красивая плашка "Магазин выключен" (только для админа) -->
            <button
                v-if="isDisabled && isAdmin"
                class="disabled-alert-btn mb-3 mx-2 sticky-top"
                @click="showDisabledModal"
                type="button"
            >
                <div class="alert-content">
                    <div class="alert-left">
                        <div class="alert-icon-wrapper">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="alert-text-group">
                            <div class="alert-title">Магазин выключен</div>
                            <div class="alert-subtitle">Нажмите, чтобы перейти в настройки и включить</div>
                        </div>
                    </div>
                    <div class="alert-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Декоративный блик при наведении -->
                <div class="alert-shine"></div>
            </button>

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
                    v-for="item in menuItems"
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

            <AppDivider
                icon="fa-regular fa-address-card"
                text="Делимся контактами"
                class="flex-grow-1 m-0 my-3"
            />
            <TaplinkButton
                class="mb-3"
                label="Таплинк" icon="fa-regular fa-hand-pointer" />

            <!-- ===== АДМИНКА (только для админов) ===== -->
            <template v-if="isAdmin">


                <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                    <AppDivider
                        icon="fa-solid fa-screwdriver-wrench"
                        text="Админка"
                        class="flex-grow-1 m-0"
                    />



                    <!-- 🆕 Переключатель вида: Список / Сетка -->
                    <div class="admin-view-toggle ms-2">
                        <button
                            class="toggle-btn"
                            :class="{ active: adminViewMode === 'list' }"
                            @click="setAdminViewMode('list')"
                            title="Список"
                        >
                            <i class="fa-solid fa-list"></i>
                        </button>
                        <button
                            class="toggle-btn"
                            :class="{ active: adminViewMode === 'grid' }"
                            @click="setAdminViewMode('grid')"
                            title="Сетка"
                        >
                            <i class="fa-solid fa-border-all"></i>
                        </button>
                    </div>
                </div>


                <a
                    v-if="canOpenCrm"
                    :href="crmBoardUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-crm-link"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Открыть CRM</span>
                </a>

                <!-- ВАРИАНТ 1: СПИСОК -->
                <div v-if="adminViewMode === 'list'" class="admin-menu-list">
                    <button
                        v-for="item in simpleAdminItems"
                        :key="item.key"
                        class="admin-menu-item"
                        @click="goTo(item.route)"
                    >
                        <div class="admin-item-icon">
                            <i :class="item.icon"></i>
                        </div>
                        <div class="admin-item-text">
                            <span class="admin-item-title">{{ item.text }}</span>
                            <span class="admin-item-desc">{{ item.desc }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right admin-item-arrow"></i>
                    </button>
                </div>

                <!-- ВАРИАНТ 2: СЕТКА (КАРТОЧКИ) -->
                <div v-else class="admin-menu-grid">
                    <button
                        v-for="item in simpleAdminItems"
                        :key="item.key"
                        class="admin-grid-item"
                        @click="goTo(item.route)"
                    >
                        <div class="admin-grid-icon">
                            <i :class="item.icon"></i>
                        </div>
                        <div class="admin-grid-title">{{ item.text }}</div>
                        <div class="admin-grid-desc">{{ item.desc }}</div>
                    </button>
                </div>
            </template>

        </div>

        <!-- 🆕 МОДАЛКА: Магазин выключен -->
        <div class="modal fade" id="disabledShopModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header bg-danger text-white border-0 pb-0">
                        <div class="modal-icon bg-white text-danger">
                            <i class="fa-solid fa-power-off"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Магазин выключен</h5>
                            <small class="text-white-50">Пользователи видят страницу обслуживания</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <p class="mb-0 text-muted">
                            В данный момент магазин находится в режиме технических работ.
                            Обычные пользователи не имеют доступа к витрине.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Позже</button>
                        <button type="button" class="btn btn-primary px-4" @click="goToSettings">
                            <i class="fa-solid fa-gear me-2"></i> Перейти в настройки и включить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🆕 Модалка создания истории (только для админа) -->
        <StoryCreateModal
            v-if="showCreateStoryModal"
            @close="showCreateStoryModal = false"
            @saved="showCreateStoryModal = false"
        />
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
import { usePermissions } from '@/MobileClient/Composables/usePermissions.js';
import StoryCreateModal from '@/MobileClient/Components/Shop/Stories/StoryCreateModal.vue'; // 🆕
import TaplinkButton from '@/MobileClient/Components/Common/TaplinkButton.vue';
export default {
    name: "HomePage",

    components: {
        AppDivider,
        TaplinkButton,
        ThemeToggle,
        StoryCreateModal,
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
        const { isAdmin,hasPermission } = usePermissions();


        return {
            storiesStore,
            basketStore,
            isAdmin,
            hasPermission

        };
    },

    data() {
        return {
            showThemeSettings: false,
            adminViewMode: localStorage.getItem('adminViewMode') || 'list',
            disabledModal: null,
            showCreateStoryModal: false,
            carouselConfig: {
                itemsToShow: 2.5,
                wrapAround: true,
                snapAlign: 'start',
            },
            script_data: {
                is_disabled: false,
                disabled_text: '',
            },
            touchStartX: 0,
            touchEndX: 0,
        };
    },

    computed: {
        canOpenCrm() {
            console.log("settings",this.settings )
            return this.settings?.kanban?.enabled && this.settings?.kanban?.board_uuid && this.settings?.kanban?.board_uuid.length > 5;
        },
        crmBoardUrl() {
            if (!this.settings?.kanban?.board_uuid) return '#';
            let baseUrl = this.settings?.kanban?.base_url || 'https://crm.mypwa.ru';
            // Убираем /api/v1 (с возможным слэшем на конце) и любые trailing slashes
            baseUrl = baseUrl.replace(/\/api\/v1\/?$/i, '').replace(/\/$/, '');
            return `${baseUrl}/board/${this.settings?.kanban?.board_uuid}`;
        },
        tenant() {
            return window.Tenant || null;
        },
        self() {
            return window.TenantUser || null;
        },
        settings() {
            return this.tenant?.settings || null;
        },

        isDisabled() {
            return this.settings?.is_disabled === true || this.script_data?.is_disabled === true;
        },

        disabledText() {
            return this.settings?.disabled_text || this.script_data?.disabled_text || 'Сервис временно недоступен';
        },
        // ✅ Логика работы: Открыт, если НЕ отключен И (нет расписания ИЛИ сейчас время работы)
        isWork() {
            const settings = this.settings;
            if (!settings) return true; // Нет настроек = работаем

            // 1. Если магазин принудительно выключен админом или скриптом — он закрыт.
            if (this.isDisabled) {
                return false;
            }

            const schedule = settings.schedule;

            // 2. Если расписания нет или оно пустое, а принудительного отключения нет — работаем.
            if (!Array.isArray(schedule) || schedule.length === 0) {
                return true;
            }

            // 3. Проверка текущего времени по расписанию
            try {
                const now = new Date();
                const jsDay = now.getDay(); // 0 = Вс, 1 = Пн, ..., 6 = Сб

                // Адаптация под бэкенд, где массив обычно начинается с Понедельника (0 = Пн, 6 = Вс)
                const currentDayIndex = (jsDay + 6) % 7;

                const todaySchedule = schedule[currentDayIndex];

                // Если данных на сегодня нет, не блокируем магазин
                if (!todaySchedule || typeof todaySchedule !== 'object') {
                    return true;
                }

                // Если сегодня официально выходной
                if (todaySchedule.closed === true || todaySchedule.closed === 1 || todaySchedule.closed === "1") {
                    return false;
                }

                // Если указаны часы работы, проверяем текущее время
                if (todaySchedule.start_at && todaySchedule.end_at) {
                    const currentMinutes = now.getHours() * 60 + now.getMinutes();

                    const [startH, startM] = String(todaySchedule.start_at).split(':').map(Number);
                    const [endH, endM] = String(todaySchedule.end_at).split(':').map(Number);

                    const startMinutes = startH * 60 + startM;
                    const endMinutes = endH * 60 + endM;

                    // Обработка ночных смен (например, работает с 22:00 до 04:00)
                    if (endMinutes < startMinutes) {
                        return currentMinutes >= startMinutes || currentMinutes <= endMinutes;
                    }

                    // Обработка обычных дневных смен
                    return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
                }

                // Если closed !== true, но время не указано, считаем что работаем
                return true;

            } catch (e) {
                console.error('Ошибка при проверке времени работы:', e);
                // В случае любой непредвиденной ошибки безопаснее показать "Открыто"
                return true;
            }
        },
        menuItems() {
            const mainMenuSettings = this.settings?.main_menu_items || {};

            const baseConfig = {
                shop: { route: 'Catalog', defaultTitle: 'Магазин', defaultImg: '/images/shop/shop.png', order: 1 },
                basket: { route: 'Cart', defaultTitle: 'Корзина', defaultImg: '/images/shop/basket.png', order: 2 },
                profile: { route: 'Profile', defaultTitle: 'Профиль', defaultImg: '/images/shop/profile.png', order: 3 },
                booking: { route: 'TableBooking', defaultTitle: 'Бронь столика', defaultImg: '/images/shop/tables.png', order: 4, condition: 'can_use_booking' },
                history: { route: 'Orders', defaultTitle: 'История', defaultImg: '/images/shop/history.png', order: 5 },
                chat: { route: 'ChatList', defaultTitle: 'Чат', defaultImg: '/images/shop/chat.png', order: 6 },
                events: { route: 'WheelClassic', defaultTitle: 'Розыгрыши', defaultImg: '/images/shop/events.png', order: 7 },
                about: { route: 'Contacts', defaultTitle: 'Контакты', defaultImg: '/images/shop/contacts.png', order: 8 },
                referral: { route: 'ReferralsPage', defaultTitle: 'Реферальная программа', defaultImg: '/images/shop/referral.png', order: 9 },
            };

            return Object.entries(baseConfig)
                .filter(([key, config]) => {
                    if (config.condition && !this.settings?.[config.condition]) return false;
                    const setting = mainMenuSettings[key];
                    return setting ? setting.is_visible !== false : true;
                })
                .map(([key, config]) => {
                    const setting = mainMenuSettings[key] || {};
                    return {
                        key: key,
                        route: config.route,
                        text: setting.title || config.defaultTitle,
                        img: setting.img || config.defaultImg,
                        order: setting.order ?? config.order,
                    };
                })
                .sort((a, b) => a.order - b.order);
        },

        // 🆕 Полное, но визуально простое админское меню
        simpleAdminItems() {
            const items = [
                { key: 'products', route: 'AdminShop', text: 'Товары', desc: 'Управление каталогом', icon: 'fa-solid fa-box-open', permission: 'manage_products' },
                { key: 'orders', route: 'AdminOrders', text: 'Заказы', desc: 'Просмотр и обработка', icon: 'fa-solid fa-receipt', permission: 'manage_orders' },
                { key: 'clients', route: 'AdminClients', text: 'Пользователи', desc: 'База посетителей', icon: 'fa-solid fa-users', permission: 'manage_users' },
                { key: 'roles', route: 'AdminRoles', text: 'Роли и доступы', desc: 'Управление разрешениями', icon: 'fa-solid fa-user-shield', permission: 'manage_settings' },
                { key: 'partners', route: 'AdminPartners', text: 'Партнеры', desc: 'Сотрудничество и интеграции', icon: 'fa-solid fa-handshake', permission: 'manage_partners' },

                {
                    key: 'transactions',
                    route: 'AdminTransactions',
                    text: 'Транзакции',
                    desc: 'История платежей и статусы',
                    icon: 'fa-solid fa-money-bill-transfer',
                    permission: 'view_statistics' // Или 'manage_orders', если хотите ограничить доступ
                },

                {
                    key: 'achievements',
                    route: 'AdminAchievements',
                    text: 'Достижения',
                    desc: 'Ачивки и система наград',
                    icon: 'fa-solid fa-trophy',
                    permission: 'manage_achievements' // Если такого права нет, можно временно использовать 'manage_settings' или 'manage_promos'
                },

                { key: 'mailing', route: 'AdminBroadcastsPage', text: 'Рассылки', desc: 'Уведомления и акции', icon: 'fa-solid fa-envelope', permission: 'manage_broadcasts' },
                { key: 'stories', route: 'AdminStories', text: 'Истории', desc: 'Управление сторис', icon: 'fa-solid fa-circle-play', permission: 'manage_stories' },
                { key: 'tables', route: 'AdminTablesManager', text: 'Столики', desc: 'Бронирование и схема', icon: 'fa-solid fa-chair', permission: 'manage_tables',
                    },
                { key: 'promo', route: 'AdminPromoCodes', text: 'Промокоды', desc: 'Скидки и бонусы', icon: 'fa-solid fa-tags', permission: 'manage_promos' },
                { key: 'wheel', route: 'WheelAdmin', text: 'Колесо фортуны', desc: 'Розыгрыш призов', icon: 'fa-solid fa-dharmachakra', permission: 'manage_settings' },
                { key: 'crm', route: 'AdminKanban', text: 'CRM', desc: 'Воронка и сделки', icon: 'fa-solid fa-address-book', permission: 'manage_crm' },
                { key: 'statistic', route: 'AdminStatistic', text: 'Статистика', desc: 'Аналитика и отчеты', icon: 'fa-solid fa-chart-line', permission: 'view_statistics' },
                { key: 'landing', route: 'AdminShopLanding', text: 'Лендинг', desc: 'Настройка посадочной страницы', icon: 'fa-solid fa-laptop-code', permission: 'manage_landing' },
                { key: 'tap_link', route: 'TapLinkAdmin', text: 'Tap-link', desc: 'Мобильная визитка', icon: 'fa-solid fa-mobile-screen', permission: 'manage_taplink' },
                { key: 'utm', route: 'LinkManagerV2', text: 'UTM-метки', desc: 'Трекинг источников', icon: 'fa-solid fa-link', permission: 'manage_utm' },
                { key: 'invoice', route: 'AdminInvoice', text: 'Счета', desc: 'Выставление счетов', icon: 'fa-solid fa-file-invoice-dollar', permission: 'manage_invoices' },
                { key: 'settings', route: 'AdminTenant', text: 'Настройки', desc: 'Конфигурация магазина', icon: 'fa-solid fa-gear', permission: 'manage_settings' },
            ];

            // Фильтрация: должно выполняться И условие системы (condition), И право пользователя (permission)
            return items.filter(item => {
                // 1. Проверка системного флага (например, включены ли столики в настройках)
                if (item.condition && !this.settings?.[item.condition]) {
                    return false;
                }

                // 2. Проверка прав доступа текущего пользователя
                if (!this.hasPermission(item.permission)) {
                    return false;
                }

                return true;
            });
        },

        // 🆕 Развлекательные кнопки
        entertainment() {
            const baseItems = [
                { key: 'wheel_of_fortune_btn', img: 'fortune.png', text: 'Колесо фортуны', route: 'WheelClassic' },
                { key: 'coffee_bonus_btn', img: 'coffee.png', text: 'Больше кофе', route: 'Coffee' },
                { key: 'social_quest_btn', img: 'social-quest.png', text: 'Квесты', route: 'GamesCatalog' },
            ];

            return this.applyMenuSettings(baseItems, {
                checkVisibility: true,
                checkCondition: true,
                useSettingsTitle: true,
                useSettingsImage: true,
            });
        },
    },

    mounted() {
        this.loadStories();
        this.loadScriptData();
        this.initDisabledModal();
    },

    beforeUnmount() {
        // 🆕 Очистка экземпляра модалки при уничтожении компонента
        if (this.disabledModal) {
            this.disabledModal.dispose();
        }
    },
    methods: {
        setAdminViewMode(mode) {
            this.adminViewMode = mode;
            localStorage.setItem('adminViewMode', mode);
        },
        initDisabledModal() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('disabledShopModal');
                    if (modalEl) {
                        this.disabledModal = new bootstrap.Modal(modalEl);
                    }
                }
            });
        },

        showDisabledModal() {
            if (this.disabledModal) {
                this.disabledModal.show();
            }
        },

        goToSettings() {
            if (this.disabledModal) {
                this.disabledModal.hide();
            }
            // Перенаправление на страницу настроек тенанта
            this.goTo('AdminTenant');
        },
        applyMenuSettings(items, options = {}) {
            const {
                checkVisibility = true,
                checkCondition = true,
                useSettingsTitle = true,
                useSettingsImage = true,
            } = options;

            const settingsIcons = this.settings?.icons || [];
            const iconsMap = new Map(settingsIcons.map(icon => [icon.slug, icon]));

            return items
                .filter(item => {
                    if (!checkCondition || !item.condition) return true;
                    return !!this.settings?.[item.condition];
                })
                .filter(item => {
                    if (!checkVisibility) return true;
                    const iconConfig = iconsMap.get(item.key);
                    if (!iconConfig) return true;
                    return iconConfig.is_visible !== false;
                })
                .map(item => {
                    const iconConfig = iconsMap.get(item.key);
                    if (!iconConfig) return { ...item };

                    const result = { ...item };
                    if (useSettingsTitle && iconConfig.title) result.text = iconConfig.title;
                    if (useSettingsImage && iconConfig.image_url) result.img = iconConfig.image_url;

                    return result;
                });
        },
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

.sparkle-1 { top: -4px; right: -4px; animation-delay: 0s; }
.sparkle-2 { bottom: -4px; left: -4px; animation-delay: 0.7s; }
.sparkle-3 { top: 50%; right: -8px; animation-delay: 1.4s; }

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
   АДМИН МЕНЮ (Полный список)
   ========================================== */
.admin-menu-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.admin-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    color: var(--bs-body-color);
}

.admin-menu-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.admin-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.admin-item-text {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.admin-item-title {
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.2;
}

.admin-item-desc {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-top: 2px;
}

.admin-item-arrow {
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
    opacity: 0.5;
}

@media (max-width: 576px) {
    .greeting-name { font-size: 1.3rem; }
    .hero-avatar { width: 48px; height: 48px; font-size: 1.3rem; }
    .stat-pill { padding: 8px 10px; }
    .stat-value { font-size: 0.85rem; }
    .fortune-content { flex-wrap: wrap; }
    .fortune-action { width: 100%; justify-content: center; margin-top: 8px; }
}

:deep(*) {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* ==========================================
   ПЛАШКА "МАГАЗИН ВЫКЛЮЧЕН" (Premium Style)
   ========================================== */
.disabled-alert-btn {
    width: calc(100% - 16px); /* mx-2 компенсируется */
    border: none;
    border-radius: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
    box-shadow: 0 8px 24px rgba(255, 65, 108, 0.35);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    text-align: left;
    cursor: pointer;
    z-index: 1020; /* Выше обычного контента, но ниже модалок */
}

/* Эффект приподнимания и усиления тени при наведении */
.disabled-alert-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(255, 65, 108, 0.45);
}

.disabled-alert-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(255, 65, 108, 0.35);
}

/* Декоративный пробегающий блик */
.alert-shine {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.15),
        transparent
    );
    transform: translateX(-100%);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.disabled-alert-btn:hover .alert-shine {
    transform: translateX(100%);
}

/* Внутренняя структура */
.alert-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 2;
}

.alert-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Иконка в полупрозрачном круге (glassmorphism) */
.alert-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.alert-text-group {
    display: flex;
    flex-direction: column;
}

.alert-title {
    font-weight: 700;
    font-size: 1rem;
    line-height: 1.2;
    letter-spacing: 0.2px;
}

.alert-subtitle {
    font-size: 0.8rem;
    opacity: 0.9;
    font-weight: 400;
    margin-top: 3px;
}

/* Стрелка справа */
.alert-arrow {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.disabled-alert-btn:hover .alert-arrow {
    background: rgba(255, 255, 255, 0.35);
    transform: translateX(4px);
}

/* Адаптив для очень маленьких экранов */
@media (max-width: 380px) {
    .disabled-alert-btn {
        padding: 14px 16px;
    }
    .alert-icon-wrapper {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }
    .alert-title {
        font-size: 0.9rem;
    }
    .alert-subtitle {
        font-size: 0.75rem;
    }
}

/* Добавьте это к существующим стилям модалки, если их там нет */
.modal-header.bg-danger .modal-icon {
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    border: 1px solid rgba(220, 53, 69, 0.1);
}

.modal-header.bg-danger {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%) !important;
}

/* ==========================================
   🆕 ПЕРЕКЛЮЧАТЕЛЬ ВИДА АДМИН-МЕНЮ
   ========================================== */
.admin-view-toggle {
    display: flex;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
    flex-shrink: 0;
}

.admin-view-toggle .toggle-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 6px;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.admin-view-toggle .toggle-btn.active {
    background: var(--bs-primary);
    color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.admin-view-toggle .toggle-btn:hover:not(.active) {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

/* ==========================================
   🆕 СЕТКА (GRID) ДЛЯ АДМИН-МЕНЮ
   ========================================== */
.admin-menu-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 колонки на мобильных */
    gap: 12px;
}

@media (min-width: 576px) {
    .admin-menu-grid {
        grid-template-columns: repeat(3, 1fr); /* 3 колонки на планшетах */
    }
}

@media (min-width: 768px) {
    .admin-menu-grid {
        grid-template-columns: repeat(4, 1fr); /* 4 колонки на десктопе */
    }
}

.admin-grid-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--bs-body-color);
    position: relative;
    overflow: hidden;
}

.admin-grid-item:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.admin-grid-item:active {
    transform: translateY(-1px);
}

.admin-grid-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 14px;
    transition: all 0.25s ease;
}

.admin-grid-item:hover .admin-grid-icon {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.05);
}

.admin-grid-title {
    font-weight: 700;
    font-size: 0.9rem;
    line-height: 1.2;
    margin-bottom: 6px;
    color: var(--bs-body-color);
}

.admin-grid-desc {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    line-height: 1.3;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Ограничиваем описание 2 строками */
    -webkit-box-orient: vertical;
}

/* ==========================================
   АДАПТИВ БОНУСОВ ДЛЯ БОЛЬШИХ ЭКРАНОВ
   ========================================== */
@media (min-width: 992px) {
    /* 1. Центрируем и ограничиваем заголовок секции */
    .section-header {
        max-width: 1000px;
        margin: 32px auto 20px auto; /* Чуть больше воздуха сверху */
        padding: 0 16px;
    }

    /* 2. Ограничиваем карусель, убираем отрицательные отступы */
    .bonus-carousel {
        margin: 0 auto !important;
        max-width: 1000px;
        padding: 0 16px;
    }

    /* 3. Делаем сами карточки компактными и пропорциональными */
    .bonus-card {
        max-width: 380px; /* Оптимальная ширина для десктопной карточки */
        margin: 0 auto;   /* Центрируем карточку внутри её слайда */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    /* Усиливаем hover-эффект для десктопа (мышь vs палец) */
    .bonus-card:hover:not(:disabled) {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        border-color: var(--bs-primary);
    }

    /* 4. Корректируем пропорции изображения */
    .bonus-image-wrapper {
        aspect-ratio: 4/3; /* Более классическая "карточная" пропорция вместо 16/10 */
    }

    /* 5. Добавляем воздуха внутрь карточки */
    .bonus-info {
        padding: 16px;
    }

    .bonus-title {
        font-size: 1rem;
        margin-bottom: 12px;
        line-height: 1.3;
    }

    .bonus-action {
        font-size: 0.8rem;
    }
}

/* 🆕 СТИЛИ ДЛЯ КНОПКИ CRM */
.btn-crm-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    color: var(--primary);
    border: 2px solid var(--primary);
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
    margin-bottom:16px;
}
.btn-crm-link:hover {
    transform: translateY(-1px);
}
.btn-crm-link i {
    font-size: 13px;
    transition: transform 0.2s;
}
.btn-crm-link:hover i {
    transform: translate(2px, -2px);
}
</style>
