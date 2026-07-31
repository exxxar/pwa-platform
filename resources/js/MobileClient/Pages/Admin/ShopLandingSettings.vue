<template>
    <div class="shop-settings">
        <!-- Шапка -->
        <div class="settings-header">
            <div class="header-left">
                <h2><i class="fa-solid fa-sliders"></i> Настройки лендинга</h2>
                <span class="header-hint">Изменения применяются к предпросмотру мгновенно</span>
            </div>
            <div class="header-actions">
                <button class="btn-secondary" @click="resetToDefaults">
                    <i class="fa-solid fa-rotate-left"></i> Сбросить
                </button>
                <button class="btn-primary" @click="saveSettings" :disabled="isSaving">
                    <span v-if="isSaving" class="spinner"></span>
                    <template v-else><i class="fa-solid fa-check"></i> Сохранить</template>
                </button>
            </div>
        </div>

        <div class="settings-layout">
            <!-- Боковое меню -->
            <aside class="settings-sidebar">
                <nav class="settings-nav">
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        class="nav-item"
                        :class="{ 'active': activeSection === section.id }"
                        @click="activeSection = section.id"
                    >
                        <i :class="section.icon"></i>
                        <span>{{ section.label }}</span>
                    </button>
                </nav>
            </aside>

            <!-- Основная область с динамическими компонентами -->
            <main class="settings-content">
                <TabTheme v-if="activeSection === 'theme'" :theme="localConfig.theme"/>
                <TabHero v-if="activeSection === 'hero'" :hero="localConfig.hero"/>
                <TabCategories v-if="activeSection === 'categories'" :categories="localConfig.categories"/>
                <TabProducts
                    v-if="activeSection === 'products'"
                    :products="localConfig.items"
                    :categories="localConfig.categories"
                />
                <TabReviews
                    v-if="activeSection === 'reviews'"
                    :reviews="localConfig.reviews"
                    :reviews-section="localConfig.reviewsSection"
                />
                <TabMisc
                    v-if="['cta', 'footer', 'cart', 'feedback', 'privacy'].includes(activeSection)"
                    :active-section="activeSection"
                    :config="localConfig"
                />
            </main>
        </div>
    </div>
</template>

<script>
import TabTheme from '@/MobileClient/Components/ShopLanding/Settings/TabTheme.vue';
import TabHero from '@/MobileClient/Components/ShopLanding/Settings/TabHero.vue';
import TabCategories from '@/MobileClient/Components/ShopLanding/Settings/TabCategories.vue';
import TabProducts from '@/MobileClient/Components/ShopLanding/Settings/TabProducts.vue';
import TabReviews from '@/MobileClient/Components/ShopLanding/Settings/TabReviews.vue';
import TabMisc from '@/MobileClient/Components/ShopLanding/Settings/TabMisc.vue';

export default {
    name: "ShopLandingSettings",
    components: {TabTheme, TabHero, TabCategories, TabProducts, TabReviews, TabMisc},

    props: {
        initialConfig: {type: Object, default: () => ({})}
    },
    emits: ['save', 'reset'],

    data() {
        return {
            isSaving: false,
            activeSection: 'theme',
            localConfig: this.mergeConfig(this.initialConfig),
            sections: [
                {id: 'theme', label: 'Цвета', icon: 'fa-solid fa-palette'},
                {id: 'hero', label: 'Hero секция', icon: 'fa-solid fa-image'},
                {id: 'categories', label: 'Категории', icon: 'fa-solid fa-layer-group'},
                {id: 'products', label: 'Товары', icon: 'fa-solid fa-box'},
                {id: 'reviews', label: 'Отзывы', icon: 'fa-solid fa-star'},
                {id: 'cta', label: 'CTA секция', icon: 'fa-solid fa-bullhorn'},
                {id: 'footer', label: 'Футер', icon: 'fa-solid fa-shoe-prints'},
                {id: 'cart', label: 'Корзина', icon: 'fa-solid fa-cart-shopping'},
                {id: 'feedback', label: 'Обратная связь', icon: 'fa-solid fa-envelope'},
                {id: 'privacy', label: 'Конфиденциальность', icon: 'fa-solid fa-shield-halved'},
            ]
        };
    },

    watch: {
        initialConfig: {
            deep: true,
            handler(newVal) {
                this.localConfig = this.mergeConfig(newVal);
            }
        }
    },

    methods: {
        deepClone(obj) {
            return JSON.parse(JSON.stringify(obj));
        },

        getDefaultConfig() {
            return {
                theme: {
                    primary: '#3b82f6',
                    primaryDark: '#2563eb',
                    primaryLight: '#60a5fa',
                    accent: '#f59e0b',
                    dark: '#1f2937',
                    light: '#f9fafb'
                },
                hero: {badge: '', title: '', subtitle: '', buttonText: '', backgroundImage: ''},
                categories: [], items: [], reviews: [],
                reviewsSection: {title: '', subtitle: ''},
                cta: {title: '', text: '', buttonText: ''},
                footer: {companyName: '', description: '', phone: '', email: '', address: '', socialLinks: []},
                cart: {title: '', emptyText: '', checkoutText: '', totalText: ''},
                feedbackModal: {
                    title: '',
                    subtitle: '',
                    nameLabel: '',
                    phoneLabel: '',
                    messageLabel: '',
                    submitText: ''
                },
                privacyModal: {title: '', content: ''}
            };
        },

        mergeConfig(initial) {
            const defaults = this.getDefaultConfig();
            const cloned = this.deepClone(initial || {});
            const merged = this.deepClone(defaults);
            for (const key in cloned) {
                if (cloned[key] !== undefined && cloned[key] !== null) {
                    if (typeof cloned[key] === 'object' && !Array.isArray(cloned[key]) && typeof merged[key] === 'object' && !Array.isArray(merged[key])) {
                        merged[key] = {...merged[key], ...cloned[key]};
                    } else {
                        merged[key] = cloned[key];
                    }
                }
            }
            return merged;
        },

        async saveSettings() {
            if (!this.localConfig.hero.title?.trim()) {
                this.$notify?.({title: 'Ошибка', text: 'Заполните заголовок Hero секции', type: 'error'});
                this.activeSection = 'hero';
                return;
            }
            this.isSaving = true;
            try {
                await new Promise(resolve => setTimeout(resolve, 600)); // Имитация запроса
                this.$emit('save', this.deepClone(this.localConfig));
                this.$notify?.({title: 'Успех', text: 'Настройки сохранены', type: 'success'});
            } catch (error) {
                this.$notify?.({title: 'Ошибка', text: 'Не удалось сохранить', type: 'error'});
            } finally {
                this.isSaving = false;
            }
        },

        resetToDefaults() {
            if (!confirm('Сбросить все настройки к значениям по умолчанию?')) return;
            this.localConfig = this.mergeConfig(this.initialConfig);
            this.$emit('reset');
        }
    }
};
</script>

<style lang="scss" scoped>
// Базовые стили для оркестратора (Layout, Header, Sidebar)
$primary: #3b82f6;
$primary-dark: #2563eb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.shop-settings {
    background: $bg;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}

.settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    flex-wrap: wrap;
    gap: 16px;
}

.header-left h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    color: $text;
    display: flex;
    align-items: center;
    gap: 10px;

    i {
        color: $primary;
    }
}

.header-hint {
    font-size: 0.85rem;
    color: $text-muted;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.settings-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.settings-sidebar {
    position: sticky;
    top: 100px;
    align-self: start;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.settings-nav {
    background: $card-bg;
    border-radius: 16px;
    padding: 8px;
    border: 1px solid $border;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;

    i {
        width: 20px;
        text-align: center;
    }

    &:hover {
        background: rgba($primary, 0.05);
        color: $primary;
    }

    &.active {
        background: $primary;
        color: white;
        font-weight: 600;
    }
}

.settings-content {
    min-width: 0;
}

.btn-primary, .btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-secondary {
    background: $bg;
    color: $text;
    border: 1px solid $border;

    &:hover {
        background: #f3f4f6;
        border-color: $primary;
        color: $primary;
    }
}

.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1024px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    .settings-sidebar {
        position: static;
        max-height: none;
    }
    .settings-nav {
        flex-direction: row;
        overflow-x: auto;
        padding: 6px;
    }
    .nav-item {
        white-space: nowrap;
        flex-shrink: 0;
    }
}
</style>
