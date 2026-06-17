<template>
    <div class="partner-products-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ НАСТРОЕК (STICKY) -->
        <!-- ========================================== -->
        <div class="settings-panel">
            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Наценка</h4>
                        <p class="setting-description">
                            Процент наценки на товары партнера
                        </p>
                    </div>
                </div>
                <div class="charge-input-wrapper">
                    <input
                        type="number"
                        v-model.number="extra_charge"
                        class="charge-input"
                        min="0"
                        max="100"
                        placeholder="0"
                    >
                    <span class="charge-suffix">%</span>
                </div>
            </div>

            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Режим настройки</h4>
                        <p class="setting-description">
                            Настройка отображения товаров
                        </p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="need-product-config"
                        type="checkbox"
                        v-model="need_product_config"
                        class="switch-input"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="load_content && categories.length === 0" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка товаров...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК КАТЕГОРИЙ -->
        <!-- ========================================== -->
        <div v-if="!load_content || categories.length > 0" class="categories-container">

            <div v-if="categories.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3>Товары не найдены</h3>
                <p>У партнера пока нет товаров</p>
            </div>

            <div v-else class="categories-list">
                <div
                    v-for="category in categories"
                    :key="'cat-' + category.id"
                    class="category-card"
                >
                    <!-- Заголовок категории (collapsible) -->
                    <div
                        class="category-header"
                        @click="toggleCategory(category.id)"
                    >
                        <div class="category-icon">
                            <i class="fa-solid fa-folder"></i>
                        </div>
                        <div class="category-info">
                            <h4 class="category-title">{{ category.title }}</h4>
                            <div class="category-meta">
                                <span class="meta-count">
                                    <i class="fa-solid fa-cube"></i>
                                    {{ category.products.length }} из {{ category.products_count }} товаров
                                </span>
                            </div>
                        </div>
                        <div class="category-toggle">
                            <i class="fa-solid" :class="expandedCategories.includes(category.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </div>
                    </div>

                    <!-- Содержимое категории -->
                    <div v-show="expandedCategories.includes(category.id)" class="category-body">
                        <template v-if="category.products.length > 0">
                            <div class="products-list">
                                <div
                                    v-for="product in category.products"
                                    :key="'prod-' + product.id"
                                    class="product-item"
                                    :class="{ 'is-excluded': isProductExcluded(product.id) }"
                                >
                                    <div class="product-info">
                                        <h5 class="product-title">{{ product.title }}</h5>
                                        <div class="product-price">
                                            <template v-if="extra_charge === 0">
                                                <span class="price-current">{{ formatPrice(product.current_price) }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="price-original">{{ formatPrice(product.current_price) }}</span>
                                                <span class="price-with-charge">{{ formatPrice(calculatePriceWithCharge(product.current_price)) }}</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Переключатель отображения -->
                                    <div v-if="need_product_config" class="product-toggle">
                                        <div class="switch-control small">
                                            <input
                                                :id="'product-toggle-' + product.id"
                                                type="checkbox"
                                                :checked="!isProductExcluded(product.id)"
                                                class="switch-input"
                                                @change="changeStatus(product.id, isProductExcluded(product.id) ? 0 : 1)"
                                            >
                                            <span class="switch-slider"></span>
                                        </div>
                                        <label :for="'product-toggle-' + product.id" class="toggle-label">
                                            {{ isProductExcluded(product.id) ? 'Скрыт' : 'Показан' }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Кнопка "Загрузить еще" -->
                            <button
                                v-if="category.products_count > category.products.length"
                                class="btn-load-more"
                                @click="loadMore(category.id, category.products.length)"
                                :disabled="load_content"
                            >
                                <span v-if="!load_content">
                                    <i class="fa-solid fa-plus"></i>
                                    Загрузить еще ({{ category.products_count - category.products.length }})
                                </span>
                                <span v-else class="loading-text">
                                    <span class="spinner-small"></span>
                                    Загрузка...
                                </span>
                            </button>
                        </template>

                        <div v-else class="empty-category">
                            <i class="fa-solid fa-box"></i>
                            <p>В категории нет товаров</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapActions } from 'pinia'

export default {
    name: 'PartnerProductList',

    props: {
        partner: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            load_content: false,
            categories: [],
            extra_charge: 0,
            need_product_config: false,
            expandedCategories: [],
        }
    },

    computed: {
        excludes() {
            return this.partner.config?.excludes || []
        },
    },

    mounted() {
        this.extra_charge = this.partner.extra_charge || 0
        this.loadProducts()
    },

    methods: {
        ...mapActions('partners', [
            'loadProductsByCategory',
            'loadMoreProductsByCategory',
            'changePartnerProductStatus',
        ]),

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadProducts() {
            this.load_content = true
            try {
                const resp = await this.loadProductsByCategory({
                    partner_id: this.partner.bot_partner_id || null,
                })

                this.categories = resp.data || []

                // Разворачиваем первую категорию по умолчанию
                if (this.categories.length > 0) {
                    this.expandedCategories.push(this.categories[0].id)
                }
            } catch (err) {
                console.error('Ошибка загрузки товаров:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                })
            } finally {
                this.load_content = false
            }
        },

        async loadMore(catId, offset) {
            this.load_content = true
            try {
                const resp = await this.loadMoreProductsByCategory({
                    partner_id: this.partner?.bot_partner_id || null,
                    category_id: catId,
                    offset: offset,
                })

                const count = resp?.length || 0
                if (count === 0) {
                    const category = this.categories.find(c => c.id === catId)
                    if (category) {
                        category.products_count = offset
                    }
                    return
                }

                const category = this.categories.find(c => c.id === catId)
                if (category) {
                    category.products.push(...resp)
                }
            } catch (err) {
                console.error('Ошибка загрузки дополнительных товаров:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                })
            } finally {
                this.load_content = false
            }
        },

        // ==========================================
        // УПРАВЛЕНИЕ КАТЕГОРИЯМИ
        // ==========================================
        toggleCategory(catId) {
            const index = this.expandedCategories.indexOf(catId)
            if (index === -1) {
                this.expandedCategories.push(catId)
            } else {
                this.expandedCategories.splice(index, 1)
            }
        },

        // ==========================================
        // СТАТУС ТОВАРОВ
        // ==========================================
        isProductExcluded(productId) {
            return this.excludes.includes(productId)
        },

        async changeStatus(productId, status) {
            const excludes = this.partner.config?.excludes || []
            const index = excludes.indexOf(productId)

            // Оптимистичное обновление UI
            if (index === -1) {
                excludes.push(productId)
            } else {
                excludes.splice(index, 1)
            }

            if (!this.partner.config) {
                this.partner.config = { excludes: [] }
            }
            this.partner.config.excludes = excludes

            try {
                await this.changePartnerProductStatus({
                    product_id: productId,
                    partner_id: this.partner.id,
                    status: status,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: status === 0 ? 'Товар отображается' : 'Товар скрыт',
                    type: 'success',
                })
            } catch (err) {
                // Откат при ошибке
                if (index === -1) {
                    excludes.splice(excludes.indexOf(productId), 1)
                } else {
                    excludes.push(productId)
                }

                console.error('Ошибка изменения статуса:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус',
                    type: 'error',
                })
            }
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================
        calculatePriceWithCharge(price) {
            const basePrice = parseFloat(price) || 0
            const charge = parseFloat(this.extra_charge) || 0
            return basePrice + (basePrice * charge / 100)
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0)
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

.partner-products-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ПАНЕЛЬ НАСТРОЕК
// ==========================================
.settings-panel {
    position: sticky;
    top: 0;
    z-index: 100;
    background: $admin-bg;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-bottom: 1px solid $admin-border;
}

.setting-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
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
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 2px 0;
}

.setting-description {
    font-size: 0.75rem;
    color: $admin-text-muted;
    margin: 0;
}

.charge-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.charge-input {
    width: 70px;
    padding: 8px 28px 8px 12px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
    background: $admin-bg;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        background: $admin-card-bg;
    }
}

.charge-suffix {
    position: absolute;
    right: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
    pointer-events: none;
}

// ==========================================
// SWITCH
// ==========================================
.switch-control {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;

    &.small {
        width: 40px;
        height: 24px;

        .switch-slider::before {
            height: 18px;
            width: 18px;
        }

        .switch-input:checked + .switch-slider::before {
            transform: translateX(16px);
        }
    }
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-success;

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
    border: 2px solid rgba($admin-primary, 0.3);
    border-top-color: $admin-primary;
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
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
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

.category-toggle {
    color: $admin-text-muted;
    font-size: 0.9rem;
    transition: transform 0.3s;
}

.category-body {
    border-top: 1px solid $admin-border;
}

// ==========================================
// СПИСОК ТОВАРОВ
// ==========================================
.products-list {
    display: flex;
    flex-direction: column;
}

.product-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid $admin-border;
    transition: background 0.2s;

    &:last-child {
        border-bottom: none;
    }

    &.is-excluded {
        background: rgba($admin-warning, 0.08);
        opacity: 0.7;
    }
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-title {
    font-size: 0.9rem;
    font-weight: 500;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.price-current {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;
}

.price-original {
    font-size: 0.8rem;
    color: $admin-text-muted;
    text-decoration: line-through;
}

.price-with-charge {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-success;
}

.product-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.toggle-label {
    font-size: 0.75rem;
    color: $admin-text-muted;
    white-space: nowrap;
}

// ==========================================
// КНОПКА "ЗАГРУЗИТЬ ЕЩЕ"
// ==========================================
.btn-load-more {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: $admin-bg;
    border: none;
    border-top: 1px solid $admin-border;
    color: $admin-primary;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:active:not(:disabled) {
        background: rgba($admin-primary, 0.08);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .loading-text {
        display: flex;
        align-items: center;
        gap: 8px;
    }
}

// ==========================================
// ПУСТАЯ КАТЕГОРИЯ
// ==========================================
.empty-category {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 16px;
    color: $admin-text-muted;

    i {
        font-size: 1.5rem;
        opacity: 0.5;
    }

    p {
        font-size: 0.85rem;
        margin: 0;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .categories-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }

    .settings-panel {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px 24px;
    }
}
</style>
