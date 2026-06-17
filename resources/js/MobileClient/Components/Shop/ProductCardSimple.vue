<template>
    <div v-if="item" class="cart-product-card">

        <!-- ========================================== -->
        <!-- ОСНОВНАЯ ЧАСТЬ -->
        <!-- ========================================== -->
        <div class="card-main">

            <!-- Изображение -->
            <div class="product-image-wrapper" @click="addToCart">
                <img
                    v-lazy="item.images?.[0] || '/no-image.png'"
                    :alt="item.name"
                    class="product-image"
                    loading="lazy"
                >

                <!-- Бейджи -->
                <div class="image-badges">
                    <div v-if="discountPercent > 0" class="badge discount-badge">
                        -{{ discountPercent }}%
                    </div>
                    <div v-if="item.is_weight_product" class="badge weight-badge">
                        <i class="fa-solid fa-weight-hanging"></i>
                    </div>
                </div>

                <!-- Индикатор "Нет в наличии" -->
                <div v-if="item.in_stop_list_at" class="out-of-stock-overlay">
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <!-- Информация -->
            <div class="product-info">

                <!-- Название -->
                <div class="product-header">
                    <h6 class="product-name">
                        {{ item.name || 'Товар' }}
                    </h6>
                    <slot name="partner"></slot>
                </div>

                <!-- Цена -->
                <div class="price-block">
                    <div class="price-main">
                        <span class="price-current">{{ formatPrice(currentPrice) }}</span>
                        <span v-if="item.old_price > 0 && !config?.discount_price" class="price-old">
                            {{ formatPrice(item.old_price) }}
                        </span>
                    </div>

                    <!-- Экономия -->
                    <div v-if="savings > 0" class="price-savings">
                        <i class="fa-solid fa-piggy-bank"></i>
                        <span>Экономия {{ formatPrice(savings) }}</span>
                    </div>

                    <!-- Итого -->
                    <div v-if="cartCount > 0" class="price-total">
                        <span class="total-label">Итого:</span>
                        <span class="total-value">{{ formatPrice(totalPrice) }}</span>
                        <span v-if="item.is_weight_product" class="total-unit">
                            ({{ weightDisplay }})
                        </span>
                    </div>
                </div>

                <!-- Счётчик количества -->
                <div class="quantity-section">

                    <!-- Не в корзине — кнопка "Добавить" -->
                    <button
                        v-if="cartCount === 0"
                        type="button"
                        class="add-btn"
                        :class="{ 'pulse': justAdded }"
                        :disabled="item.in_stop_list_at || !canProductAction"
                        @click="incProductCart"
                    >
                        <i class="fa-solid fa-cart-plus"></i>
                        <span>Добавить</span>
                        <span class="add-price">{{ formatPrice(currentPrice) }}</span>
                    </button>

                    <!-- В корзине — stepper -->
                    <div v-else class="quantity-stepper">
                        <button
                            type="button"
                            class="stepper-btn minus"
                            :disabled="!canProductAction || item.in_stop_list_at"
                            @click="decProductCart"
                        >
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <div class="stepper-value">
                            <span class="value-number">{{ cartCount }}</span>
                            <span v-if="item.is_weight_product" class="value-unit">
                                {{ weightUnit }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="stepper-btn plus"
                            :disabled="!canProductAction || item.in_stop_list_at"
                            @click="incProductCart"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОММЕНТАРИЙ -->
        <!-- ========================================== -->
        <div class="comment-section">

            <!-- Toggle комментария -->
            <button
                type="button"
                class="comment-toggle"
                :class="{ 'active': form.need_comment }"
                @click="form.need_comment = !form.need_comment"
            >
                <div class="toggle-icon">
                    <i class="fa-solid fa-comment-dots"></i>
                </div>
                <span class="toggle-text">
                    {{ form.need_comment ? 'Комментарий добавляется' : 'Добавить комментарий' }}
                </span>
                <div class="toggle-switch">
                    <div class="switch-track" :class="{ 'active': form.need_comment }">
                        <div class="switch-thumb"></div>
                    </div>
                </div>
            </button>

            <!-- Поле комментария -->
            <transition name="slide-down">
                <div v-if="form.need_comment" class="comment-input-wrapper">
                    <div class="comment-input-box">
                        <i class="fa-solid fa-pen comment-icon"></i>
                        <input
                            type="text"
                            v-model="form.comment"
                            @blur="addCommentToProduct"
                            @keyup.enter="addCommentToProduct"
                            placeholder="Например: без лука, добавить соус..."
                            class="comment-input"
                        >
                        <button
                            v-if="form.comment"
                            type="button"
                            class="comment-save"
                            @click="addCommentToProduct"
                            :disabled="saving"
                        >
                            <span v-if="saving" class="save-spinner"></span>
                            <i v-else class="fa-solid fa-check"></i>
                        </button>
                    </div>
                </div>
            </transition>

        </div>

        <!-- ========================================== -->
        <!-- ОСОБЕННОСТИ ДОСТАВКИ -->
        <!-- ========================================== -->
        <div v-if="item.delivery_terms" class="delivery-notice">
            <div class="notice-icon">
                <i class="fa-solid fa-truck"></i>
            </div>
            <div class="notice-content">
                <div class="notice-title">Особенности доставки</div>
                <div class="notice-text">{{ item.delivery_terms }}</div>
            </div>
        </div>

    </div>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';

export default {
    name: "ProductCardSimple",

    props: {
        item: {
            type: Object,
            required: true,
        },
        comment: {
            type: String,
            default: null,
        },
        config: {
            type: Object,
            default: null,
        },
    },

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            sending: false,
            isOnline: navigator.onLine,
            justAdded: false,
            form: {
                id: null,
                comment: null,
                need_comment: false,
            },
        };
    },

    computed: {
        canProductAction() {
            return this.isOnline && !this.sending;
        },

        cartCount() {
            return this.basketStore.inCart(this.item.id) || 0;
        },

        currentPrice() {
            return this.config?.discount_price || this.item.price || 0;
        },

        discountPercent() {
            if (!this.item.old_price || !this.item.price) return 0;
            const discount = Math.round(
                ((this.item.old_price - this.item.price) / this.item.old_price) * 100
            );
            return discount > 0 ? discount : 0;
        },

        savings() {
            if (this.config?.discount_price) {
                return (this.item.price || 0) - this.config.discount_price;
            }
            if (this.item.old_price > 0) {
                return this.item.old_price - this.item.price;
            }
            return 0;
        },

        totalPrice() {
            return this.currentPrice * this.cartCount;
        },

        weightDisplay() {
            if (!this.item.is_weight_product) return '';
            const step = this.item.weight_config?.step || 100;
            const totalWeight = this.cartCount * step;
            return totalWeight >= 1000
                ? `${(totalWeight / 1000).toFixed(1)} кг`
                : `${totalWeight} г`;
        },

        weightUnit() {
            const step = this.item.weight_config?.step || 100;
            return step >= 1000 ? 'кг' : 'г';
        },
    },

    mounted() {
        window.addEventListener('online', this.onOnline);
        window.addEventListener('offline', this.onOffline);

        if (this.comment) {
            this.form.need_comment = true;
            this.form.comment = this.comment;
        }
    },

    beforeUnmount() {
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
    },

    methods: {
        onOnline() {
            this.isOnline = true;
        },

        onOffline() {
            this.isOnline = false;
        },

        addToCart() {
            if (this.cartCount === 0) {
                this.incProductCart();
            }
        },

        async addCommentToProduct() {
            if (!this.form.comment?.trim()) return;

            this.sending = true;
            this.form.id = this.item.id;

            try {
                await this.basketStore.addCommentToProduct({ ...this.form });
                this.$notify?.({
                    title: 'Комментарий',
                    text: 'Комментарий успешно добавлен',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка добавления комментария:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить комментарий',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        async incProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.addProductToCart(this.item);

                this.justAdded = true;
                setTimeout(() => { this.justAdded = false; }, 600);

                this.$notify?.({
                    title: 'Корзина',
                    text: `«${this.item.name}» добавлен`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка добавления:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить товар',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        async decProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.removeProductFromCart(this.item.id);
                this.$notify?.({
                    title: 'Корзина',
                    text: `«${this.item.name}» убран`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка удаления:', error);
            } finally {
                this.sending = false;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },
    },
};
</script>

<style scoped>
.cart-product-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.cart-product-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* ==========================================
   ОСНОВНАЯ ЧАСТЬ
   ========================================== */
.card-main {
    display: flex;
    gap: 14px;
    padding: 14px;
}

/* Изображение */
.product-image-wrapper {
    position: relative;
    width: 110px;
    height: 110px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--bs-secondary-bg);
    cursor: pointer;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-image-wrapper:hover .product-image {
    transform: scale(1.05);
}

/* Бейджи */
.image-badges {
    position: absolute;
    top: 6px;
    left: 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 3px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.discount-badge {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.weight-badge {
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    color: white;
}

/* Нет в наличии */
.out-of-stock-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

/* Информация */
.product-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.product-header {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.product-name {
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Цена */
.price-block {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.price-main {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.price-current {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.price-old {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    text-decoration: line-through;
}

.price-savings {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    width: fit-content;
}

.price-savings i {
    font-size: 0.65rem;
}

.price-total {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.total-label {
    font-weight: 500;
}

.total-value {
    font-weight: 700;
    color: var(--bs-body-color);
}

.total-unit {
    font-size: 0.7rem;
    opacity: 0.7;
}

/* Счётчик количества */
.quantity-section {
    margin-top: auto;
}

/* Кнопка "Добавить" */
.add-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 10px 14px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
}

.add-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.35);
}

.add-btn:active:not(:disabled) {
    transform: translateY(0);
}

.add-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.add-btn.pulse {
    animation: addPulse 0.6s ease;
}

@keyframes addPulse {
    0% { transform: scale(1); }
    30% { transform: scale(1.03); }
    60% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

.add-btn i {
    font-size: 0.9rem;
}

.add-price {
    margin-left: auto;
    font-weight: 700;
}

/* Stepper */
.quantity-stepper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-primary);
    border-radius: 12px;
    overflow: hidden;
}

.stepper-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: transparent;
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stepper-btn:hover:not(:disabled) {
    background: var(--bs-primary);
    color: white;
}

.stepper-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.stepper-btn.minus {
    border-right: 1px solid var(--bs-border-color);
}

.stepper-btn.plus {
    border-left: 1px solid var(--bs-border-color);
}

.stepper-value {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.value-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.value-unit {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    font-weight: 500;
}

/* ==========================================
   КОММЕНТАРИЙ
   ========================================== */
.comment-section {
    border-top: 1px solid var(--bs-border-color-translucent);
    padding: 12px 14px;
}

.comment-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: var(--bs-secondary-bg);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.comment-toggle:hover {
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.comment-toggle.active {
    background: rgba(var(--bs-primary-rgb), 0.08);
}

.toggle-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.comment-toggle.active .toggle-icon {
    background: var(--bs-primary);
    color: white;
}

.toggle-text {
    flex: 1;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.toggle-switch {
    flex-shrink: 0;
}

.switch-track {
    width: 40px;
    height: 22px;
    border-radius: 11px;
    background: var(--bs-border-color);
    position: relative;
    transition: background 0.3s ease;
}

.switch-track.active {
    background: var(--bs-primary);
}

.switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.switch-track.active .switch-thumb {
    transform: translateX(18px);
}

/* Поле комментария */
.comment-input-wrapper {
    margin-top: 10px;
}

.comment-input-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.comment-input-box:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.comment-icon {
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
}

.comment-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px 0;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    outline: none;
}

.comment-input::placeholder {
    color: var(--bs-secondary-color);
}

.comment-save {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-primary);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.comment-save:hover:not(:disabled) {
    transform: scale(1.1);
}

.comment-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.save-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Анимация */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 100px;
}

/* ==========================================
   ОСОБЕННОСТИ ДОСТАВКИ
   ========================================== */
.delivery-notice {
    display: flex;
    gap: 12px;
    padding: 12px 14px;
    background: rgba(255, 193, 7, 0.08);
    border-top: 1px solid rgba(255, 193, 7, 0.2);
}

.notice-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 193, 7, 0.15);
    color: #b8860b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.notice-content {
    flex: 1;
    min-width: 0;
}

.notice-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #b8860b;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.notice-text {
    font-size: 0.8rem;
    color: var(--bs-body-color);
    line-height: 1.4;
    font-style: italic;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .card-main {
        padding: 12px;
        gap: 12px;
    }

    .product-image-wrapper {
        width: 90px;
        height: 90px;
    }

    .product-name {
        font-size: 0.9rem;
    }

    .price-current {
        font-size: 1rem;
    }

    .add-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .stepper-btn {
        width: 40px;
        height: 40px;
    }
}
</style>
