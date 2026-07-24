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

        <!-- ========================================== -->
        <!-- МОДАЛКА: ДЕТАЛИ РАСЧЕТА ДОСТАВКИ -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="deliveryPriceModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-bottom-sheet">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>
                            Детали расчета доставки
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-3">
                        <!-- Если данных нет -->
                        <div v-if="!deliveryForm?.delivery_details || Object.keys(deliveryForm.delivery_details).length === 0" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-circle-info fa-2x mb-3 opacity-50"></i>
                            <p class="mb-0">Детали расчета пока недоступны</p>
                            <small>Стоимость будет рассчитана при оформлении</small>
                        </div>

                        <!-- Список магазинов/партнеров -->
                        <div v-else class="delivery-details-list">
                            <!-- Используем Object.values для итерации по объекту config -->
                            <div
                                v-for="(detail, uuid) in deliveryForm.delivery_details"
                                :key="uuid"
                                class="detail-item-card"
                            >
                                <div class="detail-header">
                                    <div class="detail-icon">
                                        <i class="fa-solid fa-store"></i>
                                    </div>
                                    <div class="detail-title">{{ detail.title || 'Магазин' }}</div>
                                </div>

                                <div class="detail-body">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fa-solid fa-route text-muted me-1"></i>
                                            Расстояние
                                        </span>
                                        <span class="detail-value">{{ Number(detail.distance).toFixed(2) }} км</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fa-solid fa-ruble-sign text-muted me-1"></i>
                                            Стоимость доставки
                                        </span>
                                        <span class="detail-value price-highlight">{{ formatPrice(detail.price) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ИТОГО -->
                            <div class="detail-total-card mt-3">
                                <div class="total-row">
                                    <span class="total-label">Общее расстояние</span>
                                    <span class="total-value">{{ Number(deliveryForm.distance).toFixed(2) }} км</span>
                                </div>
                                <div class="total-divider"></div>
                                <div class="total-row main-total">
                                    <span class="total-label">Итого к оплате за доставку</span>
                                    <span class="total-value text-primary">{{ formatPrice(deliveryForm.delivery_price) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form> <!-- Закрывающий тег формы -->
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
            lastRequestedCoords:'',
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

        this.lastRequestedCoords = '';

        window.addEventListener('change-delivery-address', this.handleAddressChange);

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
        handleAddressChange(event) {


            const { address, lng, lat, city, location_id } = event.detail;
            const currentCoords = `${location_id}`;
            console.log("coords", this.lastRequestedCoords, currentCoords)
            // Если координаты те же самые, что и в последнем запросе — игнорируем
            if (this.lastRequestedCoords === currentCoords) {
                return;
            }

            this.lastRequestedCoords = currentCoords;


            this.getDeliveryPriceDataNew(address, lat, lng);
            this.$notify?.({
                title: 'Поиск адреса',
                text: `${address} (${lat}, ${lng})`,
            });
        },
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

                // 🆕 ИСПРАВЛЕНО: config это объект, поэтому fallback должен быть {}, а не []
                this.deliveryForm.delivery_details = resp.config || {};

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
                this.deliveryForm.delivery_details = {}; // 🆕 Сбрасываем в пустой объект
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

/* ==========================================
   МОДАЛКА: НИЖНЯЯ ШТОРКА (BOTTOM SHEET)
   ========================================== */
.modal-bottom-sheet .modal-dialog {
    margin: 0;
    max-width: 100%;
    height: auto;
    max-height: 85vh; /* Не на весь экран, оставляем верх видимым */
    display: flex;
    align-items: flex-end; /* Прижимаем к низу экрана */
}

.modal-bottom-sheet .modal-content {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    border: none;
    box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.15);
    position: relative;
}

/* Декоративная полоска сверху для ощущения "шторки" */
.modal-bottom-sheet .modal-content::before {
    content: '';
    display: block;
    width: 40px;
    height: 4px;
    background: var(--bs-border-color);
    border-radius: 2px;
    margin: 12px auto 0;
}

.modal-bottom-sheet .modal-header {
    padding: 16px 24px 8px;
}

.modal-bottom-sheet .modal-body {
    padding: 16px 24px 32px;
}

/* ==========================================
   КАРТОЧКИ ДЕТАЛЕЙ ДОСТАВКИ
   ========================================== */
.delivery-details-list {
    display: flex;
    flex-direction: column;
}

.detail-item-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    transition: transform 0.2s ease;
}

.detail-item-card:hover {
    border-color: rgba(var(--bs-primary-rgb), 0.3);
}

.detail-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px dashed var(--bs-border-color);
}

.detail-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.detail-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.detail-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.detail-label {
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
}

.detail-value {
    font-weight: 600;
    color: var(--bs-body-color);
}

.detail-value.price-highlight {
    color: var(--bs-primary);
    font-size: 1.05rem;
}

/* ==========================================
   БЛОК "ИТОГО"
   ========================================== */
.detail-total-card {
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 16px;
    padding: 16px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.95rem;
}

.total-label {
    color: var(--bs-secondary-color);
}

.total-value {
    font-weight: 700;
    color: var(--bs-body-color);
}

.total-divider {
    height: 1px;
    background: rgba(var(--bs-primary-rgb), 0.2);
    margin: 12px 0;
}

.main-total .total-label {
    font-weight: 600;
    color: var(--bs-body-color);
    font-size: 1rem;
}

.main-total .total-value {
    font-size: 1.2rem;
    font-weight: 800;
}

/* Делаем карточку кликабельной визуально */
.delivery-info-card {
    cursor: pointer;
    transition: all 0.2s ease;
}

.delivery-info-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.delivery-info-hint {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--bs-border-color);
    text-align: center;
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
</style>
