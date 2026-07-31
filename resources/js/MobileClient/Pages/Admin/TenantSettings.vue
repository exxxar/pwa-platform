<template>
    <div class="settings-page">
        <!-- Шапка -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-gear"></i> Настройки платформы</h1>
                <p class="page-subtitle">Управляйте всеми параметрами вашего магазина</p>
            </div>
            <div v-if="hasUnsavedChanges" class="unsaved-badge">
                <i class="fa-solid fa-circle-exclamation"></i> Есть несохранённые изменения
            </div>
        </div>

        <!-- Табы -->
        <div class="tabs-container">
            <div class="tabs-scroll">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab.key"
                    class="tab-button"
                    :class="{ 'is-active': activeTab === index, 'is-dirty': isSectionDirty(tab.section) }"
                    @click="changeActiveTab(index)"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.title }}</span>
                    <span v-if="isSectionDirty(tab.section)" class="dirty-dot"></span>
                </button>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <div class="loader-spinner"></div>
            <p>Загрузка настроек...</p>
        </div>

        <!-- Динамический контент -->
        <div v-else class="tab-content">
            <component
                :is="currentTab.component"
                :form="currentForm"
                :is-saving="isSectionSaving(currentTab.section)"
                :extra-props="currentExtraProps"
                @save="handleSave"
                @mark-dirty="markDirty"
                @notify="handleNotify"
            />
        </div>
    </div>
</template>

<script>
import { useTenantSettings } from '@/MobileClient/Composables/useTenantSettings.js';
import { themeSchemes } from '@/MobileClient/constants/themeSchemes.js';

// Импорт компонентов вкладок
import TabBasic from '@/MobileClient/components/settings/TabBasic.vue';
import TabPwa from '@/MobileClient/components/settings/TabPwa.vue';
import TabShop from '@/MobileClient/components/settings/TabShop.vue';
import TabCashback from '@/MobileClient/components/settings/TabCashback.vue';
import TabInteractive from '@/MobileClient/components/settings/TabInteractive.vue';
import TabTables from '@/MobileClient/components/settings/TabTables.vue';
import TabMenu from '@/MobileClient/components/settings/TabMenu.vue';
import TabCalculators from '@/MobileClient/components/settings/TabCalculators.vue';
import TabGames from '@/MobileClient/components/settings/TabGames.vue';
import TabMainMenu from '@/MobileClient/components/settings/TabMainMenu.vue';
import TabGuests from '@/MobileClient/components/settings/TabGuests.vue';
import TabFaq from '@/MobileClient/components/settings/TabFaq.vue';

export default {
    name: 'TenantSettingsPage',

    // ✅ Пробрасываем composable через setup() (как в оригинале)
    setup() {
        const settings = useTenantSettings();
        return { ...settings };
    },

    data() {
        return {
            activeTab: 0,
            activePwaSubTab: 'general',
            availableSchemes: themeSchemes,
            defaultMenuIcons: {
                shop: 'shop.png', basket: 'basket.png', profile: 'profile.png',
                booking: 'tables.png', history: 'history.png', chat: 'chat.png',
                events: 'events.png', about: 'contacts.png', referral: 'referral.png',
            },

            // 🎯 Конфигурация вкладок: компонент + функция сохранения
            tabs: [
                { key: 'basic', title: 'Основное', icon: 'fa-solid fa-building', section: 'company', component: TabBasic, saveAction: 'saveBasicInfo' },
                { key: 'pwa', title: 'PWA приложение', icon: 'fa-solid fa-mobile-screen', section: 'pwa', component: TabPwa, saveAction: 'savePwaSettings' },
                { key: 'shop', title: 'Магазин', icon: 'fa-solid fa-store', section: 'shop', component: TabShop, saveAction: 'saveShopSettings' },
                { key: 'cashback', title: 'Баллы', icon: 'fa-solid fa-coins', section: 'cashback', component: TabCashback, saveAction: 'saveCashbackSettings' },
                { key: 'interactive', title: 'Интерактив', icon: 'fa-solid fa-gamepad', section: 'interactive', component: TabInteractive, saveAction: 'saveInteractiveSettings' },
                { key: 'tables', title: 'Столики', icon: 'fa-solid fa-utensils', section: 'tables', component: TabTables, saveAction: 'saveTablesSettings' },
                { key: 'menu', title: 'Пункты бокового меню', icon: 'fa-solid fa-bars', section: 'sidebar-menu', component: TabMenu, saveAction: 'saveMenuSettings' },
                { key: 'calculators', title: 'Калькуляторы', icon: 'fa-solid fa-calculator', section: 'calculators', component: TabCalculators, saveAction: 'saveCalculatorsSettings' },
                { key: 'games', title: 'Бонус-игры', icon: 'fa-solid fa-dice', section: 'games', component: TabGames, saveAction: 'saveGamesSettings' },
                { key: 'main_menu', title: 'Пункты главного меню', icon: 'fa-solid fa-bars', section: 'main-menu', component: TabMainMenu, saveAction: 'saveMainMenuSettings' },
                { key: 'guests', title: 'Гости', icon: 'fa-solid fa-user-astronaut', section: 'guests', component: TabGuests, saveAction: 'saveGuestsSettings' },
                { key: 'faq', title: 'FAQ', icon: 'fa-solid fa-circle-question', section: 'faq', component: TabFaq, saveAction: 'saveFaqSettings' },
            ],

            // ==========================================
            // ЕДИНЫЙ ИСТОЧНИК ИСТИНЫ: ВСЕ ФОРМЫ
            // ==========================================
            companyForm: {
                id: null, title: null, description: null,
                phones: ['+7'], email: null,
                links: { vk: null, inst: null, map_link: null, site: null },
                schedule: [
                    { day: 'Понедельник', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Вторник', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Среда', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Четверг', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Пятница', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Суббота', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' },
                    { day: 'Воскресенье', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной' }
                ]
            },

            pwaForm: {
                name: null, short_name: null, description: null,
                theme_color: '#ff8a00', background_color: '#ffffff',
                orientation: 'portrait', display: 'standalone',
                lang: 'ru', categories: ['shopping', 'food', 'business'],
                icons: { icon_192: null, icon_512: null, icon_192_maskable: null, icon_512_maskable: null },
                screenshots: { mobile: null, desktop: null },
                shortcuts: {
                    menu: { enabled: true, name: 'Меню', short_name: 'Меню', url: '/pwa/#/menu', icon: null },
                    cart: { enabled: true, name: 'Корзина', short_name: 'Корзина', url: '/pwa/#/cart', icon: null },
                    cashback: { enabled: true, name: 'Кэшбэк', short_name: 'Кэшбэк', url: '/pwa/#/cashback', icon: null },
                    wheel: { enabled: true, name: 'Колесо', short_name: 'Колесо', url: '/pwa/#/wheel-classic', icon: null }
                }
            },
            iconPreviews: {},
            screenshotPreviews: {},
            shortcutIconPreviews: {},

            shopForm: {
                is_disabled: false, has_booking: false, disabled_text: null,
                shop_display_type: 0, is_product_list: false,
                can_buy_after_closing: false, need_hide_disabled_products: true,
                min_price: 80, delivery_price_text: null,
                need_automatic_delivery_request: true, need_hide_delivery_period: false,
                min_base_delivery_price: 0, price_per_km: 80, free_shipping_starts_from: 0,
                shop_coords: '0,0', map_tiler: null, payment_info: null,
                need_pay_after_call: false, can_use_cash: true, can_use_card: true,
                venue_tags: '', nearest_cities: '',
                sbp_banks: {
                    tinkoff: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    sber: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    psb: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    vtb: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    yandex: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                },
                need_promo_code: true, need_bonuses_section: true,
                need_person_counter: true, need_health_restrictions: true,
                allow_delivery: true, allow_pickup: true,
                address: '',
                manager: { name: '', phone: '', email: '', social_link: '' },
                default_theme_scheme: 'default',
                delivery_zones: [
                    { id: 1, name: 'Центр', time: '30-40 мин', price: 'Бесплатно', minOrder: 1000 },
                    { id: 2, name: 'Спальные районы', time: '40-60 мин', price: '150 ₽', minOrder: 1500 },
                    { id: 3, name: 'Пригород', time: '60-90 мин', price: '300 ₽', minOrder: 2000 }
                ],
                delivery_services: [
                    { id: 1, title: 'Термосумки', description: 'Сохраняем температуру блюд' },
                    { id: 2, title: 'Бесплатная доставка', description: 'При заказе от 2000 ₽' },
                    { id: 3, title: 'Отслеживание', description: 'Курьер на карте в реальном времени' }
                ]
            },

            cashbackForm: {
                max_cashback_use_percent: 15, level_1: 0, level_2: 0, level_3: 0,
                expiration_period: 'never', expiration_percent: 100,
                notify_expiration: false, notify_days_before: 3,
            },
            certificateForm: {
                title: 'Подарочный сертификат', description: '500 рублей на CashBack',
                amount: 500, type: 'cashback', is_active: false
            },

            coffeeForm: {
                enabled: true, max: 7,
                rules: '1. За каждую покупку кофе — 1 отметка.\n2. После 7 кружек — 1 кофе бесплатно.\n3. Отметки действуют 30 дней.\n4. Бесплатный кофе нельзя обменять на деньги.'
            },

            tablesForm: { max_tables: 0, need_table_list: false },
            menuForm: {},
            calculatorsForm: {},
            gamesForm: {},
            mainMenuForm: {},
            mainMenuPreviews: {},

            guestsForm: {
                identities: '',
                welcome_message: 'Приветствуем вас в системе, <b>{name}</b>! 🐾\nРады видеть вас среди наших гостей.'
            },

            faqForm: [
                { id: 1, icon: 'fa-solid fa-clock', question: 'Сколько времени занимает доставка?', answer: 'Среднее время доставки — 40-60 минут.', is_visible: true },
                { id: 2, icon: 'fa-solid fa-ruble-sign', question: 'Какая минимальная сумма заказа?', answer: 'Минимальная сумма — 500 рублей.', is_visible: true },
                { id: 3, icon: 'fa-solid fa-credit-card', question: 'Какие способы оплаты доступны?', answer: 'Карты, СБП, наличные, Apple Pay, Google Pay.', is_visible: true },
                { id: 4, icon: 'fa-solid fa-coins', question: 'Как работают бонусы и кэшбэк?', answer: 'С каждого заказа начисляется от 3% до 10% бонусами.', is_visible: true },
                { id: 5, icon: 'fa-solid fa-rotate-left', question: 'Можно ли вернуть заказ?', answer: 'Если качество не устроило — вернём деньги.', is_visible: true },
                { id: 6, icon: 'fa-solid fa-utensils', question: 'Можно ли изменить заказ?', answer: 'В течение 5 минут после оформления.', is_visible: true },
                { id: 7, icon: 'fa-solid fa-gift', question: 'Есть ли акции и промокоды?', answer: 'Да! Мы регулярно раздаём промокоды в Telegram.', is_visible: true }
            ],
        };
    },

    computed: {
        currentTab() {
            return this.tabs[this.activeTab];
        },
        // 🎯 Динамически возвращаем нужную форму для активной вкладки
        currentForm() {
            const map = {
                basic: this.companyForm,
                pwa: this.pwaForm,
                shop: this.shopForm,
                cashback: { cashback: this.cashbackForm, certificate: this.certificateForm },
                interactive: this.coffeeForm,
                tables: this.tablesForm,
                menu: this.menuForm,
                calculators: this.calculatorsForm,
                games: this.gamesForm,
                main_menu: this.mainMenuForm,
                guests: this.guestsForm,
                faq: this.faqForm,
            };
            return map[this.currentTab.key];
        },
        // Дополнительные пропсы для отдельных вкладок
        currentExtraProps() {
            const map = {
                pwa: {
                    subTabs: [
                        { key: 'general', title: 'Основное', icon: 'fa-solid fa-info-circle' },
                        { key: 'appearance', title: 'Внешний вид', icon: 'fa-solid fa-palette' },
                        { key: 'icons', title: 'Иконки', icon: 'fa-solid fa-icons' },
                        { key: 'screenshots', title: 'Скриншоты', icon: 'fa-solid fa-camera' },
                        { key: 'shortcuts', title: 'Шорткаты', icon: 'fa-solid fa-bolt' },
                    ],
                    iconPreviews: this.iconPreviews,
                    screenshotPreviews: this.screenshotPreviews,
                    shortcutIconPreviews: this.shortcutIconPreviews,
                },
                cashback: { certificateForm: this.certificateForm },
                main_menu: {
                    previews: this.mainMenuPreviews,
                    defaultIcons: this.defaultMenuIcons,
                },
            };
            return map[this.currentTab.key] || {};
        }
    },

    async mounted() {
        try {
            await this.initForms();
        } catch (error) {
            console.error('Ошибка загрузки:', error);
        }
    },

    methods: {
        changeActiveTab(index) {
            this.activeTab = index;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // 🎯 Универсальный обработчик сохранения от дочерних компонентов
        async handleSave(payload) {
            const { saveAction } = this.currentTab;
            if (!saveAction || typeof this[saveAction] !== 'function') {
                console.error('Метод сохранения не найден:', saveAction);
                return;
            }

            try {
                await this[saveAction](payload);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Настройки сохранены',
                    type: 'success'
                });
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить',
                    type: 'error'
                });
            }
        },

        // Универсальное уведомление от дочерних компонентов
        handleNotify(notification) {
            this.$notify?.(notification);
        },

        // ==========================================
        // ИНИЦИАЛИЗАЦИЯ ФОРМ (из оригинала)
        // ==========================================
        async initForms() {
            const tenant = window.Tenant;
            if (!tenant) return;
            const settings = tenant.settings || {};

            this.companyForm.title = tenant.name || null;
            this.companyForm.description = tenant.description || null;
            const companyMeta = settings.company || {};
            this.companyForm.phones = companyMeta.phones || ['+7'];
            this.companyForm.email = companyMeta.email || null;
            this.companyForm.links = companyMeta.links || { vk: null, inst: null, map_link: null, site: null };
            if (companyMeta.schedule?.length >= 7) this.companyForm.schedule = companyMeta.schedule;

            this.shopForm = { ...this.shopForm, ...(settings.shop || {}), ...(settings || {}) };
            if (typeof this.shopForm.manager === 'string' || !this.shopForm.manager.name) {
                this.shopForm.manager = { name: '', phone: '', email: '', social_link: this.shopForm.manager?.link || '' };
            }
            if (settings.shop?.sbp) {
                this.shopForm.sbp_banks.tinkoff = { ...this.shopForm.sbp_banks.tinkoff, ...(settings.shop.sbp.tinkoff || {}) };
            }

            this.cashbackForm = { ...this.cashbackForm, ...(settings.cashback || {}) };
            this.certificateForm = { ...this.certificateForm, ...(settings.init_certificate || {}) };
            this.coffeeForm = { ...this.coffeeForm, ...(settings.coffee || {}) };
            this.tablesForm = { ...this.tablesForm, ...(settings.tables || {}) };
            this.menuForm = JSON.parse(JSON.stringify(settings.menu_items || {}));
            this.calculatorsForm = JSON.parse(JSON.stringify(settings.food_calculators || {}));
            this.gamesForm = JSON.parse(JSON.stringify(settings.bonus_games || {}));

            const mainMenuData = settings.main_menu_items || {};
            this.mainMenuForm = JSON.parse(JSON.stringify(mainMenuData));
            this.mainMenuPreviews = {};
            Object.keys(this.mainMenuForm).forEach(key => {
                if (this.mainMenuForm[key].img) this.mainMenuPreviews[key] = this.mainMenuForm[key].img;
            });

            const guestsSettings = settings.guests || {};
            if (Array.isArray(guestsSettings.identities)) {
                this.guestsForm.identities = guestsSettings.identities.join('\n');
            }
            if (guestsSettings.welcome_message) {
                this.guestsForm.welcome_message = guestsSettings.welcome_message;
            }

            if (settings.faq && Array.isArray(settings.faq)) {
                this.faqForm = settings.faq;
            }

            try {
                const response = await axios.get('/admin/tenant-settings/pwa');
                const pwaData = response.data.settings || {};
                this.pwaForm = { ...this.pwaForm, ...pwaData };
                this.iconPreviews = pwaData.icons_urls || {};
                this.screenshotPreviews = pwaData.screenshots_urls || {};
                this.shortcutIconPreviews = pwaData.shortcuts_icons_urls || {};
            } catch (error) {
                console.error('Ошибка загрузки PWA настроек:', error);
            }
        },
    },
};
</script>


<style lang="scss" >
@use 'sass:color';
// ==========================================
// ПЕРЕМЕННЫЕ (SASS)
// ==========================================

$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$text-muted-light: #9ca3af;
$success: #22c55e;
$danger: #ef4444;
$warning: #f59e0b;

.settings-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

.page-header {
    background: $bg;
    padding: 24px 20px;
    border-bottom: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    color: $text;

    i {
        color: $primary;
    }
}

.page-subtitle {
    margin: 4px 0 0 0;
    color: $text-muted;
    font-size: 0.9rem;
}

.unsaved-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba($warning, 0.1);
    color: color.adjust($warning, $lightness: -15%) ;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;

    i {
        color: $warning;
    }
}

.tabs-container {
    background: $bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.tabs-scroll {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;

    &::-webkit-scrollbar {
        display: none;
    }
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    position: relative;

    i {
        font-size: 1rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.is-active {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }

    &.is-dirty::after {
        content: '';
        position: absolute;
        top: 8px;
        right: 8px;
        width: 6px;
        height: 6px;
        background: $warning;
        border-radius: 50%;
    }
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: $text-muted;

    .loader-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    p {
        margin: 0;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.tab-content {
    max-width: 900px;
    margin: 24px auto;
    padding: 0 16px;
}

.tab-panel {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 20px;
    color: $text;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;

    i {
        color: $primary;
        font-size: 1.2rem;
    }
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;

    .full-width {
        grid-column: 1 / -1;
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        display: flex;
        align-items: center;
        gap: 6px;

        i {
            color: $text-muted;
            font-size: 0.8rem;
        }
    }

    input, textarea, select {
        padding: 10px 14px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 10px;
        font-size: 0.9rem;
        color: $text;
        transition: all 0.2s;
        font-family: inherit;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &::placeholder {
            color: $text-muted-light;
        }
    }

    textarea {
        resize: vertical;
        min-height: 80px;
    }
}

.input-with-suffix {
    display: flex;
    align-items: stretch;

    input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-suffix {
        padding: 10px 14px;
        background: $bg-secondary;
        border: 1px solid $border;
        border-left: none;
        border-radius: 0 10px 10px 0;
        font-weight: 600;
        color: $text-muted;
        display: flex;
        align-items: center;
    }
}

.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;

    input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: relative;
        width: 44px;
        height: 24px;
        background: $border;
        border-radius: 12px;
        transition: all 0.3s;

        &::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    }

    input:checked + .toggle-slider {
        background: $primary;

        &::after {
            left: 22px;
        }
    }

    .toggle-label {
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid rgba($border, 0.5);

    &:last-child {
        border-bottom: none;
    }

    .toggle-info {
        flex: 1;

        h4 {
            margin: 0 0 4px;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;

            i {
                color: $primary;
                font-size: 0.9rem;
            }
        }

        p {
            margin: 0;
            font-size: 0.8rem;
            color: $text-muted;
        }
    }
}

.toggle-list {
    display: flex;
    flex-direction: column;
}

.alert-info {
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
    padding: 12px 16px;
    background: rgba($primary, 0.05);
    border-left: 3px solid $primary;
    border-radius: 8px;
    font-size: 0.85rem;
    color: $text;
    margin: 16px 0;

    i {
        color: $primary;
        margin-top: 2px;
    }
}

.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.schedule-day {
    display: grid;
    grid-template-columns: 120px auto 1fr;
    gap: 12px;
    align-items: center;
    padding: 12px 16px;
    background: $bg-secondary;
    border-radius: 10px;

    &.is-closed {
        background: rgba($danger, 0.05);
    }

    .closed-comment-input {
        width: 100%;
        padding: 8px 12px;
        background: #ffffff; // Белый фон для контраста с красной строкой
        border: 1px solid rgba($danger, 0.2); // Легкая красная обводка
        border-radius: 8px;
        font-size: 0.85rem;
        color: $text;
        transition: all 0.2s ease;

        &::placeholder {
            color: rgba($danger, 0.5); // Плейсхолдер в тон теме выходного
            font-style: italic;
        }

        &:focus {
            outline: none;
            border-color: $danger; // Яркая обводка при клике
            box-shadow: 0 0 0 3px rgba($danger, 0.1); // Легкое свечение
        }
    }
}

.schedule-day-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.time-inputs {
    display: flex;
    align-items: center;
    gap: 8px;

    input {
        padding: 8px 10px;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.85rem;
        background: $bg;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }

    span {
        color: $text-muted;
    }
}

.save-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// СЕРТИФИКАТ (превью)
// ==========================================
.certificate-preview {
    position: relative;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 2 / 1;
    background: $bg-secondary;

    .cert-bg {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cert-content {
        position: absolute;
        top: 30%;
        width: 100%;
        text-align: center;
        color: black;
    }

    .cert-title {
        font-size: 1rem;
        font-weight: bold;
        margin: 0 0 4px;
    }

    .cert-desc {
        font-size: 0.8rem;
        margin: 0 0 4px;
    }

    .cert-date {
        font-size: 0.7rem;
        margin: 0;
    }

    .cert-qr {
        position: absolute;
        right: 10%;
        bottom: -40%;
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: $text-muted;
    }
}

// ==========================================
// КАРТОЧКИ (калькуляторы, игры)
// ==========================================
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.feature-card {
    position: relative;
    padding: 20px;
    border-radius: 16px;
    color: white;
    background-size: cover;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 180px;
    transition: all 0.3s;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.2);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-disabled {
        opacity: 0.5;
        filter: grayscale(0.7);
    }
}

.card-emoji {
    font-size: 3rem;
}

.card-icon {
    font-size: 2rem;
    background: rgba(255, 255, 255, 0.2);
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-info {
    flex: 1;

    h4 {
        margin: 0 0 4px;
        font-size: 1.1rem;
    }

    p {
        margin: 0 0 8px;
        font-size: 0.85rem;
        opacity: 0.9;
    }
}

.card-meta {
    display: flex;
    gap: 12px;
    font-size: 0.8rem;
    opacity: 0.85;
}

.feature-card .toggle-switch {
    .toggle-slider {
        background: rgba(255, 255, 255, 0.3);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .page-header {
        padding: 16px;
    }
    .page-title {
        font-size: 1.2rem;
    }
    .tab-content {
        padding: 0 12px;
        margin: 16px auto;
    }
    .form-section {
        padding: 16px;
    }
    .schedule-day {
        grid-template-columns: 1fr;
    }
    .form-grid {
        grid-template-columns: 1fr;
    }
    .cards-grid {
        grid-template-columns: 1fr;
    }
}

// ==========================================
// 🆕 ПОДТАБЫ
// ==========================================
.sub-tabs {
    display: flex;
    gap: 4px;
    padding: 8px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.sub-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    i {
        font-size: 0.9rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.is-active {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }
}

// ==========================================
// 🆕 ПОДСКАЗКИ ПОЛЕЙ
// ==========================================
.field-hint {
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 4px;
}

.char-counter {
    position: absolute;
    bottom: 8px;
    right: 12px;
    font-size: 0.7rem;
    color: $text-muted;
}

// ==========================================
// 🆕 COLOR PICKER
// ==========================================
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;

    input[type="color"] {
        width: 48px;
        height: 48px;
        padding: 2px;
        border: 1px solid $border;
        border-radius: 10px;
        cursor: pointer;
        background: $bg;

        &::-webkit-color-swatch-wrapper {
            padding: 2px;
        }

        &::-webkit-color-swatch {
            border: none;
            border-radius: 6px;
        }
    }

    .color-text {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid $border;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.9rem;
        text-transform: uppercase;
    }
}

// ==========================================
// 🆕 ПРЕВЬЮ PWA
// ==========================================
.pwa-preview {
    margin-top: 24px;
    padding: 20px;
    background: $bg-secondary;
    border-radius: 12px;

    h4 {
        margin: 0 0 16px;
        font-size: 0.95rem;
        color: $text;
    }
}

.preview-browser {
    max-width: 320px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border-top: 3px solid;

    .preview-toolbar {
        padding: 8px 12px;
        display: flex;
        align-items: center;
    }

    .preview-url {
        flex: 1;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        color: white;
        text-align: center;
    }

    .preview-content {
        padding: 24px;
        text-align: center;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .preview-app-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .preview-app-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: $text;
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА ИКОНОК
// ==========================================
.icons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

.icon-upload-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.icon-preview {
    width: 100%;
    aspect-ratio: 1;
    max-width: 120px;
    margin: 0 auto;
    border-radius: 20px;
    overflow: hidden;
    background: $bg;
    border: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &.maskable {
        border-radius: 0;

        .mask-overlay {
            position: absolute;
            inset: 0;
            border: 3px dashed rgba($primary, 0.5);
            border-radius: 50%;
            pointer-events: none;
        }
    }
}

.icon-placeholder {
    color: $text-muted-light;
    font-size: 2rem;
}

.icon-info {
    text-align: center;

    h5 {
        margin: 0 0 4px;
        font-size: 0.9rem;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: $bg;
    border: 1px dashed $border;
    border-radius: 8px;
    color: $primary;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    input[type="file"] {
        display: none;
    }

    &:hover {
        background: rgba($primary, 0.05);
        border-color: $primary;
    }

    &.small {
        padding: 8px;
        font-size: 0.8rem;
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА СКРИНШОТОВ
// ==========================================
.screenshots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.screenshot-upload-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.screenshot-preview {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: $bg;
    border: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;

    &.mobile {
        aspect-ratio: 375 / 667;
    }

    &.desktop {
        aspect-ratio: 16 / 9;
    }

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.screenshot-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $text-muted-light;

    i {
        font-size: 2.5rem;
    }

    span {
        font-size: 0.8rem;
    }
}

.screenshot-info {
    h5 {
        margin: 0 0 4px;
        font-size: 0.95rem;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

// ==========================================
// 🆕 ШОРТКАТЫ
// ==========================================
.shortcuts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.shortcut-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-disabled {
        opacity: 0.6;
    }
}

.shortcut-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;

    .shortcut-icon-preview {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
    }

    .shortcut-title {
        flex: 1;
        font-weight: 600;
        color: $text;
    }
}

.shortcut-fields {
    padding: 0 16px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    border-top: 1px solid $border;
    padding-top: 16px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .icons-grid,
    .screenshots-grid {
        grid-template-columns: 1fr;
    }

    .sub-tabs {
        padding: 6px;
    }

    .sub-tab {
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .preview-browser {
        max-width: 100%;
    }
}

.main-menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.main-menu-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;

    &.is-disabled {
        opacity: 0.6;
        background: var(--bs-secondary-bg);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .preview-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--bs-secondary-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-primary);
        font-size: 1.2rem;
        flex-shrink: 0;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    .card-title {
        flex: 1;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .card-fields {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--bs-border-color);
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА ИКОНОК ГЛАВНОГО МЕНЮ
// ==========================================
.icon-upload-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.icon-preview-small {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: contain; // contain лучше для иконок меню, чтобы не обрезались
        padding: 4px;
    }
}

// ==========================================
// 🆕 ДЕЙСТВИЯ С ИКОНКОЙ (Загрузка + Сброс)
// ==========================================
.icon-upload-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.icon-preview-small {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: contain; // contain лучше для иконок, чтобы не обрезались
        padding: 4px;
    }
}

.icon-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 10px;
    background: #ffffff;
    border: 1px dashed #ef4444; // Красная пунктирная рамка для действия "сброс"
    border-radius: 6px;
    color: #ef4444;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba(239, 68, 68, 0.05);
        border-style: solid;
    }

    &.small {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
}

$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$text-muted-light: #9ca3af;
$success: #22c55e;
$danger: #ef4444;
$warning: #f59e0b;

// ... (вставьте сюда все ваши существующие стили из исходного файла) ...

// 🆕 Стили для СБП банков
.sbp-banks-wrapper {
    margin-top: 16px;
}

.sbp-bank-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s;

    &.is-active {
        border-color: var(--bs-primary);
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.1);
    }
}

.sbp-bank-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.sbp-bank-name {
    font-weight: 600;
    font-size: 1rem;
    color: var(--text);
}

.sbp-bank-fields {
    padding-top: 12px;
    border-top: 1px dashed var(--border);
    animation: fadeIn 0.3s ease;
}

// 🆕 Кнопка скачивания PDF
.btn-download-pdf {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
    }

    i {
        font-size: 1.1rem;
    }
}

.test-payment-wrapper {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px dashed var(--border, #e5e7eb);
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.btn-test-payment {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%); // Зеленый цвет для теста
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.monospace-textarea {
    font-family: 'Courier New', Courier, monospace;
    line-height: 1.4;
}

.dynamic-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 16px;
}

.list-item-card {
    background: var(--light, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s ease;

    &:hover {
        border-color: var(--primary, #ff8a00);
        box-shadow: 0 4px 12px rgba(255, 138, 0, 0.05);
    }
}

.list-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px dashed var(--border, #e2e8f0);
}

.list-item-badge {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: rgba(0, 0, 0, 0.04);
    padding: 4px 8px;
    border-radius: 6px;
}

.btn-icon-danger {
    background: transparent;
    border: none;
    color: #ef4444;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.2s;
    font-size: 0.9rem;

    &:hover {
        background: rgba(239, 68, 68, 0.1);
    }
}

.btn-add-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    background: transparent;
    border: 2px dashed var(--border, #cbd5e1);
    color: var(--text-muted, #64748b);
    padding: 14px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: var(--primary, #ff8a00);
        color: var(--primary, #ff8a00);
        background: rgba(255, 138, 0, 0.03);
    }
}
</style>
