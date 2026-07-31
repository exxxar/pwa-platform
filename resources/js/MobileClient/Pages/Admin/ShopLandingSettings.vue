<template>
    <div class="shop-settings">
        <!-- ========================================== -->
        <!-- ШАПКА С ИНДИКАТОРОМ НЕСОХРАНЕННЫХ ИЗМЕНЕНИЙ -->
        <!-- ========================================== -->
        <header class="settings-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fa-solid fa-sliders"></i>
                    Настройки лендинга
                </h1>
                <p class="page-subtitle">Управляйте внешним видом, контентом и функционалом вашего магазина</p>
            </div>
            <div v-if="hasUnsavedChanges" class="unsaved-badge">
                <i class="fa-solid fa-circle-exclamation"></i>
                Есть несохранённые изменения
            </div>
        </header>

        <!-- ========================================== -->
        <!-- ГОРИЗОНТАЛЬНЫЕ ТАБЫ -->
        <!-- ========================================== -->
        <div class="tabs-container">
            <div class="tabs-scroll">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab.key"
                    class="tab-button"
                    :class="{
                        'is-active': activeTab === index,
                        'is-dirty': isSectionDirty(tab.section)
                    }"
                    @click="changeActiveTab(index)"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.title }}</span>
                    <span v-if="isSectionDirty(tab.section)" class="dirty-dot"></span is-dirty>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОСНОВНАЯ ОБЛАСТЬ КОНТЕНТА -->
        <!-- ========================================== -->
        <main class="settings-content">
            <transition name="fade-slide" mode="out-in">
                <!-- Используем key для корректной анимации перехода -->
                <div :key="currentTab.key" class="content-wrapper">

                    <TabTheme
                        v-if="currentTab.key === 'theme'"
                        :theme="localConfig.theme"
                        @input="markDirty('theme')"
                    />
                    <TabHero
                        v-if="currentTab.key === 'hero'"
                        :hero="localConfig.hero"
                        @input="markDirty('hero')"
                    />
                    <TabSections
                        v-if="currentTab.key === 'sections'"
                        :sections="localConfig.sectionsVisibility"
                        @input="markDirty('sections')"
                    />
                    <TabReviews
                        v-if="currentTab.key === 'reviews'"
                        :reviews="localConfig.reviews"
                        :reviews-section="localConfig.reviewsSection"
                        @input="markDirty('reviews')"
                    />
                    <TabMisc
                        v-if="['cta', 'footer', 'cart', 'feedback', 'privacy'].includes(currentTab.key)"
                        :active-section="currentTab.key"
                        :config="localConfig"
                        @input="markDirty(currentTab.key)"
                    />

                </div>
            </transition>
        </main>

        <!-- ========================================== -->
        <!-- ЗАКРЕПЛЕННЫЙ ФУТЕР С ДЕЙСТВИЯМИ -->
        <!-- ========================================== -->
        <footer class="settings-footer">
            <div class="footer-content">
                <div class="footer-hint">
                    <i class="fa-regular fa-circle-check"></i>
                    <span>Изменения применяются к предпросмотру мгновенно</span>
                </div>
                <div class="footer-actions">
                    <button class="btn-secondary" @click="resetToDefaults">
                        <i class="fa-solid fa-rotate-left"></i> Сбросить
                    </button>
                    <button class="btn-primary" @click="saveSettings" :disabled="isSaving">
                        <span v-if="isSaving" class="spinner"></span>
                        <template v-else>
                            <i class="fa-solid fa-check"></i> Сохранить изменения
                        </template>
                    </button>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import TabTheme from '@/MobileClient/Components/ShopLanding/Settings/TabTheme.vue';
import TabHero from '@/MobileClient/Components/ShopLanding/Settings/TabHero.vue';
import TabSections from '@/MobileClient/Components/ShopLanding/Settings/TabSections.vue';
import TabReviews from '@/MobileClient/Components/ShopLanding/Settings/TabReviews.vue';
import TabMisc from '@/MobileClient/Components/ShopLanding/Settings/TabMisc.vue';

export default {
    name: "ShopLandingSettings",
    components: { TabTheme, TabHero, TabSections, TabReviews, TabMisc },

    emits: ['save', 'reset'],

    data() {
        return {
            isSaving: false,
            activeTab: 0, // Индекс активной вкладки

            // 🆕 Отслеживание несохраненных изменений по секциям
            dirtySections: new Set(),

            // 🆕 Конфигурация табов
            tabs: [
                { key: 'theme', title: 'Цвета', icon: 'fa-solid fa-palette', section: 'theme' },
                { key: 'hero', title: 'Hero секция', icon: 'fa-solid fa-image', section: 'hero' },
                { key: 'sections', title: 'Видимость блоков', icon: 'fa-solid fa-eye', section: 'sections' },
                { key: 'reviews', title: 'Отзывы', icon: 'fa-solid fa-star', section: 'reviews' },
                { key: 'cta', title: 'CTA секция', icon: 'fa-solid fa-bullhorn', section: 'cta' },
                { key: 'footer', title: 'Футер', icon: 'fa-solid fa-shoe-prints', section: 'footer' },
                { key: 'cart', title: 'Корзина', icon: 'fa-solid fa-cart-shopping', section: 'cart' },
                { key: 'feedback', title: 'Обратная связь', icon: 'fa-solid fa-envelope', section: 'feedback' },
                { key: 'privacy', title: 'Конфиденциальность', icon: 'fa-solid fa-shield-halved', section: 'privacy' },
            ],

            localConfig: {} // Инициализируется в mounted
        };
    },

    computed: {
        currentTab() {
            return this.tabs[this.activeTab] || this.tabs[0];
        },

        hasUnsavedChanges() {
            return this.dirtySections.size > 0;
        }
    },

    methods: {
        deepClone(obj) {
            return JSON.parse(JSON.stringify(obj));
        },

        getDefaultConfig() {
            return {
                theme: { primary: '#ff7a00', primaryDark: '#e56f00', primaryLight: '#ffb300', accent: '#f4c542', dark: '#0f0f14', light: '#fffdf8' },
                hero: { badge: 'Мобильный магазин', title: 'Свежие продукты с доставкой', subtitle: 'Выберите заведение и закажите любимые товары', buttonText: 'Выбрать заведение', backgroundImage: '' },
                sectionsVisibility: {
                    hero: true, partners: true, promotions: true, delivery: true,
                    pwaBanner: true, loyalty: true, wheel: true, reviews: true,
                    faq: true, reservation: true, cta: true, footer: true
                },
                categories: [], items: [], reviews: [],
                reviewsSection: { title: 'Что говорят клиенты', subtitle: 'Реальные отзывы наших покупателей' },
                cta: { title: 'Остались вопросы?', text: 'Свяжитесь с нами — поможем с выбором и расскажем о актуальных акциях', buttonText: 'Написать нам' },
                footer: { companyName: 'Ваш Магазин', description: 'Доставка свежих продуктов и готовой еды', phone: '+7 (999) 123-45-67', email: 'info@example.com', address: 'г. Москва, ул. Примерная, 1', socialLinks: [] },
                cart: { title: 'Ваша корзина', emptyText: 'Корзина пуста', checkoutText: 'Оформить заказ', totalText: 'Итого:' },
                feedbackModal: { title: 'Обратная связь', subtitle: '', nameLabel: 'Имя', phoneLabel: 'Телефон', messageLabel: 'Сообщение', submitText: 'Отправить' },
                privacyModal: { title: 'Политика конфиденциальности', content: '' }
            };
        },

        mergeConfig(initial) {
            const defaults = this.getDefaultConfig();
            const cloned = this.deepClone(initial || {});
            const merged = this.deepClone(defaults);
            for (const key in cloned) {
                if (cloned[key] !== undefined && cloned[key] !== null) {
                    if (typeof cloned[key] === 'object' && !Array.isArray(cloned[key]) && typeof merged[key] === 'object' && !Array.isArray(merged[key])) {
                        merged[key] = { ...merged[key], ...cloned[key] };
                    } else {
                        merged[key] = cloned[key];
                    }
                }
            }
            return merged;
        },

        async initSettings() {
            const tenant = window.Tenant;
            if (!tenant) return;
            const settings = tenant.settings || {};
            const landingData = settings.landing || {};
            this.localConfig = this.mergeConfig(landingData);
        },

        // 🆕 Управление переключением табов
        changeActiveTab(index) {
            this.activeTab = index;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // 🆕 Отметить секцию как измененную
        markDirty(section) {
            this.dirtySections.add(section);
        },

        // 🆕 Проверить, изменена ли секция
        isSectionDirty(section) {
            return this.dirtySections.has(section);
        },

        // 🆕 Очистить статус изменений для секции
        clearDirty(section) {
            this.dirtySections.delete(section);
        },

        async saveSettings() {
            if (!this.localConfig.hero.title?.trim()) {
                this.$notify?.({ title: 'Ошибка', text: 'Заполните заголовок Hero секции', type: 'error' });
                // Находим индекс вкладки 'hero' и переключаемся на нее
                const heroIndex = this.tabs.findIndex(t => t.key === 'hero');
                if (heroIndex !== -1) this.changeActiveTab(heroIndex);
                return;
            }

            this.isSaving = true;
            try {
                const payload = this.deepClone(this.localConfig);
                await axios.put('/admin/tenant-settings/landing', { landing: payload });

                if (window.Tenant && window.Tenant.settings) {
                    window.Tenant.settings.landing = payload;
                }

                // 🆕 Очищаем все флаги "грязных" секций после успешного сохранения
                this.dirtySections.clear();

                this.$notify?.({ title: 'Успех', text: 'Настройки лендинга успешно сохранены', type: 'success' });
                this.$emit('save', payload);
            } catch (error) {
                console.error('Ошибка сохранения настроек лендинга:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить настройки',
                    type: 'error'
                });
            } finally {
                this.isSaving = false;
            }
        },

        async resetToDefaults() {
            if (!confirm('Сбросить все настройки лендинга к значениям по умолчанию? Это действие нельзя отменить.')) return;

            try {
                const response = await axios.post('/admin/tenant-settings/landing/reset');
                const defaultConfig = response.data.settings || response.data || {};

                this.localConfig = this.deepClone(defaultConfig);

                if (window.Tenant && window.Tenant.settings) {
                    window.Tenant.settings.landing = this.deepClone(defaultConfig);
                }

                // 🆕 Очищаем флаги изменений при сбросе
                this.dirtySections.clear();

                this.$emit('reset', defaultConfig);
                this.$notify?.({ title: 'Сброшено', text: 'Настройки возвращены к значениям по умолчанию', type: 'info' });
            } catch (error) {
                console.error('Ошибка сброса настроек:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сбросить настройки', type: 'error' });
            }
        }
    },

    async mounted() {
        try {
            await this.initSettings();
        } catch (error) {
            console.error('Ошибка инициализации настроек лендинга:', error);
        }
    }
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$warning: #f59e0b;

.shop-settings {
    min-height: 100vh;
    background: $bg-secondary;
    display: flex;
    flex-direction: column;
}

// ==========================================
// ШАПКА
// ==========================================
.settings-header {
    background: $bg;
    padding: 24px 32px;
    border-bottom: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.header-content {
    .page-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 6px 0;
        color: $text;
        i { color: $primary; }
    }
    .page-subtitle {
        margin: 0;
        color: $text-muted;
        font-size: 0.95rem;
    }
}

.unsaved-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba($warning, 0.1);
    color: darken($warning, 15%);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    animation: pulse 2s infinite;
    i { color: $warning; }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

// ==========================================
// 🆕 ГОРИЗОНТАЛЬНЫЕ ТАБЫ
// ==========================================
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
    padding: 12px 32px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    &::-webkit-scrollbar { display: none; }
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

    i { font-size: 1rem; }

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

// ==========================================
// КОНТЕНТ
// ==========================================
.settings-content {
    flex: 1;
    max-width: 900px;
    width: 100%;
    margin: 24px auto;
    padding: 0 32px;
}

.content-wrapper {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(12px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

// ==========================================
// ФУТЕР
// ==========================================
.settings-footer {
    position: sticky;
    bottom: 0;
    z-index: 1020;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid $border;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.03);
    padding: 16px 32px;
}

.footer-content {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.footer-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: $text-muted;
    font-weight: 500;
    i { color: #10b981; font-size: 1rem; }
}

.footer-actions {
    display: flex;
    gap: 12px;
}

// ==========================================
// КНОПКИ
// ==========================================
.btn-primary, .btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-primary {
    background: $primary;
    color: white;
    box-shadow: 0 2px 4px rgba($primary, 0.2);

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba($primary, 0.3);
    }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.btn-secondary {
    background: white;
    color: $text;
    border: 1px solid $border;

    &:hover {
        background: $bg-secondary;
        border-color: $text-muted;
    }
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .settings-header { padding: 20px; }
    .header-content .page-title { font-size: 1.25rem; }

    .tabs-scroll { padding: 12px 20px; }
    .tab-button { padding: 8px 14px; font-size: 0.85rem; }

    .settings-content { padding: 0 20px; margin: 20px auto; }

    .settings-footer { padding: 16px 20px; }
    .footer-content { flex-direction: column; align-items: stretch; gap: 12px; }
    .footer-hint { justify-content: center; font-size: 0.8rem; }
    .footer-actions { justify-content: stretch; button { flex: 1; } }
}
</style>
