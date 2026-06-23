<template>
    <div
        v-if="item"
        class="product-card"
        :class="{
            'is-collection': collectionMode,
            'is-loading': isProductLoading(item.id),
            'is-offline': !isOnline
        }"
    >

        <!-- ========================================== -->
        <!-- ИЗОБРАЖЕНИЕ -->
        <!-- ========================================== -->
        <div class="product-image-wrapper" @click="showProductDetails">
            <img
                v-lazy="item?.images?.[0] || '/no-image.png'"
                :alt="item?.name"
                class="product-image"
                loading="lazy"
            >

            <!-- Overlay с элементами управления -->
            <div class="image-overlay">

                <!-- Верхний ряд -->
                <div class="overlay-top">
                    <!-- Бейдж скидки -->
                    <div v-if="discountPercent > 0" class="discount-badge">
                        -{{ discountPercent }}%
                    </div>

                    <!-- Бейдж "Нет в наличии" -->
                    <div v-if="item?.in_stop_list" class="stock-badge">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <!-- Нижний ряд -->
                <div class="overlay-bottom">
                    <!-- Рейтинг -->
                    <div v-if="item?.rating > 0" class="rating-badge">
                        <i class="fa-solid fa-star"></i>
                        <span>{{ item.rating.toFixed(1) }}</span>
                    </div>

                    <!-- Доставка -->
                    <div v-if="item?.delivery_terms" class="delivery-badge">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div class="spacer"></div>


                    <!-- Избранное -->
                    <button
                        class="favorite-btn"
                        :class="{
                'is-favorite': isInFavorites(item.id),
                'is-animating': favAnimating,
                'is-loading': favLoading
            }"
                        @click.stop="toggleFavoriteItem"
                        :disabled="favLoading"
                    >
                        <i v-if="favLoading" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else :class="isInFavorites(item.id) ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
                    </button>
                </div>
            </div>

            <!-- Индикатор загрузки при добавлении товара -->
            <transition name="fade">
                <div v-if="isProductLoading(item.id)" class="loading-overlay">
                    <div class="loading-spinner"></div>
                </div>
            </transition>
        </div>

        <!-- ========================================== -->
        <!-- ИНФОРМАЦИЯ О ТОВАРЕ -->
        <!-- ========================================== -->
        <div class="product-info" @click="showProductDetails">
            <h6 class="product-name">{{ item.name }}</h6>
            <p v-if="item?.short_description" class="product-description">
                {{ item.short_description }}
            </p>
        </div>

        <!-- ========================================== -->
        <!-- ДЕЙСТВИЕ -->
        <!-- ========================================== -->
        <div class="product-action">
            <template v-if="collectionMode">
                <!-- ... режим коллекции без изменений ... -->
            </template>

            <template v-else>
                <template v-if="!item?.in_stop_list">

                    <!-- НЕ В КОРЗИНЕ — кнопка "Добавить" -->
                    <button
                        v-if="checkInCart === 0"
                        class="add-btn"
                        :class="{
                            'pulse': justAdded,
                            'is-loading': isProductLoading(item.id)
                        }"
                        :disabled="!canProductAction"
                        @click.stop="incProductCart"
                    >
                        <div class="add-btn-content">
                            <div class="add-icon">
                                <i v-if="isProductLoading(item.id)" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-plus"></i>
                            </div>
                            <div class="add-btn-info">
                                <span class="add-btn-label">
                                    {{ isProductLoading(item.id) ? 'Добавляем...' : 'В корзину' }}
                                </span>
                                <span class="add-btn-price">
                                    <template v-if="item?.is_weight_product">
                                        от {{ formatPrice(pricePerUnit * minWeight) }}
                                        <span class="weight-hint">
                                            / {{ minWeight }}{{ weightUnit }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        {{ formatPrice(item?.price) }}
                                        <span v-if="item?.old_price > 0" class="old-price">
                                            {{ formatPrice(item.old_price) }}
                                        </span>
                                    </template>
                                </span>
                            </div>
                        </div>
                    </button>

                    <!-- В КОРЗИНЕ — счётчик -->
                    <div
                        v-else
                        class="quantity-stepper"
                        :class="{
                            'is-updating': isProductLoading(item.id),
                            'is-weight': item?.is_weight_product
                        }"
                    >
                        <button
                            class="stepper-btn minus"
                            @click.stop="decProductCart"

                        >
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <div class="stepper-value">
                            <span class="value-number">{{ displayCount }}</span>
                            <span v-if="item?.is_weight_product" class="value-unit">
                                {{ weightUnit }}
                            </span>
                        </div>

                        <button
                            class="stepper-btn plus"
                            @click.stop="incProductCart"
                            :disabled="!canIncrement"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                    <!-- Подсказка о весе -->
                    <div v-if="item?.is_weight_product && checkInCart > 0" class="weight-info">
                        <span v-if="maxWeight > 0">
                            {{ displayCount }} / {{ maxWeight }}{{ weightUnit }}
                        </span>
                        <span v-else>
                            Мин: {{ minWeight }}{{ weightUnit }}, шаг: {{ weightStep }}{{ weightUnit }}
                        </span>
                    </div>
                </template>

                <div v-else class="out-of-stock">
                    <i class="fa-solid fa-lock"></i>
                    <span>Нет в наличии</span>
                </div>
            </template>
        </div>

    </div>
</template>

<script>
import { useBasket } from '@/MobileClient/Composables/useBasket.js';
import { useProductsStore } from "@/MobileClient/stores/Shop/products.js";
import { useFavorites } from '@/MobileClient/Composables/useFavorites.js';
export default {
    name: "ProductCard",

    props: {
        item: {
            type: Object,
            required: true,
        },
        collectionMode: {
            type: Boolean,
            default: false,
        },
        canSelect: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['select-in-collection'],


    setup() {
        const basket = useBasket();
        const productStore = useProductsStore();
        const favorites = useFavorites();

        return {
            // Корзина (из composable)
            addProduct: basket.addProduct,
            removeProduct: basket.removeProduct,
            isProductLoading: basket.isProductLoading,
            getProductAction: basket.getProductAction,
            inCartFn: basket.inCart, // <-- переименовали, чтобы не конфликтовало с computed
            getItemById: basket.getItemById,
            // Store продуктов (для избранного)
            productStore,

            // Избранное
            isInFavorites: favorites.isInFavorites,
            toggleFavoriteAction: favorites.toggleFavorite,
        };
    },

    data() {
        return {
            favLoading: false,
            favAnimating: false,
            justAdded: false,
            isOnline: navigator.onLine,
            _onlineHandler: null,
            _offlineHandler: null,
        };
    },

    computed: {
        checkInCart() {
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
            // Пытаемся получить из params корзины (сохранено бэкендом)
            const fromBasket = this.basketItem?.params?.weight_config;
            if (fromBasket) return fromBasket;

            // Fallback: из самого товара
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
         * Что показывать в счётчике
         */
        displayCount() {
            if (!this.item?.is_weight_product) return this.checkInCart;
            return this.checkInCart; // Для весовых — это граммы
        },

        /**
         * Цена за единицу (грамм или штуку)
         */
        pricePerUnit() {
            return this.item?.price || 0;
        },

        /**
         * Можно ли уменьшить количество
         */
        canDecrement() {
            if (!this.canProductAction) return false;
            if (this.isProductLoading(this.item.id)) return false;

            if (this.item?.is_weight_product) {
                // Для весовых: нельзя уменьшить, если станет меньше минимума
                const nextCount = this.checkInCart - this.weightStep;
                return nextCount >= this.minWeight;
            }
            return true;
        },

        /**
         * Можно ли увеличить количество
         */
        canIncrement() {
            if (!this.canProductAction) return false;
            if (this.isProductLoading(this.item.id)) return false;

            if (this.item?.is_weight_product && this.maxWeight > 0) {
                // Для весовых: нельзя увеличить, если превысит максимум
                return (this.checkInCart + this.weightStep) <= this.maxWeight;
            }
            return true;
        },


        canProductAction() {
            return (
                this.isOnline &&
                !this.isProductLoading(this.item.id) &&
                !this.collectionMode
            );
        },

        discountPercent() {
            if (!this.item?.old_price || !this.item?.price) return 0;
            const discount = Math.round(
                ((this.item.old_price - this.item.price) / this.item.old_price) * 100
            );
            return discount > 0 ? discount : 0;
        },
    },

    mounted() {
        // Регистрируем обработчики online/offline с сохранением ссылок
        this._onlineHandler = () => { this.isOnline = true; };
        this._offlineHandler = () => { this.isOnline = false; };

        window.addEventListener('online', this._onlineHandler);
        window.addEventListener('offline', this._offlineHandler);
    },

    beforeUnmount() {
        // Корректно удаляем обработчики (предотвращаем утечки памяти)
        if (this._onlineHandler) {
            window.removeEventListener('online', this._onlineHandler);
        }
        if (this._offlineHandler) {
            window.removeEventListener('offline', this._offlineHandler);
        }
    },

    methods: {
        // ==========================================
        // ПРОСМОТР ДЕТАЛЕЙ
        // ==========================================
        showProductDetails() {
            this.$productInfo?.show(this.item);
        },

        // ==========================================
        // ИЗБРАННОЕ
        // ==========================================
        async toggleFavoriteItem() {
            if (this.favLoading) return;

            this.favLoading = true;
            this.favAnimating = true;

            try {
                const result = await this.toggleFavoriteAction(this.item);

                // Уведомление пользователя
                this.$notify?.({
                    title: 'Избранное',
                    text: result.isFavorite
                        ? `«${this.item.name}» добавлен в избранное`
                        : `«${this.item.name}» удалён из избранного`,
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка избранного:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось обновить избранное',
                    type: 'error',
                });
            } finally {
                this.favLoading = false;
                setTimeout(() => { this.favAnimating = false; }, 400);
            }
        },

        // ==========================================
        // КОРЗИНА
        // ==========================================

        /**
         * Добавление товара в корзину
         * Использует безопасный метод из composable, который защищает от повторных кликов
         */
        async incProductCart() {
            if (!this.canProductAction) return;

            try {
                await this.addProduct(this.item.id);

                // Анимация "только что добавлено"
                this.justAdded = true;
                setTimeout(() => { this.justAdded = false; }, 600);

                this.$notify?.({
                    title: "Корзина",
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
            }
        },

        /**
         * Уменьшение количества товара в корзине
         */
        async decProductCart() {
            if (!this.canProductAction) return;

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

        // ==========================================
        // КОЛЛЕКЦИИ
        // ==========================================
        selectInCollection(product) {
            if (this.canSelect) {
                this.$emit('select-in-collection', product);
            }
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================
        formatPrice(price) {
            if (!price && price !== 0) return '';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },


    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// SCSS-ПЕРЕМЕННЫЕ (для функций lighten/darken)
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$primary-light: #60a5fa;
$danger: #dc3545;
$danger-dark: #c82333;
$warning: #ffc107;
$success: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #ffffff;
$bg-secondary: #f8f9fa;

// ==========================================
// БАЗА
// ==========================================
.product-card {
    background: var(--bs-body-bg, #{$bg});
    border: 1px solid var(--bs-border-color, #{$border});
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
        border-color: var(--bs-primary, #{$primary});
    }

    &.is-loading {
        pointer-events: none;

        .product-image {
            filter: brightness(0.85);
        }
    }

    &.is-offline {
        opacity: 0.7;

        .add-btn,
        .stepper-btn {
            cursor: not-allowed;
        }
    }
}

// ==========================================
// ИЗОБРАЖЕНИЕ
// ==========================================
.product-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--bs-secondary-bg, #{$bg-secondary});
    cursor: pointer;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;

    .product-card:hover & {
        transform: scale(1.08);
    }
}

.image-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 10px;
    pointer-events: none;
}

.overlay-top,
.overlay-bottom {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    pointer-events: auto;
}

.overlay-bottom {
    align-items: flex-end;
}

.spacer {
    flex: 1;
}

// Бейдж скидки
.discount-badge {
    padding: 4px 10px;
    background: linear-gradient(135deg, $danger 0%, darken($danger, 10%) 100%);
    color: white;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba($danger, 0.4);
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.stock-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.rating-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;

    i {
        color: $warning;
        font-size: 0.7rem;
    }
}

.delivery-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.favorite-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);

    &:hover {
        transform: scale(1.15);
        color: $danger;
    }

    &.is-favorite {
        color: $danger;
        background: white;
    }

    &.is-animating {
        animation: heartBeat 0.4s ease;
    }
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(0.9); }
    75% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

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
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.is-loading::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
            90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.3) 50%,
            transparent 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    z-index: 4;
    pointer-events: none;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// ИНФОРМАЦИЯ О ТОВАРЕ
// ==========================================
.product-info {
    padding: 12px 12px 8px;
    cursor: pointer;
    flex: 1;
}

.product-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--bs-body-color, #{$text});
    margin: 0 0 4px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-description {
    font-size: 0.75rem;
    color: var(--bs-secondary-color, #{$text-muted});
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// ==========================================
// ДЕЙСТВИЕ
// ==========================================
.product-action {
    padding: 0 12px 12px;
}

.add-btn {
    width: 100%;
    padding: 10px 12px;
    background: linear-gradient(135deg, $primary 0%, lighten($primary, 10%) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba($primary, 0.25);
    overflow: hidden;

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
    30% { transform: scale(1.05); }
    60% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

.add-btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.add-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.add-btn-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.add-btn-label {
    font-size: 0.7rem;
    opacity: 0.9;
    line-height: 1;
    margin-bottom: 2px;
}

.add-btn-price {
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1;
    display: flex;
    align-items: center;
    gap: 6px;
}

.old-price {
    font-size: 0.75rem;
    font-weight: 400;
    text-decoration: line-through;
    opacity: 0.7;
}

.quantity-stepper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg, #{$bg});
    border: 2px solid var(--bs-primary, #{$primary});
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;

    &.is-updating {
        border-color: lighten($primary, 20%);
        background: rgba($primary, 0.03);
    }
}

.stepper-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: var(--bs-primary, #{$primary});
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        background: var(--bs-primary, #{$primary});
        color: white;
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    &.minus {
        border-right: 1px solid var(--bs-border-color, #{$border});
    }

    &.plus {
        border-left: 1px solid var(--bs-border-color, #{$border});
    }
}

.stepper-value {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 0 8px;
}

.value-number {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-primary, #{$primary});
}

.value-unit {
    font-size: 0.7rem;
    color: var(--bs-secondary-color, #{$text-muted});
    font-weight: 500;
}

.select-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--bs-body-bg, #{$bg});
    border: 2px solid var(--bs-border-color, #{$border});
    border-radius: 12px;
    color: var(--bs-body-color, #{$text});
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        border-color: var(--bs-primary, #{$primary});
        color: var(--bs-primary, #{$primary});
        background: rgba($primary, 0.03);
    }

    &.selected {
        background: linear-gradient(135deg, $primary 0%, lighten($primary, 10%) 100%);
        border-color: $primary;
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.3);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.out-of-stock {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--bs-secondary-bg, #{$bg-secondary});
    border: 1px solid var(--bs-border-color, #{$border});
    border-radius: 12px;
    color: var(--bs-secondary-color, #{$text-muted});
    font-size: 0.85rem;
    font-weight: 500;

    i {
        font-size: 0.8rem;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 576px) {
    .product-name {
        font-size: 0.85rem;
    }

    .add-btn-price {
        font-size: 0.9rem;
    }

    .stepper-btn {
        width: 36px;
        height: 36px;
    }

    .favorite-btn {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
}

// ==========================================
// НОВЫЕ СТИЛИ ДЛЯ ВЕСОВЫХ ТОВАРОВ
// ==========================================

// Подсказка о весе под счётчиком
.weight-info {
    margin-top: 6px;
    padding: 4px 8px;
    background: rgba($primary, 0.05);
    border-radius: 6px;
    font-size: 0.7rem;
    color: $text-muted;
    text-align: center;
}

// Подсказка "от X ₽ / Y г" на кнопке
.weight-hint {
    font-size: 0.7rem;
    font-weight: 500;
    opacity: 0.85;
    margin-left: 2px;
}

// Счётчик для весовых товаров — чуть шире для отображения граммов
.quantity-stepper.is-weight {
    .stepper-value {
        min-width: 70px;
    }
}

// Заблокированная кнопка — особый стиль
.stepper-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;

    &:hover {
        background: transparent;
        color: inherit;
    }
}
</style>
