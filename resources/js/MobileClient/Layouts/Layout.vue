<template>
    <div class="app-layout">


        <Head :title="pageTitle" />

        <!-- ========================================== -->
        <!-- OFFLINE BANNER (Баннер отсутствия сети) -->
        <!-- ========================================== -->
        <transition name="slide-down">
            <div v-if="queue.isOffline" class="offline-banner">
                <i class="fa-solid fa-wifi-slash"></i>
                <span>Нет подключения к интернету. Данные сохраняются локально.</span>
            </div>
        </transition>
        <!-- ========================================== -->
        <!-- MODERN HEADER -->
        <!-- ========================================== -->
        <header class="modern-header">
            <div class="header-container">

                <!-- 1. Кнопка меню (Гамбургер) -->
                <button class="menu-btn" @click="toggleSidebar" aria-label="Открыть меню">
                    <HamburgerMenu target-id="sidebar-menu" />
                </button>

                <!-- 2. Название магазина (Триггер модалки) -->
                <div
                    class="brand-area"
                    data-bs-toggle="modal"
                    data-bs-target="#bot-info-modal"
                    role="button"
                    tabindex="0"
                >
                    <span class="brand-name">{{ tenant?.name || 'Магазин' }}</span>
                    <i class="fa-solid fa-chevron-down brand-arrow"></i>
                </div>



                <!-- 3. Кэшбэк (Премиальный бейдж) -->
                <a
                    v-if="loadedCashback"
                    @click.prevent="goTo('Cashback')"
                    class="cashback-pill"
                    href="#"
                    title="Ваш кэшбэк"
                >
                    <i class="fa-solid fa-coins"></i>
                    <span class="cashback-amount">{{ formatCashback(self.cashback_balance || 0) }}</span>
                </a>

            </div>
        </header>

        <!-- ========================================== -->
        <!-- 🆕 QUEUE PILL (Вынесен под хедер, по центру) -->
        <!-- ========================================== -->
        <transition name="fade-scale">
            <div v-if="queue.hasTasks" class="queue-pill-wrapper">
                <button
                    class="queue-pill"
                    @click="openQueueModal"
                    title="Очередь отправки"
                >
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="queue-count">{{ queue.tasksCount }}</span>
                    <span class="queue-text">в очереди</span>
                    <i v-if="queue.isProcessing" class="fa-solid fa-spinner fa-spin ms-1"></i>
                </button>
            </div>
        </transition>

        <!-- LOADER -->
        <Preloader
            :visible="isLoading"
            fullscreen
            backdrop
            icon="fa-solid fa-spinner"
            text="Загрузка"
        />

        <!-- CONTENT -->
        <main class="app-content">
            <slot/>
        </main>

        <!-- ProductInfo модалка (если нужна глобально) -->
        <ProductInfo/>

        <!-- BottomMenu -->
        <BottomMenu/>

        <!-- FOOTER -->
        <Footer/>

        <AppSidebar id="sidebar-menu" @close="toggleSidebar"/>
        <ShopInfoModal/>

        <!-- MODAL: График работы -->
        <div
            class="modal fade"
            id="schedule-list-display"
            tabindex="-1"
            aria-labelledby="scheduleModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="scheduleModalLabel">График работы</h1>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="!canBuy" class="alert alert-danger mb-2">
                            Внимание! В данный момент покупки недоступны!
                        </div>
                        <ScheduleList
                            v-if="tenant"
                            :schedule="settings?.schedule"
                        />
                        <p v-if="isAdmin" class="my-2 d-flex justify-content-center">
                            <button
                                class="btn btn-link p-0 text-primary"
                                style="font-size: 12px;"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-shop-footer-description-modal"
                            >
                                <i class="fa-solid fa-pen-to-square"></i> редактировать
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 🆕 QUEUE MODAL (Модалка очереди) -->
        <!-- ========================================== -->
        <div class="modal fade" id="queue-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content modern-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-inbox text-primary me-2"></i> Очередь отправки
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>

                    <div class="modal-body">
                        <div v-if="!queue.hasTasks" class="text-center text-muted py-4">
                            <i class="fa-solid fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                            <p class="mb-0">Очередь пуста. Все данные синхронизированы.</p>
                        </div>

                        <ul v-else class="list-group list-group-flush queue-list">
                            <li v-for="task in queue.tasks" :key="task.id" class="list-group-item queue-item d-flex align-items-start gap-3 px-0">
                                <div class="queue-icon" :class="`type-${task.type}`">
                                    <i :class="getTaskIcon(task.type)"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ task.title }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ formatTaskDate(task.timestamp) }}
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger border-0" @click="queue.removeTask(task.id)" title="Удалить">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="modal-footer border-0 pt-0" v-if="queue.hasTasks">
                        <button class="btn btn-link text-danger" @click="queue.clearQueue()">
                            Очистить всё
                        </button>
                        <button class="btn btn-primary" @click="retryQueue" :disabled="queue.isProcessing || queue.isOffline">
                            <i class="fa-solid fa-rotate-right me-1" :class="{'fa-spin': queue.isProcessing}"></i>
                            {{ queue.isOffline ? 'Нет сети' : 'Отправить всё' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Редактор -->
        <div
            class="modal fade"
            id="edit-shop-footer-description-modal"
            tabindex="-1"
            aria-labelledby="editModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editModalLabel">Редактор</h1>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <!-- CompanyInfo -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 🚀 MODERN PWA INSTALLATION MODAL -->
        <transition name="modal-fade">
            <div v-if="showPwaModal" class="pwa-modal-overlay" @click.self="showPwaModal = false">
                <div class="pwa-modal-container">

                    <!-- Декоративное свечение -->
                    <div class="pwa-modal-glow"></div>

                    <!-- Шапка -->
                    <div class="pwa-modal-header">
                        <div class="pwa-icon-wrapper">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <h3 class="pwa-title">Установите приложение</h3>
                        <p class="pwa-subtitle">Получите мгновенный доступ к {{ tenant?.name || 'магазину' }} прямо с рабочего стола вашего устройства</p>
                    </div>

                    <!-- Тело: Инструкции в зависимости от ОС -->
                    <div class="pwa-modal-body">

                        <!-- Для iOS -->
                        <div v-if="isIOS" class="pwa-instruction-card ios-card">
                            <div class="instruction-step">
                                <span class="step-number">1</span>
                                <div class="step-content">
                                    <span>Нажмите кнопку</span>
                                    <span class="ios-share-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                                        Поделиться
                                    </span>
                                </div>
                            </div>
                            <div class="instruction-step">
                                <span class="step-number">2</span>
                                <div class="step-content">
                                    <span>Выберите</span>
                                    <span class="ios-action-text">«На экран «Домой»»</span>
                                </div>
                            </div>
                        </div>

                        <!-- Для Android / Desktop -->
                        <div v-else class="pwa-instruction-card desktop-card">
                            <p>Нажмите кнопку ниже, чтобы добавить ярлык приложения на главный экран или рабочий стол.</p>
                            <div class="browser-hints">
                                <span class="hint-badge"><i class="fa-brands fa-chrome"></i> Chrome</span>
                                <span class="hint-badge"><i class="fa-brands fa-safari"></i> Safari</span>
                                <span class="hint-badge"><i class="fa-brands fa-edge"></i> Edge</span>
                            </div>
                        </div>

                    </div>

                    <!-- Подвал с действиями -->
                    <div class="pwa-modal-footer">
                        <button class="pwa-btn-secondary" @click="hidePwaPrompt">
                            Позже
                        </button>
                        <button v-if="!isIOS && deferredPrompt" class="pwa-btn-primary" @click="installPWA">
                            <i class="fa-solid fa-download"></i>
                            Установить
                        </button>
                        <button v-else-if="isIOS" class="pwa-btn-primary" @click="hidePwaPrompt">
                            Понятно
                        </button>
                    </div>

                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'; // Обязательно импортируем
import ScheduleList from "@/MobileClient/Components/Shop/ScheduleList.vue";
import ProductInfo from "@/MobileClient/Components/Shop/ProductInfo.vue";
import Preloader from "@/MobileClient/Components/Shop/Preloader.vue";
import BottomMenu from "@/MobileClient/Components/BottomMenu.vue";
import ShareLink from "@/MobileClient/Components/ShareLink.vue";
import Footer from "@/MobileClient/Components/Footer.vue";
import AppSidebar from '@/MobileClient/Components/AppSidebar.vue';
import HamburgerMenu from '@/MobileClient/Components/HamburgerMenu.vue';
import pushNotifications from '@/MobileClient/mixins/pushNotifications';
import {useFavorites} from "@/MobileClient/composables/useFavorites";
import {useBasket} from "@/MobileClient/composables/useBasket";
import {useChat} from "@/MobileClient/composables/useChat";
import { getThemeScheme } from '@/MobileClient/constants/themeSchemes.js';
import ShopInfoModal from "@/MobileClient/Components/Shop/ShopInfoModal.vue";
import {useQueueStore} from "@/MobileClient/stores/useQueueStore";

export default {
    name: "AppLayout",
    mixins: [pushNotifications],
    components: {
        Head,
        ScheduleList,
        ProductInfo,
        Preloader,
        BottomMenu,
        ShareLink,
        ShopInfoModal,
        Footer,
        AppSidebar,
        HamburgerMenu,
    },
    setup() {
        const favorites = useFavorites();
        const basket = useBasket();
        const chat = useChat();
        const queue = useQueueStore(); // 🆕

        return { favorites, basket, chat, queue };
    },
    data() {
        return {
            queueModalInstance: null,
            loadedCashback: false,
            cashback: 0,
            themeObserver: null,
            isLoading: false,

            // 🆕 PWA State
            showPwaModal: false,
            deferredPrompt: null,
            isIOS: false,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },
        pageTitle(){
          return this.tenant?.name || 'Мини-магазин'
        },
        self() {
            return window.TenantUser || null;
        },

        settings() {
            return this.tenant?.settings || null;
        },

        isAdmin() {
            return this.self?.is_admin === true || this.self?.role === 'admin';
        },

        isWork() {
            if (!this.settings?.schedule) return true;
            if (typeof window.isCorrectSchedule !== 'function') return true;
            if (!window.isCorrectSchedule(this.settings.schedule)) return true;
            return this.settings.is_work ?? true;
        },

        canBuy() {
            if (!this.settings?.schedule) return true;
            if (typeof window.isCorrectSchedule !== 'function') return true;
            if (!window.isCorrectSchedule(this.settings.schedule)) return true;
            return (this.settings.is_work ?? true) || !!this.settings.can_buy_after_closing;
        },

        preparedMenuItem() {
            if (!this.settings?.icons) return {};
            return Object.fromEntries(
                this.settings.icons.map(item => [item.slug, item])
            );
        },

        links() {
            const links = this.settings?.links || {};
            return {
                inst: links.inst || null,
                vk: links.vk || null,
                map_link: links.map_link || null,
                site: links.site || null,
            };
        },

        sidebarItems() {
            const defaults = [
                {route: 'Menu', title: 'Главное меню'},
                {route: 'Profile', title: this.preparedMenuItem.profile?.title || 'Профиль'},
                {route: 'Catalog', title: this.preparedMenuItem.shop?.title || 'Магазин'},
                {route: 'ShopCart', title: this.preparedMenuItem.basket?.title || 'Корзина'},
                {route: 'Orders', title: this.preparedMenuItem.history?.title || 'История заказов'},
                {route: 'CashBack', title: 'CashBack'},
                {route: 'FeedBack', title: 'Оставить отзыв'},
            ];
            return defaults;
        },

        cartTotalCount() {
            return 0;
        },

        currentYear() {
            return new Date().getFullYear();
        },

        tenantSlug() {
            return this.tenant?.slug || 'any';
        },
    },

    mounted() {
        this.loadCashback();
        this.applyCurrentTheme();
        this.watchThemeChanges();
        this.initPWA(); // 🆕 Инициализируем логику PWA

        this.$router.beforeEach((to, from, next) => {
            this.isLoading = true;
            next();
        });

        this.$nextTick(() => {
            this.initQueueModal();
        });

        this.$router.afterEach(() => {
            setTimeout(() => {
                this.isLoading = false;
            }, 700);
        });
    },

    beforeUnmount() {
        if (this.themeObserver) {
            this.themeObserver.disconnect();
        }
        window.removeEventListener('storage', this.handleStorageChange);
    },

    methods: {

        // 🆕 Полная инициализация PWA
        initPWA() {
            // 1. Определяем iOS
            this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.navigator.standalone === true;

            // 2. Если это iOS, не в режиме приложения, и пользователь не скрывал подсказку
            if (this.isIOS && !isStandalone && !localStorage.getItem('ios_prompt_hidden')) {
                // Показываем модалку с небольшой задержкой, чтобы не мешать первой отрисовке
                setTimeout(() => {
                    this.showPwaModal = true;
                }, 1500);
            }

            // 3. Слушаем событие установки для Android/Desktop
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault(); // Отменяем стандартный баннер браузера
                this.deferredPrompt = e; // Сохраняем событие

                // Показываем нашу красивую модалку
                this.showPwaModal = true;
            });

            // 4. Отслеживаем успешную установку
            window.addEventListener('appinstalled', () => {
                this.deferredPrompt = null;
                this.showPwaModal = false;
                localStorage.setItem('ios_prompt_hidden', 'true'); // На всякий случай
                this.$notify?.({ title: 'Успех', text: 'Приложение успешно установлено!', type: 'success' });
            });
        },

        // 🆕 Логика кнопки "Установить"
        async installPWA() {
            if (this.isIOS) {
                // Для iOS мы просто закрываем модалку, пользователь должен сделать это вручную
                this.hidePwaPrompt();
                return;
            }

            if (this.deferredPrompt) {
                // Показываем нативный диалог установки браузера
                this.deferredPrompt.prompt();

                // Ждем выбора пользователя
                const { outcome } = await this.deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('Пользователь принял установку PWA');
                } else {
                    console.log('Пользователь отклонил установку PWA');
                }

                this.deferredPrompt = null;
            } else {
                console.warn('Установка PWA недоступна в этом браузере');
            }

            this.showPwaModal = false;
        },

        // 🆕 Логика кнопки "Позже" / "Понятно"
        hidePwaPrompt() {
            this.showPwaModal = false;
            // Запоминаем, что пользователь не хочет видеть подсказку (для iOS)
            localStorage.setItem('ios_prompt_hidden', 'true');
        },
        // 🆕 Форматирование числа кэшбэка (например: "1 250")
        formatCashback(amount) {
            return new Intl.NumberFormat('ru-RU').format(amount || 0);
        },

        applyCurrentTheme() {
            const tenantSlug = this.tenantSlug;
            const savedColor = localStorage.getItem(`theme_color_${tenantSlug}`);
            const savedScheme = localStorage.getItem(`theme_scheme_${tenantSlug}`);

            const schemeId = savedScheme || (this.settings?.default_theme_scheme) || 'default';
            this.applySchemeById(schemeId);

            if (savedColor) {
                this.applyColor(savedColor);
            }
        },



        toggleSidebar() {
            this.chat.loadUnreadCount();
            const el = document.getElementById('sidebar-menu');
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(el);
            bsOffcanvas ? bsOffcanvas.show() : bsOffcanvas.hide();
        },

        async loadCashback() {
            try {
                this.cashback = this.self?.cashBack?.amount || 0;
                this.loadedCashback = true;
            } catch (error) {
                console.error('Ошибка загрузки кэшбэка:', error);
                this.loadedCashback = false;
            }
        },

        applyColor(hex) {
            const root = document.documentElement;
            root.style.setProperty('--bs-primary', hex);

            const rgb = this.hexToRgb(hex);
            if (rgb) {
                root.style.setProperty('--bs-primary-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
            }

            root.style.setProperty('--bs-primary-hover', this.adjustColor(hex, -15));
            root.style.setProperty('--bs-primary-light', `${hex}20`);
        },

        applySchemeById(schemeId) {
            const scheme = getThemeScheme(schemeId);
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const colors = isDark ? scheme.dark : scheme.light;

            const root = document.documentElement;
            Object.entries(colors).forEach(([key, value]) => {
                root.style.setProperty(`--bs-${key}`, value);
            });
        },

        watchThemeChanges() {
            this.themeObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'data-bs-theme') {
                        this.handleThemeModeChange();
                    }
                });
            });

            this.themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme']
            });

            window.addEventListener('storage', this.handleStorageChange);
        },

        handleThemeModeChange() {
            this.$nextTick(() => {
                this.applyCurrentTheme();
            });
        },

        handleStorageChange(event) {
            if (!event.key) return;
            const tenantSlug = this.tenantSlug;

            if (event.key === `theme_color_${tenantSlug}` && event.newValue) {
                this.applyColor(event.newValue);
            }
            if (event.key === `theme_scheme_${tenantSlug}` && event.newValue) {
                this.applySchemeById(event.newValue);
            }
        },

        hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        adjustColor(color, percent) {
            const num = parseInt(color.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return '#' + (
                0x1000000 +
                (R < 255 ? (R < 0 ? 0 : R) : 255) * 0x10000 +
                (G < 255 ? (G < 0 ? 0 : G) : 255) * 0x100 +
                (B < 255 ? (B < 0 ? 0 : B) : 255)
            ).toString(16).slice(1);
        },

        goTo(name) {
            if (!name) return;
            this.$router.push({name});
        },

        goToBookingTable() {
            this.closeModal('bot-info-modal');
            this.goTo('TableBooking');
        },

        closeModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        },

        scrollTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        },

        initQueueModal() {
            const el = document.getElementById('queue-modal');
            if (el && typeof bootstrap !== 'undefined') {
                this.queueModalInstance = bootstrap.Modal.getOrCreateInstance(el);
            }
        },

        openQueueModal() {
            // 🛡️ Защита: если инстанс не создался в mounted — создадим сейчас
            if (!this.queueModalInstance) {
                const el = document.getElementById('queue-modal');
                if (el && typeof bootstrap !== 'undefined') {
                    this.queueModalInstance = bootstrap.Modal.getOrCreateInstance(el);
                }
            }

            if (this.queueModalInstance) {
                this.queueModalInstance.show();
            } else {
                console.error('Не удалось инициализировать модалку очереди');
            }
        },

        retryQueue() {
            this.queue.processQueue();
        },

        getTaskIcon(type) {
            switch(type) {
                case 'order': return 'fa-solid fa-cart-shopping';
                case 'message': return 'fa-solid fa-comment-dots';
                case 'feedback': return 'fa-solid fa-star';
                default: return 'fa-solid fa-paper-plane';
            }
        },

        formatTaskDate(isoString) {
            return new Date(isoString).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        }
    },
};
</script>

<style scoped>
/* ==========================================
   MODERN HEADER STYLES (Базовые / Мобильные)
   ========================================== */
.modern-header {
    position: sticky;
    top: 0;
    z-index: 1020;
    /* Эффект стекла (Glassmorphism) */
    background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(var(--bs-border-color-rgb, 0, 0, 0), 0.08);
    transition: all 0.3s ease;
}

/* Поддержка темной темы для стекла */
[data-bs-theme="dark"] .modern-header {
    background: rgba(var(--bs-body-bg-rgb, 33, 37, 41), 0.85);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    max-width: 1200px;
    margin: 0 auto;
}

/* 1. Кнопка меню */
.menu-btn {
    background: transparent;
    border: none;
    color: var(--bs-body-color);
    padding: 8px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-btn:hover {
    background: rgba(var(--bs-body-color-rgb, 0, 0, 0), 0.06);
    transform: scale(1.05);
}

.menu-btn:active {
    transform: scale(0.95);
}

/* 2. Бренд / Название магазина */
.brand-area {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 99px;
    transition: all 0.25s ease;
    /* На мобильных центрируем абсолютно */
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.brand-area:hover {
    background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.08);
}

.brand-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
    letter-spacing: -0.02em;
    white-space: nowrap;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.brand-arrow {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.brand-area:hover .brand-arrow {
    transform: translateY(2px);
}

/* 3. Кэшбэк (Премиальная пилюля) */
.cashback-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--bs-primary, #0d6efd) 0%, var(--bs-primary-hover, #0b5ed7) 100%);
    color: white;
    border-radius: 99px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.cashback-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.4);
    color: white;
}

.cashback-pill:active {
    transform: translateY(0);
}

.cashback-pill i {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
}

/* Адаптив для маленьких экранов */
@media (max-width: 576px) {
    .brand-name {
        max-width: 140px;
        font-size: 1rem;
    }

    .cashback-pill {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
}

/* ==========================================
   АДАПТИВ ДЛЯ БОЛЬШИХ ЭКРАНОВ (Десктоп / Планшет)
   ========================================== */
@media (min-width: 992px) {
    .modern-header {
        /* Делаем шапку визуально тоньше */
        padding: 4px 0;
    }

    .header-container {
        /* 1. Ограничиваем ширину, чтобы элементы не разлетались по краям монитора */
        max-width: 600px; /* Можно изменить на 500px или 700px по вкусу */

        /* 2. Уменьшаем вертикальные отступы */
        padding: 6px 20px;

        /* 3. Группируем элементы по центру как единый компактный блок
              (отлично сочетается с центрированным нижним меню) */
        justify-content: center;
        gap: 16px; /* Аккуратное расстояние между элементами */
    }

    /* Отключаем абсолютное позиционирование бренда,
       чтобы он встал в общий flex-поток рядом с другими элементами */
    .brand-area {
        position: static;
        transform: none;
        left: auto;
        padding: 6px 12px; /* Чуть компактнее */
    }

    /* Делаем сами элементы чуть компактнее для десктопа */
    .menu-btn {
        padding: 6px;
    }

    .cashback-pill {
        padding: 6px 14px;
        font-size: 0.85rem;
    }

    .brand-name {
        font-size: 1rem;
    }
}

/* Основной контент */
.app-content {
    min-height: calc(100vh - 60px);
}


/* ==========================================
   🚀 MODERN PWA INSTALLATION MODAL
   ========================================== */
.pwa-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.pwa-modal-container {
    position: relative;
    background: var(--bs-body-bg, #ffffff);
    width: 100%;
    max-width: 420px;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.pwa-modal-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(var(--bs-primary-rgb, 13, 110, 253), 0.08) 0%, transparent 60%);
    pointer-events: none;
}

.pwa-modal-header {
    padding: 32px 24px 16px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.pwa-icon-wrapper {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: linear-gradient(135deg, var(--bs-primary, #0d6efd) 0%, var(--bs-primary-hover, #0b5ed7) 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.3);
}

.pwa-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0 0 8px;
}

.pwa-subtitle {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
    margin: 0;
}

.pwa-modal-body {
    padding: 0 24px 24px;
    position: relative;
    z-index: 1;
}

.pwa-instruction-card {
    background: var(--bs-secondary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e9ecef);
    border-radius: 16px;
    padding: 16px;
}

.instruction-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.instruction-step:last-child {
    margin-bottom: 0;
}

.step-number {
    width: 24px;
    height: 24px;
    background: var(--bs-primary, #0d6efd);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.step-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.ios-share-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: var(--bs-primary, #007aff);
    background: rgba(var(--bs-primary-rgb, 0, 122, 255), 0.1);
    padding: 4px 10px;
    border-radius: 8px;
    width: fit-content;
    margin-top: 4px;
}

.ios-action-text {
    font-weight: 600;
    color: var(--bs-body-color);
    margin-top: 4px;
}

.desktop-card {
    text-align: center;
}

.desktop-card p {
    margin: 0 0 16px;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

.browser-hints {
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.hint-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: white;
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.pwa-modal-footer {
    padding: 16px 24px 24px;
    display: flex;
    gap: 12px;
    position: relative;
    z-index: 1;
}

.pwa-btn-secondary, .pwa-btn-primary {
    flex: 1;
    padding: 14px;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.pwa-btn-secondary {
    background: var(--bs-secondary-bg, #f8f9fa);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
}

.pwa-btn-secondary:hover {
    background: var(--bs-border-color);
}

.pwa-btn-primary {
    background: linear-gradient(135deg, var(--bs-primary, #0d6efd) 0%, var(--bs-primary-hover, #0b5ed7) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.3);
}

.pwa-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.4);
}

.pwa-btn-primary:active {
    transform: translateY(0);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

/* ==========================================
   📱 FLOATING HEADER ДЛЯ iPHONE (iOS Safari)
   ==========================================
   Детектор: -webkit-touch-callout работает только в iOS Safari / WebView
*/


@supports (-webkit-touch-callout: none) {
    .app-layout {
        padding-top: calc(env(safe-area-inset-top, 0px) + 8px);
    }

    .modern-header {
        margin: calc(env(safe-area-inset-top, 0px) + 8px) 12px 8px 12px;
        border-radius: 22px;

        /* Настоящее iOS-стекло */
        background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.72);
        backdrop-filter: saturate(180%) blur(20px);
        -webkit-backdrop-filter: saturate(180%) blur(20px);

        /* Тонкая окантовка в стиле iOS */
        border: 0.5px solid rgba(0, 0, 0, 0.12);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.5) inset,
            0 8px 24px rgba(0, 0, 0, 0.08);

        border-bottom: none; /* Убираем лишний бордер */
    }

    [data-bs-theme="dark"] .modern-header {
        background: rgba(var(--bs-body-bg-rgb, 33, 37, 41), 0.72);
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.05) inset,
            0 8px 24px rgba(0, 0, 0, 0.4);
    }

    /* Более тонкие внутренние отступы для компактности */
    .header-container {
        padding: 6px 12px;
    }
}

/* ==========================================
   🔧 ДОПОЛНИТЕЛЬНО: Скрываем плавающий стиль
      на iPad, оставляем только для iPhone (узкие экраны)
   ========================================== */
@supports (-webkit-touch-callout: none) {
    @media (min-width: 768px) {
        /* На iPad возвращаем классический полноэкранный header */
        .modern-header {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            border: none;
            border-bottom: 1px solid rgba(var(--bs-border-color-rgb, 0, 0, 0), 0.08);
        }

        .header-container {
            padding: 12px 16px;
            border-radius: 0;
        }
    }
}

/* ==========================================
   📡 OFFLINE BANNER
   ========================================== */
.offline-banner {
    position: sticky;
    top: 0;
    z-index: 1030; /* Выше хедера */
    background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
    color: white;
    text-align: center;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.offline-banner i {
    font-size: 1rem;
}

/* Анимация появления */
.slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}

/* ==========================================
   ⏳ QUEUE PILL (Под хедером, по центру, плавающий)
   ========================================== */
.queue-pill-wrapper {
    position: sticky;
    top: 90px; /* Отступ под хедером (зависит от высоты хедера) */
    z-index: 1015; /* Ниже хедера (1020), но выше контента */
    display: flex;
    justify-content: center;
    padding: 8px 16px 0;
    pointer-events: none; /* Пропускаем клики мимо кнопки */
    margin-bottom: -36px; /* Подтягиваем следующий контент вверх */
}

.queue-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #000;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 99px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    box-shadow:
        0 4px 16px rgba(255, 152, 0, 0.4),
        0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: pulse-warning 2s infinite, float 3s ease-in-out infinite;
    pointer-events: auto; /* Возвращаем кликабельность самой кнопке */
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.queue-pill:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow:
        0 8px 24px rgba(255, 152, 0, 0.5),
        0 4px 8px rgba(0, 0, 0, 0.15);
}

.queue-pill:active {
    transform: translateY(-1px) scale(0.98);
}

.queue-count {
    background: rgba(0, 0, 0, 0.15);
    color: #000;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 800;
    min-width: 22px;
    text-align: center;
}

.queue-text {
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

/* Анимация плавания (как будто висит в воздухе) */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

/* Анимация пульсации (привлекает внимание) */
@keyframes pulse-warning {
    0% {
        box-shadow:
            0 0 0 0 rgba(255, 152, 0, 0.7),
            0 4px 16px rgba(255, 152, 0, 0.4);
    }
    70% {
        box-shadow:
            0 0 0 12px rgba(255, 152, 0, 0),
            0 4px 16px rgba(255, 152, 0, 0.4);
    }
    100% {
        box-shadow:
            0 0 0 0 rgba(255, 152, 0, 0),
            0 4px 16px rgba(255, 152, 0, 0.4);
    }
}

/* Адаптив для iOS с плавающим хедером */
@supports (-webkit-touch-callout: none) {
    .queue-pill-wrapper {
        top: 72px; /* Чуть ниже из-за увеличенного хедера на iPhone */
    }
}

/* На десктопе с более компактным хедером */
@media (min-width: 992px) {
    .queue-pill-wrapper {
        top: 52px;
    }
}

.fade-scale-enter-active, .fade-scale-leave-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.fade-scale-enter-from, .fade-scale-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.85);
}
.fade-scale-enter-to {
    opacity: 1;
    transform: translateY(0) scale(1);
}
/* ==========================================
   📋 QUEUE MODAL STYLES
   ========================================== */
.modern-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.queue-list {
    max-height: 300px;
    overflow-y: auto;
}

.queue-item {
    border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0,0,0,0.05)) !important;
    padding-top: 12px !important;
    padding-bottom: 12px !important;
}

.queue-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.queue-icon.type-order { background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); }
.queue-icon.type-message { background: rgba(var(--bs-success-rgb), 0.1); color: var(--bs-success); }
.queue-icon.type-feedback { background: rgba(var(--bs-warning-rgb), 0.1); color: var(--bs-warning); }
.queue-icon.type-request { background: rgba(var(--bs-secondary-rgb), 0.1); color: var(--bs-secondary); }
</style>
