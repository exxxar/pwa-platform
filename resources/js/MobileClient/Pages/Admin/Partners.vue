<template>
    <div class="partners-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="page-hero">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h1 class="hero-title">Партнёрская программа</h1>
                <p class="hero-subtitle">
                    Управляйте партнёрами и настройками интеграции
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СТАТИСТИКА -->
        <!-- ========================================== -->
        <div class="stats-section">
            <div class="stats-grid">
                <!-- Всего партнёров -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ partnersCount }}</div>
                        <div class="stat-label">Всего партнёров</div>
                    </div>
                </div>

                <!-- Активных -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ activePartners.length }}</div>
                        <div class="stat-label">Активных</div>
                    </div>
                </div>

                <!-- 🆕 Всего товаров -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fa-solid fa-cube"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">
                    <span v-if="isPartnersStatsLoading" class="stat-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </span>
                            <template v-else>{{ formatNumber(totalPartnerProducts) }}</template>
                        </div>
                        <div class="stat-label">Товаров в каталоге</div>
                    </div>
                </div>

                <!-- 🆕 Общая сумма -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fa-solid fa-ruble-sign"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">
                    <span v-if="isPartnersStatsLoading" class="stat-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </span>
                            <template v-else>{{ formatPrice(totalPartnerProductsSum) }}</template>
                        </div>
                        <div class="stat-label">Общая стоимость</div>
                    </div>
                </div>

                <!-- Избранных -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ favoritePartners.length }}</div>
                        <div class="stat-label">Избранных</div>
                    </div>
                </div>

                <!-- Категорий -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ formatNumber(totalPartnerCategories) }}</div>
                        <div class="stat-label">Категорий</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 🆕 НАСТРОЙКИ ПРОГРАММЫ -->
        <!-- ========================================== -->
        <div class="settings-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-gear"></i>
                    Настройки программы
                </h2>
                <span v-if="savingSettings" class="saving-indicator">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Сохранение...
                </span>
            </div>

            <div class="settings-container">

                <button class="config-button" @click="showUiSettings = true">
                    <div class="config-button-icon"><i class="fa-solid fa-palette"></i></div>
                    <div class="config-button-text">
                        <span class="config-button-title">Настроить внешний вид</span>
                        <span class="config-button-desc">Кухни, сервисы, фильтры и тексты</span>
                    </div>
                    <i class="fa-solid fa-chevron-right config-button-arrow"></i>
                </button>

                <!-- Режим работы с партнерами -->
                <div class="setting-card" :class="{ 'is-active': form.is_active }">
                    <div class="setting-info">
                        <div class="setting-icon">
                            <i class="fa-solid fa-toggle-on"></i>
                        </div>
                        <div class="setting-text">
                            <h4 class="setting-title">Партнёрская программа</h4>
                            <p class="setting-description">
                                Включите для активации партнёрской программы и возможности добавлять партнёров
                            </p>
                        </div>
                    </div>
                    <div class="switch-control">
                        <input
                            id="partners-is-active"
                            type="checkbox"
                            v-model="form.is_active"
                            class="switch-input"
                            @change="handleSettingsChange"
                            :disabled="savingSettings"
                        >
                        <label for="partners-is-active" class="switch-slider"></label>
                    </div>
                </div>

                <!-- Отображать себя в списке -->
                <div
                    class="setting-card"
                    :class="{
                        'is-active': form.display_self,
                        'is-disabled': !form.is_active
                    }"
                >
                    <div class="setting-info">
                        <div class="setting-icon">
                            <i class="fa-solid fa-eye"></i>
                        </div>
                        <div class="setting-text">
                            <h4 class="setting-title">Отображать себя в списке</h4>
                            <p class="setting-description">
                                Ваша компания будет видна в списке партнёров для клиентов
                            </p>
                        </div>
                    </div>
                    <div class="switch-control">
                        <input
                            id="partners-display-self"
                            type="checkbox"
                            v-model="form.display_self"
                            class="switch-input"
                            :disabled="!form.is_active || savingSettings"
                            @change="handleSettingsChange"
                        >
                        <label for="partners-display-self" class="switch-slider"></label>
                    </div>
                </div>

                <!-- Кнопка настройки своих параметров -->
                <transition name="fade">
                    <button
                        v-if="form.display_self && form.is_active"
                        class="config-button"
                        @click="openSelfConfigModal"
                    >
                        <div class="config-button-icon">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div class="config-button-text">
                            <span class="config-button-title">Настроить свои параметры</span>
                            <span class="config-button-desc">Название, описание, логотип, контакты</span>
                        </div>
                        <i class="fa-solid fa-chevron-right config-button-arrow"></i>
                    </button>
                </transition>

            </div>
        </div>



        <!-- ========================================== -->
        <!-- СПИСОК ПАРТНЕРОВ -->
        <!-- ========================================== -->
        <div class="partners-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-list"></i>
                    Список партнёров
                </h2>
                <div class="section-actions">
                    <button class="refresh-btn" @click="refreshPartners" :disabled="isLoading">
                        <i class="fa-solid fa-rotate" :class="{ 'fa-spin': isLoading }"></i>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>

            <div v-if="!loading" class="partners-container">
                <PartnerList @refresh="onPartnerRefresh" />
                <AddPartnerForm @callback="onPartnerAdded" />
            </div>

            <div v-else class="loading-state">
                <div class="loader-spinner"></div>
                <p>Загрузка партнёров...</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: НАСТРОЙКА СВОИХ ПАРАМЕТРОВ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showSelfConfigModal" class="modal-overlay" @click.self="closeSelfConfigModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <button class="modal-close" @click="closeSelfConfigModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">Настройка партнёра</h3>
                                <p class="modal-subtitle">Информация о вашей компании</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <SelfConfigForm @success="onSelfConfigSuccess" />
                    </div>
                </div>
            </div>
        </transition>


    </div>

    <!-- Сама модалка -->
    <PartnersUiSettingsModal
        v-model="showUiSettings"
        @saved="handleSettingsSaved"
    />
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'
import AddPartnerForm from '@/MobileClient/Components/Admin/Partners/AddPartnerForm.vue'
import PartnerList from '@/MobileClient/Components/Admin/Partners/PartnerList.vue'
import SelfConfigForm from '@/MobileClient/Components/Admin/Partners/SelfConfigForm.vue'
import PartnersUiSettings from '@/MobileClient/Components/Admin/Partners/PartnersUiSettings.vue'
import PartnersUiSettingsModal from '@/MobileClient/Components/Admin/Partners/PartnersUiSettingsModal.vue'
export default {
    name: 'PartnersSettings',

    components: {
        AddPartnerForm,
        PartnerList,
        PartnersUiSettings,
        PartnersUiSettingsModal,
        SelfConfigForm,
    },

    setup() {
        const partners = usePartners()
        return { ...partners }
    },

    data() {
        return {
            showUiSettings: false,
            loading: false,
            showSelfConfigModal: false,
            form: {
                is_active: false,
                display_self: false,
            },


            savingSettings: false,
        }
    },

    computed: {
        tenant() {
            return window.Tenant
        },
    },

    async mounted() {
        // Загружаем данные
        await this.loadPartners()
        await this.loadCategories()

        // Инициализируем форму
        this.form.is_active = this.tenant?.settings?.partners?.is_active ?? false
        this.form.display_self = this.tenant?.settings?.partners?.display_self ?? false

        // 🆕 Загружаем статистику товаров
        try {
            await this.loadPartnersStats()
        } catch (error) {
            console.error('Не удалось загрузить статистику:', error)
        }
    },

    methods: {
        /**
         * Форматирование числа с разделителями
         */
        formatNumber(value) {
            return new Intl.NumberFormat('ru-RU').format(value || 0)
        },

        /**
         * Форматирование цены
         */
        formatPrice(value) {
            const num = parseFloat(value || 0)

            // Для больших сумм используем сокращение
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1).replace('.0', '') + ' млн ₽'
            }
            if (num >= 1000) {
                return new Intl.NumberFormat('ru-RU').format(num) + ' ₽'
            }

            return num + ' ₽'
        },
        async handleSettingsChange() {

            console.log("Test")
            if (this.savingSettings) return

            this.savingSettings = true

            try {
                // Отправляем обновлённые настройки на сервер
                await this.updatePartnersSettings(this.form)

                this.$notify?.({
                    title: 'Успех',
                    text: 'Настройки сохранены',
                    type: 'success',
                })

                // Обновляем window.Tenant для актуальности
                if (window.Tenant?.settings) {
                    window.Tenant.settings.partners = { ...this.form }
                }

            } catch (err) {
                console.error('❌ Ошибка сохранения настроек:', err)

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить настройки',
                    type: 'error',
                })

                // Откатываем изменения
                this.initForm()
            } finally {
                this.savingSettings = false
            }
        },


        async refreshPartners() {
            try {
                await this.loadPartners()
                this.$notify?.({
                    title: 'Успех',
                    text: 'Список обновлён',
                    type: 'success',
                })
            } catch (err) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось обновить список',
                    type: 'error',
                })
            }
        },

        onPartnerRefresh() {
            this.loading = true
            this.$nextTick(() => {
                this.loading = false
            })
        },

        onPartnerAdded() {
            this.onPartnerRefresh()
            this.$notify?.({
                title: 'Успех',
                text: 'Партнёр добавлен',
                type: 'success',
            })
        },

        openSelfConfigModal() {
            this.showSelfConfigModal = true
            document.body.style.overflow = 'hidden'
        },

        closeSelfConfigModal() {
            this.showSelfConfigModal = false
            document.body.style.overflow = ''
        },

        onSelfConfigSuccess() {
            this.closeSelfConfigModal()
            this.$notify?.({
                title: 'Успех',
                text: 'Параметры обновлены',
                type: 'success',
            })
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-danger: #ef4444;

.partners-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// HERO СЕКЦИЯ
// ==========================================
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px 50px;
    color: white;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.3;

        &.blob-1 {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.3);
            top: -100px;
            right: -50px;
            animation: float 20s ease-in-out infinite;
        }

        &.blob-2 {
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.2);
            bottom: -80px;
            left: -30px;
            animation: float 25s ease-in-out infinite reverse;
        }
    }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -20px) scale(1.1); }
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
}

.hero-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.hero-title {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 8px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

// ==========================================
// СТАТИСТИКА
// ==========================================
.stats-section {
    padding: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 6px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 14px;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 800;
    color: #2c3e50;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 2px;
    line-height: 100%;
}

.stat-loading {
    color: #6c757d;
    font-size: 1rem;
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-card {
        padding: 12px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .stat-value {
        font-size: 0.9rem;
    }
}

// ==========================================
// НАСТРОЙКИ
// ==========================================
.settings-section {
    padding: 0 16px 20px;
    max-width: 900px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: $admin-text;

    i {
        color: $admin-primary;
    }
}

.section-actions {
    display: flex;
    gap: 8px;
}

.refresh-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    color: $admin-text;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.settings-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.setting-card {
    background: $admin-card-bg;
    border: 2px solid $admin-border;
    border-radius: 14px;
    padding: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    transition: all 0.3s;

    &.is-active {
        border-color: $admin-primary;
        background: linear-gradient(135deg, rgba($admin-primary, 0.02) 0%, rgba($admin-primary, 0.05) 100%);
    }

    &.is-disabled {
        opacity: 0.5;
        pointer-events: none;
    }
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.setting-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: all 0.3s;

    .is-active & {
        background: $admin-primary;
        color: white;
    }
}

.setting-text {
    flex: 1;
    min-width: 0;
}

.setting-title {
    font-size: 1rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.setting-description {
    font-size: 0.8rem;
    color: $admin-text-muted;
    margin: 0;
    line-height: 1.4;
}

// ==========================================
// SWITCH (ПЕРЕКЛЮЧАТЕЛЬ)
// ==========================================
.switch-control {
    position: relative;
    width: 52px;
    height: 30px;
    flex-shrink: 0;
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-primary;

        &::before {
            transform: translateX(22px);
        }
    }

    &:disabled + .switch-slider {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: $admin-border;
    transition: 0.3s;
    border-radius: 30px;

    &::before {
        position: absolute;
        content: '';
        height: 24px;
        width: 24px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

// ==========================================
// КНОПКА НАСТРОЙКИ
// ==========================================
.config-button {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: $admin-card-bg;
    border: 2px solid $admin-border;
    border-radius: 14px;
    color: $admin-text;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;

    &:hover {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.02);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:active {
        transform: translateY(0);
    }
}

.config-button-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.config-button-text {
    flex: 1;
    min-width: 0;
}

.config-button-title {
    display: block;
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;
    margin-bottom: 2px;
}

.config-button-desc {
    display: block;
    font-size: 0.8rem;
    color: $admin-text-muted;
}

.config-button-arrow {
    color: $admin-text-muted;
    font-size: 0.9rem;
    transition: transform 0.2s;

    .config-button:hover & {
        transform: translateX(4px);
        color: $admin-primary;
    }
}

// ==========================================
// СПИСОК ПАРТНЕРОВ
// ==========================================
.partners-section {
    padding: 0 16px 20px;
    max-width: 900px;
    margin: 0 auto;
}

.partners-container {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 14px;
    padding: 16px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 14px;
    color: $admin-text-muted;

    .loader-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $admin-border;
        border-top-color: $admin-primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// МОДАЛКА
// ==========================================
.modal-overlay {
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

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid $admin-border;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: $admin-text;
}

.modal-subtitle {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.modal-fade-enter-active {
    transition: opacity 0.3s ease;

    .modal-container {
        animation: modalSlideUp 0.3s ease;
    }
}

.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
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

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .page-hero {
        padding: 30px 16px 40px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-card {
        padding: 12px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .stat-value {
        font-size: 0.9rem;
    }

    .setting-card {
        padding: 14px;
    }

    .setting-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .setting-title {
        font-size: 0.9rem;
    }

    .setting-description {
        font-size: 0.75rem;
    }

    .modal-overlay {
        padding: 0;
    }

    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .settings-container {
        max-width: 700px;
    }

    .partners-container {
        max-width: 900px;
    }
}
</style>
