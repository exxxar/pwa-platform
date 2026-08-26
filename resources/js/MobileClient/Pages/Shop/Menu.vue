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
                        <button
                            v-if="isAdmin && self?.id"
                            class="copy-id-btn"
                            :class="{ 'is-copied': isCopied }"
                            @click="copyUserId"
                            title="Скопировать ID пользователя"
                        >
                            <i :class="isCopied ? 'fa-solid fa-check' : 'fa-regular fa-copy'"></i>
                            <span class="ms-1">#{{ self.id }}</span>
                        </button>
                    </div>
                    <div @click="goTo('Profile')" class="hero-avatar">
                        <img v-if="self?.avatar" v-lazy="self.avatar" alt="Аватар">
                        <i v-else class="fa-solid fa-user"></i>
                    </div>
                </div>

                <!-- Мини-статистика -->
                <div class="hero-stats">
                    <button class="stat-pill" @click="goTo('Cashback')">
                        <i class="fa-solid fa-coins"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.cashback_balance || 0 }} ₽</span>
                            <span class="stat-label">Баланс</span>
                        </div>
                    </button>
                    <button class="stat-pill" @click="goTo('Orders')">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <div class="stat-info">
                            <span class="stat-value">{{ self?.orders_count || 0 }}</span>
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

        <!-- Истории -->
        <div v-if="storiesStore.stories?.length || isAdmin" class="px-2 mt-3">
            <StoryList
                :stories="storiesStore.stories"
                :is-admin="isAdmin"
                @create-story="showCreateStoryModal = true"
            />
        </div>

        <!-- Статус магазина -->
        <StoreStatusBanner
            :is-work="isWork"
            :schedule="tenant?.settings?.company?.schedule"
        />

        <div class="container py-2 px-2">
            <!-- Плашка "Магазин выключен" (только для админа) -->
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
                    <div class="alert-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </div>
                <div class="alert-shine"></div>
            </button>

            <!-- Настройки темы -->
            <div class="theme-settings-card mb-4">
                <button class="theme-toggle-header" @click="showThemeSettings = !showThemeSettings">
                    <div class="d-flex align-items-center gap-2">
                        <div class="theme-icon-mini"><i class="fa-solid fa-palette"></i></div>
                        <div class="text-start">
                            <div class="fw-semibold small">Настройки оформления</div>
                            <small class="text-muted" style="font-size: 0.7rem;">Тема, цвет, режим</small>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow" :class="{ 'rotated': showThemeSettings }"></i>
                </button>

                <transition name="slide-down">
                    <div v-show="showThemeSettings" class="theme-settings-body">
                        <ThemeToggle class="mb-2"/>
                        <ThemeColorPicker class="mb-2"/>
                        <ThemeSchemePicker/>
                    </div>
                </transition>
            </div>

            <!-- Ежедневный бонус -->
            <button
                v-if="dailyBonusState.available"
                class="daily-bonus-card"
                :class="{ 'is-claimed': dailyBonusState.already_claimed }"
                @click="goToDailyBonus"
            >
                <div class="db-background"></div>
                <div class="db-shine"></div>
                <div v-if="!dailyBonusState.already_claimed" class="db-particles">
                    <span class="db-coin coin-1">💰</span><span class="db-coin coin-2">✨</span>
                    <span class="db-coin coin-3">💎</span><span class="db-coin coin-4">⭐</span>
                    <span class="db-coin coin-5">🎁</span><span class="db-coin coin-6">💰</span>
                </div>

                <div class="db-content">
                    <div class="db-icon-wrapper">
                        <div class="db-icon" :class="{ 'claimed': dailyBonusState.already_claimed }">
                            <i :class="dailyBonusState.already_claimed ? 'fa-solid fa-box-open' : 'fa-solid fa-gift'"></i>
                        </div>
                        <div v-if="!dailyBonusState.already_claimed" class="db-glow"></div>
                        <div v-if="!dailyBonusState.already_claimed" class="db-pulse"></div>
                    </div>

                    <div class="db-text">
                        <div class="db-label"><i class="fa-solid fa-calendar-check"></i> Ежедневный бонус</div>
                        <div class="db-title" v-if="!dailyBonusState.already_claimed">Забери свой подарок!</div>
                        <div class="db-title claimed" v-else>Бонус уже получен</div>
                        <div class="db-streak" v-if="dailyBonusState.current_streak > 0">
                            <i class="fa-solid fa-fire"></i>
                            <span>Серия: <strong>{{ dailyBonusState.current_streak }}</strong> {{ pluralizeDays(dailyBonusState.current_streak) }}</span>
                        </div>
                    </div>

                    <div class="db-action" :class="{ 'claimed': dailyBonusState.already_claimed }">
                        <span v-if="!dailyBonusState.already_claimed"><i class="fa-solid fa-hand-pointer"></i> Забрать</span>
                        <span v-else class="db-timer"><i class="fa-regular fa-clock"></i> {{ timeUntilNext }}</span>
                        <i class="fa-solid fa-arrow-right db-arrow"></i>
                    </div>
                </div>
                <div v-if="!dailyBonusState.already_claimed" class="db-bottom-shine"></div>
            </button>

            <!-- Секция: Сервисы -->
            <div class="section-header">
                <div class="section-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                <div>
                    <h6 class="section-title">Наши сервисы</h6>
                    <p class="section-subtitle">Быстрый доступ к функциям</p>
                </div>
            </div>

            <div class="services-grid" @touchstart="onTouchStart" @touchend="onTouchEnd">
                <div v-for="item in menuItems" :key="item.key">
                    <MainMenuItem
                        :route="item.route"
                        :default-image="item.img"
                        :default-text="item.text"
                        :item="item"
                    >
                        <template #counter v-if="item.key === 'basket'">
                            <span class="counter badge bg-primary">{{ basketStore.cartTotalCount || 0 }}</span>
                        </template>
                        <template #counter v-if="item.key === 'chat'">
                            <span class="counter badge bg-primary">{{ chatStore?.unreadCount || 0 }}</span>
                        </template>
                    </MainMenuItem>
                </div>
            </div>

            <!-- Колесо Фортуны -->
            <div class="fortune-card mt-4" @click="goTo('WheelClassic')">
                <div class="fortune-background"></div>
                <div class="fortune-content">
                    <div class="fortune-icon-wrapper">
                        <div class="fortune-icon"><i class="fa-solid fa-dice"></i></div>
                        <div class="fortune-sparkle sparkle-1">✨</div>
                        <div class="fortune-sparkle sparkle-2">✨</div>
                        <div class="fortune-sparkle sparkle-3">✨</div>
                    </div>
                    <div class="fortune-text">
                        <h5 class="fortune-title">Колесо Фортуны</h5>
                        <p class="fortune-description">Крути колесо и получай бонусы, скидки и призы!</p>
                    </div>
                    <div class="fortune-action">
                        <span>Испытать удачу</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>

            <!-- Секция: Бонусы -->
            <div class="section-header mt-4">
                <div class="section-icon bonus-icon"><i class="fa-solid fa-gift"></i></div>
                <div>
                    <h6 class="section-title">Система бонусов</h6>
                    <p class="section-subtitle">Зарабатывай и получай награды</p>
                </div>
            </div>

            <Carousel v-bind="carouselConfig" class="bonus-carousel">
                <Slide v-for="item in entertainment" :key="item.key">
                    <div class="bonus-slide">
                        <button class="bonus-card" @click="goTo(item.route)" :disabled="!item.route">
                            <div class="bonus-image-wrapper">
                                <img :src="'/images/shop/' + item.img" :alt="item.title">
                            </div>
                            <div class="bonus-info">
                                <div class="bonus-title">{{ item.title }}</div>
                                <div class="bonus-action">
                                    <span>Открыть</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                        </button>
                    </div>
                </Slide>
                <template #addons><Navigation/></template>
            </Carousel>

            <OrderPeriscope />
            <AppDivider icon="fa-regular fa-address-card" text="Делимся контактами" class="flex-grow-1 m-0 my-3" />
            <TaplinkButton class="mb-3" label="Таплинк" icon="fa-regular fa-hand-pointer"/>

            <!-- ===== АДМИНКА (только для админов) ===== -->
            <template v-if="isAdmin">
                <AdminBillingCard/>

                <div class="admin-section-header mt-4 mb-3">
                    <AppDivider icon="fa-solid fa-screwdriver-wrench" text="Админ-панель" class="flex-grow-1 m-0" />
                </div>

                <div class="admin-tabs-wrapper mb-3">
                    <div class="admin-tabs">
                        <button class="admin-tab" :class="{ active: adminCategory === 'basic' }" @click="adminCategory = 'basic'">
                            <div class="admin-tab-icon"><i class="fa-solid fa-store"></i></div>
                            <div class="admin-tab-text">
                                <span class="admin-tab-title">Магазин</span>
                                <span class="admin-tab-count">{{ basicAdminItems.length }}</span>
                            </div>
                        </button>
                        <button class="admin-tab" :class="{ active: adminCategory === 'games' }" @click="adminCategory = 'games'">
                            <div class="admin-tab-icon games"><i class="fa-solid fa-gamepad"></i></div>
                            <div class="admin-tab-text">
                                <span class="admin-tab-title">Игры</span>
                                <span class="admin-tab-count games">{{ gamesAdminItems.length }}</span>
                            </div>
                            <div class="admin-tab-badge" v-if="gamesAdminItems.length > 0"><span class="badge-pulse"></span></div>
                        </button>
                    </div>

                    <div class="admin-view-toggle">
                        <button class="toggle-btn" :class="{ active: adminViewMode === 'list' }" @click="adminViewMode = 'list'" title="Список">
                            <i class="fa-solid fa-list"></i>
                        </button>
                        <button class="toggle-btn" :class="{ active: adminViewMode === 'grid' }" @click="adminViewMode = 'grid'" title="Сетка">
                            <i class="fa-solid fa-border-all"></i>
                        </button>
                    </div>
                </div>

                <transition name="tab-fade" mode="out-in">
                    <div v-if="adminCategory === 'basic'" key="basic" class="admin-category-desc">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Управление товарами, заказами, пользователями и настройками магазина</span>
                    </div>
                    <div v-else key="games" class="admin-category-desc games">
                        <i class="fa-solid fa-dice"></i>
                        <span>Настройка игровых механик, призов и шансов выпадения</span>
                    </div>
                </transition>

                <a v-if="canOpenCrm && adminCategory === 'basic'" :href="crmBoardUrl" target="_blank" rel="noopener noreferrer" class="btn-crm-link">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Открыть CRM</span>
                </a>

                <transition name="tab-slide" mode="out-in">
                    <!-- Базовое меню -->
                    <div v-if="adminCategory === 'basic'" key="basic-content">
                        <div v-if="adminViewMode === 'list'" class="admin-menu-list">
                            <button v-for="item in basicAdminItems" :key="item.key" class="admin-menu-item" @click="goTo(item.route)">
                                <div class="admin-item-icon"><i :class="item.icon"></i></div>
                                <div class="admin-item-text">
                                    <span class="admin-item-title">{{ item.text }}</span>
                                    <span class="admin-item-desc">{{ item.desc }}</span>
                                </div>
                                <i class="fa-solid fa-chevron-right admin-item-arrow"></i>
                            </button>
                            <div v-if="!basicAdminItems.length" class="admin-empty"><i class="fa-solid fa-lock"></i><span>Нет доступных разделов</span></div>
                        </div>
                        <div v-else class="admin-menu-grid">
                            <button v-for="item in basicAdminItems" :key="item.key" class="admin-grid-item" @click="goTo(item.route)">
                                <div class="admin-grid-icon"><i :class="item.icon"></i></div>
                                <div class="admin-grid-title">{{ item.text }}</div>
                                <div class="admin-grid-desc">{{ item.desc }}</div>
                            </button>
                            <div v-if="!basicAdminItems.length" class="admin-empty"><i class="fa-solid fa-lock"></i><span>Нет доступных разделов</span></div>
                        </div>
                    </div>

                    <!-- Игровое меню -->
                    <div v-else key="games-content">
                        <div v-if="adminViewMode === 'list'" class="admin-menu-list games-list">
                            <button v-for="item in gamesAdminItems" :key="item.key" class="admin-menu-item game-item" @click="goTo(item.route)">
                                <div class="admin-item-icon game-icon" :style="{ background: item.gradient }"><i :class="item.icon"></i></div>
                                <div class="admin-item-text">
                                    <span class="admin-item-title">{{ item.text }}</span>
                                    <span class="admin-item-desc">{{ item.desc }}</span>
                                </div>
                                <div class="game-item-badge" :style="{ color: item.accentColor }"><i :class="item.badgeIcon"></i></div>
                                <i class="fa-solid fa-chevron-right admin-item-arrow"></i>
                            </button>
                            <div v-if="!gamesAdminItems.length" class="admin-empty"><i class="fa-solid fa-gamepad"></i><span>Нет доступных игр</span></div>
                        </div>
                        <div v-else class="games-menu-grid">
                            <button v-for="item in gamesAdminItems" :key="item.key" class="game-grid-card" :style="{ '--accent': item.accentColor, '--gradient': item.gradient }" @click="goTo(item.route)">
                                <div class="game-grid-bg"></div>
                                <div class="game-grid-content">
                                    <div class="game-grid-icon"><i :class="item.icon"></i></div>
                                    <div class="game-grid-title">{{ item.text }}</div>
                                    <div class="game-grid-desc">{{ item.desc }}</div>
                                </div>
                                <div class="game-grid-shine"></div>
                            </button>
                            <div v-if="!gamesAdminItems.length" class="admin-empty"><i class="fa-solid fa-gamepad"></i><span>Нет доступных игр</span></div>
                        </div>
                    </div>
                </transition>
            </template>
        </div>

        <!-- МОДАЛКА: Магазин выключен -->
        <div class="modal fade" id="disabledShopModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content edit-modal">
                    <div class="modal-header bg-danger text-white border-0 pb-0">
                        <div class="modal-icon bg-white text-danger"><i class="fa-solid fa-power-off"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">Магазин выключен</h5>
                            <small class="text-white-50">Пользователи видят страницу обслуживания</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <p class="mb-0 text-muted">В данный момент магазин находится в режиме технических работ. Обычные пользователи не имеют доступа к витрине.</p>
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


        <!-- МОДАЛКА: Создание истории -->
        <StoryCreateModal v-if="showCreateStoryModal" @close="showCreateStoryModal = false" @saved="showCreateStoryModal = false" />
    </div>
</template>

<script>
import ThemeToggle from '@/MobileClient/Components/ThemeToggle.vue';
import ThemeSchemePicker from '@/MobileClient/Components/ThemeSchemePicker.vue';
import ThemeColorPicker from "@/MobileClient/Components/ThemeColorPicker.vue";
import MainMenuItem from "@/MobileClient/Components/Shop/MainMenuItem.vue";
import StoryList from "@/MobileClient/Components/Shop/Stories/StoryList.vue";
import 'vue3-carousel/dist/carousel.css';
import { Carousel, Slide, Navigation } from 'vue3-carousel';
import { useStoriesStore } from "@/MobileClient/stores/Shop/stories.js";
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import StoreStatusBanner from '@/MobileClient/Components/StoreStatusBanner.vue';
import AppDivider from "@/MobileClient/Components/AppDivider.vue";
import OrderPeriscope from "@/MobileClient/Components/Shop/OrderPeriscope.vue";
import { usePermissions } from '@/MobileClient/composables/usePermissions.js';
import StoryCreateModal from '@/MobileClient/Components/Shop/Stories/StoryCreateModal.vue';
import TaplinkButton from '@/MobileClient/Components/Common/TaplinkButton.vue';
import AdminBillingCard from '@/MobileClient/Components/Admin/AdminBillingCard.vue';

export default {
    name: "HomePage",
    components: {
        AppDivider, TaplinkButton, AdminBillingCard, ThemeToggle, StoryCreateModal,
        OrderPeriscope, ThemeColorPicker, ThemeSchemePicker, MainMenuItem, StoryList,
        Carousel, Slide, Navigation, StoreStatusBanner,
    },

    setup() {
        return {
            storiesStore: useStoriesStore(),
            basketStore: useBasketStore(),
            ...usePermissions(),
        };
    },

    data() {
        return {
            showThemeSettings: false,
            adminViewMode: localStorage.getItem('adminViewMode') || 'list',
            adminCategory: localStorage.getItem('adminCategory') || 'basic',
            disabledModal: null,
            showCreateStoryModal: false,
            carouselConfig: { itemsToShow: 2.5, wrapAround: true, snapAlign: 'start' },
            script_data: { is_disabled: false, disabled_text: '' },
            touchStartX: 0,
            touchEndX: 0,
            isCopied: false,
            copyTimeout: null,
            dailyBonusState: { available: false, current_streak: 0, already_claimed: false, server_date: null },
            timeUntilNext: '',
            timerInterval: null,

        };
    },

    computed: {
        tenant() { return window.Tenant || null; },
        self() { return window.TenantUser || null; },
        settings() { return this.tenant?.settings || null; },
        isDisabled() { return this.settings?.is_disabled === true || this.script_data?.is_disabled === true; },


        canOpenCrm() {
            return this.settings?.kanban?.enabled && this.settings?.kanban?.board_uuid?.length > 5;
        },
        crmBoardUrl() {
            if (!this.settings?.kanban?.board_uuid) return '#';
            const baseUrl = (this.settings?.kanban?.base_url || 'https://crm.mypwa.ru').replace(/\/api\/v1\/?$/i, '').replace(/\/$/, '');
            return `${baseUrl}/board/${this.settings.kanban.board_uuid}`;
        },

        basicAdminItems() {
            const items = [
                { key: 'products', route: 'AdminShop', text: 'Товары', desc: 'Управление каталогом', icon: 'fa-solid fa-box-open', permission: 'manage_products' },
                { key: 'orders', route: 'AdminOrders', text: 'Заказы', desc: 'Просмотр и обработка', icon: 'fa-solid fa-receipt', permission: 'manage_orders' },
                { key: 'clients', route: 'AdminClients', text: 'Пользователи', desc: 'База посетителей', icon: 'fa-solid fa-users', permission: 'manage_users' },
                { key: 'roles', route: 'AdminRoles', text: 'Роли и доступы', desc: 'Управление разрешениями', icon: 'fa-solid fa-user-shield', permission: 'manage_settings' },
                { key: 'partners', route: 'AdminPartners', text: 'Партнеры', desc: 'Сотрудничество и интеграции', icon: 'fa-solid fa-handshake', permission: 'manage_partners' },
                { key: 'transactions', route: 'AdminTransactions', text: 'Транзакции', desc: 'История платежей и статусы', icon: 'fa-solid fa-money-bill-transfer', permission: 'view_statistics' },
                { key: 'achievements', route: 'AdminAchievements', text: 'Достижения', desc: 'Ачивки и система наград', icon: 'fa-solid fa-trophy', permission: 'manage_achievements' },
                { key: 'mailing', route: 'AdminBroadcastsPage', text: 'Рассылки', desc: 'Уведомления и акции', icon: 'fa-solid fa-envelope', permission: 'manage_broadcasts' },
                { key: 'stories', route: 'AdminStories', text: 'Истории', desc: 'Управление сторис', icon: 'fa-solid fa-circle-play', permission: 'manage_stories' },
                { key: 'tables', route: 'AdminTablesManager', text: 'Столики', desc: 'Бронирование и схема', icon: 'fa-solid fa-chair', permission: 'manage_tables' },
                { key: 'promo', route: 'AdminPromoCodes', text: 'Промокоды', desc: 'Скидки и бонусы', icon: 'fa-solid fa-tags', permission: 'manage_promos' },
                { key: 'crm', route: 'AdminKanban', text: 'CRM', desc: 'Воронка и сделки', icon: 'fa-solid fa-address-book', permission: 'manage_crm' },
                { key: 'statistic', route: 'AdminStatistic', text: 'Статистика', desc: 'Аналитика и отчеты', icon: 'fa-solid fa-chart-line', permission: 'view_statistics' },
                { key: 'landing', route: 'AdminShopLanding', text: 'Лендинг', desc: 'Настройка посадочной страницы', icon: 'fa-solid fa-laptop-code', permission: 'manage_landing' },
                { key: 'tap_link', route: 'TapLinkAdmin', text: 'Tap-link', desc: 'Мобильная визитка', icon: 'fa-solid fa-mobile-screen', permission: 'manage_taplink' },
                { key: 'utm', route: 'LinkManagerV2', text: 'UTM-метки', desc: 'Трекинг источников', icon: 'fa-solid fa-link', permission: 'manage_utm' },
                { key: 'invoice', route: 'AdminInvoice', text: 'Счета', desc: 'Выставление счетов', icon: 'fa-solid fa-file-invoice-dollar', permission: 'manage_invoices' },
                { key: 'settings', route: 'AdminTenant', text: 'Настройки', desc: 'Конфигурация магазина', icon: 'fa-solid fa-gear', permission: 'manage_settings' },
            ];
            return items.filter(item => !item.condition || this.settings?.[item.condition])
                .filter(item => this.hasPermission(item.permission));
        },

        gamesAdminItems() {
            const items = [
                { key: 'wheel', route: 'WheelAdmin', text: 'Колесо фортуны', desc: 'Сектора, смайлы и призы', icon: 'fa-solid fa-dharmachakra', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #c0392b 0%, #e74c3c 100%)', accentColor: '#e74c3c', badgeIcon: 'fa-solid fa-star' },
                { key: 'daily_bonus', route: 'DailyBonusAdmin', text: 'Ежедневный бонус', desc: 'Серия дней и сундучки', icon: 'fa-solid fa-calendar-check', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', accentColor: '#fa709a', badgeIcon: 'fa-solid fa-fire' },
                { key: 'card_game', route: 'CardGameAdmin', text: 'Карточная игра', desc: 'Сетка карт и призы', icon: 'fa-solid fa-layer-group', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', accentColor: '#667eea', badgeIcon: 'fa-solid fa-clone' },
                { key: 'scratch_card', route: 'ScratchCardAdmin', text: 'Скретч-карта', desc: 'Стирание защитного слоя', icon: 'fa-solid fa-credit-card', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', accentColor: '#f5576c', badgeIcon: 'fa-solid fa-hand-pointer' },
                { key: 'slot_machine', route: 'SlotMachineAdmin', text: 'Слот-машина', desc: 'Барабаны и комбинации', icon: 'fa-solid fa-dice', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', accentColor: '#4facfe', badgeIcon: 'fa-solid fa-coins' },
                { key: 'quiz', route: 'QuizAdmin', text: 'Викторина', desc: 'Вопросы и ответы', icon: 'fa-solid fa-question', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', accentColor: '#30cfd0', badgeIcon: 'fa-solid fa-brain' },
                { key: 'guess_number', route: 'GuessNumberAdmin', text: 'Угадай число', desc: '3 режима и награды', icon: 'fa-solid fa-hashtag', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', accentColor: '#43e97b', badgeIcon: 'fa-solid fa-bullseye' },
                { key: 'treasure_hunt', route: 'TreasureHuntAdmin', text: 'Охота за сокровищами', desc: 'Карта, бустеры, уровни', icon: 'fa-solid fa-map-location-dot', permission: 'manage_settings', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)', accentColor: '#4facfe', badgeIcon: 'fa-solid fa-gem' },
            ];
            return items.filter(item => !item.condition || this.settings?.[item.condition])
                .filter(item => this.hasPermission(item.permission));
        },

        isWork() {
            const settings = this.settings;
            if (!settings || this.isDisabled) return !this.isDisabled;
            const schedule = settings.schedule;
            if (!Array.isArray(schedule) || !schedule.length) return true;

            try {
                const now = new Date();
                const todaySchedule = schedule[(now.getDay() + 6) % 7];
                if (!todaySchedule || typeof todaySchedule !== 'object' || todaySchedule.closed) return !todaySchedule?.closed;

                if (todaySchedule.start_at && todaySchedule.end_at) {
                    const currentMinutes = now.getHours() * 60 + now.getMinutes();
                    const [startH, startM] = String(todaySchedule.start_at).split(':').map(Number);
                    const [endH, endM] = String(todaySchedule.end_at).split(':').map(Number);
                    const startMinutes = startH * 60 + startM;
                    const endMinutes = endH * 60 + endM;
                    return endMinutes < startMinutes
                        ? (currentMinutes >= startMinutes || currentMinutes <= endMinutes)
                        : (currentMinutes >= startMinutes && currentMinutes <= endMinutes);
                }
                return true;
            } catch (e) {
                console.error('Ошибка при проверке времени работы:', e);
                return true;
            }
        },

        menuItems() {
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

            const iconsMap = new Map((this.settings?.icons || []).map(icon => [icon.slug, icon]));

            return Object.entries(baseConfig)
                .filter(([_, config]) => !config.condition || this.settings?.[config.condition])
                .map(([key, config]) => {
                    const setting = this.settings?.main_menu_items?.[key] || {};
                    const iconConfig = iconsMap.get(key) || {};
                    return {
                        key,
                        route: config.route,
                        text: setting.title || config.defaultTitle,
                        img: setting.img || config.defaultImg,
                        order: setting.order ?? config.order,
                        isVisible: iconConfig.is_visible !== false,
                    };
                })
                .filter(item => item.isVisible)
                .sort((a, b) => a.order - b.order);
        },

        entertainment() {
            const baseItems = [
                { key: 'wheel_of_fortune_btn', img: 'fortune.png', title: 'Колесо фортуны', route: 'WheelClassic' },
                { key: 'coffee_bonus_btn', img: 'coffee.png', title: 'Больше кофе', route: 'Coffee' },
                { key: 'social_quest_btn', img: 'social-quest.png', title: 'Квесты', route: 'GamesCatalog' },
            ];
            const iconsMap = new Map((this.settings?.icons || []).map(icon => [icon.slug, icon]));

            return baseItems.map(item => {
                const iconConfig = iconsMap.get(item.key) || {};
                return {
                    ...item,
                    title: iconConfig.title || item.title,
                    img: iconConfig.image_url || item.img,
                };
            }).filter(item => {
                const iconConfig = iconsMap.get(item.key);
                return !iconConfig || iconConfig.is_visible !== false;
            });
        }
    },

    mounted() {
        this.loadStories();
        this.initDisabledModal();
        this.loadDailyBonusState();
    },

    beforeUnmount() {
        if (this.disabledModal) this.disabledModal.dispose();
        if (this.copyTimeout) clearTimeout(this.copyTimeout);
        if (this.timerInterval) clearInterval(this.timerInterval);
    },

    methods: {
        goTo(name) { if (name) this.$router.push({ name }); },

        initDisabledModal() {
            this.$nextTick(() => {
                const modalEl = document.getElementById('disabledShopModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    this.disabledModal = new bootstrap.Modal(modalEl);
                }
            });
        },
        showDisabledModal() { if (this.disabledModal) this.disabledModal.show(); },
        goToSettings() {
            if (this.disabledModal) this.disabledModal.hide();
            this.goTo('AdminTenant');
        },

        // --- Бонусы ---
        async loadDailyBonusState() {
            try {
                const response = await this.$axios?.get('/daily-bonus/state') || { data: {} };
                if (response.data?.success) {
                    this.dailyBonusState = {
                        available: true,
                        current_streak: response.data.current_streak || 0,
                        already_claimed: response.data.today_opened || false,
                        server_date: response.data.server_date,
                    };
                    if (this.dailyBonusState.already_claimed) this.startNextDayTimer();
                }
            } catch (error) {
                this.dailyBonusState.available = true; // Fallback
            }
        },
        startNextDayTimer() {
            this.updateTimeUntilNext();
            this.timerInterval = setInterval(() => this.updateTimeUntilNext(), 1000);
        },
        updateTimeUntilNext() {
            const now = new Date();
            const endOfDay = new Date(now);
            endOfDay.setHours(23, 59, 59, 999);
            const diff = endOfDay - now;

            if (diff <= 0) {
                this.timeUntilNext = 'Обновляется...';
                this.loadDailyBonusState();
                return;
            }
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            this.timeUntilNext = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        },
        pluralizeDays(n) {
            const abs = Math.abs(n) % 100;
            const n1 = abs % 10;
            if (abs > 10 && abs < 20) return 'дней';
            if (n1 > 1 && n1 < 5) return 'дня';
            if (n1 === 1) return 'день';
            return 'дней';
        },
        goToDailyBonus() { this.goTo('DailyBonusGame'); },

        // --- Утилиты ---
        async copyUserId() {
            if (!this.self?.id) return;
            try {
                await navigator.clipboard.writeText(String(this.self.id));
                this.isCopied = true;
                if (this.copyTimeout) clearTimeout(this.copyTimeout);
                this.copyTimeout = setTimeout(() => { this.isCopied = false; }, 2000);
                this.$notify?.({ title: 'Успех', text: `ID пользователя #${this.self.id} скопирован`, type: 'success' });
            } catch (err) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось скопировать в буфер обмена', type: 'error' });
            }
        },
        async loadStories() {
            try { await this.storiesStore.loadStories(); }
            catch (e) { console.error('Ошибка загрузки stories:', e); }
        },
        onTouchStart(e) { this.touchStartX = e.changedTouches[0].screenX; },
        onTouchEnd(e) {
            const diff = this.touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                this.goTo(diff > 0 ? 'Cart' : 'Catalog');
            }
        },
    },
};
</script>


<style lang="scss" scoped>


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
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
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
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
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
    0%, 100% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
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
        margin: 0 auto; /* Центрируем карточку внутри её слайда */
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
    margin-bottom: 16px;
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

/* ==========================================
   🆕 КНОПКА КОПИРОВАНИЯ ID ПОЛЬЗОВАТЕЛЯ
   ========================================== */
.copy-id-btn {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    padding: 4px 10px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    font-weight: 500;
    font-family: monospace; /* Делает ID похожим на код */
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 4px;

    i {
        font-size: 0.8rem;
        transition: transform 0.2s ease;
    }

    &:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
        color: #fff;
        transform: translateY(-1px);

        i {
            transform: scale(1.1);
        }
    }

    &:active {
        transform: translateY(0);
    }

    /* Состояние после успешного копирования */
    &.is-copied {
        background: rgba(40, 167, 69, 0.25); /* Зеленый с прозрачностью */
        border-color: rgba(40, 167, 69, 0.5);
        color: #fff;

        i {
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
    }
}

@keyframes popIn {
    0% {
        transform: scale(0.5);
    }
    100% {
        transform: scale(1);
    }
}

/* ==========================================
   🆕 ШАПКА АДМИН-СЕКЦИИ
   ========================================== */
.admin-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ==========================================
   🆕 ТАБЫ: БАЗОВОЕ / ИГРЫ
   ========================================== */
.admin-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.admin-tabs {
    display: flex;
    gap: 8px;
    flex: 1;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.admin-tab {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--bs-secondary-color);
    text-align: left;
    position: relative;
    overflow: hidden;
}

.admin-tab:hover:not(.active) {
    background: rgba(var(--bs-primary-rgb), 0.04);
    color: var(--bs-body-color);
}

.admin-tab.active {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.admin-tab-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.admin-tab-icon.games {
    background: rgba(245, 87, 108, 0.1);
    color: #f5576c;
}

.admin-tab.active .admin-tab-icon {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

.admin-tab-text {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-width: 0;
}

.admin-tab-title {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

.admin-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 11px;
    font-size: 0.72rem;
    font-weight: 700;
    transition: all 0.25s ease;
}

.admin-tab-count.games {
    background: rgba(245, 87, 108, 0.1);
    color: #f5576c;
}

.admin-tab.active .admin-tab-count {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

/* Пульсирующий индикатор для игр (показывает, что там много интересного) */
.admin-tab-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    pointer-events: none;
}

.badge-pulse {
    display: block;
    width: 100%;
    height: 100%;
    background: #10B981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
    animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.admin-tab.active .admin-tab-badge {
    display: none;
}

/* ==========================================
   🆕 ОПИСАНИЕ КАТЕГОРИИ
   ========================================== */
.admin-category-desc {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(var(--bs-primary-rgb), 0.04);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
    margin-bottom: 16px;
}

.admin-category-desc i {
    color: var(--bs-primary);
    font-size: 0.9rem;
}

.admin-category-desc.games {
    background: rgba(245, 87, 108, 0.04);
    border-color: rgba(245, 87, 108, 0.15);
}

.admin-category-desc.games i {
    color: #f5576c;
}

/* Анимация переключения описания */
.tab-fade-enter-active,
.tab-fade-leave-active {
    transition: opacity 0.2s ease;
}

.tab-fade-enter-from,
.tab-fade-leave-to {
    opacity: 0;
}

/* Анимация переключения контента */
.tab-slide-enter-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tab-slide-leave-active {
    transition: all 0.2s ease;
}

.tab-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.tab-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* ==========================================
   🆕 ИГРОВОЕ МЕНЮ: СПИСОК (с акцентной иконкой)
   ========================================== */
.admin-menu-list.games-list {
    border-color: rgba(245, 87, 108, 0.15);
}

.admin-menu-item.game-item:hover {
    background: rgba(245, 87, 108, 0.04);
}

.admin-item-icon.game-icon {
    color: white !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.game-item-badge {
    font-size: 1rem;
    opacity: 0.7;
    margin-right: 4px;
}

/* ==========================================
   🆕 ИГРОВОЕ МЕНЮ: СЕТКА (премиум-карточки с градиентом)
   ========================================== */
.games-menu-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

@media (min-width: 576px) {
    .games-menu-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 768px) {
    .games-menu-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.game-grid-card {
    position: relative;
    padding: 20px 16px;
    border-radius: 18px;
    cursor: pointer;
    overflow: hidden;
    border: none;
    color: white;
    text-align: left;
    min-height: 140px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    background: var(--gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
}

.game-grid-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gradient);
    z-index: 0;
}

.game-grid-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
}

.game-grid-card:active {
    transform: translateY(-2px);
}

.game-grid-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    z-index: 1;
    pointer-events: none;
}

.game-grid-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.game-grid-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    margin-bottom: 14px;
    transition: transform 0.3s ease;
}

.game-grid-card:hover .game-grid-icon {
    transform: scale(1.08) rotate(-5deg);
}

.game-grid-title {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.2;
    margin-bottom: 6px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.game-grid-desc {
    font-size: 0.72rem;
    opacity: 0.9;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Декоративный блик при наведении */
.game-grid-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.18),
            transparent
    );
    transform: skewX(-20deg);
    transition: left 0.6s ease;
    z-index: 3;
    pointer-events: none;
}

.game-grid-card:hover .game-grid-shine {
    left: 120%;
}

/* ==========================================
   🆕 ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.admin-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
    text-align: center;
    grid-column: 1 / -1;
}

.admin-empty i {
    font-size: 2rem;
    opacity: 0.5;
}

.admin-empty span {
    font-size: 0.9rem;
    font-weight: 500;
}

/* ==========================================
   АДАПТИВ ТАБОВ
   ========================================== */
@media (max-width: 400px) {
    .admin-tabs-wrapper {
        flex-direction: column;
        align-items: stretch;
    }

    .admin-view-toggle {
        align-self: flex-end;
    }

    .admin-tab {
        padding: 8px 10px;
    }

    .admin-tab-icon {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }

    .admin-tab-title {
        font-size: 0.82rem;
    }
}

/* ==========================================
   🎁 ЕЖЕДНЕВНЫЙ БОНУС (Premium Gold Card)
   ========================================== */
.daily-bonus-card {
    position: relative;
    width: 100%;
    padding: 20px;
    margin-bottom: 24px;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    border: none;
    text-align: left;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    /* Золотой градиент */
    background: linear-gradient(
            135deg,
            #ffd700 0%,
            #ffb800 25%,
            #ff9500 50%,
            #ff7e00 75%,
            #ff6b00 100%
    );

    box-shadow: 0 8px 24px rgba(255, 165, 0, 0.35),
    0 2px 8px rgba(255, 140, 0, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);

    color: #4a2c00;

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(255, 165, 0, 0.45),
        0 4px 12px rgba(255, 140, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    &:active {
        transform: translateY(-1px);
    }

    /* Состояние "уже получен" */
    &.is-claimed {
        background: linear-gradient(135deg, #dd7b00, #dfab57, #ffb100);
        box-shadow: 0 4px 16px rgba(139, 115, 85, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
        color: #3a2c1a;

        &:hover {
            box-shadow: 0 8px 24px rgba(139, 115, 85, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }
    }
}

/* Фоновые декоративные элементы */
.db-background {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.3) 0%, transparent 40%),
    radial-gradient(circle at 90% 80%, rgba(255, 200, 0, 0.4) 0%, transparent 40%),
    radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
    pointer-events: none;

    .is-claimed & {
        opacity: 0.5;
    }
}

/* Пробегающий блик */
.db-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.4),
            transparent
    );
    transform: skewX(-20deg);
    animation: dbShine 3s ease-in-out infinite;
    pointer-events: none;
    z-index: 5;

    .is-claimed & {
        animation: none;
        opacity: 0;
    }
}

@keyframes dbShine {
    0% {
        left: -100%;
    }
    50%, 100% {
        left: 150%;
    }
}

/* Летающие частицы (монетки, звёздочки) */
.db-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.db-coin {
    position: absolute;
    font-size: 1.2rem;
    opacity: 0.7;
    animation: dbFloat 4s ease-in-out infinite;

    &.coin-1 {
        top: 15%;
        left: 10%;
        animation-delay: 0s;
    }

    &.coin-2 {
        top: 20%;
        right: 15%;
        animation-delay: 0.5s;
        font-size: 1rem;
    }

    &.coin-3 {
        top: 60%;
        left: 8%;
        animation-delay: 1s;
        font-size: 1.1rem;
    }

    &.coin-4 {
        bottom: 25%;
        right: 12%;
        animation-delay: 1.5s;
        font-size: 0.9rem;
    }

    &.coin-5 {
        top: 40%;
        left: 50%;
        animation-delay: 2s;
        font-size: 1.3rem;
        opacity: 0.5;
    }

    &.coin-6 {
        bottom: 15%;
        left: 30%;
        animation-delay: 2.5s;
        font-size: 1rem;
    }
}

@keyframes dbFloat {
    0%, 100% {
        transform: translate(0, 0) rotate(0deg);
        opacity: 0.7;
    }
    25% {
        transform: translate(8px, -12px) rotate(10deg);
        opacity: 0.9;
    }
    50% {
        transform: translate(-5px, -20px) rotate(-5deg);
        opacity: 0.6;
    }
    75% {
        transform: translate(10px, -10px) rotate(8deg);
        opacity: 0.8;
    }
}

/* Контент карточки */
.db-content {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Иконка сундучка */
.db-icon-wrapper {
    position: relative;
    flex-shrink: 0;
}

.db-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #fff5d6 0%, #ffe8a8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #b8860b;
    border: 2px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 6px 16px rgba(184, 134, 11, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
    animation: dbIconFloat 3s ease-in-out infinite;

    &.claimed {
        background: linear-gradient(135deg, #f5f0e6 0%, #e8dcc0 100%);
        color: #8b7355;
        animation: none;
        box-shadow: 0 4px 12px rgba(139, 115, 85, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }
}

@keyframes dbIconFloat {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-6px) rotate(-3deg);
    }
}

/* Пульсирующее свечение вокруг иконки */
.db-glow {
    position: absolute;
    inset: -8px;
    border-radius: 22px;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.5) 0%, transparent 70%);
    animation: dbGlowPulse 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes dbGlowPulse {
    0%, 100% {
        opacity: 0.4;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
    }
}

/* Волны-пульсации */
.db-pulse {
    position: absolute;
    inset: 0;
    border-radius: 18px;
    border: 2px solid rgba(255, 215, 0, 0.8);
    animation: dbPulse 2s ease-out infinite;
    pointer-events: none;
}

@keyframes dbPulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* Текстовый блок */
.db-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.db-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        font-size: 0.7rem;
    }
}

.db-title {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.2;
    color: #3a1f00;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);

    &.claimed {
        color: #4a3820;
        font-weight: 700;
        font-size: 1rem;
    }
}

.db-streak {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #7a3f00;
    margin-top: 2px;

    i {
        color: #ff4500;
        animation: streakFire 1.5s ease-in-out infinite;
    }

    strong {
        font-weight: 800;
        color: #3a1f00;
    }
}

@keyframes streakFire {
    0%, 100% {
        transform: scale(1) translateY(0);
    }
    50% {
        transform: scale(1.2) translateY(-2px);
    }
}

/* Кнопка действия */
.db-action {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #4a2c00 0%, #7a4500 100%);
    color: #ffd700;
    border-radius: 14px;
    font-size: 0.85rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(74, 44, 0, 0.3),
    inset 0 1px 0 rgba(255, 215, 0, 0.2);
    transition: all 0.2s ease;

    i:first-child {
        font-size: 0.9rem;
    }

    .db-arrow {
        transition: transform 0.2s ease;
    }

    &:hover .db-arrow {
        transform: translateX(3px);
    }

    &.claimed {
        background: linear-gradient(135deg, #5a4a30 0%, #6a5538 100%);
        color: #d4b870;
        font-size: 0.8rem;
    }

    .db-timer {
        display: flex;
        align-items: center;
        gap: 6px;
        font-variant-numeric: tabular-nums;
    }
}

.daily-bonus-card:hover .db-action:not(.claimed) {
    background: linear-gradient(135deg, #5a3400 0%, #8a5200 100%);
    box-shadow: 0 6px 16px rgba(74, 44, 0, 0.4),
    inset 0 1px 0 rgba(255, 215, 0, 0.3);
}

/* Нижний декоративный блик */
.db-bottom-shine {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40%;
    background: linear-gradient(
            to top,
            rgba(255, 255, 255, 0.15),
            transparent
    );
    pointer-events: none;
    border-radius: 0 0 20px 20px;
}

/* ==========================================
   🎁 АДАПТИВ ДЛЯ МАЛЕНЬКИХ ЭКРАНОВ
   ========================================== */
@media (max-width: 400px) {
    .daily-bonus-card {
        padding: 16px;
    }

    .db-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }

    .db-title {
        font-size: 1rem;
    }

    .db-action {
        padding: 8px 12px;
        font-size: 0.78rem;
    }

    .db-coin {
        font-size: 0.9rem;
        opacity: 0.5;
    }
}

@media (max-width: 350px) {
    .db-content {
        flex-wrap: wrap;
    }

    .db-action {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
    }
}

/* ==========================================
   🆕 МОДАЛЬНОЕ ОКНО ВЫБОРА ТАРИФА
   ========================================== */
.modal-overlay-custom {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container-custom {
    background: var(--bs-body-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header-custom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--bs-border-color);

    .modal-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--bs-body-color);
    }
}

.btn-close-custom {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: var(--bs-danger);
        color: white;
    }
}

.modal-body-custom {
    padding: 24px;
    overflow-y: auto;
}

.tariff-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;

    @media (max-width: 768px) {
        grid-template-columns: 1fr;
        max-width: 400px;
        margin: 0 auto;
    }
}

.tariff-card {
    position: relative;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;

    &:hover {
        border-color: var(--bs-primary);
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    }

    &.is-current {
        border-color: var(--bs-success);
        background: rgba(var(--bs-success-rgb, 25, 135, 84), 0.03);
    }

    &.is-popular {
        border-color: var(--bs-primary);
        box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.15);
    }
}

.tariff-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.tariff-header {
    text-align: center;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--bs-border-color);

    .tariff-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin: 0 0 8px 0;
    }

    .tariff-price {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--bs-primary);

        .period {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--bs-secondary-color);
        }
    }
}

.tariff-desc {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    text-align: center;
    margin-bottom: 20px;
    line-height: 1.4;
    min-height: 40px;
}

.tariff-features {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
    flex: 1;

    li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.9rem;
        color: var(--bs-body-color);
        margin-bottom: 12px;
        line-height: 1.4;

        i {
            color: var(--bs-success);
            margin-top: 4px;
            flex-shrink: 0;
        }
    }
}

.tariff-btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;

    &.btn-select {
        background: var(--bs-primary);
        color: white;

        &:hover:not(:disabled) {
            background: var(--bs-primary-hover, var(--bs-primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }
    }

    &.btn-current {
        background: var(--bs-success);
        color: white;
        cursor: default;
        opacity: 0.9;
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }
}
</style>
