<template>
    <div class="app-layout">
        <Head>
            <title>CashMan - система твоего бизнеса внутри</title>
            <meta name="description" content="CashMan - система твоего бизнеса внутри"/>
        </Head>

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
                    <span class="cashback-amount">{{ formatCashback(cashback) }}</span>
                </a>

            </div>
        </header>

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

        <!-- PWA установка -->
        <div class="modal fade" id="installPwaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-download me-2"></i>Установить приложение
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Вы можете установить Kanban как приложение и запускать его прямо с рабочего стола.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Позже</button>
                        <button class="btn btn-primary" @click="installPWA">
                            <i class="fa-solid fa-download me-2"></i>Установить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кастомная подсказка для iOS (скрыта по умолчанию) -->
        <div id="ios-install-prompt" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: #fff; padding: 20px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 9999; text-align: center;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #333;">
                Установите приложение для удобного доступа:<br>
                Нажмите <strong>Поделиться</strong>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                → <strong>На экран «Домой»</strong>
            </p>
            <button onclick="document.getElementById('ios-install-prompt').style.display='none'" style="background: #007aff; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 14px; cursor: pointer;">Понятно</button>
        </div>
    </div>
</template>

<script>
import {Head} from '@inertiajs/vue3';
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

        return { favorites, basket, chat };
    },
    data() {
        return {
            loadedCashback: false,
            cashback: 0,
            themeObserver: null,
            isLoading: false,
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

        this.$router.beforeEach((to, from, next) => {
            this.isLoading = true;
            next();
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

        installPWA() {
            if (typeof window.installPWA === 'function') {
                window.installPWA();
            } else {
                console.warn('PWA installation not available');
            }
        },

        toggleSidebar() {
            this.chat.fetchUnreadCount();
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
</style>
