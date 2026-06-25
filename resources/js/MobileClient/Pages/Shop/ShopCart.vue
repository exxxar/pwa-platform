<template>
    <div class="cart-page">

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="isLoading && !isHydrated" class="cart-loading">
            <SkeletonLoader type="list" :count="5" />
        </div>

        <!-- ========================================== -->
        <!-- КОРЗИНА ПУСТАЯ -->
        <!-- ========================================== -->
        <div v-else-if="cartTotalCount === 0" class="empty-cart">
            <div class="empty-cart-content">
                <div class="empty-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h4 class="empty-title">Корзина пуста</h4>
                <p class="empty-text">
                    Добавьте товары из каталога, чтобы оформить заказ
                </p>
                <button class="empty-btn" @click="goToCatalog">
                    <i class="fa-solid fa-store"></i>
                    <span>Перейти в каталог</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОФОРМЛЕНИЕ ЗАКАЗА -->
        <!-- ========================================== -->
        <template v-else>

            <!-- STEPPER -->
            <div class="checkout-stepper">
                <div class="stepper-container">
                    <div
                        v-for="(step, index) in visibleSteps"
                        :key="index"
                        class="step-item"
                        :class="{
                            'active': currentStep === index,
                            'completed': currentStep > index
                        }"
                        @click="goToStep(index)"
                    >
                        <div class="step-circle">
                            <i v-if="currentStep > index" class="fa-solid fa-check"></i>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="step-label">{{ step.label }}</div>

                        <div
                            v-if="index < visibleSteps.length - 1"
                            class="step-connector"
                            :class="{ 'filled': currentStep > index }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Заголовок текущего шага -->
            <div class="step-header">
                <h5 class="step-title">
                    <i :class="currentStepConfig.icon"></i>
                    <span>{{ currentStepConfig.title }}</span>
                </h5>
                <p class="step-subtitle">{{ currentStepConfig.subtitle }}</p>
            </div>

            <!-- ========================================== -->
            <!-- ШАГ 1: КОРЗИНА -->
            <!-- ========================================== -->
            <transition name="step-fade" mode="out-in">
                <div v-if="currentStep === 0" key="cart" class="step-content">
                    <CartProductList
                        :form-data="deliveryForm"
                        @select-prize="selectPrize"
                        @change-tab="changeTab"
                    >
                        <template #upper-text>
                            <div class="cart-header-info">
                                <div class="cart-header-icon">
                                    <i class="fa-solid fa-basket-shopping"></i>
                                </div>
                                <div>
                                    <h6 class="cart-header-title">Ваш заказ</h6>
                                    <p class="cart-header-subtitle">
                                        {{ cartTotalCount }}
                                        {{ pluralize(cartTotalCount, 'товар', 'товара', 'товаров') }}
                                        на сумму {{ formatPrice(cartTotalPrice) }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template #recommendation-list>
                            <ProductRecommendationList />
                        </template>
                    </CartProductList>
                </div>

                <!-- ========================================== -->
                <!-- ШАГ 2: ОФОРМЛЕНИЕ -->
                <!-- ========================================== -->
                <div v-else-if="currentStep === 1" key="checkout" class="step-content">
                    <CheckoutProductForm
                        v-if="settings.shop_display_type === 0"
                        v-model="deliveryForm"
                        @start-checkout="startCheckout"
                        @change-tab="changeTab"
                    />
                    <CheckoutNonFoodGoodsForm
                        v-else
                        v-model="deliveryForm"
                        @start-checkout="startCheckout"
                        @change-tab="changeTab"
                    />
                </div>

                <!-- ========================================== -->
                <!-- ШАГ 3: СКРИНШОТ ОПЛАТЫ -->
                <!-- ========================================== -->
                <div v-else-if="currentStep === 2" key="receipt" class="step-content">
                    <ScreenPaymentForm
                        v-model="deliveryForm"
                        @start-checkout="startCheckout"
                    />
                </div>

                <!-- ========================================== -->
                <!-- ШАГ 4: СПОСОБ ОПЛАТЫ -->
                <!-- ========================================== -->
                <div v-else-if="currentStep === 3" key="payment" class="step-content">
                    <div class="payment-section">
                        <div class="section-card">
                            <div class="section-header">
                                <i class="fa-solid fa-credit-card"></i>
                                <h6>Способ оплаты</h6>
                            </div>
                            <PaymentTypes v-model="deliveryForm" />
                        </div>

                        <div class="section-card mt-3">
                            <Summary v-model="deliveryForm" />
                        </div>

                        <button
                            type="button"
                            class="back-to-cart-btn mt-3"
                            @click="currentStep = 0"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Вернуться в корзину</span>
                        </button>
                    </div>
                </div>
            </transition>

            <!-- ========================================== -->
            <!-- STICKY FOOTER -->
            <!-- ========================================== -->
            <div class="checkout-footer">
                <div class="footer-content">

                    <!-- Сводка -->
                    <div class="footer-summary">
                        <span class="summary-label">К оплате:</span>
                        <span class="summary-value">{{ formatPrice(totalToPay) }}</span>
                    </div>

                    <!-- Кнопка действия -->
                    <button
                        class="action-btn"
                        :class="actionButtonClass"
                        :disabled="sending || !isActionButtonEnabled"
                        @click="handleActionClick"
                    >
                        <span v-if="sending" class="btn-spinner"></span>
                        <template v-else>
                            <i v-if="actionButtonIcon" :class="actionButtonIcon"></i>
                            <span v-if="actionButtonImage">
                                <span>{{ actionButtonText }}</span>
                                <img :src="actionButtonImage" alt="Т-Банк" class="tbank-logo">
                            </span>
                            <span v-else>{{ actionButtonText }}</span>
                        </template>
                    </button>

                </div>
            </div>

        </template>
    </div>
</template>

<script>
import CartProductList from "@/MobileClient/Components/Cart/CartProductList.vue";
import CheckoutProductForm from "@/MobileClient/Components/Cart/CheckoutProductForm.vue";
import CheckoutNonFoodGoodsForm from "@/MobileClient/Components/Cart/CheckoutNonFoodGoodsForm.vue";
import ScreenPaymentForm from "@/MobileClient/Components/Cart/ScreenPaymentForm.vue";
import ProductRecommendationList from "@/MobileClient/Components/Shop/ProductRecommendationList.vue";
import Summary from "@/MobileClient/Components/Cart/Summary.vue";
import PaymentTypes from "@/MobileClient/Components/Cart/PaymentTypes.vue";
import SkeletonLoader from '@/MobileClient/Components/Common/SkeletonLoader.vue';
import { useBasket } from '@/MobileClient/Composables/useBasket.js';

export default {
    name: "CartPage",

    components: {
        CartProductList,
        CheckoutProductForm,
        CheckoutNonFoodGoodsForm,
        ScreenPaymentForm,
        ProductRecommendationList,
        Summary,
        PaymentTypes,
        SkeletonLoader,
    },

    props: {
        type: { type: String, default: null },
    },

    setup() {
        const basket = useBasket();

        // Загружаем корзину при первом входе
        if (!basket.isHydrated.value) {
            basket.loadProductsInBasket();
        }

        return {
            basket_items: basket.basket_items,
            isLoading: basket.isLoading,
            isHydrated: basket.isHydrated,
            cartTotalPrice: basket.cartTotalPrice,
            lastError: basket.lastError,
            addProduct: basket.addProduct,
            removeProduct: basket.removeProduct,
            isProductLoading: basket.isProductLoading,
            clearCart: basket.clearCart,
            startCheckoutAction: basket.startCheckout,
            createCheckoutLink: basket.createCheckoutLink,
        };
    },

    data() {
        return {
            currentStep: 0,
            sending: false,

            // Все шаги оформления
            allSteps: [
                {
                    label: 'Корзина',
                    title: 'Ваш заказ',
                    subtitle: 'Проверьте товары перед оформлением',
                    icon: 'fa-solid fa-cart-shopping',
                },
                {
                    label: 'Данные',
                    title: 'Данные доставки',
                    subtitle: 'Укажите адрес и контактные данные',
                    icon: 'fa-solid fa-truck',
                },
                {
                    label: 'Чек',
                    title: 'Подтверждение оплаты',
                    subtitle: 'Загрузите скриншот перевода',
                    icon: 'fa-solid fa-receipt',
                },
                {
                    label: 'Оплата',
                    title: 'Способ оплаты',
                    subtitle: 'Выберите удобный способ',
                    icon: 'fa-solid fa-credit-card',
                },
            ],

            deliveryForm: {
                name: null,
                phone: null,
                address: null,
                location_id: null,
                lng: null,
                lat: null,
                discount: 0,
                cdek: {
                    tariff: null,
                    to: { region: null, city: null, office: null, address: null },
                },
                city: null,
                street: null,
                building: null,
                flat_number: null,
                entrance_number: null,
                floor_number: null,
                table_number: null,
                info: null,
                need_pickup: false,
                pick_up_type: 1,
                has_disability: false,
                use_cashback: false,
                disabilities: [],
                money: null,
                cash: true,
                payment_type: 2,
                persons: 1,
                time: null,
                when_ready: true,
                image: null,
                image_info: null,
                delivery_price: 0,
                distance: 0,
                delivery_details: [],
                allergy: null,
                action_prize: null,
            },

            // Обработчики глобальных событий (для корректного удаления)
            _scrollToBasketHandler: null,
            _switchToCartHandler: null,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        cartTotalCount() {
            return this.basket_items?.reduce((sum, item) => {
                return sum + (item.product?.is_weight_product ? 1 : item.count);
            }, 0) || 0;
        },

        /**
         * Итоговая сумма с учётом доставки
         */
        totalToPay() {
            return (this.cartTotalPrice || 0) + (this.deliveryForm.delivery_price || 0);
        },

        /**
         * Видимые шаги (скрываем шаг "Чек", если не нужен скриншот)
         */
        visibleSteps() {
            const steps = [...this.allSteps];

            // Если не нужна оплата переводом — скрываем шаг "Чек"
            const needReceiptStep = this.deliveryForm.payment_type === 2;
            if (!needReceiptStep) {
                steps.splice(2, 1); // Удаляем шаг с индексом 2 (Чек)
            }

            return steps;
        },

        /**
         * Конфигурация текущего шага
         */
        currentStepConfig() {
            return this.visibleSteps[this.currentStep] || this.allSteps[0];
        },

        /**
         * Текст кнопки действия в футере
         */
        actionButtonText() {
            const paymentType = this.deliveryForm.payment_type;

            if (this.currentStep === 0) return 'Оформить заказ';
            if (this.currentStep === 1 && paymentType === 2) return 'Оплатить переводом';
            if (this.currentStep === 1 && paymentType === 3 && this.settings.need_pay_after_call) return 'Оформить';
            if (this.currentStep === 2) return 'Подтвердить оплату';
            if (this.currentStep === 3 && paymentType === 4) return 'Оплатить через Т-Банк';
            if (this.currentStep === 3 && paymentType === 2) return 'Оплатить переводом';
            if (this.currentStep === 3 && paymentType === 3 && this.settings.need_pay_after_call) return 'Подтвердить заказ';

            return 'Далее';
        },

        /**
         * Иконка кнопки действия
         */
        actionButtonIcon() {
            if (this.currentStep === 0) return 'fa-solid fa-arrow-right';
            if (this.actionButtonImage) return null; // Для Т-Банка используем картинку
            if (this.currentStep === 1 && this.deliveryForm.payment_type === 2) return 'fa-solid fa-receipt';
            if (this.currentStep === 1 && this.deliveryForm.payment_type === 3) return 'fa-solid fa-hourglass';
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 2) return 'fa-solid fa-receipt';
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 3) return 'fa-solid fa-check';
            return 'fa-solid fa-arrow-right';
        },

        /**
         * Картинка для кнопки (Т-Банк)
         */
        actionButtonImage() {
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 4) {
                return '/images/Т-Банк.png';
            }
            return null;
        },

        /**
         * CSS-класс кнопки действия
         */
        actionButtonClass() {
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 4) {
                return 'tbank-btn';
            }
            return '';
        },

        /**
         * Активна ли кнопка действия
         */
        isActionButtonEnabled() {
            // На шаге корзины — всегда активна, если есть товары
            if (this.currentStep === 0) return this.cartTotalCount > 0;
            return true;
        },
    },

    watch: {
        currentStep() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        'deliveryForm.need_pickup'(value) {
            if (value) {
                this.deliveryForm.delivery_price = 0;
                this.deliveryForm.distance = 0;
            }
        },

        'deliveryForm.cash'(value) {
            if (!value) this.deliveryForm.money = null;
        },

        'deliveryForm.has_disability'() {
            this.deliveryForm.disabilities = [];
            this.deliveryForm.allergy = null;
        },

        /**
         * Если корзина опустела во время оформления — возвращаем на шаг 0
         */
        cartTotalCount(newCount) {
            if (newCount === 0 && this.currentStep > 0) {
                this.currentStep = 0;
            }
        },
    },

    mounted() {
        // Регистрируем глобальные обработчики с сохранением ссылок
        this._scrollToBasketHandler = () => {
            this.currentStep = 1;
        };
        this._switchToCartHandler = () => {
            this.currentStep = 0;
        };

        window.addEventListener("scroll-to-basket", this._scrollToBasketHandler);
        document.addEventListener('switch-to-cart', this._switchToCartHandler);
    },

    beforeUnmount() {
        // Корректно удаляем обработчики (предотвращаем утечки памяти)
        if (this._scrollToBasketHandler) {
            window.removeEventListener("scroll-to-basket", this._scrollToBasketHandler);
        }
        if (this._switchToCartHandler) {
            document.removeEventListener('switch-to-cart', this._switchToCartHandler);
        }
    },

    methods: {
        // ==========================================
        // НАВИГАЦИЯ
        // ==========================================

        /**
         * Переход к шагу (только на предыдущие или текущий)
         */
        goToStep(index) {
            if (index <= this.currentStep) {
                this.currentStep = index;
            }
        },

        /**
         * Изменение шага из дочерних компонентов
         */
        changeTab(index) {
            this.currentStep = index;
        },

        /**
         * Переход в каталог
         */
        goToCatalog() {
            this.$router.push({ name: 'Catalog' });
        },

        /**
         * Выбор приза
         */
        selectPrize(item) {
            this.deliveryForm.action_prize = item;
        },

        // ==========================================
        // ОБРАБОТКА КНОПКИ ДЕЙСТВИЯ
        // ==========================================

        /**
         * Универсальный обработчик клика по кнопке действия
         */
        handleActionClick() {
            const paymentType = this.deliveryForm.payment_type;

            // Шаг 0: Переход к оформлению
            if (this.currentStep === 0) {
                this.currentStep = 1;
                return;
            }

            // Шаг 1: Оплата переводом → переход к скриншоту
            if (this.currentStep === 1 && paymentType === 2) {
                this.currentStep = 2;
                return;
            }

            // Шаг 1: Оплата после звонка → оформление
            if (this.currentStep === 1 && paymentType === 3 && this.settings.need_pay_after_call) {
                this.startCheckout();
                return;
            }

            // Шаг 2: Подтверждение скриншота
            if (this.currentStep === 2) {
                this.startCheckout();
                return;
            }

            // Шаг 3: Т-Банк → оплата
            if (this.currentStep === 3 && paymentType === 4) {
                this.startCheckout();
                return;
            }

            // Шаг 3: Перевод → к скриншоту
            if (this.currentStep === 3 && paymentType === 2) {
                this.currentStep = 2;
                return;
            }

            // Шаг 3: Подтверждение после звонка
            if (this.currentStep === 3 && paymentType === 3 && this.settings.need_pay_after_call) {
                this.startCheckout();
                return;
            }
        },

        // ==========================================
        // ОФОРМЛЕНИЕ ЗАКАЗА
        // ==========================================

        /**
         * Начало оформления заказа
         */
        async startCheckout() {
            if (this.sending) return;

            const data = new FormData();
            data.append("display_type", this.settings.shop_display_type || 0);

            // Сериализация формы
            Object.keys(this.deliveryForm).forEach(key => {
                const item = this.deliveryForm[key];
                if (item === null || item === undefined) {
                    data.append(key, '');
                } else if (typeof item === 'object' && !(item instanceof File)) {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });

            if (this.type) data.append("type", this.type);
            if (this.settings.need_automatic_delivery_request) {
                data.append("need_automatic_delivery_request", true);
            }

            // Фото чека (если это File, а не строка)
            if (this.deliveryForm.image instanceof File) {
                data.append('photo', this.deliveryForm.image);
                data.delete("image");
            }

            // Оплата картой онлайн — создаём ссылку на оплату
            if (this.deliveryForm.payment_type === 0 && this.settings.can_use_card) {
                try {
                    this.sending = true;
                    const response = await this.createCheckoutLink({ deliveryForm: data });
                    if (response?.url) {
                        window.location.href = response.url;
                    }
                } catch (error) {
                    console.error('Ошибка создания ссылки на оплату:', error);
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Не удалось создать ссылку на оплату',
                        type: 'error',
                    });
                } finally {
                    this.sending = false;
                }
                return;
            }

            // Стандартное оформление
            this.sending = true;

            try {
                await this.startCheckoutAction({ deliveryForm: data });

                this.$notify?.({
                    title: 'Заказ оформлен',
                    text: 'Инструкция отправлена вам в бот!',
                    type: 'success',
                });

                // Корзина уже очищена в Pinia action
            } catch (error) {
                console.error('Ошибка оформления заказа:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось оформить заказ. Попробуйте позже.',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

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

<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.cart-page {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 120px;
}

.cart-loading {
    padding: 20px 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-cart {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.empty-cart-content {
    text-align: center;
    max-width: 320px;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.empty-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 24px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-title {
    font-weight: 700;
    font-size: 1.4rem;
    margin: 0 0 8px 0;
    color: $text;
}

.empty-text {
    font-size: 0.95rem;
    color: $text-muted;
    margin: 0 0 28px 0;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 32px;
    background: linear-gradient(135deg, $primary 0%, lighten($primary, 10%) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($primary, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }
}

// ==========================================
// STEPPER
// ==========================================
.checkout-stepper {
    background: $card-bg;
    padding: 20px 16px;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(10px);
}

.stepper-container {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 500px;
    margin: 0 auto;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
    cursor: pointer;
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: 2px solid $border;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    z-index: 2;
}

.step-item.active .step-circle {
    background: $primary;
    border-color: $primary;
    color: white;
    box-shadow: 0 4px 12px rgba($primary, 0.4);
    transform: scale(1.1);
}

.step-item.completed .step-circle {
    background: $success;
    border-color: $success;
    color: white;
}

.step-label {
    margin-top: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    color: $text-muted;
    text-align: center;
    transition: color 0.3s ease;
}

.step-item.active .step-label {
    color: $primary;
}

.step-item.completed .step-label {
    color: $success;
}

.step-connector {
    position: absolute;
    top: 18px;
    left: calc(50% + 20px);
    right: calc(-50% + 20px);
    height: 2px;
    background: $border;
    transition: background 0.3s ease;
    z-index: 1;

    &.filled {
        background: $success;
    }
}

// ==========================================
// ЗАГОЛОВОК ШАГА
// ==========================================
.step-header {
    padding: 20px 16px 12px;
    text-align: center;
}

.step-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin: 0 0 4px 0;
    color: $text;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    i {
        color: $primary;
    }
}

.step-subtitle {
    margin: 0;
    font-size: 0.85rem;
    color: $text-muted;
}

// ==========================================
// КОНТЕНТ ШАГА
// ==========================================
.step-content {
    padding: 0px;
}

// Переходы между шагами
.step-fade-enter-active,
.step-fade-leave-active {
    transition: all 0.3s ease;
}

.step-fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.step-fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

// Заголовок корзины
.cart-header-info {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: rgba($primary, 0.05);
    border-radius: 14px;
}

.cart-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.cart-header-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: $text;
}

.cart-header-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: $text-muted;
}

// Секции
.section-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 16px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    color: $primary;

    i {
        font-size: 1.1rem;
    }

    h6 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
    }
}

.back-to-cart-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: transparent;
    border: 2px solid $border;
    border-radius: 12px;
    color: $text;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.03);
    }
}

// ==========================================
// STICKY FOOTER
// ==========================================
.checkout-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: $card-bg;
    border-top: 1px solid $border;
    padding: 12px 16px;
    z-index: 1000;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(10px);
}

.footer-content {
    max-width: 600px;
    margin: 0 auto;
}

.footer-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 0 4px;
}

.summary-label {
    font-size: 0.85rem;
    color: $text-muted;
}

.summary-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: $primary;
}

.action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, $primary 0%, lighten($primary, 10%) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    // Т-Банк
    &.tbank-btn {
        background: linear-gradient(135deg, #ffdd2d 0%, #ffcc00 100%);
        color: #1a1a1a;
        box-shadow: 0 4px 16px rgba(255, 221, 45, 0.4);

        &:hover:not(:disabled) {
            box-shadow: 0 8px 24px rgba(255, 221, 45, 0.5);
        }
    }
}

.tbank-logo {
    width: 80px;
    height: auto;
    object-fit: contain;
    margin-left: 8px;
}

.btn-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .step-label {
        font-size: 0.65rem;
    }

    .step-circle {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    .step-title {
        font-size: 1.1rem;
    }

    .action-btn {
        padding: 14px 20px;
        font-size: 0.95rem;
    }
}
</style>
