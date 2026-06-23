<template>
    <div
        v-if="item"
        class="cart-product-card"
        :class="{
            'is-loading': isProductLoading(item.id),
            'is-weight': item.is_weight_product
        }"
    >

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
                        <span>Вес</span>
                    </div>
                </div>

                <!-- Индикатор "Нет в наличии" -->
                <div v-if="item.in_stop_list_at" class="out-of-stock-overlay">
                    <i class="fa-solid fa-lock"></i>
                </div>

                <!-- Индикатор загрузки -->
                <transition name="fade">
                    <div v-if="isProductLoading(item.id)" class="loading-overlay">
                        <div class="loading-spinner"></div>
                    </div>
                </transition>
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

                    <!-- Подсказка для весовых: цена за шаг -->
                    <div v-if="item.is_weight_product" class="price-per-step">
                        <i class="fa-solid fa-scale-balanced"></i>
                        <span>{{ formatPrice(pricePerStep) }} / {{ weightStep }}{{ weightUnit }}</span>
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
                            ({{ cartCount }}{{ weightUnit }})
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
                        :class="{
                            'pulse': justAdded,
                            'is-loading': isProductLoading(item.id)
                        }"
                        :disabled="item.in_stop_list_at || !canProductAction"
                        @click="incProductCart"
                    >
                        <i :class="isProductLoading(item.id) ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-cart-plus'"></i>
                        <span>{{ isProductLoading(item.id) ? 'Добавляем...' : 'Добавить' }}</span>
                        <span class="add-price">
                            {{ item.is_weight_product
                            ? `от ${formatPrice(pricePerMin)}`
                            : formatPrice(currentPrice)
                            }}
                        </span>
                    </button>

                    <!-- В корзине — stepper -->
                    <div
                        v-else
                        class="quantity-stepper"
                        :class="{ 'is-updating': isProductLoading(item.id) }"
                    >
                        <button
                            type="button"
                            class="stepper-btn minus"
                            :disabled="!canDecrement"
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
                            :disabled="!canIncrement"
                            @click="incProductCart"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                    <!-- Подсказка о весе (для весовых товаров) -->
                    <div v-if="item.is_weight_product && cartCount > 0" class="weight-info">
                        <span v-if="maxWeight > 0">
                            {{ cartCount }} / {{ maxWeight }}{{ weightUnit }}
                        </span>
                        <span v-else>
                            Мин: {{ minWeight }}{{ weightUnit }}, шаг: {{ weightStep }}{{ weightUnit }}
                        </span>
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
import { useBasket } from '@/MobileClient/Composables/useBasket.js';

export default {
    name: "CartProductCard",

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
        const basket = useBasket();

        return {
            addProduct: basket.addProduct,
            removeProduct: basket.removeProduct,
            isProductLoading: basket.isProductLoading,
            inCartFn: basket.inCart,
            getItemById: basket.getItemById,
            addCommentToCart: basket.addCommentToProduct,
        };
    },

    data() {
        return {
            saving: false,
            isOnline: navigator.onLine,
            justAdded: false,
            _onlineHandler: null,
            _offlineHandler: null,
            form: {
                id: null,
                comment: null,
                need_comment: false,
            },
        };
    },

    computed: {
        /**
         * Количество товара в корзине
         */
        cartCount() {
            return this.inCartFn(this.item.id) || 0;
        },

        /**
         * Текущий элемент корзины (для доступа к params)
         */
        basketItem() {
            return this.getItemById(this.item.id);
        },

        /**
         * Конфигурация веса товара
         */
        weightConfig() {
            // Приоритет 1: из params корзины (сохранено бэкендом)
            const fromBasket = this.basketItem?.params?.weight_config;
            if (fromBasket) return fromBasket;

            // Приоритет 2: из самого товара
            const fromProduct = this.item?.weight_config;
            if (!fromProduct) return { min: 100, max: 0, step: 50 };

            if (typeof fromProduct === 'string') {
                try {
                    return JSON.parse(fromProduct);
                } catch {
                    return { min: 100, max: 0, step: 50 };
                }
            }
            return fromProduct;
        },

        minWeight() {
            return Math.max(1, parseInt(this.weightConfig?.min) || 100);
        },

        maxWeight() {
            return Math.max(0, parseInt(this.weightConfig?.max) || 0);
        },

        weightStep() {
            return Math.max(1, parseInt(this.weightConfig?.step) || 50);
        },

        weightUnit() {
            return 'г';
        },

        /**
         * Можно ли выполнять действия
         */
        canProductAction() {
            return (
                this.isOnline &&
                !this.isProductLoading(this.item.id) &&
                !this.item?.in_stop_list_at
            );
        },

        /**
         * Можно ли уменьшить количество
         */
        canDecrement() {
            if (!this.canProductAction) return false;

            if (this.item?.is_weight_product) {
                // Для весовых: нельзя уменьшить, если станет меньше минимума
                const nextCount = this.cartCount - this.weightStep;
                return nextCount >= this.minWeight;
            }
            return true;
        },

        /**
         * Можно ли увеличить количество
         */
        canIncrement() {
            if (!this.canProductAction) return false;

            if (this.item?.is_weight_product && this.maxWeight > 0) {
                // Для весовых: нельзя увеличить, если превысит максимум
                return (this.cartCount + this.weightStep) <= this.maxWeight;
            }
            return true;
        },

        /**
         * Текущая цена (с учётом скидки)
         */
        currentPrice() {
            return this.config?.discount_price || this.item.price || 0;
        },

        /**
         * Процент скидки
         */
        discountPercent() {
            if (!this.item.old_price || !this.item.price) return 0;
            const discount = Math.round(
                ((this.item.old_price - this.item.price) / this.item.old_price) * 100
            );
            return discount > 0 ? discount : 0;
        },

        /**
         * Экономия
         */
        savings() {
            if (this.config?.discount_price) {
                return (this.item.price || 0) - this.config.discount_price;
            }
            if (this.item.old_price > 0) {
                return this.item.old_price - this.item.price;
            }
            return 0;
        },

        /**
         * Итоговая стоимость (с учётом количества и веса)
         */
        totalPrice() {
            if (this.item?.is_weight_product) {
                // Для весовых: цена за шаг × (количество / шаг)
                const stepsCount = Math.ceil(this.cartCount / this.weightStep);
                return this.currentPrice * stepsCount;
            }
            return this.currentPrice * this.cartCount;
        },

        /**
         * Цена за один шаг (для подсказки)
         */
        pricePerStep() {
            return this.currentPrice;
        },

        /**
         * Цена за минимальный вес (для кнопки "Добавить")
         */
        pricePerMin() {
            const stepsInMin = Math.ceil(this.minWeight / this.weightStep);
            return this.currentPrice * stepsInMin;
        },
    },

    mounted() {
        // Регистрируем обработчики online/offline
        this._onlineHandler = () => { this.isOnline = true; };
        this._offlineHandler = () => { this.isOnline = false; };
        window.addEventListener('online', this._onlineHandler);
        window.addEventListener('offline', this._offlineHandler);

        // Инициализация комментария
        if (this.comment) {
            this.form.need_comment = true;
            this.form.comment = this.comment;
        }
    },

    beforeUnmount() {
        // Корректно удаляем обработчики
        if (this._onlineHandler) window.removeEventListener('online', this._onlineHandler);
        if (this._offlineHandler) window.removeEventListener('offline', this._offlineHandler);
    },

    methods: {
        /**
         * Добавление товара в корзину (клик по изображению)
         */
        addToCart() {
            if (this.cartCount === 0 && !this.item?.in_stop_list_at) {
                this.incProductCart();
            }
        },

        /**
         * Добавление комментария к товару
         */
        async addCommentToProduct() {
            if (!this.form.comment?.trim()) return;

            this.saving = true;
            this.form.id = this.item.id;

            try {
                await this.addCommentToCart({ form: { ...this.form } });
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
                this.saving = false;
            }
        },

        /**
         * Увеличение количества товара
         */
        async incProductCart() {
            if (!this.canIncrement) {
                // Показываем уведомление о достижении лимита
                if (this.item?.is_weight_product && this.maxWeight > 0) {
                    this.$notify?.({
                        title: 'Ограничение',
                        text: `Достигнут максимальный вес: ${this.maxWeight}${this.weightUnit}`,
                        type: 'warning',
                    });
                }
                return;
            }

            try {
                await this.addProduct(this.item.id);

                this.justAdded = true;
                setTimeout(() => { this.justAdded = false; }, 600);

                this.$notify?.({
                    title: 'Корзина',
                    text: `«${this.item.name}» добавлен`,
                    type: 'success',
                });
            } catch (error) {
                // Обработка ошибки "достигнут максимум"
                const message = error?.response?.data?.message || 'Не удалось добавить товар';
                this.$notify?.({
                    title: 'Ошибка',
                    text: message,
                    type: 'error',
                });
            }
        },

        /**
         * Уменьшение количества товара
         */
        async decProductCart() {
            if (!this.canDecrement) return;

            try {
                await this.removeProduct(this.item.id);
            } catch (error) {
                console.error('Ошибка уменьшения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить количество',
                    type: 'error',
                });
            }
        },

        /**
         * Форматирование цены
         */
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

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$primary-light: #60a5fa;
$danger: #dc3545;
$success: #198754;
$warning: #ffc107;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #ffffff;
$bg-secondary: #f8f9fa;

// ==========================================
// БАЗА
// ==========================================
.cart-product-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 12px;
    transition: all 0.2s ease;

    &:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    &.is-loading {
        .card-main {
            opacity: 0.7;
        }
    }
}

// ==========================================
// ОСНОВНАЯ ЧАСТЬ
// ==========================================
.card-main {
    display: flex;
    gap: 14px;
    padding: 14px;
}

// Изображение
.product-image-wrapper {
    position: relative;
    width: 110px;
    height: 110px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: $bg-secondary;
    cursor: pointer;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;

    .product-image-wrapper:hover & {
        transform: scale(1.05);
    }
}

// Бейджи
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
    background: linear-gradient(135deg, $danger 0%, darken($danger, 10%) 100%);
    color: white;
}

.weight-badge {
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    color: white;
}

// Нет в наличии
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

// Индикатор загрузки
.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
    z-index: 5;
}

.loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// Информация
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
    color: $text;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// Цена
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
    color: $primary;
}

.price-old {
    font-size: 0.85rem;
    color: $text-muted;
    text-decoration: line-through;
}

.price-per-step {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: $text-muted;
    padding: 2px 8px;
    background: rgba($primary, 0.05);
    border-radius: 6px;
    width: fit-content;

    i {
        font-size: 0.7rem;
        color: $primary;
    }
}

.price-savings {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba($success, 0.1);
    color: $success;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    width: fit-content;

    i {
        font-size: 0.65rem;
    }
}

.price-total {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;
}

.total-label {
    font-weight: 500;
}

.total-value {
    font-weight: 700;
    color: $text;
}

.total-unit {
    font-size: 0.7rem;
    opacity: 0.7;
}

// Счётчик количества
.quantity-section {
    margin-top: auto;
}

// Кнопка "Добавить"
.add-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 10px 14px;
    background: linear-gradient(135deg, $primary 0%, lighten($primary, 10%) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba($primary, 0.25);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba($primary, 0.35);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    &.pulse {
        animation: addPulse 0.6s ease;
    }

    &.is-loading {
        background: linear-gradient(135deg, lighten($primary, 15%) 0%, lighten($primary, 25%) 100%);
    }
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

// Stepper
.quantity-stepper {
    display: flex;
    align-items: center;
    background: $bg;
    border: 2px solid $primary;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;

    &.is-updating {
        border-color: lighten($primary, 20%);
        background: rgba($primary, 0.03);
    }
}

.stepper-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: transparent;
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        background: $primary;
        color: white;
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;

        &:hover {
            background: transparent;
            color: $primary;
        }
    }

    &.minus {
        border-right: 1px solid $border;
    }

    &.plus {
        border-left: 1px solid $border;
    }
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
    color: $primary;
}

.value-unit {
    font-size: 0.7rem;
    color: $text-muted;
    font-weight: 500;
}

// Подсказка о весе
.weight-info {
    margin-top: 6px;
    padding: 4px 8px;
    background: rgba($primary, 0.05);
    border-radius: 6px;
    font-size: 0.7rem;
    color: $text-muted;
    text-align: center;
}

// ==========================================
// КОММЕНТАРИЙ
// ==========================================
.comment-section {
    border-top: 1px solid rgba($border, 0.5);
    padding: 12px 14px;
}

.comment-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: $bg-secondary;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: rgba($primary, 0.05);
    }

    &.active {
        background: rgba($primary, 0.08);
    }
}

.toggle-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;

    .comment-toggle.active & {
        background: $primary;
        color: white;
    }
}

.toggle-text {
    flex: 1;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
}

.toggle-switch {
    flex-shrink: 0;
}

.switch-track {
    width: 40px;
    height: 22px;
    border-radius: 11px;
    background: $border;
    position: relative;
    transition: background 0.3s ease;

    &.active {
        background: $primary;
    }
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

    .switch-track.active & {
        transform: translateX(18px);
    }
}

// Поле комментария
.comment-input-wrapper {
    margin-top: 10px;
}

.comment-input-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px;
    background: $bg;
    border: 2px solid $border;
    border-radius: 12px;
    transition: all 0.2s ease;

    &:focus-within {
        border-color: $primary;
        box-shadow: 0 0 0 4px rgba($primary, 0.1);
    }
}

.comment-icon {
    color: $text-muted;
    font-size: 0.85rem;
}

.comment-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px 0;
    font-size: 0.9rem;
    color: $text;
    outline: none;

    &::placeholder {
        color: $text-muted;
    }
}

.comment-save {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: $primary;
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        transform: scale(1.1);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.save-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

// Анимация
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

// ==========================================
// ОСОБЕННОСТИ ДОСТАВКИ
// ==========================================
.delivery-notice {
    display: flex;
    gap: 12px;
    padding: 12px 14px;
    background: rgba($warning, 0.08);
    border-top: 1px solid rgba($warning, 0.2);
}

.notice-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba($warning, 0.15);
    color: darken($warning, 20%);
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
    color: darken($warning, 20%);
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.notice-text {
    font-size: 0.8rem;
    color: $text;
    line-height: 1.4;
    font-style: italic;
}

// ==========================================
// АНИМАЦИИ ПЕРЕХОДОВ
// ==========================================
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
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
