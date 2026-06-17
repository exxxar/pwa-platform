<template>
    <div class="partners-page">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fa-solid fa-handshake"></i>
            </div>
            <h2 class="header-title">Работа с партнерами</h2>
        </div>

        <!-- ========================================== -->
        <!-- НАСТРОЙКИ -->
        <!-- ========================================== -->
        <div class="settings-container">

            <!-- Режим работы с партнерами -->
            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Режим работы с партнерами</h4>
                        <p class="setting-description">
                            Включите для активации партнерской программы
                        </p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="partners-is-active"
                        type="checkbox"
                        v-model="form.is_active"
                        class="switch-input"
                        @change="updatePartnersSettings"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>

            <!-- Отображать себя в списке -->
            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Отображать себя в списке</h4>
                        <p class="setting-description">
                            Ваша компания будет видна в списке партнеров
                        </p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="partners-display-self"
                        type="checkbox"
                        v-model="form.display_self"
                        class="switch-input"
                        @change="updatePartnersSettings"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>

            <!-- Кнопка настройки своих параметров -->
            <button
                v-if="form.display_self"
                class="btn-secondary-modern"
                @click="openSelfConfigModal"
            >
                <i class="fa-solid fa-gear"></i>
                <span>Настроить свои параметры отображения</span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>

        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПАРТНЕРОВ -->
        <!-- ========================================== -->
        <div v-if="!loading" class="partners-container">
            <PartnerList @refresh="onPartnerRefresh" />
            <AddPartnerForm @callback="onPartnerAdded" />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: НАСТРОЙКА СВОИХ ПАРАМЕТРОВ -->
        <!-- ========================================== -->
        <div v-if="showSelfConfigModal" class="modal-overlay mobile-fullscreen" @click.self="closeSelfConfigModal">
            <div class="modal-container config-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="closeSelfConfigModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Настройка партнера</h3>
                </div>
                <div class="modal-body">
                    <SelfConfigForm @success="onSelfConfigSuccess" />
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapActions } from 'pinia'
import AddPartnerForm from '@/MobileClient/Components/Admin/Partners/AddPartnerForm.vue'
import PartnerList from '@/MobileClient/Components/Admin/Partners/PartnerList.vue'
import SelfConfigForm from '@/MobileClient/Components/Admin/Partners/SelfConfigForm.vue'

export default {
    name: 'PartnersSettings',

    components: {
        AddPartnerForm,
        PartnerList,
        SelfConfigForm,
    },

    data() {
        return {
            loading: false,
            showSelfConfigModal: false,
            form: {
                is_active: false,
                display_self: false,
            },
        }
    },

    computed: {
        tenant() {
            return window.Tenant
        },
    },

    mounted() {
        this.form.is_active = this.tenant?.settings?.partners?.is_active ?? false
        this.form.display_self = this.tenant?.settings?.partners?.display_self ?? false
    },

    methods: {
        ...mapActions('partners', ['updatePartnersSettings', 'loadPartners']),

        onPartnerRefresh() {
            this.loading = true
            this.$nextTick(() => {
                this.loading = false
            })
        },

        onPartnerAdded() {
            this.onPartnerRefresh()
        },

        openSelfConfigModal() {
            this.showSelfConfigModal = true
        },

        closeSelfConfigModal() {
            this.showSelfConfigModal = false
        },

        async updatePartnersSettings() {
            try {
                await this.updatePartnersSettings(this.form)
                this.$notify?.({
                    title: 'Успех',
                    text: 'Настройки сохранены',
                    type: 'success',
                })
            } catch (err) {
                console.error('Ошибка сохранения настроек:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить настройки',
                    type: 'error',
                })
            }
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

.partners-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0;
}

// ==========================================
// НАСТРОЙКИ
// ==========================================
.settings-container {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.setting-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.setting-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.setting-text {
    flex: 1;
    min-width: 0;
}

.setting-title {
    font-size: 0.95rem;
    font-weight: 600;
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
    width: 48px;
    height: 28px;
    flex-shrink: 0;
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-primary;

        &::before {
            transform: translateX(20px);
        }
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
    border-radius: 28px;

    &::before {
        position: absolute;
        content: '';
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}

// ==========================================
// КНОПКА НАСТРОЙКИ
// ==========================================
.btn-secondary-modern {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    color: $admin-text;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;

    i {
        &:first-child {
            color: $admin-primary;
            font-size: 1rem;
        }

        &:last-child {
            margin-left: auto;
            color: $admin-text-muted;
            font-size: 0.8rem;
        }
    }

    &:active {
        transform: scale(0.98);
        background: rgba($admin-primary, 0.04);
        border-color: $admin-primary;
    }
}

// ==========================================
// СПИСОК ПАРТНЕРОВ
// ==========================================
.partners-container {
    padding: 0 16px 16px;
}

// ==========================================
// МОДАЛКА
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.2s ease;

    &.mobile-fullscreen {
        align-items: stretch;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;

    .mobile-fullscreen & {
        border-radius: 0;
        max-height: 100vh;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.config-modal {
    max-width: 100%;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    background: $admin-card-bg;
    z-index: 10;

    h3 {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }
}

.modal-back {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-primary;
        color: white;
    }
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .page-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .settings-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 24px;
    }

    .partners-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 24px 24px;
    }

    .modal-overlay {
        padding: 20px;

        &.mobile-fullscreen {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 700px;
        border-radius: 16px;
        max-height: 90vh;
    }
}
</style>
