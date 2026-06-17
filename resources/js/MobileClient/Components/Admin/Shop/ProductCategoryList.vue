<template>
    <div class="category-list-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
        <!-- ========================================== -->
        <div class="control-panel">


            <button class="btn-icon" :class="{ 'is-active': showFilters }" @click="showFilters = !showFilters">
                <i class="fa-solid fa-sliders"></i>
            </button>

            <button class="btn-primary-modern" @click="openAddModal">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ ФИЛЬТРОВ (СКРЫВАЕМАЯ) -->
        <!-- ========================================== -->
        <div v-if="showFilters" class="filters-bar mobile-optimized">
            <div class="filter-section">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        v-model="search"
                        class="search-input"
                        placeholder="Поиск категории..."
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
                <label class="filter-label">Сортировка</label>
                <div class="sort-controls">
                    <select v-model="sort.param" @change="reloadCategories" class="select-modern">
                        <option value="id">По ID</option>
                        <option value="title">По названию</option>
                        <option value="order_position">По позиции выдачи</option>
                        <option value="is_active">По статусу</option>
                        <option value="updated_at">По дате</option>
                    </select>
                    <button class="sort-direction-btn" @click="toggleSortDirection">
                        <i class="fa-solid" :class="sort.direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                    </button>
                </div>
            </div>

            <div class="filter-section">
                <label class="filter-label">Режим</label>
                <button
                    class="toggle-btn"
                    :class="{ 'is-active': need_recommendation_config }"
                    @click="need_recommendation_config = !need_recommendation_config"
                >
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Настройка рекомендаций</span>
                </button>
            </div>

            <div class="filter-stats">
                <span class="stats-badge">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>{{ totalCategories }}</span>
                </span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- РЕЖИМ РЕКОМЕНДАЦИЙ -->
        <!-- ========================================== -->
        <div v-if="need_recommendation_config" class="recommendation-bar">
            <div class="reco-icon">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
            <div class="reco-info">
                <strong>Режим рекомендаций</strong>
                <span>Выберите статус для каждой категории</span>
            </div>
            <button class="btn-text" @click="need_recommendation_config = false">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка категорий...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК КАТЕГОРИЙ -->
        <!-- ========================================== -->
        <div v-if="!isLoading" class="categories-container">

            <div v-if="categories.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <h3>Категории не найдены</h3>
                <p>{{ search ? 'Измените поисковый запрос' : 'Добавьте первую категорию' }}</p>
                <button v-if="!search" class="btn-primary-modern w-100" @click="openAddModal">
                    <i class="fa-solid fa-plus"></i> Добавить категорию
                </button>
            </div>

            <div v-else class="categories-list">
                <div
                    v-for="category in categories"
                    :key="'cat-' + category.id"
                    class="category-card"
                    :class="{ 'is-inactive': !category.is_active }"
                >
                    <div class="category-header" @click="openEditModal(category)">
                        <div class="category-icon">
                            <i class="fa-solid fa-folder"></i>
                        </div>
                        <div class="category-info">
                            <h4 class="category-title">{{ category.title }}</h4>
                            <div class="category-meta">
                                <span class="meta-id">ID: {{ category.id }}</span>
                                <span v-if="category.products_count" class="meta-count">
                                    <i class="fa-solid fa-cube"></i>
                                    {{ category.products_count }}
                                </span>
                            </div>
                        </div>
                        <div class="category-status">
                            <span class="status-pill" :class="category.is_active ? 'active' : 'inactive'">
                                <i class="fa-solid" :class="category.is_active ? 'fa-eye' : 'fa-eye-slash'"></i>
                            </span>
                        </div>
                    </div>

                    <div class="category-controls">
                        <div class="position-control">
                            <label class="position-label">
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Позиция
                            </label>
                            <input
                                type="number"
                                class="position-input"
                                v-model.number="category.order_position"
                                @change="updateCategory(category)"
                                min="0"
                            >
                        </div>

                        <div class="category-actions">
                            <button
                                class="action-btn-small"
                                :class="category.is_active ? 'success' : 'warning'"
                                @click="changeCategoryStatus(category)"
                                :title="category.is_active ? 'Скрыть' : 'Показать'"
                            >
                                <i class="fa-solid" :class="category.is_active ? 'fa-eye' : 'fa-eye-slash'"></i>
                            </button>
                            <button
                                class="action-btn-small"
                                @click="openEditModal(category)"
                                title="Редактировать"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button
                                class="action-btn-small danger"
                                @click="openRemoveModal(category)"
                                title="Удалить"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Режим рекомендаций -->
                    <div v-if="need_recommendation_config" class="category-recommendation">
                        <div class="reco-buttons">
                            <button
                                class="reco-btn"
                                :class="{ 'active': !isCategoryRecommended(category.id) }"
                                @click="changeRecommendationStatus(category.id, 0)"
                            >
                                <i class="fa-solid fa-minus"></i>
                                <span>Обычная</span>
                            </button>
                            <button
                                class="reco-btn recommend"
                                :class="{ 'active': isCategoryRecommended(category.id) }"
                                @click="changeRecommendationStatus(category.id, 1)"
                            >
                                <i class="fa-solid fa-star"></i>
                                <span>Рекомендация</span>
                            </button>
                        </div>
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
                @pagination_page="nextCategories"
                :pagination="paginate"
            />
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: РЕДАКТИРОВАНИЕ КАТЕГОРИИ -->
        <!-- ========================================== -->
        <div v-if="showEditModal" class="modal-overlay mobile-fullscreen" @click.self="hideEditModal">
            <div class="modal-container edit-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="hideEditModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Редактирование категории</h3>
                </div>
                <div class="modal-body">
                    <CategoryForm
                        v-if="selectedCategory"
                        :item="selectedCategory"
                        @callback="onCategorySaved"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ДОБАВЛЕНИЕ КАТЕГОРИИ -->
        <!-- ========================================== -->
        <div v-if="showAddModal" class="modal-overlay mobile-fullscreen" @click.self="showAddModal = false">
            <div class="modal-container add-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="showAddModal = false">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>Новая категория</h3>
                </div>
                <div class="modal-body">
                    <CategoryForm
                        @callback="onCategoryAdded"
                        @cancel="showAddModal = false"
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
                <h4>Удалить категорию?</h4>
                <p>
                    Категория <strong>«{{ selectedCategory?.title }}»</strong> будет удалена.
                    Это действие нельзя отменить.
                </p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="hideRemoveModal">Отмена</button>
                    <button class="btn-primary-modern danger" @click="removeCategory" :disabled="isLoading">
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
import CategoryForm from '@/MobileClient/Components/Admin/Shop/CategoryForm.vue'

export default {
    name: 'ProductCategoryList',

    components: {
        Pagination,
        CategoryForm,
    },

    data() {
        return {
            isLoading: false,
            search: '',
            searchDebounce: null,
            showFilters: false,
            need_recommendation_config: false,

            sort: {
                param: 'id',
                direction: 'asc',
            },

            selectedCategory: null,
            showEditModal: false,
            showAddModal: false,
            showRemoveModal: false,
        }
    },

    computed: {
        ...mapState(useProductsStore, [
            'categories',
            'categories_paginate_object',
        ]),

        tenant() {
            return window.Tenant || null
        },

        totalCategories() {
            return this.categories_paginate_object?.total || this.categories?.length || 0
        },

        paginate() {
            return this.categories_paginate_object
        },

        recommendations() {
            return this.tenant?.settings?.recommendation?.categories || []
        },
    },

    watch: {
        search() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => this.reloadCategories(), 300)
        },
    },

    mounted() {
        this.loadCategoriesPage(0)
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce)
    },

    methods: {
        ...mapActions(useProductsStore, [
            'loadCategories',
            'storeProductCategory',
            'removeProductCategory',
            'changeProductCategoryStatus',
            'changeCategoryRecommendationStatus',
        ]),

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        reloadCategories() {
            this.loadCategoriesPage(0)
        },

        async loadCategoriesPage(page = 0) {
            this.isLoading = true
            try {
                await this.loadCategories({
                    dataObject: {
                        tenant_id: this.tenant?.id,
                        search: this.search,
                        order_by: this.sort.param,
                        direction: this.sort.direction,
                    },
                    page,
                    size: 50,
                })
            } catch (err) {
                console.error('Ошибка загрузки категорий:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить категории',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        nextCategories(index) {
            this.loadCategoriesPage(index)
        },

        // ==========================================
        // СОРТИРОВКА
        // ==========================================
        toggleSortDirection() {
            this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
            this.reloadCategories()
        },

        // ==========================================
        // РЕКОМЕНДАЦИИ
        // ==========================================
        isCategoryRecommended(categoryId) {
            return this.recommendations.includes(categoryId)
        },

        async changeRecommendationStatus(categoryId, status) {
            try {
                await this.changeCategoryRecommendationStatus({
                    category_id: categoryId,
                    status,
                })
                this.$notify?.({
                    title: 'Успех',
                    text: 'Статус рекомендации обновлён',
                    type: 'success',
                })
            } catch (err) {
                console.error('Ошибка изменения статуса рекомендации:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось обновить статус',
                    type: 'error',
                })
            }
        },

        // ==========================================
        // ДЕЙСТВИЯ С КАТЕГОРИЯМИ
        // ==========================================
        async updateCategory(item) {
            try {
                await this.storeProductCategory({
                    category: item,
                    tenant_id: this.tenant?.id,
                })
                this.$notify?.({
                    title: 'Успех',
                    text: 'Позиция сохранена',
                    type: 'success',
                })
            } catch (err) {
                console.error('Ошибка сохранения позиции:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить позицию',
                    type: 'error',
                })
                this.loadCategoriesPage(0)
            }
        },

        async changeCategoryStatus(item) {
            const index = this.categories.findIndex(c => c.id === item.id)
            if (index === -1) return

            // Оптимистичное обновление UI
            this.categories[index].is_active = !this.categories[index].is_active
            const wasActive = !this.categories[index].is_active

            try {
                await this.changeProductCategoryStatus(item.id)
                this.$notify?.({
                    title: 'Успех',
                    text: wasActive ? 'Категория скрыта' : 'Категория показана',
                    type: 'success',
                })
            } catch (err) {
                // Откат при ошибке
                this.categories[index].is_active = wasActive
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
        openEditModal(category) {
            this.selectedCategory = { ...category }
            this.showEditModal = true
        },

        hideEditModal() {
            this.showEditModal = false
            this.selectedCategory = null
        },

        openAddModal() {
            this.showAddModal = true
        },

        openRemoveModal(category) {
            this.selectedCategory = category
            this.showRemoveModal = true
        },

        hideRemoveModal() {
            this.showRemoveModal = false
            this.selectedCategory = null
        },

        async removeCategory() {
            if (!this.selectedCategory) return
            this.isLoading = true

            try {
                await this.removeProductCategory({ category_id: this.selectedCategory.id })
                this.$notify?.({
                    title: 'Успех',
                    text: 'Категория удалена',
                    type: 'success',
                })
                this.hideRemoveModal()
                this.loadCategoriesPage(0)
            } catch (err) {
                console.error('Ошибка удаления категории:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить категорию',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        onCategorySaved() {
            this.hideEditModal()
            this.loadCategoriesPage(0)
            this.$notify?.({
                title: 'Успех',
                text: 'Категория сохранена',
                type: 'success',
            })
        },

        onCategoryAdded() {
            this.showAddModal = false
            this.loadCategoriesPage(0)
            this.$notify?.({
                title: 'Успех',
                text: 'Категория добавлена',
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

.category-list-page {
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

.toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;
    width: 100%;

    &.is-active {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        border-color: #7c3aed;
        color: white;
    }

    &:active {
        transform: scale(0.98);
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
// РЕЖИМ РЕКОМЕНДАЦИЙ
// ==========================================
.recommendation-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(#8b5cf6, 0.08) 0%, rgba(#7c3aed, 0.08) 100%);
    border-bottom: 1px solid rgba(#8b5cf6, 0.2);
}

.reco-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.reco-info {
    flex: 1;
    display: flex;
    flex-direction: column;

    strong {
        font-size: 0.85rem;
        color: $admin-text;
    }

    span {
        font-size: 0.75rem;
        color: $admin-text-muted;
    }
}

.btn-text {
    background: none;
    border: none;
    color: $admin-text-muted;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;

    &:active {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
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
// КОНТЕЙНЕР КАТЕГОРИЙ
// ==========================================
.categories-container {
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
// СПИСОК КАТЕГОРИЙ
// ==========================================
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-inactive {
        opacity: 0.6;
    }
}

.category-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    cursor: pointer;
    transition: background 0.2s;

    &:active {
        background: rgba($admin-primary, 0.04);
    }
}

.category-icon {
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

.category-info {
    flex: 1;
    min-width: 0;
}

.category-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-meta {
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

.category-status {
    flex-shrink: 0;
}

.status-pill {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;

    &.active {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.inactive {
        background: rgba($admin-text-muted, 0.1);
        color: $admin-text-muted;
    }
}

.category-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 14px 14px;
}

.position-control {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: $admin-bg;
    border-radius: 8px;
}

.position-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $admin-text-muted;
    white-space: nowrap;
}

.position-input {
    width: 60px;
    padding: 6px 8px;
    border: 1px solid $admin-border;
    border-radius: 6px;
    font-size: 0.9rem;
    text-align: center;
    background: $admin-card-bg;

    &:focus {
        outline: none;
        border-color: $admin-primary;
    }
}

.category-actions {
    display: flex;
    gap: 6px;
}

.action-btn-small {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;

    &:active {
        transform: scale(0.9);
    }

    &.success:active {
        background: $admin-success;
        border-color: $admin-success;
        color: white;
    }

    &.warning:active {
        background: $admin-warning;
        border-color: $admin-warning;
        color: white;
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }
}

// ==========================================
// РЕКОМЕНДАЦИИ В КАРТОЧКЕ
// ==========================================
.category-recommendation {
    padding: 12px 14px;
    background: $admin-bg;
    border-top: 1px solid $admin-border;
}

.reco-buttons {
    display: flex;
    gap: 8px;
}

.reco-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.85rem;
    min-height: 44px;

    span {
        font-weight: 500;
    }

    &.active {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }

    &.recommend.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        border-color: #7c3aed;
    }

    &:active {
        transform: scale(0.98);
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

.edit-modal, .add-modal {
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

    .categories-container {
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
