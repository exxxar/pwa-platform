<template>
    <div class="cart-page pb-5">

        <!-- ========================================== -->
        <!-- КОРЗИНА ПУСТАЯ -->
        <!-- ========================================== -->
        <div v-if="cartTotalCount === 0" class="empty-cart">
            <div class="empty-cart-content">
                <div class="empty-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h4 class="empty-title">Корзина пуста</h4>
                <p class="empty-text">
                    Добавьте товары из каталога, чтобы оформить заказ
                </p>
                <button class="empty-btn" @click="goToCatalog">
                    <i class="fa-solid fa-store me-2"></i>
                    Перейти в каталог
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОФОРМЛЕНИЕ ЗАКАЗА -->
        <!-- ========================================== -->
        <template v-else>

            <!-- STEPPER (Прогресс оформления) -->
            <div class="checkout-stepper">
                <div class="stepper-container">
                    <div
                        v-for="(step, index) in steps"
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

                        <!-- Линия-коннектор -->
                        <div
                            v-if="index < steps.length - 1"
                            class="step-connector"
                            :class="{ 'filled': currentStep > index }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Заголовок текущего шага -->
            <div class="step-header">
                <h5 class="step-title">
                    <i :class="steps[currentStep].icon" class="me-2"></i>
                    {{ steps[currentStep].title }}
                </h5>
                <p class="step-subtitle">{{ steps[currentStep].subtitle }}</p>
            </div>

            <!-- ========================================== -->
            <!-- ШАГ 1: КОРЗИНА -->
            <!-- ========================================== -->
            <div v-if="currentStep === 0" class="step-content">
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
            <div v-if="currentStep === 1" class="step-content">
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
            <div v-if="currentStep === 2" class="step-content">
                <ScreenPaymentForm
                    v-model="deliveryForm"
                    @start-checkout="startCheckout"
                />
            </div>

            <!-- ========================================== -->
            <!-- ШАГ 4: СПОСОБ ОПЛАТЫ -->
            <!-- ========================================== -->
            <div v-if="currentStep === 3" class="step-content">
                <div class="payment-section">

                    <!-- Способы оплаты -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fa-solid fa-credit-card"></i>
                            <h6>Способ оплаты</h6>
                        </div>
                        <PaymentTypes v-model="deliveryForm" />
                    </div>

                    <!-- Итого -->
                    <div class="section-card mt-3">
                        <Summary v-model="deliveryForm" />
                    </div>

                    <!-- Кнопка "Вернуться в корзину" -->
                    <button
                        type="button"
                        class="back-to-cart-btn mt-3"
                        @click="currentStep = 0"
                    >
                        <i class="fa-solid fa-arrow-left me-2"></i>
                        Вернуться в корзину
                    </button>

                </div>
            </div>

            <!-- ========================================== -->
            <!-- STICKY FOOTER С КНОПКОЙ ДЕЙСТВИЯ -->
            <!-- ========================================== -->
            <div class="checkout-footer">
                <div class="footer-content">

                    <!-- Краткая сводка -->
                    <div class="footer-summary">
                        <span class="summary-label">К оплате:</span>
                        <span class="summary-value">{{ formatPrice(cartTotalPrice) }}</span>
                    </div>

                    <!-- Кнопка действия (зависит от шага) -->

                    <!-- Шаг 0: Перейти к оформлению -->
                    <button
                        v-if="currentStep === 0"
                        class="action-btn"
                        @click="currentStep = 1"
                    >
                        <span>Оформить заказ</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    <!-- Шаг 1: Оплатить переводом -->
                    <button
                        v-if="currentStep === 1 && deliveryForm.payment_type === 2"
                        class="action-btn"
                        @click="currentStep = 2"
                    >
                        <i class="fa-solid fa-receipt me-2"></i>
                        <span>Оплатить переводом</span>
                    </button>

                    <!-- Шаг 1: Оформить (после звонка) -->
                    <button
                        v-if="currentStep === 1 && deliveryForm.payment_type === 3 && settings.need_pay_after_call"
                        class="action-btn"
                        @click="startCheckout"
                    >
                        <i class="fa-solid fa-hourglass me-2"></i>
                        <span>Оформить</span>
                    </button>

                    <!-- Шаг 3: Оплатить через Т-Банк -->
                    <button
                        v-if="currentStep === 3 && deliveryForm.payment_type === 4"
                        class="action-btn tbank-btn"
                        @click="startCheckout"
                    >
                        <span>Оплатить через</span>
                        <img
                            src="/images/Т-Банк.png"
                            alt="Т-Банк"
                            class="tbank-logo"
                        >
                    </button>

                    <!-- Шаг 3: Оплатить переводом -->
                    <button
                        v-if="currentStep === 3 && deliveryForm.payment_type === 2"
                        class="action-btn"
                        @click="currentStep = 2"
                    >
                        <i class="fa-solid fa-receipt me-2"></i>
                        <span>Оплатить переводом</span>
                    </button>

                    <!-- Шаг 3: Оформить -->
                    <button
                        v-if="currentStep === 3 && deliveryForm.payment_type === 3 && settings.need_pay_after_call"
                        class="action-btn"
                        @click="startCheckout"
                    >
                        <i class="fa-solid fa-check me-2"></i>
                        <span>Подтвердить заказ</span>
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
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";

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
    },

    props: {
        type: { type: String, default: null },
    },

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            currentStep: 0,
            sending: false,

            // Шаги оформления
            steps: [
                {
                    label: 'Корзина',
                    title: 'Ваш заказ',
                    subtitle: 'Проверьте товары перед оформлением',
                    icon: 'fa-solid fa-cart-shopping'
                },
                {
                    label: 'Данные',
                    title: 'Данные доставки',
                    subtitle: 'Укажите адрес и контактные данные',
                    icon: 'fa-solid fa-truck'
                },
                {
                    label: 'Чек',
                    title: 'Подтверждение оплаты',
                    subtitle: 'Загрузите скриншот перевода',
                    icon: 'fa-solid fa-receipt'
                },
                {
                    label: 'Оплата',
                    title: 'Способ оплаты',
                    subtitle: 'Выберите удобный способ',
                    icon: 'fa-solid fa-credit-card'
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
            return this.basketStore.cartTotalCount || 0;
        },

        cartTotalPrice() {
            return this.basketStore.cartTotalPrice || 0;
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
    },

    mounted() {
        this.loadBasketData();

        window.addEventListener("scroll-to-basket", () => {
            this.currentStep = 1;
        });

        document.addEventListener('switch-to-cart', () => {
            this.currentStep = 0;
        });
    },

    methods: {
        goToStep(index) {
            // Можно переходить только на предыдущие шаги или текущий
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

        async loadBasketData() {
            try {
                await this.basketStore.loadProductsInBasket({
                    dataObject: { search: '', categories: '' },
                    page: 0,
                    size: 12,
                });
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        async startCheckout() {
            const data = new FormData();
            data.append("display_type", this.settings.shop_display_type);

            Object.keys(this.deliveryForm).forEach(key => {
                const item = this.deliveryForm[key] || '';
                if (typeof item === 'object') {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });

            if (this.type) data.append("type", this.type);
            data.append("need_automatic_delivery_request", this.settings.need_automatic_delivery_request);

            // Оплата картой онлайн
            if (this.deliveryForm.payment_type === 0 && this.settings.can_use_card) {
                try {
                    // TODO: Замени на Pinia action
                    // const resp = await this.basketStore.createCheckoutLink(data);
                } catch (error) {
                    console.error('Ошибка создания ссылки:', error);
                }
                return;
            }

            // Фото чека
            if (this.deliveryForm.image && typeof this.deliveryForm.image !== "string") {
                data.append('photo', this.deliveryForm.image);
                data.delete("image");
            }

            this.sending = true;

            try {
                // TODO: Замени на Pinia action
                // const response = await this.basketStore.startCheckout(data);

                // Имитация запроса
                await new Promise(resolve => setTimeout(resolve, 1500));

                this.$notify?.({
                    title: "Заказ оформлен",
                    text: "Инструкция отправлена вам в бот!",
                    type: "success",
                });

                // Очистка корзины
                await this.basketStore.clearCart();

                // Редирект на оплату если есть
                // if (response.url) window.location.href = response.url;

            } catch (error) {
                console.error('Ошибка оформления:', error);
                this.$notify?.({
                    title: "Ошибка",
                    text: "Не удалось оформить заказ. Попробуйте позже.",
                    type: "error",
                });
            } finally {
                this.sending = false;
            }
        },

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

<style scoped>
.cart-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px; /* Отступ под sticky footer */
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
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
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.empty-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
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
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    margin-bottom: 28px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

/* ==========================================
   STEPPER (ПРОГРЕСС ОФОРМЛЕНИЯ)
   ========================================== */
.checkout-stepper {
    background: var(--bs-body-bg);
    padding: 20px 16px;
    border-bottom: 1px solid var(--bs-border-color);
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
    background: var(--bs-secondary-bg);
    border: 2px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    z-index: 2;
}

.step-item.active .step-circle {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
    transform: scale(1.1);
}

.step-item.completed .step-circle {
    background: #198754;
    border-color: #198754;
    color: white;
}

.step-label {
    margin-top: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-align: center;
    transition: color 0.3s ease;
}

.step-item.active .step-label {
    color: var(--bs-primary);
}

.step-item.completed .step-label {
    color: #198754;
}

/* Коннектор между шагами */
.step-connector {
    position: absolute;
    top: 18px;
    left: calc(50% + 20px);
    right: calc(-50% + 20px);
    height: 2px;
    background: var(--bs-border-color);
    transition: background 0.3s ease;
    z-index: 1;
}

.step-connector.filled {
    background: #198754;
}

/* ==========================================
   ЗАГОЛОВОК ШАГА
   ========================================== */
.step-header {
    padding: 20px 16px 12px;
    text-align: center;
}

.step-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin: 0 0 4px 0;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-title i {
    color: var(--bs-primary);
}

.step-subtitle {
    margin: 0;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КОНТЕНТ ШАГА
   ========================================== */
.step-content {
    padding: 0 16px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Заголовок корзины */
.cart-header-info {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-radius: 14px;
    margin-bottom: 16px;
}

.cart-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--bs-primary);
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
    color: var(--bs-body-color);
}

.cart-header-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* Секции в шаге оплаты */
.section-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    color: var(--bs-primary);
}

.section-header i {
    font-size: 1.1rem;
}

.section-header h6 {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
}

.back-to-cart-btn {
    width: 100%;
    padding: 14px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.back-to-cart-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

/* ==========================================
   STICKY FOOTER С КНОПКОЙ ДЕЙСТВИЯ
   ========================================== */
.checkout-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
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
    color: var(--bs-secondary-color);
}

.summary-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.action-btn:active {
    transform: translateY(0);
}

/* Кнопка Т-Банк */
.tbank-btn {
    background: linear-gradient(135deg, #ffdd2d 0%, #ffcc00 100%);
    color: #1a1a1a;
    box-shadow: 0 4px 16px rgba(255, 221, 45, 0.4);
}

.tbank-btn:hover {
    box-shadow: 0 8px 24px rgba(255, 221, 45, 0.5);
}

.tbank-logo {
    width: 80px;
    height: auto;
    object-fit: contain;
    margin-left: 8px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
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
