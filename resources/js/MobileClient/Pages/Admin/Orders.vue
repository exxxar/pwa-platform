<template>
    <div class="orders-page">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <h2 class="header-title">Список заказов</h2>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ УПРАВЛЕНИЯ (STICKY) -->
        <!-- ========================================== -->
        <div class="control-panel">
            <!-- Поиск -->
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    v-model="search"
                    class="search-input"
                    placeholder="Поиск по заказам..."
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

            <!-- Табы -->
            <div class="tabs-container">
                <button
                    class="tab-btn"
                    :class="{ 'is-active': tab === 0 }"
                    @click="switchTab(0)"
                >
                    <i class="fa-solid fa-receipt"></i>
                    <span>Заказы</span>
                </button>
                <button
                    class="tab-btn"
                    :class="{ 'is-active': tab === 1 }"
                    @click="switchTab(1)"
                >
                    <i class="fa-solid fa-star"></i>
                    <span>Отзывы</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ВКЛАДКА: ЗАКАЗЫ -->
        <!-- ========================================== -->
        <div v-if="tab === 0" class="tab-content">

            <!-- Панель сортировки -->
            <div class="sort-panel">
                <div class="sort-controls">
                    <select v-model="sort.param" @change="reloadOrders" class="select-modern">
                        <option value="id">По номеру</option>
                        <option value="is_cashback_crediting">По CashBack</option>
                        <option value="summary_price">По цене</option>
                        <option value="product_count">По кол-ву товаров</option>
                        <option value="updated_at">По дате</option>
                    </select>
                    <button class="sort-direction-btn" @click="toggleSortDirection">
                        <i class="fa-solid" :class="sort.direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                    </button>
                </div>
            </div>

            <!-- Индикатор загрузки -->
            <div v-if="isLoading" class="loading-overlay">
                <div class="loading-spinner"></div>
                <p>Загрузка заказов...</p>
            </div>

            <!-- Список заказов -->
            <div v-else class="orders-container">
                <div v-if="orders && orders.length > 0" class="orders-list">
                    <OrderItem
                        v-for="item in orders"
                        :key="'order-' + item.id"
                        :item="item"
                    />
                </div>

                <!-- Пустое состояние -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h3>Заказов пока нет</h3>
                    <p>{{ search ? 'Попробуйте изменить поисковый запрос' : 'Заказы появятся здесь после оформления' }}</p>
                </div>
            </div>

            <!-- Пагинация -->
            <div v-if="orders_paginate_object && orders_paginate_object.last_page > 1" class="pagination-wrapper">
                <Pagination
                    :simple="true"
                    @pagination_page="nextOrders"
                    :pagination="orders_paginate_object"
                />
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ВКЛАДКА: ОТЗЫВЫ -->
        <!-- ========================================== -->
        <div v-if="tab === 1" class="tab-content">

            <!-- Индикатор загрузки -->
            <div v-if="isLoading" class="loading-overlay">
                <div class="loading-spinner"></div>
                <p>Загрузка отзывов...</p>
            </div>

            <!-- Список отзывов -->
            <div v-else class="reviews-container">
                <div v-if="reviews.length > 0" class="reviews-list">
                    <div
                        v-for="(review, index) in reviews"
                        :key="'review-' + index"
                        class="review-item"
                    >
                        <ReviewCard
                            :is-admin="true"
                            :need-product="true"
                            v-model="reviews[index]"
                        />
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-comment-slash"></i>
                    </div>
                    <h3>Отзывов пока нет</h3>
                    <p>Отзывы появятся здесь после их добавления</p>
                </div>
            </div>

            <!-- Пагинация -->
            <div v-if="reviews_paginate_object && reviews_paginate_object.last_page > 1" class="pagination-wrapper">
                <Pagination
                    :simple="true"
                    @pagination_page="nextReviews"
                    :pagination="reviews_paginate_object"
                />
            </div>
        </div>

    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import Pagination from '@/MobileClient/Components/Pagination.vue'
import ReviewCard from '@/MobileClient/Components/Shop/Reviews/ReviewCard.vue'
import OrderItem from '@/MobileClient/Components/Admin/Orders/OrderItem.vue'

export default {
    name: 'OrdersList',

    components: {
        Pagination,
        ReviewCard,
        OrderItem,
    },

    props: {
        botUser: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            isLoading: false,
            search: '',
            searchDebounce: null,
            tab: 0,
            sort: {
                param: 'id',
                direction: 'desc',
            },
        }
    },

    computed: {
        ...mapState('orders', [
            'orders',
            'orders_paginate_object',
            'reviews',
            'reviews_paginate_object',
        ]),
    },

    watch: {
        search() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => this.reloadOrders(), 300)
        },
    },

    mounted() {
        this.loadOrdersPage(0)
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce)
    },

    methods: {
        ...mapActions('orders', [
            'loadAllOrders',
            'loadReviews',
        ]),

        // ==========================================
        // ПЕРЕКЛЮЧЕНИЕ ТАБОВ
        // ==========================================
        switchTab(newTab) {
            this.tab = newTab
            if (newTab === 1 && (!this.reviews || this.reviews.length === 0)) {
                this.loadReviewsPage(0)
            }
        },

        // ==========================================
        // ЗАКАЗЫ
        // ==========================================
        reloadOrders() {
            this.loadOrdersPage(0)
        },

        async loadOrdersPage(page = 0) {
            this.isLoading = true
            try {
                await this.loadAllOrders({
                    dataObject: {
                        search: this.search || null,
                        order_by: this.sort.param || null,
                        direction: this.sort.direction || 'desc',
                        bot_user_id: this.botUser ? this.botUser.id : null,
                    },
                    page,
                    size: 20,
                })
            } catch (err) {
                console.error('Ошибка загрузки заказов:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить заказы',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        nextOrders(index) {
            this.loadOrdersPage(index)
        },

        // ==========================================
        // ОТЗЫВЫ
        // ==========================================
        async loadReviewsPage(page = 0) {
            this.isLoading = true
            try {
                await this.loadReviews({
                    dataObject: {
                        bot_user_id: this.botUser ? this.botUser.id : null,
                    },
                    page: page || 0,
                    size: 20,
                })
            } catch (err) {
                console.error('Ошибка загрузки отзывов:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить отзывы',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        nextReviews(index) {
            this.loadReviewsPage(index)
        },

        // ==========================================
        // СОРТИРОВКА
        // ==========================================
        toggleSortDirection() {
            this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
            this.reloadOrders()
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

.orders-page {
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
// ПАНЕЛЬ УПРАВЛЕНИЯ (STICKY)
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

// ==========================================
// ТАБЫ
// ==========================================
.tabs-container {
    display: flex;
    gap: 8px;
    background: $admin-bg;
    padding: 4px;
    border-radius: 10px;
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active {
        transform: scale(0.98);
    }

    &.is-active {
        background: $admin-card-bg;
        color: $admin-primary;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
}

// ==========================================
// ПАНЕЛЬ СОРТИРОВКИ
// ==========================================
.sort-panel {
    padding: 12px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
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

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// КОНТЕЙНЕРЫ
// ==========================================
.orders-container,
.reviews-container {
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
// СПИСКИ
// ==========================================
.orders-list,
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.review-item {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
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
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .page-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .control-panel {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px 24px;
    }

    .sort-panel {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px 24px;
    }

    .orders-container,
    .reviews-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }
}
</style>
