<template>
    <Teleport to="body">
        <!-- Оверлей показывается только если isOpen === true -->
        <div class="cart-overlay" v-if="isOpen" @click.self="$emit('close')">
            <div class="cart-sidebar" :class="{ 'is-open': isOpen }">

                <!-- Шапка корзины -->
                <div class="cart-header">
                    <h3>{{ config?.title || 'Ваш заказ' }}</h3>
                    <button class="close-btn" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- 🆕 ОТЛАДОЧНЫЙ БЛОК (можно удалить потом, если всё заработает) -->
                <!-- <pre style="position: absolute; top: 60px; right: 10px; background: rgba(0,0,0,0.8); color: #0f0; padding: 10px; font-size: 10px; z-index: 100;">{{ cartItemsList }}</pre> -->

                <!-- Пустая корзина -->
                <!-- 🆕 Используем надежную проверку длины массива вместо basket.isEmpty -->
                <div v-if="isCartEmpty" class="cart-empty">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <p>{{ config?.emptyText || 'Корзина пуста' }}</p>
                    <button class="btn-primary" @click="$emit('close')">Перейти к меню</button>
                </div>

                <!-- Список товаров -->
                <!-- 🆕 Итерируемся по безопасному computed свойству cartItemsList -->
                <div v-else class="cart-items-list">

                    <div v-for="item in cartItemsList" :key="item.id || item.product_id" class="cart-item">
                        <img v-lazy="item.product?.images[0] || 'https://via.placeholder.com/80'"
                             :alt="item.product.name" class="item-image">

                        <div class="item-details">
                            <h4 class="item-name">{{ item.product.name }}</h4>
                            <div class="item-price">{{ formatPrice(item.product.price) }} ₽</div>

                            <div class="item-controls">
                                <button
                                    class="qty-btn"
                                    :disabled="basket.isProductLoading(item.product_id || item.product.id)"
                                    @click="basket.decrementQuantity(item.product_id || item.product.id)"
                                >
                                    <i v-if="basket.isProductLoading(item.product_id || item.product.id)"
                                       class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-minus"></i>
                                </button>

                                <span class="qty-value">{{ item.count || item.quantity || 0 }}</span>

                                <button
                                    class="qty-btn"
                                    :disabled="basket.isProductLoading(item.product_id || item.product.id)"
                                    @click="basket.incrementQuantity(item.product_id || item.product.id)"
                                >
                                    <i v-if="basket.isProductLoading(item.product_id || item.product.id)"
                                       class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-plus"></i>
                                </button>

                                <button
                                    class="remove-btn"
                                    @click="basket.removeProductCompletely(item.product_id || item.product.id)"
                                    title="Удалить"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Итого и кнопка оформления -->
                <div v-if="!isCartEmpty" class="cart-footer">


                    <div class="cart-total">
                        <span>{{ config?.totalText || 'Итого:' }}</span>
                        <span class="total-price">{{ formatPrice(cartTotalPrice) }} ₽</span>
                    </div>
                    <button
                        class="btn-checkout"
                        :disabled="isLoading"
                        @click="handleCheckout"
                    >
                        <span v-if="isLoading"
                              class="spinner-border spinner-border-sm me-2"></span>
                        {{ config?.checkoutText || 'Оформить заказ' }}
                    </button>
                </div>

            </div>
        </div>
    </Teleport>
</template>

<script>
import {useBasket} from '@/MobileClient/composables/useBasket';

export default {
    name: "ShopCart",
    props: {
        isOpen: {type: Boolean, default: false},
        config: {type: Object, default: () => ({})}
    },
    emits: ['close', 'checkout'],

    setup() {
        return {
            basket: useBasket()
        };
    },

    watch: {
        isOpen(newVal) {
            if (newVal) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    },

    computed: {
        isLoading() {
            return this.basket.isLoading.value || this.basket.isSending.value
        },
        // 🆕 КРИТИЧЕСКИ ВАЖНО: Гарантированно возвращаем обычный массив,
        // распутывая Ref, если Vue вдруг не сделал это автоматически в v-for
        cartItemsList() {
            const items = this.basket.basket_items;

            // Если это уже массив, возвращаем его
            if (Array.isArray(items)) {
                return items;
            }

            // Если это Ref-объект (из storeToRefs), достаем .value
            if (items && typeof items === 'object' && 'value' in items) {
                return items.value || [];
            }

            // Фоллбэк на пустой массив
            return [];
        },
        cartTotalPrice() {
            return this.basket.cartTotalPrice.value || 0
        },
        // 🆕 Надежная проверка на пустоту
        isCartEmpty() {
            return this.cartItemsList.length === 0;
        }
    },

    mounted() {
        // Загружаем корзину, если вдруг она не загрузилась в ShopLanding
        if (this.isCartEmpty && !this.basket.isLoading) {
            this.basket.loadProductsInBasket();
        }
    },

    methods: {
        formatPrice(value) {
            if (!value && value !== 0) return '0';
            return Number(value).toLocaleString('ru-RU');
        },

        async handleCheckout() {
            try {
                this.$emit('checkout', {
                    total: this.basket.cartTotalPrice,
                    items: this.cartItemsList // Отправляем гарантированно чистый массив
                });
            } catch (error) {
                console.error('Ошибка оформления:', error);
            }
        }
    }
};
</script>

<style lang="scss" scoped>
/* Твои стили остаются без изменений */
.cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 99999;
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: flex-end;
}

.cart-sidebar {
    width: 100%;
    max-width: 450px;
    height: 100vh;
    background: white;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);

    &.is-open {
        transform: translateX(0);
    }
}

.cart-header {
    padding: 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;

    h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark, #222);
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--gray, #888);
        transition: color 0.2s;

        &:hover {
            color: var(--dark, #222);
        }
    }
}

.cart-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px;
    color: var(--gray, #888);

    i {
        font-size: 4rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    p {
        margin-bottom: 24px;
        font-size: 1.1rem;
    }

    .btn-primary {
        background: var(--primary, #ff7a00);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;

        &:hover {
            background: var(--primary-dark, #e56f00);
        }
    }
}

.cart-items-list {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

.cart-item {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);

    .item-image {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .item-details {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .item-name {
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: var(--dark, #222);
    }

    .item-price {
        font-size: 0.95rem;
        color: var(--primary, #ff7a00);
        font-weight: 700;
        margin-bottom: 12px;
    }

    .item-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;

            &:hover:not(:disabled) {
                background: var(--light, #f8f9fa);
                border-color: var(--primary, #ff7a00);
                color: var(--primary, #ff7a00);
            }

            &:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        }

        .qty-value {
            font-weight: 700;
            min-width: 24px;
            text-align: center;
            color: var(--dark, #222);
        }

        .remove-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--gray, #888);
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.2s;

            &:hover {
                color: #ef4444;
            }
        }
    }
}

.cart-footer {
    padding: 24px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    background: var(--light, #f8f9fa);
    flex-shrink: 0;

    .cart-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--dark, #222);

        .total-price {
            font-size: 1.5rem;
            color: var(--primary, #ff7a00);
        }
    }

    .btn-checkout {
        width: 100%;
        padding: 16px;
        background: var(--primary, #ff7a00);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.2s;

        &:hover:not(:disabled) {
            background: var(--primary-dark, #e56f00);
            transform: translateY(-2px);
        }

        &:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
    }
}
</style>
