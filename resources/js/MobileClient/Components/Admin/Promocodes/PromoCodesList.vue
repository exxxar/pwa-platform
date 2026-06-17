<template>
    <div class="promocodes-list-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ (STICKY) -->
        <!-- ========================================== -->
        <div class="control-panel">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    v-model="search"
                    class="search-input"
                    placeholder="Поиск промокода..."
                >
                <button
                    v-if="search"
                    type="button"
                    class="search-clear"
                    @click="search = ''"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="sort-controls">
                <select v-model="order" @change="reloadPromoCodes" class="select-modern">
                    <option value="id">По номеру</option>
                    <option value="code">По коду</option>
                    <option value="description">По описанию</option>
                    <option value="cashback_amount">По скидке</option>
                    <option value="is_active">По статусу</option>
                    <option value="max_activation_count">По лимиту</option>
                    <option value="updated_at">По дате</option>
                </select>
                <button class="sort-direction-btn" @click="toggleDirection">
                    <i class="fa-solid" :class="direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка промокодов...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПРОМОКОДОВ -->
        <!-- ========================================== -->
        <div v-else class="promocodes-container">

            <div v-if="codes.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>Промокодов пока нет</h3>
                <p>
                    {{ search
                    ? 'Попробуйте изменить поисковый запрос'
                    : 'Создайте первый промокод для мотивации клиентов' }}
                </p>
                <button v-if="!search" class="btn-primary-modern" @click="$emit('create')">
                    <i class="fa-solid fa-plus"></i> Создать промокод
                </button>
            </div>

            <div v-else class="promocodes-list">
                <div
                    v-for="code in codes"
                    :key="'promo-' + code.id"
                    class="promo-card"
                    :class="{ 'is-inactive': !code.is_active, 'is-deleted': code.deleted_at }"
                    @click="selectEvent(code)"
                >
                    <!-- Шапка карточки -->
                    <div class="promo-header">
                        <div class="promo-status">
                            <i class="fa-solid" :class="code.is_active ? 'fa-circle-check active' : 'fa-circle-xmark inactive'"></i>
                            <span class="promo-id">#{{ code.id }}</span>
                        </div>
                        <div class="promo-activations">
                            <span class="activations-count">
                                {{ code.current_activation_count || 0 }} / {{ code.max_activation_count }}
                            </span>
                            <span class="activations-label">активаций</span>
                        </div>
                    </div>

                    <!-- Основная информация -->
                    <div class="promo-body">
                        <h4 class="promo-code">{{ code.code || 'Не указано' }}</h4>
                        <p class="promo-description">{{ code.description || 'Не указано' }}</p>

                        <div class="promo-details">
                            <div class="detail-item discount">
                                <i class="fa-solid fa-percent"></i>
                                <span class="detail-label">Скидка:</span>
                                <strong>
                                    -{{ code.cashback_amount || 0 }}
                                    {{ code.config?.discount_in_percent ? '%' : '₽' }}
                                </strong>
                            </div>
                            <div class="detail-item min-price">
                                <i class="fa-solid fa-ruble-sign"></i>
                                <span class="detail-label">От:</span>
                                <strong>{{ code.activate_price || 0 }} ₽</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="promo-actions" v-if="!code.deleted_at">
                        <button
                            class="action-btn"
                            @click.stop="selectEvent(code)"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                            <span>Редактировать</span>
                        </button>
                        <button
                            class="action-btn danger"
                            @click.stop="prepareForRemove(code)"
                            title="Удалить"
                        >
                            <i class="fa-solid fa-trash"></i>
                            <span>Удалить</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПАГИНАЦИЯ -->
        <!-- ========================================== -->
        <div v-if="paginate_object && paginate_object.last_page > 1 && codes.length > 0" class="pagination-wrapper">
            <Pagination
                :simple="true"
                @pagination_page="nextPromoCodes"
                :pagination="paginate_object"
            />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="showRemoveModal" class="modal-overlay bottom-sheet" @click.self="closeRemoveModal">
            <div class="modal-container confirm-modal">
                <div class="confirm-icon danger">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h4>Удалить промокод?</h4>
                <p>
                    Промокод <strong>«{{ selected?.code || selected?.id }}»</strong> будет удалён.
                    Это действие нельзя отменить.
                </p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="closeRemoveModal">Отмена</button>
                    <button class="btn-primary-modern danger" @click="removeCode" :disabled="loading">
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
import Pagination from '@/MobileClient/Components/Pagination.vue'

export default {
    name: 'PromoCodesList',

    components: {
        Pagination,
    },

    emits: ['create', 'select'],

    data() {
        return {
            loading: false,
            search: '',
            searchDebounce: null,
            direction: 'desc',
            order: 'updated_at',
            selected: null,
            showRemoveModal: false,
        }
    },

    computed: {
        ...mapState('promocodes', [
            'codes',
            'paginate_object',
        ]),

        bot() {
            return window.currentBot
        },
    },

    watch: {
        search() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => this.reloadPromoCodes(), 300)
        },
    },

    mounted() {
        this.loadPromoCodesPage(0)
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce)
    },

    methods: {
        ...mapActions('promocodes', [
            'loadPromoCodes',
            'removePromoCodes',
        ]),

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        reloadPromoCodes() {
            this.loadPromoCodesPage(0)
        },

        async loadPromoCodesPage(page = 0) {
            this.loading = true
            try {
                await this.loadPromoCodes({
                    dataObject: {
                        bot_id: this.bot?.id || null,
                        search: this.search,
                        order: this.order,
                        direction: this.direction,
                    },
                    page,
                    size: 20,
                })
            } catch (err) {
                console.error('Ошибка загрузки промокодов:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить промокоды',
                    type: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        nextPromoCodes(index) {
            this.loadPromoCodesPage(index)
        },

        // ==========================================
        // СОРТИРОВКА
        // ==========================================
        toggleDirection() {
            this.direction = this.direction === 'asc' ? 'desc' : 'asc'
            this.reloadPromoCodes()
        },

        // ==========================================
        // ДЕЙСТВИЯ
        // ==========================================
        selectEvent(code) {
            this.$emit('select', code)
        },

        prepareForRemove(code) {
            this.selected = code
            this.showRemoveModal = true
        },

        closeRemoveModal() {
            this.showRemoveModal = false
            this.selected = null
        },

        async removeCode() {
            if (!this.selected) return

            this.loading = true

            try {
                await this.removePromoCodes({
                    promoCodeId: this.selected.id,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Промокод удалён',
                    type: 'success',
                })

                this.closeRemoveModal()
                this.loadPromoCodesPage(0)
            } catch (err) {
                console.error('Ошибка удаления промокода:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить промокод',
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
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;

.promocodes-list-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ПАНЕЛЬ УПРАВЛЕНИЯ
// ==========================================
.control-panel {
    position: sticky;
    top: 0;
    z-index: 100;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: $admin-text-muted;
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 10px 36px 10px 36px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }
}

.search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $admin-border;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;

    &:active {
        background: $admin-danger;
        color: white;
    }
}

.sort-controls {
    display: flex;
    gap: 8px;
}

.select-modern {
    flex: 1;
    padding: 10px 32px 10px 12px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.9rem;
    background: $admin-card-bg;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;

    &:focus {
        outline: none;
        border-color: $admin-primary;
    }
}

.sort-direction-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
    }
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
// КОНТЕЙНЕР
// ==========================================
.promocodes-container {
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
        color: $admin-primary;
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
        line-height: 1.4;
    }
}

// ==========================================
// СПИСОК ПРОМОКОДОВ
// ==========================================
.promocodes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.promo-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;

    &:active {
        transform: scale(0.98);
    }

    &.is-inactive {
        opacity: 0.7;
    }

    &.is-deleted {
        opacity: 0.5;
        border-color: $admin-danger;
    }
}

.promo-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: $admin-bg;
    border-bottom: 1px solid $admin-border;
}

.promo-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;

    i {
        font-size: 1rem;

        &.active {
            color: $admin-success;
        }

        &.inactive {
            color: $admin-danger;
        }
    }
}

.promo-id {
    font-family: monospace;
    font-size: 0.8rem;
}

.promo-activations {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.activations-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: $admin-primary;
}

.activations-label {
    font-size: 0.7rem;
    color: $admin-text-muted;
}

.promo-body {
    padding: 14px;
}

.promo-code {
    font-size: 1.1rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 6px 0;
    font-family: monospace;
    letter-spacing: 0.5px;
}

.promo-description {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0 0 12px 0;
    line-height: 1.4;
    font-style: italic;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.promo-details {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: $admin-text-muted;

    i {
        font-size: 0.8rem;
    }

    strong {
        color: $admin-text;
        font-weight: 700;
    }

    &.discount i {
        color: $admin-success;
    }

    &.min-price i {
        color: $admin-primary;
    }
}

.promo-actions {
    display: flex;
    gap: 8px;
    padding: 0 14px 14px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 40px;

    &:active {
        transform: scale(0.95);
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }

    span {
        display: none;

        @media (min-width: 480px) {
            display: inline;
        }
    }
}

// ==========================================
// ПАГИНАЦИЯ
// ==========================================
.pagination-wrapper {
    padding: 20px 16px;
    display: flex;
    justify-content: center;
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

.confirm-modal {
    padding: 24px 20px;
    text-align: center;
}

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

.btn-primary-modern,
.btn-secondary-modern {
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
// КНОПКА СОЗДАНИЯ (для пустого состояния)
// ==========================================
.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active {
        transform: scale(0.98);
        background:  color.adjust($admin-primary, $lightness: -5%);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .control-panel {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px 24px;
    }

    .promocodes-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }

    .modal-overlay {
        padding: 20px;

        &.bottom-sheet {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 400px;
        border-radius: 16px;

        .bottom-sheet & {
            border-radius: 16px;
            max-height: 90vh;
        }
    }
}
</style>
