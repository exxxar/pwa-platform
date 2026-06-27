<template>
    <div class="partner-products-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ НАСТРОЕК -->
        <!-- ========================================== -->
        <div class="settings-panel">
            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon charge">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Наценка</h4>
                        <p class="setting-description">Процент наценки на товары партнёра</p>
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
                        @change="saveExtraCharge"
                    >
                    <span class="charge-suffix">%</span>
                </div>
            </div>

            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon config">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Режим настройки</h4>
                        <p class="setting-description">Управление видимостью товаров</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="need-product-config"
                        type="checkbox"
                        v-model="need_product_config"
                        class="switch-input"
                    >
                    <label for="need-product-config" class="switch-slider"></label>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СТАТИСТИКА -->
        <!-- ========================================== -->
        <div v-if="categories.length > 0" class="stats-bar">
            <div class="stat-item">
                <i class="fa-solid fa-folder"></i>
                <span>{{ categories.length }} категорий</span>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-cube"></i>
                <span>{{ totalProductsCount }} товаров</span>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-eye-slash"></i>
                <span>{{ excludedCount }} скрыто</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА (SKELETON) -->
        <!-- ========================================== -->
        <div v-if="load_content && categories.length === 0" class="skeleton-list">
            <div v-for="i in 3" :key="i" class="skeleton-card">
                <div class="skeleton-icon shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-title shimmer"></div>
                    <div class="skeleton-meta shimmer"></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК КАТЕГОРИЙ -->
        <!-- ========================================== -->
        <div v-else-if="categories.length > 0" class="categories-container">
            <div
                v-for="category in categories"
                :key="'cat-' + category.id"
                class="category-card"
                :class="{ 'is-expanded': expandedCategories.includes(category.id) }"
            >
                <!-- Заголовок категории -->
                <div class="category-header" @click="toggleCategory(category.id)">
                    <div class="category-icon">
                        <i class="fa-solid fa-folder"></i>
                    </div>
                    <div class="category-info">
                        <h4 class="category-title">{{ category.title }}</h4>
                        <div class="category-meta">
                            <span class="meta-count">
                                <i class="fa-solid fa-cube"></i>
                                {{ category.products.length }} из {{ category.products_count }}
                            </span>
                            <span v-if="getCategoryExcludedCount(category) > 0" class="meta-excluded">
                                <i class="fa-solid fa-eye-slash"></i>
                                {{ getCategoryExcludedCount(category) }} скрыто
                            </span>
                        </div>
                    </div>
                    <div class="category-toggle">
                        <i class="fa-solid" :class="expandedCategories.includes(category.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </div>

                <!-- Содержимое категории -->
                <transition name="expand">
                    <div v-show="expandedCategories.includes(category.id)" class="category-body">
                        <template v-if="category.products.length > 0">
                            <div class="products-list">
                                <div
                                    v-for="product in category.products"
                                    :key="'prod-' + product.id"
                                    class="product-item"
                                    :class="{ 'is-excluded': isProductExcluded(product.id) }"
                                >
                                    <div class="product-icon">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div class="product-info">
                                        <h5 class="product-title">{{ product.title }}</h5>
                                        <div class="product-price">
                                            <template v-if="extra_charge === 0">
                                                <span class="price-current">{{ formatPrice(product.current_price) }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="price-original">{{ formatPrice(product.current_price) }}</span>
                                                <span class="price-with-charge">{{ formatPrice(calculatePriceWithCharge(product.current_price)) }}</span>
                                                <span class="price-badge">+{{ extra_charge }}%</span>
                                            </template>
                                        </div>

                                        <!-- Переключатель отображения -->
                                        <div v-if="need_product_config" class="product-toggle mt-2">
                                            <div class="switch-control small">
                                                <input
                                                    :id="'product-toggle-' + product.id"
                                                    type="checkbox"
                                                    :checked="!isProductExcluded(product.id)"
                                                    class="switch-input"
                                                    @change="changeStatus(product.id, isProductExcluded(product.id) ? 0 : 1)"
                                                >
                                                <label :for="'product-toggle-' + product.id" class="switch-slider"></label>
                                            </div>
                                            <label :for="'product-toggle-' + product.id" class="toggle-label">
                                                {{ isProductExcluded(product.id) ? 'Не отображается' : 'Отображается в списке' }}
                                            </label>
                                        </div>
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
                                    Загрузить ещё ({{ category.products_count - category.products.length }})
                                </span>
                                <span v-else class="loading-text">
                                    <span class="spinner-small"></span>
                                    Загрузка...
                                </span>
                            </button>
                        </template>

                        <div v-else class="empty-category">
                            <i class="fa-solid fa-box-open"></i>
                            <p>В категории нет товаров</p>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-else-if="!load_content" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3>Товары не найдены</h3>
            <p>У партнёра пока нет товаров</p>
        </div>

    </div>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'PartnerProductList',

    props: {
        partner: {
            type: Object,
            required: true,
        },
    },

    setup() {
        const partners = usePartners()
        return {
            // 🆕 Явно деструктурируем нужные методы
            loadProductsByCategory: partners.loadProductsByCategory,
            loadMoreProductsByCategory: partners.loadMoreProductsByCategory,
            changePartnerProductStatus: partners.changePartnerProductStatus,
        }
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

        totalProductsCount() {
            return this.categories.reduce((sum, cat) => sum + (cat.products_count || 0), 0)
        },

        excludedCount() {
            return this.excludes.length
        },
    },

    mounted() {
        this.extra_charge = this.partner.extra_charge || 0
        this.loadProducts()
    },

    methods: {
        async loadProducts() {
            this.load_content = true
            try {
                const resp = await this.loadProductsByCategory({
                    partner_id: this.partner.id || null,
                })
                this.categories = resp.data || []

                console.log("data", this.categories)

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
                console.error('Ошибка загрузки:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                })
            } finally {
                this.load_content = false
            }
        },

        toggleCategory(catId) {
            const index = this.expandedCategories.indexOf(catId)
            if (index === -1) {
                this.expandedCategories.push(catId)
            } else {
                this.expandedCategories.splice(index, 1)
            }
        },

        isProductExcluded(productId) {
            return this.excludes.includes(productId)
        },

        getCategoryExcludedCount(category) {
            return category.products.filter(p => this.isProductExcluded(p.id)).length
        },

        async changeStatus(productId, status) {
            const excludes = this.partner.config?.excludes || []
            const index = excludes.indexOf(productId)

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

        async saveExtraCharge() {
            // TODO: Реализовать сохранение наценки через API
            console.log('Save extra charge:', this.extra_charge)
        },

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
    position: relative;
    top: 0;
    z-index: 100;
    background: $admin-bg;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-bottom: 1px solid $admin-border;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.charge {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.config {
        background: rgba($admin-primary, 0.1);
        color: $admin-primary;
    }
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
    width: 72px;
    padding: 8px 28px 8px 12px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
    background: $admin-bg;

    &:focus {
        outline: none;
        border-color: $admin-success;
        background: $admin-card-bg;
        box-shadow: 0 0 0 3px rgba($admin-success, 0.1);
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
// СТАТИСТИКА
// ==========================================
.stats-bar {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: $admin-bg;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $admin-text;
    white-space: nowrap;

    i {
        color: $admin-primary;
        font-size: 0.85rem;
    }
}

// ==========================================
// SKELETON
// ==========================================
.skeleton-list {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.skeleton-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: $admin-bg;
    flex-shrink: 0;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-title {
    height: 16px;
    background: $admin-bg;
    border-radius: 4px;
    width: 60%;
}

.skeleton-meta {
    height: 12px;
    background: $admin-bg;
    border-radius: 4px;
    width: 40%;
}

.shimmer {
    background: linear-gradient(90deg, $admin-bg 0%, darken($admin-bg, 3%) 50%, $admin-bg 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// КАТЕГОРИИ
// ==========================================
.categories-container {
    padding: 16px;
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

    &.is-expanded {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.category-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    cursor: pointer;
    transition: background 0.2s;

    &:hover {
        background: rgba($admin-primary, 0.02);
    }

    &:active {
        background: rgba($admin-primary, 0.04);
    }
}

.category-icon {
    width: 42px;
    height: 42px;
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

.meta-count, .meta-excluded {
    display: flex;
    align-items: center;
    gap: 4px;
}

.meta-excluded {
    color: $admin-warning;
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
// ТОВАРЫ
// ==========================================
.products-list {
    display: flex;
    flex-direction: column;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid $admin-border;
    transition: background 0.2s;

    &:last-child {
        border-bottom: none;
    }

    &:hover {
        background: rgba($admin-primary, 0.02);
    }

    &.is-excluded {
        background: rgba($admin-warning, 0.05);
        opacity: 0.7;
    }
}

.product-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    display: flex;
    align-items: center;
    justify-content: center;
    color: $admin-text-muted;
    font-size: 0.9rem;
    flex-shrink: 0;
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
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
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

.price-badge {
    padding: 2px 6px;
    background: rgba($admin-success, 0.1);
    color: $admin-success;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
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
// КНОПКИ И СОСТОЯНИЯ
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

    &:hover:not(:disabled) {
        background: rgba($admin-primary, 0.05);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba($admin-primary, 0.1);
        color: $admin-primary;
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
    }
}

// ==========================================
// SWITCH И АНИМАЦИИ
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
    inset: 0;
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

.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
}

.expand-enter-to,
.expand-leave-from {
    opacity: 1;
    max-height: 2000px;
}

.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba($admin-primary, 0.3);
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (min-width: 768px) {
    .categories-container,
    .settings-panel,
    .stats-bar {
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }
}
</style>
