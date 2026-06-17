<template>
    <div class="collection-list-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
        <!-- ========================================== -->
        <div class="control-panel">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    v-model="search"
                    class="search-input"
                    placeholder="Поиск подборок..."
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

            <button class="btn-icon" :class="{ 'is-active': showFilters }" @click="showFilters = !showFilters">
                <i class="fa-solid fa-sliders"></i>
            </button>

            <button class="btn-primary-modern" @click="openEditModal(null)">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ ФИЛЬТРОВ (СКРЫВАЕМАЯ) -->
        <!-- ========================================== -->
        <div v-if="showFilters" class="filters-bar mobile-optimized">
            <div class="filter-section">
                <label class="filter-label">Сортировка</label>
                <div class="sort-controls">
                    <select v-model="sort.param" @change="reloadCollections" class="select-modern">
                        <option value="id">По ID</option>
                        <option value="title">По названию</option>
                        <option value="order_position">По позиции выдачи</option>
                        <option value="is_active">По статусу</option>
                        <option value="is_public">По публичности</option>
                        <option value="discount">По скидке</option>
                        <option value="updated_at">По дате</option>
                    </select>
                    <button class="sort-direction-btn" @click="toggleSortDirection">
                        <i class="fa-solid" :class="sort.direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                    </button>
                </div>
            </div>

            <div class="filter-stats">
                <span class="stats-badge">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>{{ totalCollections }}</span>
                </span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка подборок...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПОДБОРОК -->
        <!-- ========================================== -->
        <div v-if="!isLoading" class="collections-container">

            <div v-if="collections?.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3>Подборки не найдены</h3>
                <p>{{ search ? 'Измените поисковый запрос' : 'Добавьте первую подборку' }}</p>
                <button v-if="!search" class="btn-primary-modern" @click="openEditModal(null)">
                    <i class="fa-solid fa-plus"></i> Добавить подборку
                </button>
            </div>

            <div v-else class="collections-list">
                <div
                    v-for="collection in collections"
                    :key="'col-' + collection.id"
                    class="collection-card"
                    :class="{ 'is-inactive': !collection.is_active }"
                >
                    <div class="collection-header">
                        <div class="collection-icon">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div class="collection-info">
                            <h4 class="collection-title">{{ collection.title }}</h4>
                            <div class="collection-meta">
                                <span class="meta-id">#{{ collection.id }}</span>
                                <span class="meta-count">
                                    <i class="fa-solid fa-cube"></i>
                                    {{ (collection.products || []).length }} товаров
                                </span>
                            </div>
                        </div>
                        <div class="collection-badges">
                            <span v-if="collection.discount" class="discount-badge">
                                -{{ collection.discount }}%
                            </span>
                            <span v-if="collection.is_public" class="public-badge">
                                <i class="fa-solid fa-globe"></i>
                            </span>
                        </div>
                    </div>

                    <div class="collection-actions">
                        <button
                            class="action-btn"
                            @click="openDemoModal(collection)"
                            title="Демо"
                        >
                            <i class="fa-solid fa-eye"></i>
                            <span>Демо</span>
                        </button>
                        <button
                            class="action-btn"
                            @click="openEditModal(collection)"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                            <span>Редактировать</span>
                        </button>
                        <button
                            class="action-btn danger"
                            @click="openRemoveModal(collection)"
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
        <div v-if="paginate && paginate.last_page > 1" class="pagination-wrapper">
            <Pagination
                :simple="true"
                @pagination_page="nextCollections"
                :pagination="paginate"
            />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ДЕМО ПОДБОРКИ -->
        <!-- ========================================== -->
        <div v-if="showDemoModal" class="modal-overlay mobile-fullscreen" @click.self="hideDemoModal">
            <div class="modal-container demo-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="hideDemoModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Демонстрация: {{ selectedCollection?.title }}</h3>
                </div>
                <div class="modal-body">
                    <ProductCollectionView
                        v-if="selectedCollection"
                        :item="selectedCollection"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: РЕДАКТИРОВАНИЕ ПОДБОРКИ -->
        <!-- ========================================== -->
        <div v-if="showEditModal" class="modal-overlay mobile-fullscreen" @click.self="hideEditModal">
            <div class="modal-container edit-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="hideEditModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>{{ selectedCollection ? 'Редактирование' : 'Новая подборка' }}</h3>
                </div>
                <div class="modal-body">
                    <CollectionForm
                        v-if="formReady"
                        :item="selectedCollection"
                        @callback="onCollectionSaved"
                        @cancel="hideEditModal"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="showRemoveModal" class="modal-overlay bottom-sheet" @click.self="hideRemoveModal">
            <div class="modal-container confirm-modal">
                <div class="confirm-icon danger">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h4>Удалить подборку?</h4>
                <p>
                    Подборка <strong>«{{ selectedCollection?.title }}»</strong> (#{{ selectedCollection?.id }})
                    будет удалена. Это действие нельзя отменить.
                </p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="hideRemoveModal">Отмена</button>
                    <button class="btn-primary-modern danger" @click="removeCollection" :disabled="isLoading">
                        <span v-if="isLoading" class="spinner-small"></span>
                        <span v-else>Удалить</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useProductsStore } from '@/MobileClient/stores/Shop/products'
import Pagination from '@/MobileClient/Components/Pagination.vue'
import CollectionForm from '@/MobileClient/Components/Admin/Shop/CollectionForm.vue'
import ProductCollectionView from '@/MobileClient/Components/Shop/ProductCollectionView.vue'

export default {
    name: 'CollectionList',

    components: {
        Pagination,
        CollectionForm,
        ProductCollectionView,
    },

    data() {
        return {
            isLoading: false,
            search: '',
            searchDebounce: null,
            showFilters: false,

            sort: {
                param: 'id',
                direction: 'asc',
            },

            selectedCollection: null,
            showDemoModal: false,
            showEditModal: false,
            showRemoveModal: false,
            formReady: false,
        }
    },

    computed: {
        ...mapState(useProductsStore, [
            'collections',
            'collections_paginate_object',
        ]),

        tenant() {
            return window.Tenant || null
        },

        totalCollections() {
            return this.collections_paginate_object?.total || this.collections?.length || 0
        },

        paginate() {
            return this.collections_paginate_object
        },
    },

    watch: {
        search() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => this.reloadCollections(), 300)
        },
    },

    mounted() {
        this.loadCollectionsPage(0)
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce)
    },

    methods: {
        ...mapActions(useProductsStore, [
            'loadCollections',
            'removeProductCollection',
        ]),

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        reloadCollections() {
            this.loadCollectionsPage(0)
        },

        async loadCollectionsPage(page = 0) {
            this.isLoading = true
            try {
                await this.loadCollections({
                    dataObject: {
                        search: this.search,
                        order_by: this.sort.param,
                        direction: this.sort.direction,
                    },
                    page,
                    size: 10,
                })
            } catch (err) {
                console.error('Ошибка загрузки подборок:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить подборки',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        nextCollections(index) {
            this.loadCollectionsPage(index)
        },

        // ==========================================
        // СОРТИРОВКА
        // ==========================================
        toggleSortDirection() {
            this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
            this.reloadCollections()
        },

        // ==========================================
        // МОДАЛКИ
        // ==========================================
        openDemoModal(collection) {
            this.selectedCollection = collection
            this.showDemoModal = true
        },

        hideDemoModal() {
            this.showDemoModal = false
            this.selectedCollection = null
        },

        openEditModal(collection) {
            this.formReady = false
            this.selectedCollection = collection ? { ...collection } : null

            this.showEditModal = true

            this.$nextTick(() => {
                this.formReady = true
            })
        },

        hideEditModal() {
            this.showEditModal = false
            this.selectedCollection = null
            this.formReady = false
        },

        openRemoveModal(collection) {
            this.selectedCollection = collection
            this.showRemoveModal = true
        },

        hideRemoveModal() {
            this.showRemoveModal = false
            this.selectedCollection = null
        },

        async removeCollection() {
            if (!this.selectedCollection) return
            this.isLoading = true

            try {
                await this.removeProductCollection({
                    collectionId: this.selectedCollection.id,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Подборка удалена',
                    type: 'success',
                })

                this.hideRemoveModal()
                this.loadCollectionsPage(0)
            } catch (err) {
                console.error('Ошибка удаления подборки:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить подборку',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        onCollectionSaved() {
            this.hideEditModal()
            this.loadCollectionsPage(0)
            this.$notify?.({
                title: 'Успех',
                text: 'Подборка сохранена',
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
$admin-warning: #f59e0b;
$admin-success: #10b981;
$admin-danger: #ef4444;

.collection-list-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ПАНЕЛЬ УПРАВЛЕНИЯ
// ==========================================
.control-panel {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    z-index: 100;
}

.search-box {
    flex: 1;
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

.btn-icon {
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
    font-size: 1rem;
    transition: all 0.2s;

    &.is-active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }

    &:active {
        transform: scale(0.95);
    }
}

.btn-primary-modern {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-primary;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:active {
        transform: scale(0.95);
    }
}

// ==========================================
// ПАНЕЛЬ ФИЛЬТРОВ
// ==========================================
.filters-bar {
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    padding: 16px;
    animation: slideDown 0.3s ease;

    &.mobile-optimized {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.filter-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
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

    &:active {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
    }
}

.filter-stats {
    text-align: center;
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba($admin-primary, 0.08);
    color: $admin-primary;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
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
// КОНТЕЙНЕР ПОДБОРОК
// ==========================================
.collections-container {
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
// СПИСОК ПОДБОРОК
// ==========================================
.collections-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.collection-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-inactive {
        opacity: 0.6;
    }
}

.collection-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
}

.collection-icon {
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

.collection-info {
    flex: 1;
    min-width: 0;
}

.collection-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.collection-meta {
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

.collection-badges {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.discount-badge {
    padding: 4px 8px;
    background: $admin-danger;
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
}

.public-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba($admin-success, 0.1);
    color: $admin-success;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.collection-actions {
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
    }

    @media (min-width: 480px) {
        span {
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

.demo-modal, .edit-modal {
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
// ДЕСКТОП (ОПЦИОНАЛЬНО)
// ==========================================
@media (min-width: 768px) {
    .control-panel {
        padding: 16px 24px;
    }

    .search-box {
        max-width: 400px;
    }

    .btn-primary-modern {
        width: auto;
        padding: 10px 18px;
        gap: 8px;
    }

    .collections-container {
        padding: 24px;
        max-width: 900px;
        margin: 0 auto;
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
