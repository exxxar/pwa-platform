<template>
    <form
        v-if="deliveryForm"
        class="checkout-form"
        @submit.prevent="startCheckout"
    >

        <!-- ========================================== -->
        <!-- HERO: ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="checkout-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h2 class="hero-title">Оформление заказа</h2>
                <p class="hero-subtitle">Заполните данные для доставки</p>
            </div>
        </div>

        <div class="checkout-content">

            <!-- ========================================== -->
            <!-- СПОСОБ ПОЛУЧЕНИЯ -->
            <!-- ========================================== -->
            <div class="checkout-section">
                <div class="section-header">
                    <div class="section-icon delivery-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Способ получения</h6>
                        <p class="section-subtitle">Как получить заказ?</p>
                    </div>
                </div>
                <DeliveryTypes v-model="deliveryForm" />
            </div>

            <!-- ========================================== -->
            <!-- ИНФОРМАЦИЯ О ДОСТАВКЕ -->
            <!-- ========================================== -->
            <div class="checkout-section">
                <div class="section-header">
                    <div class="section-icon info-icon">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Информация</h6>
                        <p class="section-subtitle">Контактные данные и адрес</p>
                    </div>
                </div>

                <DeliveryForm v-model="deliveryForm" :mode="0">
                    <!-- Слот: информация о доставке -->
                    <template #loadingDeliveryData>
                        <div v-if="!loadingDelivery" class="delivery-info-card" @click="getDeliveryDetails">
                            <div class="delivery-info-row">
                                <div class="info-label">
                                    <i class="fa-solid fa-route"></i>
                                    <span>Расстояние</span>
                                </div>
                                <div class="info-value">
                                    {{ deliveryForm.distance.toFixed(2) }} км
                                </div>
                            </div>
                            <div class="delivery-info-divider"></div>
                            <div class="delivery-info-row">
                                <div class="info-label">
                                    <i class="fa-solid fa-ruble-sign"></i>
                                    <span>Стоимость доставки</span>
                                </div>
                                <div class="info-value price-value">
                                    {{ formatPrice(deliveryForm.delivery_price) }}
                                </div>
                            </div>
                            <div class="delivery-info-hint">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Нажмите для подробностей</span>
                            </div>
                        </div>

                        <div v-else class="delivery-loading-card">
                            <div class="loading-spinner"></div>
                            <p class="loading-text">Ищем ваш адрес...</p>
                        </div>
                    </template>
                </DeliveryForm>
            </div>

            <!-- ========================================== -->
            <!-- СОГЛАШЕНИЕ -->
            <!-- ========================================== -->
            <div class="checkout-section">
                <OfferForm v-model="offerAgreement" />
            </div>

            <!-- ========================================== -->
            <!-- ОШИБКА ДОСТАВКИ -->
            <!-- ========================================== -->
            <div v-if="errorDeliveryPriceMessage" class="error-banner">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ errorDeliveryPriceMessage }}</span>
            </div>

            <!-- ========================================== -->
            <!-- КНОПКИ ДЕЙСТВИЙ -->
            <!-- ========================================== -->
            <div v-if="offerAgreement" class="checkout-actions">

                <!-- Кнопка "Далее" -->
                <button
                    v-if="spentTime <= 0"
                    type="button"
                    class="next-btn"
                    :disabled="!canSubmitForm"
                    @click="nextStep(4)"
                >
                    <template v-if="!sumIsValid">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ validSumText }}</span>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>Далее к оплате</span>
                    </template>
                </button>

                <!-- Кнопка ожидания -->
                <button
                    v-else
                    type="button"
                    class="waiting-btn"
                    disabled
                >
                    <div class="waiting-spinner"></div>
                    <span>Осталось ждать {{ spentTime }} сек.</span>
                </button>

                <!-- Кнопка "Вернуться в корзину" -->
                <button
                    type="button"
                    class="back-btn"
                    @click="goToProductCart"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Вернуться в корзину</span>
                </button>

            </div>

        </div>

    </form>
</template>

<script>
import { useBasketStore } from "@/MobileClient/stores/Shop/basket.js";
import DeliveryTypes from "@/MobileClient/Components/Cart/DeliveryTypes.vue";
import DeliveryForm from "@/MobileClient/Components/Cart/DeliveryForm.vue";
import OfferForm from "@/MobileClient/Components/Cart/OfferForm.vue";

export default {
    name: "CheckoutProductForm",

    components: {
        DeliveryTypes,
        DeliveryForm,
        OfferForm,
    },

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },

    emits: ['update:modelValue', 'start-checkout', 'change-tab'],

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            spentTime: 0,
            deliveryForm: null,
            offerAgreement: true,
            deliveryPriceRequestStep: 0,
            loadingDelivery: false,
            needRequestDeliveryPrice: true,
            errorDeliveryPriceMessage: null,
            deliveryPriceModal: null,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        cartTotalPrice() {
            return this.basketStore.cartTotalPrice || 0;
        },

        cashbackLimit() {
            if (!this.deliveryForm?.use_cashback) return 0;
            const self = window.TenantUser;
            const maxUserCashback = self?.cashBack?.amount || 0;
            const botCashbackPercent = this.settings.cashback?.max_cashback_use_percent ?? this.settings.max_cashback_use_percent ?? 0;
            const cashBackAmount = (this.cartTotalPrice * (botCashbackPercent / 100));
            return Math.min(cashBackAmount, maxUserCashback);
        },

        // 🆕 ИСПРАВЛЕНО: учет вложенности settings.shop
        validSumText() {
            const minPrice = this.settings.shop?.min_price ?? this.settings.min_price ?? 0;
            const currentPrice = this.deliveryForm?.use_cashback ? this.cartTotalPrice - this.cashbackLimit : this.cartTotalPrice;
            return `${this.formatPrice(currentPrice)} из ${this.formatPrice(minPrice)}`;
        },

        sumIsValid() {
            const minPrice = this.settings.shop?.min_price ?? this.settings.min_price ?? 0;
            const currentPrice = this.deliveryForm?.use_cashback ? this.cartTotalPrice - this.cashbackLimit : this.cartTotalPrice;
            return currentPrice >= minPrice;
        },

        canSubmitForm() {
            return this.sumIsValid && this.spentTime <= 0;
        },
    },

    watch: {
        deliveryForm: {
            handler(newValue) {
                this.$emit('update:modelValue', newValue);
            },
            deep: true,
        },

        'deliveryForm.need_pickup'(newValue) {
            this.deliveryPriceRequestStep = newValue ? 1 : (this.settings.need_automatic_delivery_request ? 0 : 1);
        },

        modelValue: {
            handler(newValue) {
                this.deliveryForm = newValue;
            },
            deep: true,
        },

        cartTotalPrice(newValue) {
            if (this.settings.free_shipping_starts_from <= newValue) {
                this.deliveryForm.delivery_price = 0;
                this.deliveryPriceRequestStep = 1;
            }
        },
    },

    mounted() {
        this.deliveryForm = this.modelValue;

        // Инициализация модалки
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                const modalEl = document.getElementById('deliveryPriceModal');
                if (modalEl) {
                    this.deliveryPriceModal = new bootstrap.Modal(modalEl);
                }
            }
        });

        // Слушатели событий
        window.addEventListener('trigger-spent-timer', (event) => {
            this.spentTime = event.detail;
        });

        window.addEventListener('change-delivery-address', (event) => {
            const { address, lng, lat } = event.detail;
            this.getDeliveryPriceDataNew(address, lat, lng);
            this.$notify?.({
                title: 'Поиск адреса',
                text: `${address} (${lat}, ${lng})`,
            });
        });

        this.deliveryPriceRequestStep = this.deliveryForm.need_pickup
            ? 1
            : (this.settings.need_automatic_delivery_request ? 0 : 1);
    },

    beforeUnmount() {
        if (this.deliveryPriceModal) {
            this.deliveryPriceModal.dispose();
        }
    },

    methods: {
        goToProductCart() {
            document.dispatchEvent(new Event('switch-to-cart'));
        },

        getDeliveryDetails() {
            this.deliveryPriceModal?.show();
        },

        async getDeliveryPriceDataNew(address, lat, lng) {
            this.needRequestDeliveryPrice = false;
            this.errorDeliveryPriceMessage = null;
            this.deliveryForm.delivery_price = 0;
            this.deliveryForm.distance = 0;
            this.loadingDelivery = true;

            try {
                const resp = await this.basketStore.requestDeliveryPriceNew({
                    address,
                    lat,
                    lng,
                });

                this.deliveryForm.address = resp.address || null;
                this.deliveryForm.delivery_price = resp.price || 0;
                this.deliveryForm.distance = resp.distance || 0;
                this.deliveryForm.delivery_details = resp.config || [];
                this.needRequestDeliveryPrice = true;
                this.deliveryPriceRequestStep = 1;

                this.$notify?.({
                    title: 'Доставка',
                    text: 'Цена доставки успешно рассчитана',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка расчёта доставки:', error);
                this.deliveryForm.delivery_price = 0;
                this.deliveryForm.distance = 0;
                this.needRequestDeliveryPrice = true;
                this.deliveryPriceRequestStep = 1;
                this.errorDeliveryPriceMessage = 'Цена будет рассчитана курьером при доставке';

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось рассчитать стоимость доставки',
                    type: 'error',
                });
            } finally {
                this.loadingDelivery = false;
            }
        },

        startCheckout() {
            if (this.spentTime > 0) return;
            this.$emit('start-checkout');
            this.startTimer(10);
        },

        nextStep(step) {
            if (this.spentTime > 0) return;

            const form = document.querySelector('.checkout-form');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            Array.from(requiredFields).reverse().forEach(field => {
                if (!field.value.trim()) {
                    field.focus();
                    isValid = false;
                }
            });

            if (!isValid) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Пожалуйста, заполните все обязательные поля',
                    type: 'error',
                });
                return;
            }

            this.$emit('change-tab', step);
            this.startTimer(10);
        },

        startTimer(seconds) {
            this.spentTime = seconds;
            const interval = setInterval(() => {
                if (this.spentTime > 0) {
                    this.spentTime--;
                } else {
                    clearInterval(interval);
                }
            }, 1000);
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
.checkout-form {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO
   ========================================== */
.checkout-hero {
    position: relative;
    padding: 32px 24px;
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
    margin: 0 auto 16px;
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
   КОНТЕНТ
   ========================================== */
.checkout-content {
    padding: 20px 16px;
}

.checkout-section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.delivery-icon {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.info-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ИНФОРМАЦИЯ О ДОСТАВКЕ
   ========================================== */
.delivery-info-card {
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.delivery-info-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.1);
}

.delivery-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
}

.info-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

.info-label i {
    color: var(--bs-primary);
    width: 16px;
}

.info-value {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.price-value {
    color: var(--bs-primary);
    font-size: 1.1rem;
}

.delivery-info-divider {
    height: 1px;
    background: var(--bs-border-color);
    margin: 4px 0;
}

.delivery-info-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--bs-border-color);
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.delivery-info-hint i {
    color: var(--bs-primary);
}

/* Загрузка */
.delivery-loading-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--bs-border-color);
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
}

/* ==========================================
   ОШИБКА
   ========================================== */
.error-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(220, 53, 69, 0.08);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 12px;
    margin-bottom: 16px;
    color: #dc3545;
    font-size: 0.9rem;
}

.error-banner i {
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* ==========================================
   КНОПКИ ДЕЙСТВИЙ
   ========================================== */
.checkout-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
}

.next-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.next-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.next-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.next-btn i {
    transition: transform 0.2s ease;
}

.next-btn:hover:not(:disabled) i {
    transform: translateX(4px);
}

.waiting-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: not-allowed;
    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
}

.waiting-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: transparent;
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.back-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .next-btn,
    .waiting-btn {
        font-size: 0.95rem;
        padding: 14px 20px;
    }
}
</style>
