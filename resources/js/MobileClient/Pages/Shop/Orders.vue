<template>
    <div class="orders-page pb-5">

        <!-- ===== HERO СЕКЦИЯ ===== -->
        <div class="orders-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h2 class="hero-title">Мои заказы</h2>
                <p class="hero-subtitle">
                    {{ orders.length > 0
                    ? `Всего заказов: ${totalOrdersCount}`
                    : 'История ваших покупок' }}
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ===== ТАБЫ ===== -->
            <div class="tabs-wrapper">
                <div class="tabs-container">
                    <button
                        class="tab-item"
                        :class="{ active: tab === 0 }"
                        @click="switchTab(0)"
                    >
                        <i class="fa-solid fa-receipt"></i>
                        <span>Заказы</span>
                        <span v-if="orders.length" class="tab-badge">{{ orders.length }}</span>
                    </button>
                    <button
                        class="tab-item"
                        :class="{ active: tab === 1 }"
                        @click="switchTab(1)"
                    >
                        <i class="fa-solid fa-star"></i>
                        <span>Отзывы</span>
                        <span v-if="reviews.length" class="tab-badge">{{ reviews.length }}</span>
                    </button>
                </div>
            </div>

            <!-- ===== ИНФО-БЛОК ===== -->
            <div v-if="tab === 0" class="info-banner">
                <div class="info-icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="info-text">
                    <strong>Повторный заказ</strong> — товары из стоп-листа заведения будут автоматически исключены
                </div>
            </div>

            <!-- ===== ТАБ: ЗАКАЗЫ ===== -->
            <div v-if="tab === 0">

                <!-- Загрузка -->
                <div v-if="isLoading" class="skeleton-list">
                    <div v-for="i in 3" :key="i" class="skeleton-card">
                        <div class="skeleton-line w-60"></div>
                        <div class="skeleton-line w-40"></div>
                        <div class="skeleton-line w-80"></div>
                        <div class="skeleton-line w-30"></div>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else-if="orders.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <h5 class="empty-title">Заказов пока нет</h5>
                    <p class="empty-text">
                        Оформите первый заказ, и он появится здесь
                    </p>
                    <button class="empty-btn" @click="goToCatalog">
                        <i class="fa-solid fa-store me-2"></i>
                        Перейти в каталог
                    </button>
                </div>

                <!-- Список заказов с группировкой -->
                <div v-else class="orders-list">
                    <div
                        v-for="(group, dateKey) in groupedOrders"
                        :key="dateKey"
                        class="orders-group"
                    >
                        <!-- Заголовок группы (дата) -->
                        <div class="group-header">
                            <div class="group-date">{{ group.label }}</div>
                            <div class="group-count">
                                {{ group.orders.length }}
                                {{ pluralize(group.orders.length, 'заказ', 'заказа', 'заказов') }}
                            </div>
                        </div>


                        <!-- Карточки заказов -->
                        <div
                            v-for="order in group.orders"
                            :key="order.id"
                            class="order-card"
                            @click="select(order)"
                        >

                            <!-- Шапка заказа -->
                            <div class="order-header">
                                <div class="order-number">
                                    <span class="order-number-label">Заказ</span>
                                    <span class="order-number-value">#{{ order.id }}</span>
                                </div>
                                <div class="order-status" :class="getStatusClass(order)">
                                    {{ getStatusText(order) }}
                                </div>
                            </div>


                            <!-- Дата и время -->
                            <div class="order-meta">
                                <i class="fa-solid fa-clock"></i>
                                <span>{{ formatDateTime(order.created_at) }}</span>
                            </div>


                            <!-- Товары -->
                            <div class="order-products" v-if="getOrderProducts(order).length>0">
                                <div
                                    v-for="(product, i) in getOrderProducts(order).slice(0, 5)"
                                    :key="product.id || product.external_id || i"
                                    class="product-item"
                                >
                                    <span class="product-qty">{{ getProductQty(product) }}×</span>
                                    <span class="product-name">{{ getProductName(product) }}</span>
                                    <span v-if="product.price" class="product-price">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                </div>
                                <div
                                    v-if="getOrderProducts(order).length > 5"
                                    class="product-more"
                                >
                                    + ещё {{ getOrderProducts(order).length - 5 }}
                                    {{ pluralize(getOrderProducts(order).length - 5, 'товар', 'товара', 'товаров') }}
                                </div>
                            </div>
                            <p class="alert alert-light" v-else>Детали заказа отсутствуют</p>
                            <!-- Итого -->
                            <div v-if="getOrderTotal(order)" class="order-total">
                                <span>Итого:</span>
                                <span class="total-value">{{ formatPrice(getOrderTotal(order)) }}</span>
                            </div>

                            <!-- Отзыв -->
                            <ReviewCard
                                v-if="order.review"
                                v-model="order.review"
                                class="order-review"
                            />

                            <!-- Действия -->
                            <div class="order-actions" v-if="getOrderProducts(order).length>0">

                                <button
                                    type="button"
                                    class="repeat-btn"
                                    :class="{ disabled: order.disabled }"
                                    :disabled="order.disabled"
                                    @click.stop="repeatOrderHandler(order)"
                                >
                                    <i class="fa-solid fa-arrow-rotate-right"></i>
                                    <span>Повторить заказ</span>
                                </button>
                            </div>

                            <!-- Кнопка отзыва -->
                            <div v-if="canShowReviewButton(order)" class="review-actions">
                                <button
                                    v-if="!order.review && canReviewOrder(order)"
                                    type="button"
                                    class="review-btn"
                                    @click.stop="openReviewForm(order)"
                                >
                                    <i class="fa-solid fa-star"></i>
                                    <span>Оставить отзыв</span>
                                </button>

                                <div v-else-if="order.review" class="review-existing">
                                    <div class="review-badge">
                                        <i class="fa-solid fa-check-circle"></i>
                                        <span>Отзыв оставлен</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="review-edit-btn"
                                        @click.stop="openReviewForm(order, order.review)"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Пагинация -->
                    <Pagination
                        v-if="orders_paginate_object"
                        :simple="true"
                        :pagination="orders_paginate_object"
                        @pagination_page="loadOrdersHandler"
                        class="mt-3"
                    />
                </div>
            </div>

            <!-- ===== ТАБ: ОТЗЫВЫ ===== -->
            <div v-if="tab === 1">

                <!-- Загрузка -->
                <div v-if="isLoadingReviews" class="skeleton-list">
                    <div v-for="i in 3" :key="i" class="skeleton-card">
                        <div class="skeleton-line w-40"></div>
                        <div class="skeleton-line w-80"></div>
                        <div class="skeleton-line w-60"></div>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else-if="reviews.length === 0" class="empty-state">
                    <div class="empty-icon reviews-icon">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <h5 class="empty-title">Отзывов пока нет</h5>
                    <p class="empty-text">
                        Поделитесь мнением о товарах — оставьте первый отзыв
                    </p>
                </div>

                <!-- Список отзывов -->
                <div v-else class="reviews-list">
                    <template v-for="(review, index) in reviews" :key="review.id || index">
                        <ReviewCard
                            v-model="reviews[index]"
                            :need-product="true"
                        />
                    </template>

                    <!-- Пагинация -->
                    <Pagination
                        v-if="reviews_paginate_object"
                        :simple="true"
                        :pagination="reviews_paginate_object"
                        @pagination_page="loadReviewsHandler"
                        class="mt-3"
                    />
                </div>
            </div>

        </div>
    </div>

    <ReviewForm
        :is-visible="showReviewForm"
        :order-id="selectedOrderForReview?.id"
        :review="selectedReviewForEdit"
        @close="closeReviewForm"
        @success="onReviewSuccess"
    />
</template>

<script>
import { computed } from 'vue';
import Pagination from '@/MobileClient/Components/Shop/Helpers/Pagination.vue';
import ReviewCard from '@/MobileClient/Components/Shop/Reviews/ReviewCard.vue';
import { useOrders } from '@/MobileClient/composables/useOrders.js';
import { useBasket } from '@/MobileClient/composables/useBasket.js';
import ReviewForm from '@/MobileClient/Components/Shop/Reviews/ReviewForm.vue';

export default {
    name: "OrdersList",

    components: {
        Pagination,
        ReviewForm,
        ReviewCard,
    },

    props: {
        selected: { type: Object, default: null },
        active:   { type: Boolean, default: false },
    },

    emits: ['select'],

    setup() {
        const orderComposable = useOrders();
        const basketComposable = useBasket();

        return {
            // Состояние заказов (из store через storeToRefs)
            orders: orderComposable.orders,
            orders_paginate_object: orderComposable.orders_paginate_object,
            isLoading: orderComposable.isLoading,

            // Состояние отзывов
            reviews: orderComposable.reviews,
            reviews_paginate_object: orderComposable.reviews_paginate_object,
            isLoadingReviews: orderComposable.isLoadingReviews,

            // Методы (заказы)
            loadOrders: orderComposable.loadOrders,
            repeatOrder: orderComposable.repeatOrder,
            clearCart: orderComposable.clearCart,
            addProductToCart: orderComposable.addProductToCart,

            // Методы (отзывы)
            loadReviews: orderComposable.loadReviews,

            loadProductsInBasket: basketComposable.loadProductsInBasket,

            canReviewOrder: orderComposable.canReviewOrder,
            storeReview: orderComposable.storeReview,
            updateReview: orderComposable.updateReview,
            deleteReview: orderComposable.deleteReview,
        };
    },

    data() {
        return {
            tab: 0,
            reviewsLoaded: false,

            showReviewForm: false,
            selectedOrderForReview: null,
            selectedReviewForEdit: null,
        };
    },

    computed: {
        totalOrdersCount() {
            return this.orders_paginate_object?.total || this.orders.length;
        },

        /**
         * Группировка заказов по датам
         */
        groupedOrders() {
            const groups = {};
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            const ordersList = this.orders || [];

            ordersList.forEach(order => {
                const date = new Date(order.created_at);
                let key, label;

                if (date.toDateString() === today.toDateString()) {
                    key = 'today';
                    label = 'Сегодня';
                } else if (date.toDateString() === yesterday.toDateString()) {
                    key = 'yesterday';
                    label = 'Вчера';
                } else {
                    key = date.toISOString().split('T')[0];
                    label = date.toLocaleDateString('ru-RU', {
                        day: 'numeric',
                        month: 'long',
                        year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
                    });
                }

                if (!groups[key]) {
                    groups[key] = { key, label, orders: [] };
                }
                groups[key].orders.push(order);
            });

            // Сортируем группы (новые сверху)
            return Object.fromEntries(
                Object.entries(groups).sort((a, b) => {
                    if (a[0] === 'today') return -1;
                    if (b[0] === 'today') return 1;
                    if (a[0] === 'yesterday') return -1;
                    if (b[0] === 'yesterday') return 1;
                    return b[0].localeCompare(a[0]);
                })
            );
        },
    },

    mounted() {
        this.loadOrdersHandler(0);
    },

    methods: {

        canShowReviewButton(order) {
            // Показываем кнопку только для выполненных заказов
            const status = String(order.status ?? '').toLowerCase();
            return ['2', 'completed', 'delivered', 'done'].includes(status);
        },

        async canReviewOrder(order) {
            if (order._reviewChecked) {
                return order._canReview;
            }

            try {
                const result = await this.canReviewOrder(order.id);
                order._reviewChecked = true;
                order._canReview = result.can_review;

                if (result.has_review && result.review) {
                    order.review = result.review;
                }

                return result.can_review;
            } catch (err) {
                console.error('Ошибка проверки отзыва:', err);
                return false;
            }
        },

        openReviewForm(order, review = null) {
            this.selectedOrderForReview = order;
            this.selectedReviewForEdit = review;
            this.showReviewForm = true;
        },

        closeReviewForm() {
            this.showReviewForm = false;
            this.selectedOrderForReview = null;
            this.selectedReviewForEdit = null;
        },

        async onReviewSuccess(reviewData) {
            // Обновляем отзыв в заказе
            if (this.selectedOrderForReview) {
                this.selectedOrderForReview.review = reviewData;
                this.selectedOrderForReview._canReview = false;
            }
        },
        // ==========================================
        // Навигация и переключение
        // ==========================================
        async switchTab(index) {
            this.tab = index;
            if (index === 1 && !this.reviewsLoaded) {
                await this.loadReviewsHandler(0);
                this.reviewsLoaded = true;
            }
        },

        select(item) {
            this.$emit('select', item);
        },

        goToCatalog() {
            this.$router.push({ name: 'Catalog' });
        },

        // ==========================================
        // Обработчики загрузки (с маппингом пагинации Laravel)
        // ==========================================
        async loadOrdersHandler(page = 0) {
            try {
                await this.loadOrders({ page, size: 20 });
            } catch (error) {
                console.error('Ошибка загрузки заказов:', error);
                this.$notify?.({
                    title: 'Заказы',
                    text: 'Ошибка загрузки заказов',
                    type: 'error',
                });
            }
        },

        async loadReviewsHandler(page = 0) {
            try {
                await this.loadReviews({ page, size: 20 });
            } catch (error) {
                console.error('Ошибка загрузки отзывов:', error);
                this.$notify?.({
                    title: 'Отзывы',
                    text: 'Ошибка загрузки отзывов',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // Повтор заказа
        // ==========================================
        async repeatOrderHandler(item) {
            try {
                // 1. Вызываем бэкенд.
                // (Предполагается, что бэкенд сам очищает старую корзину и добавляет товары из этого заказа)
                await this.repeatOrder({ id: item.id });

                // 2. 🚀 КРИТИЧЕСКИ ВАЖНО: Загружаем актуальное состояние корзины с бэкенда!
                // Это единственный способ гарантировать 100% синхронизацию и реактивность Vue.
                // Метод loadProductsInBasket корректно обновит все ref-ы в Pinia store.
                await this.loadProductsInBasket();

                // 3. Показываем успешное уведомление
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Заказ повторен и добавлен в корзину',
                    type: 'success',
                });

                this.$router.push({ name: 'Cart' }).catch(() => {});


            } catch (error) {
                console.error('Ошибка повторного заказа:', error);

                // Если бэкенд вернул ошибку (например, "нет доступных товаров"), покажем её
                const errorMsg = error.response?.data?.message || 'Не удалось повторить заказ. Возможно, некоторые товары сняты с производства.';

                this.$notify?.({
                    title: 'Ошибка',
                    text: errorMsg,
                    type: 'error',
                });

                // Если бэкенд специально помечает товар как недоступный, можно заблокировать кнопку
                if (error.response?.status === 400 || error.response?.status === 422) {
                    item.disabled = true;
                }
            }
        },

        // ==========================================
        // Вспомогательные функции (маппинг под формат API)
        // ==========================================

        /**
         * Получить товары заказа
         * API: order.product_details[0].products[]
         */
        getOrderProducts(order) {
            return order.product_details?.[0]?.products ||
                order.product_details?.products || [];
        },

        /**
         * Количество товара
         * API: product.count (а не product.quantity)
         */
        getProductQty(product) {
            return product.count || product.quantity || 1;
        },

        /**
         * Название товара
         * API: product.name (а не product.title)
         */
        getProductName(product) {
            return product.name || product.title || 'Товар';
        },

        /**
         * Итого заказа
         * API: order.summary_price (а не order.total)
         */
        getOrderTotal(order) {
            return order.summary_price || order.total || 0;
        },

        /**
         * Класс статуса заказа
         * API: status — число (0, 1, 2, 3, 4, 5)
         */
        getStatusClass(order) {
            const status = String(order.status ?? '').toLowerCase();

            const statusMap = {
                '0': 'status-new',
                '1': 'status-processing',
                '2': 'status-completed',
                '3': 'status-cancelled',
                '4': 'status-processing',
                '5': 'status-processing',
            };

            if (statusMap[status]) return statusMap[status];

            // Фолбэк на строковые статусы
            if (status.includes('cancel') || status.includes('отмен')) return 'status-cancelled';
            if (status.includes('complet') || status.includes('выполн') || status.includes('доставлен')) return 'status-completed';
            if (status.includes('process') || status.includes('готов') || status.includes('в пути')) return 'status-processing';

            return 'status-new';
        },

        /**
         * Текстовое представление статуса
         */
        getStatusText(order) {
            const status = String(order.status ?? '').toLowerCase();

            const statusTextMap = {
                '0': 'Новый',
                '1': 'В обработке',
                '2': 'Выполнен',
                '3': 'Отменён',
                '4': 'Готов к доставке',
                '5': 'Передан на кухню',
            };

            if (statusTextMap[status]) return statusTextMap[status];

            if (status.includes('cancel') || status.includes('отмен')) return 'Отменён';
            if (status.includes('complet') || status.includes('выполн') || status.includes('доставлен')) return 'Выполнен';
            if (status.includes('process') || status.includes('готов') || status.includes('в пути')) return 'В обработке';

            return 'Новый';
        },

        /**
         * Форматирование даты и времени
         */
        formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        /**
         * Форматирование цены
         */
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

        /**
         * Склонение слов
         */
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style scoped>
.orders-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.orders-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
}

/* ==========================================
   ТАБЫ
   ========================================== */
.tabs-wrapper {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--bs-body-bg);
    padding: 16px 0;
    border-bottom: 1px solid var(--bs-border-color);
    margin-bottom: 16px;
}

.tabs-container {
    display: flex;
    gap: 8px;
    background: var(--bs-secondary-bg, #f5f5f5);
    padding: 4px;
    border-radius: 14px;
}

.tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.tab-item:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.tab-item.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.tab-item.active .tab-badge {
    background: var(--bs-primary);
    color: white;
}

/* ==========================================
   ИНФО-БАННЕР
   ========================================== */
.info-banner {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 14px;
    margin-bottom: 20px;
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.info-text {
    flex: 1;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    line-height: 1.4;
}

.info-text strong {
    color: var(--bs-primary);
}

/* ==========================================
   ГРУППЫ ЗАКАЗОВ
   ========================================== */
.orders-group {
    margin-bottom: 24px;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 0 4px;
}

.group-date {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.group-count {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КАРТОЧКА ЗАКАЗА
   ========================================== */
.order-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.order-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.08);
    transform: translateY(-2px);
}

/* Шапка заказа */
.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.order-number {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.order-number-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-number-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

/* Статус */
.order-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-new {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.status-processing {
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
}

.status-completed {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* Мета-информация */
.order-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-bottom: 14px;
}

.order-meta i {
    font-size: 0.75rem;
}

/* Товары */
.order-products {
    padding: 12px;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 12px;
    margin-bottom: 12px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 0.9rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.product-item:last-child {
    border-bottom: none;
}

.product-qty {
    font-weight: 700;
    color: var(--bs-primary);
    min-width: 24px;
}

.product-name {
    flex: 1;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-price {
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
}

.product-more {
    padding-top: 8px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    font-style: italic;
}

/* Итого */
.order-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-top: 1px dashed var(--bs-border-color);
    margin-bottom: 12px;
    font-size: 0.9rem;
}

.total-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-primary);
}

/* Отзыв */
.order-review {
    margin-bottom: 12px;
}

/* Действия */
.order-actions {
    display: flex;
    gap: 8px;
}

.repeat-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.repeat-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.35);
}

.repeat-btn:disabled,
.repeat-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* ==========================================
   СКЕЛЕТОН-ЗАГРУЗКА
   ========================================== */
.skeleton-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    padding: 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.skeleton-line {
    height: 14px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg, #f0f0f0) 0%,
    var(--bs-border-color, #e0e0e0) 50%,
    var(--bs-secondary-bg, #f0f0f0) 100%);
    background-size: 200% 100%;
    border-radius: 6px;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.w-30 { width: 30%; }
.skeleton-line.w-40 { width: 40%; }
.skeleton-line.w-60 { width: 60%; }
.skeleton-line.w-80 { width: 80%; }

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 24px;
    animation: fadeInUp 0.5s ease-out;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
}

.empty-icon.reviews-icon {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

/* ==========================================
   ОТЗЫВЫ
   ========================================== */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .tab-item {
        font-size: 0.85rem;
        padding: 8px 12px;
    }

    .order-number-value {
        font-size: 1rem;
    }

    .product-item {
        font-size: 0.85rem;
    }
}

/* Добавляем стили */

.review-actions {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--bs-border-color);
}

.review-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border: none;
    border-radius: 12px;
    color: #1a1a1a;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(255, 215, 0, 0.4);
}

.review-existing {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.review-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(25, 135, 84, 0.1);
    border-radius: 20px;
    color: #198754;
    font-size: 0.85rem;
    font-weight: 600;
}

.review-edit-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.review-edit-btn:hover {
    background: var(--bs-primary);
    color: white;
}
</style>
