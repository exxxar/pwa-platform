<template>
    <div class="app-layout">
        <Head>
            <title>CashMan - система твоего бизнеса внутри</title>
            <meta name="description" content="CashMan - система твоего бизнеса внутри"/>
        </Head>

        <!-- HEADER -->
        <header data-bs-theme="dark">
            <div class="navbar shadow-sm">
                <div class="container flex-row-reverse p-2">

                    <!-- Кэшбэк -->
                    <a
                        v-if="loadedCashback"
                        @click.prevent="goTo('Cashback')"
                        class="badge bg-primary btn"
                        href="#"
                    >
                        {{ cashback || 0 }} <i class="fa-solid fa-ruble-sign"></i>
                    </a>

                    <!-- Название магазина -->
                    <span
                        data-bs-toggle="modal"
                        data-bs-target="#bot-info-modal"
                        class="text-primary fw-bold cursor-pointer"
                    >
                        {{ tenant?.name || 'Магазин' }}
                    </span>


                    <HamburgerMenu
                        target-id="sidebar-menu"
                        @toggle="toggleSidebar"
                    />

                </div>
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
        <slot/>

        <!-- ProductInfo модалка (если нужна глобально) -->
        <ProductInfo/>

        <!-- BottomMenu -->
        <BottomMenu/>


        <!-- FOOTER -->
        <Footer/>


        <AppSidebar id="sidebar-menu"
                    @close="toggleSidebar"/>

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
            // Минимальная задержка для плавности
            setTimeout(() => {
                this.isLoading = false;
            }, 700);
        });


    },

    beforeUnmount() {
        // Отключаем observer при уничтожении компонента
        if (this.themeObserver) {
            this.themeObserver.disconnect();
        }
        window.removeEventListener('storage', this.handleStorageChange);
    },

    methods: {
        applyCurrentTheme() {
            const tenantSlug = this.tenantSlug

            const savedColor = localStorage.getItem(`theme_color_${tenantSlug}`);
            const savedScheme = localStorage.getItem(`theme_scheme_${tenantSlug}`);

            console.log("schecma", this.settings)
            // 🆕 1. Определяем ID схемы: приоритет у выбора пользователя, затем дефолт админа, затем 'default'
            const schemeId = savedScheme || (this.settings?.default_theme_scheme) || 'default';

            // Применяем схему
            this.applySchemeById(schemeId);

            // 2. Если пользователь явно менял акцентный цвет, применяем его поверх схемы
            if (savedColor) {
                this.applyColor(savedColor);
            }
        },
        installPWA() {
            if (typeof window.installPWA === 'function') {
                window.installPWA()
            } else {
                console.warn('PWA installation not available')
            }
        },
        toggleSidebar() {
             this.chat.fetchUnreadCount()

            // Если используешь Bootstrap Offcanvas программно:
            const el = document.getElementById('sidebar-menu');
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(el);

            bsOffcanvas ? bsOffcanvas.show() : bsOffcanvas.hide();
        },
        // Загрузка кэшбэка
        async loadCashback() {
            try {
                this.cashback = this.self?.cashBack?.amount || 0;
                this.loadedCashback = true;
            } catch (error) {
                console.error('Ошибка загрузки кэшбэка:', error);
                this.loadedCashback = false;
            }
        },



        // Применение акцентного цвета
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

        // Замените applySchemeById в AppLayout.vue на этот универсальный метод:
        applySchemeById(schemeId) {
            const scheme = getThemeScheme(schemeId);
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const colors = isDark ? scheme.dark : scheme.light;

            const root = document.documentElement;
            Object.entries(colors).forEach(([key, value]) => {
                root.style.setProperty(`--bs-${key}`, value);
            });
        },

        // Отслеживание изменений темы
        watchThemeChanges() {
            // MutationObserver для отслеживания изменения data-bs-theme
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

            // Слушаем изменения localStorage (синхронизация между вкладками)
            window.addEventListener('storage', this.handleStorageChange);
        },

        // Обработка смены режима день/ночь
        handleThemeModeChange() {
            // Переприменяем текущую тему с учётом нового режима
            this.$nextTick(() => {
                this.applyCurrentTheme();
            });
        },

        // Обработка изменений localStorage (из другой вкладки)
        handleStorageChange(event) {
            if (!event.key) return;

            const tenantSlug = this.tenantSlug;

            // Если изменился цвет темы
            if (event.key === `theme_color_${tenantSlug}` && event.newValue) {
                this.applyColor(event.newValue);
            }

            // Если изменилась схема темы
            if (event.key === `theme_scheme_${tenantSlug}` && event.newValue) {
                this.applySchemeById(event.newValue);
            }
        },

        // Конвертация HEX в RGB
        hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        // Осветление/затемнение цвета
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
