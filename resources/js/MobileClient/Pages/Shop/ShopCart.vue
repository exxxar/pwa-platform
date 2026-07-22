<template>
    <div class="cart-page">

        <!-- ========================================== -->
        <!-- 1. ЗАГРУЗКА (Показываем, если НЕ гидратировано ИЛИ идёт загрузка) -->
        <!-- ========================================== -->
        <div v-if="!isHydrated || isLoading" class="cart-loading">
            <SkeletonLoader type="list" :count="5" />
        </div>

        <!-- ========================================== -->
        <!-- 2. УСПЕШНЫЙ ЗАКАЗ (Приоритет выше, чем у пустой корзины) -->
        <!-- ========================================== -->
        <transition v-else-if="orderJustPlaced" name="order-success">
            <div class="order-success-overlay" @click.self="dismissSuccessSheet">
                <div class="order-success-sheet">
                    <!-- Декоративный фон -->
                    <div class="success-bg">
                        <div class="success-circle circle-1"></div>
                        <div class="success-circle circle-2"></div>
                        <div class="success-circle circle-3"></div>
                    </div>

                    <!-- Контент -->
                    <div class="success-content">
                        <div class="success-icon-wrapper">
                            <div class="success-icon-ring"></div>
                            <div class="success-icon-ring ring-2"></div>
                            <div class="success-icon">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="confetti confetti-1"></div>
                            <div class="confetti confetti-2"></div>
                            <div class="confetti confetti-3"></div>
                            <div class="confetti confetti-4"></div>
                            <div class="confetti confetti-5"></div>
                        </div>

                        <h3 class="success-title">Заказ оформлен!</h3>
                        <p class="success-subtitle">
                            Ваш заказ <strong>#{{ lastOrderId }}</strong> успешно создан
                        </p>

                        <div class="order-info-card">
                            <div class="info-row">
                                <span class="info-label">Номер заказа</span>
                                <span class="info-value">#{{ lastOrderId }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Сумма</span>
                                <span class="info-value price">{{ formatPrice(lastOrderTotal) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Статус</span>
                                <span class="info-value status">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Принят
                                </span>
                            </div>
                        </div>

                        <div class="success-actions">
                            <button class="success-btn primary" @click="goToOrderChat">
                                <i class="fa-solid fa-comments"></i>
                                <span>Посмотреть свой заказ</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <button class="success-btn secondary" @click="dismissSuccessSheet">
                                <i class="fa-solid fa-store"></i>
                                <span>Продолжить покупки</span>
                            </button>
                        </div>

                        <div class="auto-close-hint">
                            <i class="fa-solid fa-clock"></i>
                            <span>Автоматическое закрытие через {{ autoCloseCountdown }} сек</span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- 3. ПУСТАЯ КОРЗИНА (Безопасная проверка на 0) -->
        <!-- ========================================== -->
        <div v-else-if="(cartTotalCount || 0) === 0" class="empty-cart">
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
        <!-- 4. ОФОРМЛЕНИЕ ЗАКАЗА (Все остальные случаи) -->
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

            <!-- ШАГ 1: КОРЗИНА -->
            <transition name="step-fade" mode="out-in">
                <div v-if="currentStep === 0" key="cart" class="step-content">
                    <CartProductList
                        :form-data="deliveryForm"
                        @select-prize="selectPrize"
                        @change-tab="changeTab"
                    >
                        <template #recommendation-list>
                            <ProductRecommendationList />
                        </template>
                    </CartProductList>
                </div>

                <!-- ШАГ 2: ОФОРМЛЕНИЕ -->
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

                <!-- ШАГ 3: СКРИНШОТ ОПЛАТЫ -->
                <div v-else-if="currentStep === 2" key="receipt" class="step-content">
                    <ScreenPaymentForm
                        v-model="deliveryForm"
                        @start-checkout="startCheckout"
                    />
                </div>

                <!-- ШАГ 4: СПОСОБ ОПЛАТЫ -->
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

            <!-- STICKY FOOTER -->
            <div class="checkout-footer">
                <div class="footer-content">
                    <div class="footer-summary">
                        <span class="summary-label">К оплате:</span>
                        <span class="summary-value">{{ formatPrice(totalToPay) }}</span>
                    </div>

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

        if (!basket.isHydrated.value) {
            basket.loadProductsInBasket();
        }

        return {
            basket_items: basket.basket_items,
            isLoading: basket.isLoading,
            isHydrated: basket.isHydrated,
            isSending: basket.isSending,
            cartTotalPrice: basket.cartTotalPrice,
            cartTotalCount: basket.cartTotalCount,
            isEmpty: basket.isEmpty,
            lastError: basket.lastError,
            addProduct: basket.addProduct,
            removeProduct: basket.removeProduct,
            removeProductCompletely: basket.removeProductCompletely,
            incrementQuantity: basket.incrementQuantity,
            decrementQuantity: basket.decrementQuantity,
            addCollection: basket.addCollection,
            incrementCollection: basket.incrementCollection,
            decrementCollection: basket.decrementCollection,
            removeCollection: basket.removeCollection,
            addComment: basket.addComment,
            isProductLoading: basket.isProductLoading,
            clearCart: basket.clearCart,
            startCheckoutAction: basket.startCheckout,
            createCheckoutLink: basket.createCheckoutLink,
            useWheelOfFortunePrize: basket.useWheelOfFortunePrize,
        };
    },

    data() {
        return {
            currentStep: 0,
            sending: false,

            // 🆕 Состояние после оформления заказа
            orderJustPlaced: false,
            lastOrderId: null,
            lastOrderTotal: 0,
            autoCloseCountdown: 10,
            autoCloseTimer: null,
            countdownTimer: null,

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
                    label: 'Оплата чеком',
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

        totalToPay() {
            return (this.cartTotalPrice || 0) + (this.deliveryForm.delivery_price || 0);
        },

        visibleSteps() {
            const steps = [...this.allSteps];
            const needReceiptStep = this.deliveryForm.payment_type === 2;
            if (!needReceiptStep) {
                steps.splice(2, 1);
            }
            return steps;
        },

        currentStepConfig() {
            return this.visibleSteps[this.currentStep] || this.allSteps[0];
        },

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

        actionButtonIcon() {
            if (this.currentStep === 0) return 'fa-solid fa-arrow-right';
            if (this.actionButtonImage) return null;
            if (this.currentStep === 1 && this.deliveryForm.payment_type === 2) return 'fa-solid fa-receipt';
            if (this.currentStep === 1 && this.deliveryForm.payment_type === 3) return 'fa-solid fa-hourglass';
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 2) return 'fa-solid fa-receipt';
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 3) return 'fa-solid fa-check';
            return 'fa-solid fa-arrow-right';
        },

        actionButtonImage() {
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 4) {
                return '/images/Т-Банк.png';
            }
            return null;
        },

        actionButtonClass() {
            if (this.currentStep === 3 && this.deliveryForm.payment_type === 4) {
                return 'tbank-btn';
            }
            return '';
        },

        isActionButtonEnabled() {
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

        cartTotalCount(newCount, oldCount) {
            // Игнорируем изменения, пока данные не загружены полностью
            if (!this.isHydrated) return;

            if (newCount === 0 && oldCount > 0 && this.orderJustPlaced) {
                // Корзина обнулилась после оформления — показываем окно
                this.showOrderSuccess();
            } else if (newCount === 0 && this.currentStep > 0 && !this.orderJustPlaced) {
                // Обычный случай — возврат на шаг 0
                this.currentStep = 0;
            }
        },
    },

    mounted() {
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
        if (this._scrollToBasketHandler) {
            window.removeEventListener("scroll-to-basket", this._scrollToBasketHandler);
        }
        if (this._switchToCartHandler) {
            document.removeEventListener('switch-to-cart', this._switchToCartHandler);
        }
        // 🆕 Очистка таймеров
        this.clearAutoCloseTimers();
    },

    methods: {
        goToStep(index) {
            if (index <= this.currentStep) {
                this.currentStep = index;
            }
        },

        changeTab(index) {
            this.currentStep = index;
        },

        goToCatalog() {
            this.$router.push({ name: 'Catalog' });
        },

        selectPrize(item) {
            this.deliveryForm.action_prize = item;
        },

        handleActionClick() {
            const paymentType = this.deliveryForm.payment_type;

            if (this.currentStep === 0) {
                this.currentStep = 1;
                return;
            }

            if (this.currentStep === 1 && paymentType === 2) {
                this.currentStep = 2;
                return;
            }

            if (this.currentStep === 1 && paymentType === 3 && this.settings.need_pay_after_call) {
                this.startCheckout();
                return;
            }

            if (this.currentStep === 2) {
                this.startCheckout();
                return;
            }

            if (this.currentStep === 3 && paymentType === 4) {
                this.startCheckout();
                return;
            }

            if (this.currentStep === 3 && paymentType === 2) {
                this.currentStep = 2;
                return;
            }

            if (this.currentStep === 3 && paymentType === 3 && this.settings.need_pay_after_call) {
                this.startCheckout();
                return;
            }
        },

        async startCheckout() {
            if (this.sending) return;

            const data = new FormData();
            data.append("display_type", this.settings.shop_display_type || 0);

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

            if (this.deliveryForm.image instanceof File) {
                data.append('photo', this.deliveryForm.image);
                data.delete("image");
            }

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

            this.sending = true;

            try {
                const response = await this.startCheckoutAction({ deliveryForm: data });

                // 🆕 Сохраняем данные заказа для всплывающего окна
                this.lastOrderId = response?.data?.order_id || response?.order_id || Date.now();
                this.lastOrderTotal = response?.data?.summary_price || response?.summary_price || 0 ;
                this.orderJustPlaced = true;

                this.$notify?.({
                    title: 'Заказ оформлен',
                    text: 'Инструкция отправлена вам в бот!',
                    type: 'success',
                });
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
        // 🆕 МЕТОДЫ ДЛЯ ВСПЛЫВАЮЩЕГО ОКНА
        // ==========================================

        /**
         * Показать окно успешного заказа
         */
        showOrderSuccess() {
            this.orderJustPlaced = true;
            this.autoCloseCountdown = 10;

            // Запускаем таймер обратного отсчёта
            this.countdownTimer = setInterval(() => {
                this.autoCloseCountdown--;
                if (this.autoCloseCountdown <= 0) {
                    this.dismissSuccessSheet();
                }
            }, 1000);

            // Автозакрытие через 10 секунд
            this.autoCloseTimer = setTimeout(() => {
                this.dismissSuccessSheet();
            }, 10000);
        },

        /**
         * Закрыть окно и продолжить покупки
         */
        dismissSuccessSheet() {
            this.orderJustPlaced = false;
            this.clearAutoCloseTimers();

            // Сбрасываем состояние
            this.currentStep = 0;
            this.lastOrderId = null;
            this.lastOrderTotal = 0;

            this.goToCatalog()
        },

        /**
         * Перейти к чату с заказом
         */
        goToOrderChat() {
            this.clearAutoCloseTimers();

            // Переход на страницу чата с заказом
            this.$router.push({
                name: 'Chat', // Замените на имя вашего роута
                params: { orderId: this.lastOrderId }
            }).catch(err => {
                console.error('Ошибка навигации:', err);
                // Fallback на главную
                this.$router.push({ name: 'Catalog' });
            });

            // Сбрасываем состояние
            this.orderJustPlaced = false;
            this.lastOrderId = null;
            this.lastOrderTotal = 0;
        },

        /**
         * Очистка таймеров
         */
        clearAutoCloseTimers() {
            if (this.autoCloseTimer) {
                clearTimeout(this.autoCloseTimer);
                this.autoCloseTimer = null;
            }
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 2,
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
$success-dark: #059669;
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
// 🆕 STEPPER (С адаптацией под глобальную тему)
// ==========================================
.checkout-stepper {
    background: var(--bs-body-bg, $card-bg);
    padding: 20px 16px;
    border-bottom: 1px solid var(--bs-border-color, $border);
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
    background: var(--bs-body-bg, $bg);
    border: 2px solid var(--bs-border-color, $border);
    color: var(--bs-secondary-color, $text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    z-index: 2;
}

// Активный шаг: берем главный цвет темы
.step-item.active .step-circle {
    background: var(--bs-primary, $primary);
    border-color: var(--bs-primary, $primary);
    color: #ffffff;
    // Используем RGB-вариант переменной для тени, чтобы она тоже меняла цвет
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 59, 130, 246), 0.4);
    transform: scale(1.1);
}

// Пройденный шаг: берем цвет успеха темы
.step-item.completed .step-circle {
    background: var(--bs-success, $success);
    border-color: var(--bs-success, $success);
    color: #ffffff;
}

.step-label {
    margin-top: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color, $text-muted);
    text-align: center;
    transition: color 0.3s ease;
}

.step-item.active .step-label {
    color: var(--bs-primary, $primary);
}

.step-item.completed .step-label {
    color: var(--bs-success, $success);
}

.step-connector {
    position: absolute;
    top: 18px;
    left: calc(50% + 20px);
    right: calc(-50% + 20px);
    height: 2px;
    background: var(--bs-border-color, $border);
    transition: background 0.3s ease;
    z-index: 1;

    &.filled {
        background: var(--bs-success, $success);
    }
}

// ==========================================
// ЗАГОЛОВОК ШАГА (тоже адаптируем иконку)
// ==========================================
.step-header {
    padding: 20px 16px 12px;
    text-align: center;
}

.step-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin: 0 0 4px 0;
    color: var(--bs-body-color, $text);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    i {
        color: var(--bs-primary, $primary); // 🆕 Иконка тоже меняет цвет
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
// 🆕 КОМПАКТНОЕ ОКНО УСПЕШНОГО ЗАКАЗА
// ==========================================
.order-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.order-success-sheet {
    position: relative;
    width: 100%;
    max-width: 380px;
    background: var(--bs-body-bg, #ffffff);
    border-radius: 24px;
    overflow: hidden;
    box-shadow:
        0 20px 60px rgba(102, 126, 234, 0.25),
        0 8px 24px rgba(0, 0, 0, 0.1);
}

// 🆕 Компактный декоративный фон
.success-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
}

.success-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 40%);
    pointer-events: none;
}

.success-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    animation: circleFloat 6s ease-in-out infinite;
}

.circle-1 {
    width: 80px;
    height: 80px;
    top: -30px;
    right: -20px;
}

.circle-2 {
    width: 60px;
    height: 60px;
    top: 20px;
    left: -15px;
    animation-delay: 2s;
}

.circle-3 {
    display: none; // Скрываем для компактности
}

@keyframes circleFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(10px, -10px) scale(1.1); }
}

// Контент
.success-content {
    position: relative;
    padding: 24px 20px 20px;
    text-align: center;
}

// 🆕 Компактная иконка
.success-icon-wrapper {
    position: relative;
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
}

.success-icon {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    box-shadow:
        0 6px 20px rgba(102, 126, 234, 0.5),
        inset 0 1px 1px rgba(255, 255, 255, 0.3);
    animation: iconBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 2;
}

@keyframes iconBounce {
    0% {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
    }
    100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

.success-icon-ring {
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid rgba(102, 126, 234, 0.4);
    animation: ringExpand 2s ease-out infinite;
}

.success-icon-ring.ring-2 {
    animation-delay: 1s;
    border-color: rgba(118, 75, 162, 0.4);
}

@keyframes ringExpand {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

// 🆕 Минимальные конфетти
.confetti {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 2px;
    animation: confettiFall 3s ease-in-out infinite;
}

.confetti-1 {
    top: 5%;
    left: 15%;
    background: #fbbf24;
    animation-delay: 0s;
}

.confetti-2 {
    top: 10%;
    right: 20%;
    background: #f093fb;
    animation-delay: 0.5s;
}

.confetti-3 {
    top: 0%;
    left: 40%;
    background: #667eea;
    animation-delay: 1s;
}

.confetti-4,
.confetti-5 {
    display: none; // Скрываем лишние
}

@keyframes confettiFall {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    50% {
        transform: translateY(15px) rotate(180deg);
        opacity: 0.6;
    }
}

// 🆕 Компактный заголовок
.success-title {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 50px 0 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: titleSlideIn 0.6s ease-out 0.3s backwards;
}

@keyframes titleSlideIn {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-subtitle {
    font-size: 0.85rem;
    color: var(--bs-secondary-color, #6b7280);
    margin: 0 0 16px;
    animation: subtitleFadeIn 0.6s ease-out 0.4s backwards;

    strong {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
    }
}

@keyframes subtitleFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

// 🆕 Компактная карточка информации
.order-info-card {
    background: var(--bs-secondary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e5e7eb);
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 16px;
    animation: cardSlideIn 0.6s ease-out 0.5s backwards;
}

@keyframes cardSlideIn {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.05));

    &:last-child {
        border-bottom: none;
    }
}

.info-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color, #6b7280);
}

.info-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color, #1f2937);

    &.price {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 0.95rem;
        font-weight: 800;
    }

    &.status {
        color: #667eea;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;

        i {
            font-size: 0.75rem;
            color: #667eea;
        }
    }
}

// 🆕 Компактные кнопки
.success-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
    animation: actionsSlideIn 0.6s ease-out 0.6s backwards;
}

@keyframes actionsSlideIn {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;

    &.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        i:last-child {
            transition: transform 0.3s ease;
        }

        &:hover i:last-child {
            transform: translateX(4px);
        }
    }

    &.secondary {
        background: transparent;
        color: var(--bs-body-color, #1f2937);
        border: 1.5px solid var(--bs-border-color, #e5e7eb);
        padding: 10px 18px;

        &:hover {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.04);
        }
    }
}

// 🆕 Компактная подсказка
.auto-close-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.7rem;
    color: var(--bs-secondary-color, #6b7280);
    animation: hintFadeIn 0.6s ease-out 0.7s backwards;

    i {
        color: #667eea;
        font-size: 0.7rem;
    }
}

@keyframes hintFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

// Анимации появления/исчезновения
.order-success-enter-active {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);

    .order-success-sheet {
        animation: sheetPopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
}

.order-success-leave-active {
    transition: all 0.3s ease-in;
}

.order-success-enter-from {
    opacity: 0;

    .order-success-sheet {
        transform: scale(0.8);
    }
}

.order-success-leave-to {
    opacity: 0;

    .order-success-sheet {
        transform: scale(0.9);
    }
}

@keyframes sheetPopIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 400px) {
    .order-success-overlay {
        padding: 16px;
    }

    .order-success-sheet {
        max-width: 100%;
    }

    .success-content {
        padding: 20px 16px 16px;
    }

    .success-title {
        font-size: 1.2rem;
    }

    .success-btn {
        font-size: 0.85rem;
        padding: 11px 16px;
    }
}
</style>
