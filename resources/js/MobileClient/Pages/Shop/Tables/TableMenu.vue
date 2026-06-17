<template>
    <div class="shop-menu-page">

        <!-- Меню магазина -->
        <ShopMenu :settings="settings" />

        <!-- Нижняя панель корзины -->
        <div class="cart-bottom-bar">

            <!-- Кнопка корзины (если покупки доступны) -->
            <transition name="slide-up">
                <div v-if="canBuy" class="cart-button-wrapper">

                    <!-- Основная кнопка -->
                    <button
                        class="cart-button"
                        :class="{ 'has-items': cartTotalCount > 0 }"
                        @click="goToCart"
                    >
                        <div class="cart-left">
                            <div class="cart-icon-wrapper">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span v-if="cartTotalCount > 0" class="cart-badge">
                                    {{ cartTotalCount > 99 ? '99+' : cartTotalCount }}
                                </span>
                            </div>
                            <div class="cart-info">
                                <span class="cart-label">Ваш заказ</span>
                                <span class="cart-price">
                                    {{ formatPrice(cartTotalPrice) }}
                                    <small>₽</small>
                                </span>
                            </div>
                        </div>
                        <div class="cart-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </button>

                    <!-- Кнопка очистки (появляется при наличии товаров) -->
                    <transition name="fade-scale">
                        <button
                            v-if="cartTotalCount > 0"
                            class="clear-btn"
                            @click.stop="confirmClearCart"
                            title="Очистить корзину"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </transition>

                </div>
            </transition>

            <!-- Заглушка: покупки недоступны -->
            <transition name="slide-up">
                <div v-if="!canBuy" class="closed-notice">
                    <div class="closed-icon">
                        <i class="fa-solid fa-moon"></i>
                    </div>
                    <div class="closed-info">
                        <strong>Заведение закрыто</strong>
                        <span>Покупки временно недоступны</span>
                    </div>
                </div>
            </transition>

        </div>

        <!-- Модалка подтверждения очистки -->
        <transition name="modal-fade">
            <div v-if="showClearConfirm" class="modal-overlay" @click.self="showClearConfirm = false">
                <div class="confirm-modal">
                    <div class="confirm-icon danger">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h3>Очистить корзину?</h3>
                    <p>Все добавленные товары будут удалены. Это действие нельзя отменить.</p>
                    <div class="confirm-actions">
                        <button class="btn-secondary" @click="showClearConfirm = false">
                            Отмена
                        </button>
                        <button class="btn-danger" @click="clearCart">
                            <i class="fa-solid fa-trash-can"></i>
                            Очистить
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>

import ShopMenu from "@/MobileClient/Components/Shop/Menu/ShopMenu.vue";

export default {
    name: "ShopMenuPage",

    components: {
        ShopMenu,
    },

    data() {
        return {
            settings: null,
            settings_loaded: false,
            showClearConfirm: false,
        };
    },

    computed: {


        bot() {
            return window.currentBot || {};
        },

        canBuy() {
            // Проверка расписания
            if (window.isCorrectSchedule && !window.isCorrectSchedule(this.bot.company?.schedule)) {
                return true;
            }
            // Проверка флага работы + настройка "можно покупать после закрытия"
            const isWork = this.bot.company?.is_work ?? true;
            const canBuyAfterClosing = this.settings?.can_buy_after_closing ?? true;
            return isWork || canBuyAfterClosing;
        },
    },

    mounted() {
        if (this.bot.settings?.self_updated) {
            this.settings = this.bot.settings;
        } else {
            this.loadShopModuleData();
        }
        this.loadBasketData();
    },

    methods: {
        /**
         * Переход в корзину
         */
        goToCart() {
            this.$router.push({ name: 'TableCartV2' });
        },

        /**
         * Показать подтверждение очистки
         */
        confirmClearCart() {
            this.showClearConfirm = true;
        },

        /**
         * Очистка корзины
         */
        async clearCart() {
            try {
                await this.$store.dispatch("clearCart");
                this.showClearConfirm = false;

                this.$notify?.({
                    title: 'Корзина',
                    text: 'Корзина успешно очищена',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка очистки корзины:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось очистить корзину',
                    type: 'error',
                });
            }
        },

        /**
         * Загрузка данных корзины
         */
        async loadBasketData() {
            try {
                await this.$store.dispatch("loadProductsInBasket");
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        /**
         * Загрузка настроек магазина
         */
        async loadShopModuleData() {
            try {
                const resp = await this.$store.dispatch("loadShopModuleData");
                await this.$nextTick();

                if (!this.settings) {
                    this.settings = {};
                }

                if (resp) {
                    Object.keys(resp).forEach(key => {
                        if (key) {
                            this.settings[key] = resp[key];
                        }
                    });
                }

                this.settings_loaded = true;
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
                this.settings_loaded = true;
            }
        },

        /**
         * Форматирование цены
         */
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price || 0);
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$primary-light: #60a5fa;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.shop-menu-page {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 100px; // Отступ под нижнюю панель
}

// ==========================================
// НИЖНЯЯ ПАНЕЛЬ
// ==========================================
.cart-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px 16px;
    background: linear-gradient(to top, $card-bg 60%, transparent);
    z-index: 100;
}

.cart-button-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

// ==========================================
// КНОПКА КОРЗИНЫ
// ==========================================
.cart-button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    border: none;
    border-radius: 16px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba($primary, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba($primary, 0.4);
    }

    &:active {
        transform: translateY(0);
    }

    &.has-items {
        animation: cartPulse 0.5s ease;
    }
}

@keyframes cartPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.cart-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.cart-icon-wrapper {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.cart-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 11px;
    background: white;
    color: $primary;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    animation: badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes badgePop {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}

.cart-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.cart-label {
    font-size: 0.8rem;
    opacity: 0.9;
    font-weight: 500;
}

.cart-price {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1;

    small {
        font-size: 0.8rem;
        font-weight: 600;
        opacity: 0.7;
    }
}

.cart-arrow {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: transform 0.2s;

    .cart-button:hover & {
        transform: translateX(3px);
    }
}

// ==========================================
// КНОПКА ОЧИСТКИ
// ==========================================
.clear-btn {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: $card-bg;
    border: 1px solid $border;
    color: $danger;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;

    &:hover {
        background: $danger;
        border-color: $danger;
        color: white;
        transform: scale(1.05);
    }
}

// ==========================================
// ЗАГЛУШКА: ЗАВЕДЕНИЕ ЗАКРЫТО
// ==========================================
.closed-notice {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.closed-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.closed-info {
    flex: 1;

    strong {
        display: block;
        font-size: 1rem;
        color: $text;
        margin-bottom: 2px;
    }

    span {
        font-size: 0.85rem;
        color: $text-muted;
    }
}

// ==========================================
// МОДАЛКА ПОДТВЕРЖДЕНИЯ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.confirm-modal {
    background: $card-bg;
    border-radius: 20px;
    padding: 32px 24px 24px;
    max-width: 360px;
    width: 100%;
    text-align: center;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;

    &.danger {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.confirm-modal h3 {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: $text;
}

.confirm-modal p {
    font-size: 0.9rem;
    color: $text-muted;
    line-height: 1.5;
    margin: 0 0 24px 0;
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.btn-secondary,
.btn-danger {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-secondary {
    background: $bg;
    color: $text;
    border: 1px solid $border;

    &:hover {
        background: color.adjust($bg, $lightness: -3%);
    }
}

.btn-danger {
    background: $danger;
    color: white;

    &:hover {
        background:  color.adjust($danger, $lightness: -8%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba($danger, 0.3);
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(100%);
}

.fade-scale-enter-active,
.fade-scale-leave-active {
    transition: all 0.3s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .cart-button {
        padding: 12px 14px;
    }

    .cart-icon-wrapper {
        width: 42px;
        height: 42px;
        font-size: 1.1rem;
    }

    .cart-price {
        font-size: 1.2rem;
    }

    .clear-btn {
        width: 46px;
        height: 46px;
    }
}
</style>
