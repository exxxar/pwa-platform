<template>
    <div v-if="item" class="product-card" :class="{ 'is-collection': collectionMode }">

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

                    <!-- Spacer -->
                    <div class="spacer"></div>

                    <!-- Избранное -->
                    <button
                        class="favorite-btn"
                        :class="{ 'is-favorite': inFav, 'is-animating': favAnimating }"
                        @click.stop="addToFavorite"
                        :disabled="favLoading"
                    >
                        <i :class="inFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
                    </button>
                </div>
            </div>

            <!-- Индикатор загрузки при добавлении -->
            <div v-if="sending" class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНФОРМАЦИЯ О ТОВАРЕ -->
        <!-- ========================================== -->
        <div class="product-info" @click="showProductDetails">

            <!-- Название -->
            <h6 class="product-name">{{ item.name }}</h6>

            <!-- Описание (если есть) -->
            <p v-if="item?.short_description" class="product-description">
                {{ item.short_description }}
            </p>
        </div>

        <!-- ========================================== -->
        <!-- ДЕЙСТВИЕ -->
        <!-- ========================================== -->
        <div class="product-action">

            <!-- РЕЖИМ КОЛЛЕКЦИИ -->
            <template v-if="collectionMode">
                <button
                    class="select-btn"
                    :class="{ 'selected': item.is_checked }"
                    @click="selectInCollection(item)"
                    :disabled="!canSelect"
                >
                    <i :class="item.is_checked ? 'fa-solid fa-check-double' : 'fa-solid fa-plus'"></i>
                    <span>{{ item.is_checked ? 'Выбрано' : 'Выбрать' }}</span>
                </button>
            </template>

            <!-- ОБЫЧНЫЙ РЕЖИМ -->
            <template v-else>

                <!-- Товар доступен -->
                <template v-if="!item?.in_stop_list">

                    <!-- НЕ В КОРЗИНЕ — кнопка "Добавить" -->
                    <button
                        v-if="checkInCart === 0"
                        class="add-btn"
                        :class="{ 'pulse': justAdded }"
                        :disabled="!canProductAction"
                        @click.stop="incProductCart"
                    >
                        <div class="add-btn-content">
                            <i class="fa-solid fa-plus add-icon"></i>
                            <div class="add-btn-info">
                                <span class="add-btn-label">В корзину</span>
                                <span class="add-btn-price">
                                    {{ formatPrice(item?.price) }}
                                    <span v-if="item?.old_price > 0" class="old-price">
                                        {{ formatPrice(item.old_price) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </button>

                    <!-- В КОРЗИНЕ — счётчик -->
                    <div v-else class="quantity-stepper">
                        <button
                            class="stepper-btn minus"
                            @click.stop="decProductCart"
                            :disabled="!canProductAction"
                        >
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <div class="stepper-value">
                            <span class="value-number">{{ checkInCart }}</span>
                            <span v-if="item?.is_weight_product" class="value-unit">г</span>
                        </div>

                        <button
                            class="stepper-btn plus"
                            @click.stop="incProductCart"
                            :disabled="!canProductAction"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </template>

                <!-- НЕТ В НАЛИЧИИ -->
                <div v-else class="out-of-stock">
                    <i class="fa-solid fa-lock"></i>
                    <span>Нет в наличии</span>
                </div>

            </template>
        </div>

    </div>
</template>

<script>
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import { useProductsStore } from "@/MobileClient/stores/Shop/products.js";

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
        const basketStore = useBasketStore();
        const productStore = useProductsStore();
        return { basketStore, productStore };
    },

    data() {
        return {
            sending: false,
            favLoading: false,
            favAnimating: false,
            justAdded: false,
            isOnline: navigator.onLine,
        };
    },

    computed: {
        checkInCart() {
            return this.basketStore.inCart(this.item.id) || 0;
        },

        favorites() {
            return window.TenantUser?.settings?.favorites || [];
        },

        inFav() {
            return this.favorites.includes(this.item.id);
        },

        canProductAction() {
            return this.isOnline && !this.sending;
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
        this.onlineHandler = () => { this.isOnline = true; };
        this.offlineHandler = () => { this.isOnline = false; };
        window.addEventListener('online', this.onlineHandler);
        window.addEventListener('offline', this.offlineHandler);
    },

    beforeUnmount() {
        window.removeEventListener('online', this.onlineHandler);
        window.removeEventListener('offline', this.offlineHandler);
    },

    methods: {
        showProductDetails() {
            this.$productInfo?.show(this.item);
        },

        async addToFavorite() {
            if (this.favLoading) return;

            this.favLoading = true;
            this.favAnimating = true;

            try {
                const resp = await this.productStore.toggleProductInFavorites({
                    form: { id: this.item.id },
                });

                if (window.TenantUser?.settings) {
                    window.TenantUser.settings.favorites = resp.data.favorites;
                }

                this.$notify?.({
                    title: "Избранное",
                    text: this.inFav ? 'Добавлено в избранное' : 'Удалено из избранного',
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

        async incProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.addProductToCart(this.item.id);

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
            } finally {
                this.sending = false;
            }
        },

        async decProductCart() {
            if (this.sending) return;
            this.sending = true;

            try {
                await this.basketStore.removeProductFromCart(this.item.id);
            } catch (error) {
                console.error('Ошибка удаления:', error);
            } finally {
                this.sending = false;
            }
        },

        selectInCollection(product) {
            if (this.canSelect) {
                this.$emit('select-in-collection', product);
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },
    },
};
</script>

<style scoped>
/* ==========================================
   КАРточка товара
   ========================================== */
.product-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
    border-color: var(--bs-primary);
}

/* ==========================================
   ИЗОБРАЖЕНИЕ
   ========================================== */
.product-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--bs-secondary-bg, #f8f9fa);
    cursor: pointer;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image {
    transform: scale(1.08);
}

/* Overlay */
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

/* Бейдж скидки */
.discount-badge {
    padding: 4px 10px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Бейдж "Нет в наличии" */
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

/* Рейтинг */
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
}

.rating-badge i {
    color: #ffc107;
    font-size: 0.7rem;
}

/* Доставка */
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

/* Кнопка избранного */
.favorite-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.favorite-btn:hover {
    transform: scale(1.15);
    color: #dc3545;
}

.favorite-btn.is-favorite {
    color: #dc3545;
    background: white;
}

.favorite-btn.is-animating {
    animation: heartBeat 0.4s ease;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(0.9); }
    75% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* Индикатор загрузки */
.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
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

/* ==========================================
   ИНФОРМАЦИЯ О ТОВАРЕ
   ========================================== */
.product-info {
    padding: 12px 12px 8px;
    cursor: pointer;
    flex: 1;
}

.product-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin: 0 0 4px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-description {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ==========================================
   ДЕЙСТВИЕ
   ========================================== */
.product-action {
    padding: 0 12px 12px;
}

/* Кнопка "В корзину" */
.add-btn {
    width: 100%;
    padding: 10px 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.25);
    overflow: hidden;
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

/* Счётчик количества */
.quantity-stepper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-primary);
    border-radius: 12px;
    overflow: hidden;
}

.stepper-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
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
    gap: 2px;
    padding: 0 8px;
}

.value-number {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.value-unit {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    font-weight: 500;
}

/* Кнопка выбора (коллекция) */
.select-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.select-btn:hover:not(:disabled) {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.select-btn.selected {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.select-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Нет в наличии */
.out-of-stock {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--bs-secondary-bg, #f5f5f5);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
    font-weight: 500;
}

.out-of-stock i {
    font-size: 0.8rem;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
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
</style>
