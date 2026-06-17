<template>
    <div class="partner-list-page">

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка партнеров...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПАРТНЕРОВ -->
        <!-- ========================================== -->
        <div v-if="!loading" class="partners-container">

            <div v-if="partnerList.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h3>Партнеры не найдены</h3>
                <p>Добавьте первого партнера</p>
            </div>

            <div v-else class="partners-list">
                <div
                    v-for="partner in partnerList"
                    :key="'partner-' + partner.id"
                    class="partner-card"
                    :class="{ 'is-inactive': !partner.is_active, 'is-deleted': partner.before_deleted }"
                >
                    <div class="partner-header">
                        <div class="partner-icon">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div class="partner-info">
                            <h4 class="partner-title">{{ partner.title }}</h4>
                            <div class="partner-meta">
                                <span class="meta-count">
                                    <i class="fa-solid fa-cube"></i>
                                    {{ partner.products_count || 0 }} товаров
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Статус активности -->
                    <div class="partner-status">
                        <div class="status-switch">
                            <label class="switch-label" :for="'partner-active-' + partner.id">
                                <i class="fa-solid fa-toggle-on"></i>
                                <span>Статус:</span>
                                <strong :class="partner.is_active ? 'text-success' : 'text-danger'">
                                    {{ partner.is_active ? 'Активен' : 'Не активен' }}
                                </strong>
                            </label>
                            <div class="switch-control">
                                <input
                                    :id="'partner-active-' + partner.id"
                                    type="checkbox"
                                    v-model="partner.is_active"
                                    class="switch-input"
                                    @change="updatePartnersActiveStatus(partner)"
                                >
                                <span class="switch-slider"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="partner-actions">
                        <a
                            v-if="partner.link"
                            :href="partner.link"
                            target="_blank"
                            class="action-btn telegram"
                            title="Telegram"
                        >
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                        <button
                            class="action-btn"
                            @click="selectPartner(partner)"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button
                            class="action-btn"
                            @click="selectPartnerForProductObserve(partner)"
                            title="Товары"
                        >
                            <i class="fa-solid fa-store"></i>
                        </button>
                        <button
                            class="action-btn danger"
                            @click="selectPartnerForRemove(partner)"
                            title="Удалить"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: НАСТРОЙКА ПАРТНЕРА -->
        <!-- ========================================== -->
        <div v-if="showConfigModal" class="modal-overlay mobile-fullscreen" @click.self="closeConfigModal">
            <div class="modal-container config-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="closeConfigModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Настройка партнера</h3>
                </div>
                <div class="modal-body">
                    <ConfigPartnerForm
                        v-if="selected"
                        :initial-data="selected"
                        @success="onConfigSuccess"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ТОВАРЫ ПАРТНЕРА -->
        <!-- ========================================== -->
        <div v-if="showProductsModal" class="modal-overlay mobile-fullscreen" @click.self="closeProductsModal">
            <div class="modal-container products-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="closeProductsModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Товары партнера: {{ selected?.title }}</h3>
                </div>
                <div class="modal-body">
                    <PartnerProductList
                        v-if="selected"
                        :partner="selected"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="showRemoveModal" class="modal-overlay bottom-sheet" @click.self="closeRemoveModal">
            <div class="modal-container confirm-modal">
                <div class="confirm-icon danger">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h4>Удалить партнера?</h4>
                <p>
                    Партнер <strong>«{{ selected?.title }}»</strong> будет удален.
                    Это действие нельзя отменить.
                </p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="closeRemoveModal">Отмена</button>
                    <button class="btn-primary-modern danger" @click="removePartner" :disabled="loading">
                        <span v-if="loading" class="spinner-small"></span>
                        <span v-else>Удалить</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import ConfigPartnerForm from '@/MobileClient/Components/Admin/Partners/ConfigPartnerForm.vue'
import PartnerProductList from '@/MobileClient/Components/Admin/Partners/PartnerProductList.vue'

export default {
    name: 'PartnerList',

    components: {
        ConfigPartnerForm,
        PartnerProductList,
    },

    data() {
        return {
            loading: false,
            selected: null,
            partnerList: [],
            showConfigModal: false,
            showProductsModal: false,
            showRemoveModal: false,
        }
    },

    computed: {
        ...mapState('partners', ['getPartners', 'getPartnersPaginateObject']),

        bot() {
            return window.currentBot || null
        },

        settings() {
            return this.bot?.settings
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.partnerList = this.bot?.partners || []

            if (this.partnerList.length === 0) {
                this.loadPartners(0)
            }
        })
    },

    methods: {
        ...mapActions('partners', [
            'loadPartners',
            'updatePartnersActiveStatus',
            'removePartner',
        ]),

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadPartnersPage(pageIndex = 0) {
            this.loading = true
            try {
                await this.loadPartners({
                    dataObject: {},
                    page: pageIndex,
                })
                this.partnerList = this.getPartners || []
            } catch (err) {
                console.error('Ошибка загрузки партнеров:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить партнеров',
                    type: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        // ==========================================
        // ДЕЙСТВИЯ С ПАРТНЕРАМИ
        // ==========================================
        async updatePartnersActiveStatus(partner) {
            try {
                await this.updatePartnersActiveStatus({
                    is_active: partner.is_active,
                    id: partner.id,
                })
                this.$notify?.({
                    title: 'Успех',
                    text: partner.is_active ? 'Партнер активирован' : 'Партнер деактивирован',
                    type: 'success',
                })
            } catch (err) {
                // Откат при ошибке
                partner.is_active = !partner.is_active
                console.error('Ошибка изменения статуса:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус',
                    type: 'error',
                })
            }
        },

        // ==========================================
        // МОДАЛКИ
        // ==========================================
        selectPartner(partner) {
            this.selected = { ...partner }
            this.showConfigModal = true
        },

        closeConfigModal() {
            this.showConfigModal = false
            this.selected = null
        },

        onConfigSuccess() {
            this.closeConfigModal()
            this.loadPartnersPage(0)
            this.$notify?.({
                title: 'Успех',
                text: 'Партнер обновлен',
                type: 'success',
            })
        },

        selectPartnerForProductObserve(partner) {
            this.selected = partner
            this.showProductsModal = true
        },

        closeProductsModal() {
            this.showProductsModal = false
            this.selected = null
        },

        selectPartnerForRemove(partner) {
            this.selected = partner
            this.showRemoveModal = true
        },

        closeRemoveModal() {
            this.showRemoveModal = false
            this.selected = null
        },

        async removePartner() {
            if (!this.selected) return

            // Оптимистичное обновление UI
            const index = this.partnerList.findIndex(p => p.id === this.selected.id)
            if (index !== -1) {
                this.partnerList[index].before_deleted = true
            }

            this.loading = true

            try {
                await this.removePartner({
                    partnerId: this.selected.id,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Партнер удален',
                    type: 'success',
                })

                this.closeRemoveModal()
                this.loadPartnersPage(0)
            } catch (err) {
                // Откат при ошибке
                if (index !== -1) {
                    this.partnerList[index].before_deleted = false
                }

                console.error('Ошибка удаления партнера:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить партнера',
                    type: 'error',
                })
            } finally {
                this.loading = false
            }
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
$admin-danger: #ef4444;
$admin-telegram: #0088cc;

.partner-list-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ИНДИКАТОР ЗАГРУЗКИ
// ==========================================
.loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $admin-text-muted;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $admin-border;
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// КОНТЕЙНЕР ПАРТНЕРОВ
// ==========================================
.partners-container {
    padding: 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $admin-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
}

// ==========================================
// СПИСОК ПАРТНЕРОВ
// ==========================================
.partners-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.partner-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-inactive {
        opacity: 0.7;
    }

    &.is-deleted {
        opacity: 0.5;
        border-color: $admin-danger;
    }
}

.partner-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
}

.partner-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.partner-info {
    flex: 1;
    min-width: 0;
}

.partner-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.partner-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.75rem;
    color: $admin-text-muted;
}

.meta-count {
    display: flex;
    align-items: center;
    gap: 4px;
}

// ==========================================
// СТАТУС АКТИВНОСТИ
// ==========================================
.partner-status {
    padding: 0 14px 14px;
}

.status-switch {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 8px;
}

.switch-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: $admin-text;
    cursor: pointer;

    i {
        color: $admin-primary;
        font-size: 1rem;
    }

    strong {
        &.text-success {
            color: $admin-success;
        }

        &.text-danger {
            color: $admin-danger;
        }
    }
}

.switch-control {
    position: relative;
    width: 44px;
    height: 26px;
    flex-shrink: 0;
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-success;

        &::before {
            transform: translateX(18px);
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
    border-radius: 26px;

    &::before {
        position: absolute;
        content: '';
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}

// ==========================================
// ДЕЙСТВИЯ
// ==========================================
.partner-actions {
    display: flex;
    gap: 8px;
    padding: 0 14px 14px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    min-height: 40px;

    &:active {
        transform: scale(0.95);
    }

    &.telegram {
        color: $admin-telegram;

        &:active {
            background: $admin-telegram;
            border-color: $admin-telegram;
            color: white;
        }
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }
}

// ==========================================
// МОДАЛКИ
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

    &.bottom-sheet {
        align-items: flex-end;
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

    .bottom-sheet & {
        border-radius: 16px 16px 0 0;
        max-height: 80vh;
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

.config-modal, .products-modal {
    max-width: 100%;
}

.confirm-modal {
    padding: 24px 20px;
    text-align: center;
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
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
// ПОДТВЕРЖДЕНИЕ
// ==========================================
.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;

    &.danger {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
    }
}

.confirm-modal {
    h4 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        margin-bottom: 24px;
        line-height: 1.4;
    }
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.btn-primary-modern, .btn-secondary-modern {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;

    &.danger {
        background: $admin-danger;
    }
}

.btn-secondary-modern {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .partners-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }

    .modal-overlay {
        padding: 20px;

        &.mobile-fullscreen {
            align-items: center;
        }

        &.bottom-sheet {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 700px;
        border-radius: 16px;
        max-height: 90vh;

        .confirm-modal & {
            max-width: 400px;
        }
    }
}
</style>
