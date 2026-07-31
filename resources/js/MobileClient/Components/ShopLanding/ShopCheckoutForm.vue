<template>
    <Teleport to="body">
        <div v-if="isOpen" class="checkout-overlay" @click.self="closeModal">
            <div class="checkout-modal" :class="{ 'is-success': orderSuccess }">

                <!-- ЭКРАН УСПЕХА -->
                <div v-if="orderSuccess" class="success-screen">
                    <div class="success-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h3>Заказ успешно создан!</h3>
                    <p v-if="openedPaymentTab">
                        Ссылка на оплату открыта в новой вкладке. Пожалуйста, завершите оплату.
                    </p>
                    <p v-else>
                        Мы уже начали собирать ваш заказ. Ожидайте подтверждения от оператора.
                    </p>
                    <button class="success-btn" @click="closeModal">
                        <i class="fa-solid fa-check"></i> Отлично
                    </button>
                </div>

                <!-- ОБЫЧНЫЙ РЕЖИМ -->
                <template v-else>
                    <!-- 1. ШАПКА (фиксирована) -->
                    <div class="checkout-modal-header">
                        <div class="header-left">
                            <button v-if="currentStep > 0" class="back-step-btn" @click="currentStep--">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <div>
                                <h3>{{ currentStep === 0 ? 'Данные доставки' : 'Способ оплаты' }}</h3>
                                <p class="header-subtitle">{{ currentStep === 0 ? 'Куда и кому доставить?' : 'Выберите удобный вариант' }}</p>
                            </div>
                        </div>
                        <button class="close-btn" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- 2. ТЕЛО МОДАЛКИ (СКРОЛЛИТСЯ ЗДЕСЬ!) -->
                    <div class="checkout-modal-body">
                        <form class="checkout-form-content" @submit.prevent="handleAction">

                            <!-- ШАГ 0: ДАННЫЕ -->
                            <div v-if="currentStep === 0" class="step-content fade-in">
                                <div class="checkout-section">
                                    <div class="section-header">
                                        <div class="section-icon delivery-icon"><i class="fa-solid fa-box-open"></i></div>
                                        <div><h6 class="section-title">Способ получения</h6></div>
                                    </div>
                                    <DeliveryTypes v-model="localDeliveryForm" />
                                </div>

                                <div class="checkout-section">
                                    <div class="section-header">
                                        <div class="section-icon info-icon"><i class="fa-solid fa-user-pen"></i></div>
                                        <div><h6 class="section-title">Контактные данные</h6></div>
                                    </div>
                                    <DeliveryForm v-model="localDeliveryForm" :mode="0">
                                        <template #loadingDeliveryData>
                                            <!-- 🟢 СОСТОЯНИЕ 1: Расчет успешен, показываем карточку и скрытый блок деталей -->
                                            <template v-if="!loadingDelivery && localDeliveryForm.distance > 0">
                                                <div class="delivery-info-card" @click="toggleDeliveryDetails">
                                                    <div class="delivery-info-row">
                                                        <div class="info-label"><i class="fa-solid fa-route"></i><span>Расстояние</span></div>
                                                        <div class="info-value">{{ localDeliveryForm.distance.toFixed(2) }} км</div>
                                                    </div>
                                                    <div class="delivery-info-divider"></div>
                                                    <div class="delivery-info-row">
                                                        <div class="info-label"><i class="fa-solid fa-ruble-sign"></i><span>Стоимость доставки</span></div>
                                                        <div class="info-value price-value">{{ formatPrice(localDeliveryForm.delivery_price) }}</div>
                                                    </div>
                                                    <div class="delivery-info-hint">
                                                        <div class="hint-text">
                                                            <i class="fa-solid fa-circle-info"></i>
                                                            <span>Нажмите для подробностей</span>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-down chevron-icon" :class="{ 'rotated': showDeliveryDetailsExpanded }"></i>
                                                    </div>
                                                </div>

                                                <!-- Блок деталей (управляется через CSS класс is-expanded) -->
                                                <div class="delivery-details-expanded" :class="{ 'is-expanded': showDeliveryDetailsExpanded }">
                                                    <div class="delivery-details-list">
                                                        <div
                                                            v-for="(detail, uuid) in localDeliveryForm.delivery_details"
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

                                                        <div class="detail-total-card">
                                                            <div class="total-row">
                                                                <span class="total-label">Общее расстояние</span>
                                                                <span class="total-value">{{ Number(localDeliveryForm.distance).toFixed(2) }} км</span>
                                                            </div>
                                                            <div class="total-divider"></div>
                                                            <div class="total-row main-total">
                                                                <span class="total-label">Итого к оплате за доставку</span>
                                                                <span class="total-value text-primary">{{ formatPrice(localDeliveryForm.delivery_price) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- 🟡 СОСТОЯНИЕ 2: Идет загрузка -->
                                            <div v-else-if="loadingDelivery" class="delivery-loading-card">
                                                <div class="loading-spinner"></div>
                                                <p class="loading-text">Рассчитываем стоимость...</p>
                                            </div>

                                            <!-- 🔴 СОСТОЯНИЕ 3: Пустое (адрес не введен или самовывоз) -->
                                            <div v-else class="delivery-loading-card" style="padding: 16px;">
                                                <p class="loading-text" style="font-size: 0.85rem;">
                                                    {{ localDeliveryForm.need_pickup ? 'При самовывозе доставка бесплатна' : 'Введите адрес для расчета стоимости' }}
                                                </p>
                                            </div>
                                        </template>
                                    </DeliveryForm>
                                </div>

                                <div class="checkout-section">
                                    <OfferForm v-model="offerAgreement" />
                                </div>

                                <div v-if="errorDeliveryPriceMessage" class="error-banner">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>{{ errorDeliveryPriceMessage }}</span>
                                </div>
                            </div>

                            <!-- ШАГ 1: ОПЛАТА -->
                            <div v-if="currentStep === 1" class="step-content fade-in">
                                <div class="checkout-section">
                                    <div class="section-header">
                                        <div class="section-icon payment-icon"><i class="fa-solid fa-credit-card"></i></div>
                                        <div>
                                            <h6 class="section-title">Оплата заказа</h6>
                                            <p class="section-subtitle">Итоговая сумма: <strong>{{ formatPrice(finalTotal) }}</strong></p>
                                        </div>
                                    </div>

                                    <div class="payment-types-grid">
                                        <label v-if="settings.can_use_cash" class="payment-type-card" :class="{ 'is-active': localDeliveryForm.payment_type === 2 }">
                                            <input type="radio" name="payment" :value="2" v-model="localDeliveryForm.payment_type">
                                            <div class="payment-card-content">
                                                <i class="fa-solid fa-money-bill-wave"></i>
                                                <span>Наличными / Перевод</span>
                                                <small>Курьеру или при получении</small>
                                            </div>
                                        </label>

                                        <label v-if="settings.can_use_card" class="payment-type-card" :class="{ 'is-active': localDeliveryForm.payment_type === 0 }">
                                            <input type="radio" name="payment" :value="0" v-model="localDeliveryForm.payment_type">
                                            <div class="payment-card-content">
                                                <i class="fa-solid fa-credit-card"></i>
                                                <span>Банковской картой</span>
                                                <small>Онлайн на сайте</small>
                                            </div>
                                        </label>

                                        <label v-if="hasSbpEnabled" class="payment-type-card" :class="{ 'is-active': localDeliveryForm.payment_type === 4 }">
                                            <input type="radio" name="payment" :value="4" v-model="localDeliveryForm.payment_type">
                                            <div class="payment-card-content">
                                                <img src="/images/sbp.png" alt="СБП" class="sbp-logo" onerror="this.style.display='none'">
                                                <i v-if="!$el?.querySelector('.sbp-logo')" class="fa-solid fa-qrcode"></i>
                                                <span>СБП (Быстрый платеж)</span>
                                                <small>Оплата по QR-коду</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- 3. ФУТЕР С КНОПКАМИ (фиксирован) -->
                    <div v-if="offerAgreement" class="checkout-modal-footer">
                        <button v-if="spentTime <= 0" type="submit" form="checkout-form" class="next-btn" @click="handleAction" :disabled="!isActionEnabled || isSubmitting">
                            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>

                            <template v-if="currentStep === 0 && !sumIsValid && !isSubmitting">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Мин. сумма: {{ formatPrice(settings.min_price || 0) }}</span>
                            </template>
                            <template v-else-if="currentStep === 0 && sumIsValid">
                                <span>Далее к оплате</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </template>
                            <template v-else-if="currentStep === 1 && !isSubmitting">
                                <span v-if="localDeliveryForm.payment_type === 0">Оплатить картой</span>
                                <span v-else-if="localDeliveryForm.payment_type === 4">Оплатить через СБП</span>
                                <span v-else>Подтвердить заказ</span>
                            </template>
                        </button>

                        <button v-else type="button" class="waiting-btn" disabled>
                            <div class="waiting-spinner"></div>
                            <span>Осталось ждать {{ spentTime }} сек.</span>
                        </button>

                        <button v-if="currentStep === 1" type="button" class="back-btn" @click="currentStep = 0">
                            <i class="fa-solid fa-arrow-left"></i><span>Назад к данным</span>
                        </button>
                        <button v-else type="button" class="back-btn" @click="closeModal">
                            <i class="fa-solid fa-arrow-left"></i><span>Вернуться в корзину</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>

<script>
import { useBasket } from '@/MobileClient/composables/useBasket';
import DeliveryTypes from "@/MobileClient/Components/Cart/DeliveryTypes.vue";
import DeliveryForm from "@/MobileClient/Components/Cart/DeliveryForm.vue";
import OfferForm from "@/MobileClient/Components/Cart/OfferForm.vue";

export default {
    name: "ShopCheckoutForm",
    components: { DeliveryTypes, DeliveryForm, OfferForm },

    props: {
        isOpen: { type: Boolean, default: false },
        initialData: { type: Object, default: () => ({}) }
    },
    emits: ['close', 'success'],

    setup() {
        const basket = useBasket();
        return {
            basket,
            startCheckoutAction: basket.startCheckout,
            createCheckoutLink: basket.createCheckoutLink,
            cartTotalPrice: basket.cartTotalPrice,
            basket_items: basket.basket_items
        };
    },

    data() {
        return {
            currentStep: 0,
            isSubmitting: false,
            orderSuccess: false,
            openedPaymentTab: false,

            localDeliveryForm: {
                ...this.initialData,
                delivery_price: 0,
                distance: 0,
                need_pickup: false,
                payment_type: 2,
                persons: 1,
                when_ready: true,
                delivery_details: {}
            },
            offerAgreement: true,
            spentTime: 0,
            loadingDelivery: false,
            errorDeliveryPriceMessage: null,
            lastRequestedCoords: '',

            // 🆕 Состояние для раскрытия деталей
            showDeliveryDetailsExpanded: false,
        };
    },

    computed: {
        tenant() { return window.Tenant || null; },
        settings() { return this.tenant?.settings || {}; },

        finalTotal() {
            const delivery = this.localDeliveryForm.need_pickup ? 0 : (this.localDeliveryForm.delivery_price || 0);
            return (Number(this.cartTotalPrice?.value || this.cartTotalPrice || 0)) + Number(delivery);
        },

        sumIsValid() {
            const minPrice = this.settings.min_price || 0;
            return this.finalTotal >= minPrice;
        },

        hasSbpEnabled() {
            const sbp = this.settings.shop?.sbp_banks || this.settings.sbp_banks;
            if (!sbp) return false;
            return Object.values(sbp).some(bank => bank.enabled);
        },

        isStep1Valid() {
            return this.localDeliveryForm.name && this.localDeliveryForm.phone;
        },

        isActionEnabled() {
            if (this.currentStep === 0) {
                return this.sumIsValid && this.isStep1Valid && this.spentTime <= 0;
            }
            return this.spentTime <= 0;
        }
    },

    watch: {
        isOpen(newVal) {
            document.body.style.overflow = newVal ? 'hidden' : '';
            if (!newVal) {
                this.currentStep = 0;
                this.orderSuccess = false;
                this.openedPaymentTab = false;
                this.lastRequestedCoords = '';
                this.showDeliveryDetailsExpanded = false; // Сброс при закрытии
            }
        },
        'localDeliveryForm.need_pickup'(newValue) {
            if (newValue) {
                this.localDeliveryForm.delivery_price = 0;
                this.localDeliveryForm.distance = 0;
                this.localDeliveryForm.delivery_details = {};
                this.lastRequestedCoords = '';
                this.showDeliveryDetailsExpanded = false;
            }
        }
    },

    mounted() {
        window.addEventListener('change-delivery-address', this.handleAddressChange);
    },

    beforeUnmount() {
        window.removeEventListener('change-delivery-address', this.handleAddressChange);
        document.body.style.overflow = '';
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price || 0);
        },

        closeModal() {
            this.$emit('close');
        },

        // 🆕 Метод переключения видимости
        toggleDeliveryDetails() {
            this.showDeliveryDetailsExpanded = !this.showDeliveryDetailsExpanded;
        },

        handleAddressChange(event) {
            if (this.localDeliveryForm.need_pickup) return;

            const { address, lng, lat, location_id } = event.detail;
            const currentCoords = `${location_id}`;

            if (this.lastRequestedCoords === currentCoords) {
                return;
            }

            this.lastRequestedCoords = currentCoords;
            this.getDeliveryPriceDataNew(address, lat, lng);
        },

        async getDeliveryPriceDataNew(address, lat, lng) {
            this.errorDeliveryPriceMessage = null;
            this.localDeliveryForm.delivery_price = 0;
            this.localDeliveryForm.distance = 0;
            this.loadingDelivery = true;

            try {
                const resp = await this.basket.requestDeliveryPriceNew({
                    address,
                    lat,
                    lng,
                });

                this.localDeliveryForm.address = resp.address || address;
                this.localDeliveryForm.delivery_price = resp.price || 0;
                this.localDeliveryForm.distance = resp.distance || 0;
                this.localDeliveryForm.delivery_details = resp.config || {};

                // 🆕 Автоматически раскрываем детали при успешном расчете
                this.showDeliveryDetailsExpanded = true;

                this.$notify?.({
                    title: 'Доставка',
                    text: 'Стоимость доставки успешно рассчитана',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка расчёта доставки:', error);
                this.localDeliveryForm.delivery_price = 0;
                this.localDeliveryForm.distance = 0;
                this.localDeliveryForm.delivery_details = {};
                this.errorDeliveryPriceMessage = 'Не удалось рассчитать стоимость. Курьер уточнит цену при доставке.';

                this.$notify?.({
                    title: 'Внимание',
                    text: 'Не удалось автоматически рассчитать доставку',
                    type: 'warning',
                });
            } finally {
                this.loadingDelivery = false;
            }
        },

        async handleAction() {
            if (this.spentTime > 0 || this.isSubmitting) return;

            if (this.currentStep === 0) {
                if (this.isStep1Valid && this.sumIsValid) {
                    this.currentStep = 1;
                } else if (!this.isStep1Valid) {
                    this.$notify?.({ title: 'Ошибка', text: 'Заполните имя и телефон', type: 'error' });
                }
                return;
            }

            if (this.currentStep === 1) {
                await this.submitOrder();
            }
        },

        async submitOrder() {
            this.isSubmitting = true;

            try {
                const data = new FormData();
                data.append("display_type", this.settings.shop_display_type || 0);

                Object.keys(this.localDeliveryForm).forEach(key => {
                    const item = this.localDeliveryForm[key];
                    if (item === null || item === undefined) {
                        data.append(key, '');
                    } else if (typeof item === 'object' && !(item instanceof File)) {
                        data.append(key, JSON.stringify(item));
                    } else {
                        data.append(key, item);
                    }
                });

                if (this.settings.need_automatic_delivery_request) {
                    data.append("need_automatic_delivery_request", true);
                }

                const paymentType = this.localDeliveryForm.payment_type;

                if ((paymentType === 0 || paymentType === 4) && this.settings.can_use_card) {
                    const response = await this.createCheckoutLink({ deliveryForm: data });

                    if (response?.url) {
                        window.open(response.url, '_blank');
                        this.openedPaymentTab = true;
                        this.orderSuccess = true;
                        this.$emit('success');
                        return;
                    } else {
                        throw new Error('Не получен URL для оплаты от сервера');
                    }
                }

                const response = await this.startCheckoutAction({ deliveryForm: data });

                if (response?.success || response?.order_id) {
                    if (response.payment_data?.url) {
                        window.open(response.payment_data.url, '_blank');
                        this.openedPaymentTab = true;
                    }
                    this.orderSuccess = true;
                    this.$emit('success');
                } else {
                    throw new Error(response?.message || 'Сервер вернул ошибку');
                }

            } catch (error) {
                console.error('=== КРИТИЧЕСКАЯ ОШИБКА ОФОРМЛЕНИЯ ===', error);
                const errorMsg = error.response?.data?.message || error.message || 'Не удалось оформить заказ. Попробуйте позже.';
                this.$notify?.({ title: 'Ошибка', text: errorMsg, type: 'error', duration: 5000 });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
};
</script>

<style lang="scss" scoped>
// ==========================================
// 1. ОВЕРЛЕЙ (ФОН)
// ==========================================
.checkout-overlay {
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

// ==========================================
// 2. КОНТЕЙНЕР МОДАЛКИ
// ==========================================
.checkout-modal {
    background: var(--bs-body-bg, #ffffff);
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;

    &.is-success {
        max-width: 400px;
        text-align: center;
        padding: 40px 20px;
    }
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

// ==========================================
// 3. ШАПКА (ФИКСИРОВАННАЯ)
// ==========================================
.checkout-modal-header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color, #e5e7eb);
    background: var(--bs-body-bg, #ffffff);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.back-step-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color, #e5e7eb);
    background: transparent;
    color: var(--bs-body-color, #1f2937);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: var(--bs-primary, #3b82f6);
        color: white;
        border-color: var(--bs-primary, #3b82f6);
    }
}

.checkout-modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-body-color, #1f2937);
}

.header-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color, #6b7280);
    margin: 2px 0 0 0;
}

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #f3f4f6);
    border: none;
    color: var(--bs-secondary-color, #6b7280);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: #ef4444;
        color: white;
        transform: rotate(90deg);
    }
}

// ==========================================
// 4. ТЕЛО МОДАЛКИ (🚀 ЗДЕСЬ ПРОИСХОДИТ СКРОЛЛ)
// ==========================================
.checkout-modal-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.checkout-form-content {
    padding: 20px;
}

// ==========================================
// 5. ФУТЕР С КНОПКАМИ (ФИКСИРОВАННЫЙ)
// ==========================================
.checkout-modal-footer {
    flex-shrink: 0;
    padding: 20px;
    border-top: 1px solid var(--bs-border-color, #e5e7eb);
    background: var(--bs-body-bg, #ffffff);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

// ==========================================
// 6. ВНУТРЕННИЕ ЭЛЕМЕНТЫ
// ==========================================
.step-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}

.checkout-section {
    margin-bottom: 8px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.delivery-icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.info-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.payment-icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.section-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-body-color, #1f2937);
}

.section-subtitle {
    margin: 2px 0 0 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color, #6b7280);
}

// ==========================================
// 🆕 АНИМАЦИЯ И СТИЛИ РАСКРЫВАЮЩЕГОСЯ БЛОКА
// ==========================================
.delivery-info-card {
    background: var(--bs-secondary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e5e7eb);
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: var(--bs-primary, #3b82f6);
        background: rgba(59, 130, 246, 0.03);
    }
}

.delivery-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    &:last-child { margin-bottom: 0; }
}

.info-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color, #6b7280);
    i { color: var(--bs-primary, #3b82f6); }
}

.info-value {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color, #1f2937);
    &.price-value { color: #10b981; }
}

.delivery-info-divider {
    height: 1px;
    background: var(--bs-border-color, #e5e7eb);
    margin: 10px 0;
}

.delivery-info-hint {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--bs-border-color, #e5e7eb);
    font-size: 0.8rem;
    color: var(--bs-primary, #3b82f6);
    font-weight: 600;
    cursor: pointer;

    .hint-text {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .chevron-icon {
        transition: transform 0.3s ease;
        &.rotated {
            transform: rotate(180deg);
        }
    }
}

.delivery-details-expanded {
    margin-top: 12px;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease-out;
    max-height: 600px;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
    margin-top: 0;
    transform: translateY(-10px);
}

.delivery-details-list {
    display: flex;
    flex-direction: column;
}

.detail-item-card {
    background: var(--bs-body-bg, #ffffff);
    border: 1px solid var(--bs-border-color, #e5e7eb);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: transform 0.2s ease, border-color 0.2s ease;

    &:hover {
        border-color: rgba(59, 130, 246, 0.3);
    }
}

.detail-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px dashed var(--bs-border-color, #e5e7eb);
}

.detail-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(59, 130, 246, 0.1);
    color: var(--bs-primary, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.detail-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color, #1f2937);
}

.detail-body {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.detail-label {
    color: var(--bs-secondary-color, #6b7280);
    display: flex;
    align-items: center;
    i { font-size: 0.8rem; }
}

.detail-value {
    font-weight: 600;
    color: var(--bs-body-color, #1f2937);
    &.price-highlight {
        color: var(--bs-primary, #3b82f6);
        font-size: 1rem;
    }
}

.detail-total-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.02) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    padding: 16px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.total-label {
    color: var(--bs-secondary-color, #6b7280);
}

.total-value {
    font-weight: 700;
    color: var(--bs-body-color, #1f2937);
}

.total-divider {
    height: 1px;
    background: rgba(59, 130, 246, 0.2);
    margin: 10px 0;
}

.main-total {
    .total-label {
        font-weight: 600;
        color: var(--bs-body-color, #1f2937);
        font-size: 0.95rem;
    }
    .total-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--bs-primary, #3b82f6) !important;
    }
}

// ==========================================
// ОСТАЛЬНЫЕ ЭЛЕМЕНТЫ
// ==========================================
.delivery-loading-card {
    text-align: center;
    padding: 20px;
    color: var(--bs-secondary-color, #6b7280);
}

.loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--bs-border-color, #e5e7eb);
    border-top-color: var(--bs-primary, #3b82f6);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 8px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.error-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 10px;
    color: #ef4444;
    font-size: 0.9rem;
    font-weight: 500;
}

.payment-types-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
}

.payment-type-card {
    position: relative;
    display: flex;
    align-items: center;
    padding: 14px 16px;
    background: var(--bs-body-bg, #ffffff);
    border: 2px solid var(--bs-border-color, #e5e7eb);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;

    input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    &:hover {
        border-color: var(--bs-primary, #3b82f6);
        background: rgba(59, 130, 246, 0.02);
    }

    &.is-active {
        border-color: var(--bs-primary, #3b82f6);
        background: rgba(59, 130, 246, 0.05);
        box-shadow: 0 0 0 1px var(--bs-primary, #3b82f6);
    }
}

.payment-card-content {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;

    i {
        font-size: 1.3rem;
        color: var(--bs-primary, #3b82f6);
        width: 24px;
        text-align: center;
    }

    .sbp-logo {
        height: 24px;
        width: auto;
        object-fit: contain;
    }

    span {
        flex: 1;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--bs-body-color, #1f2937);
    }

    small {
        font-size: 0.75rem;
        color: var(--bs-secondary-color, #6b7280);
        text-align: right;
        display: block;
    }
}

.next-btn, .back-btn, .waiting-btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.next-btn {
    background: linear-gradient(135deg, var(--bs-primary, #3b82f6) 0%, #2563eb 100%);
    color: white;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
}

.back-btn {
    background: transparent;
    color: var(--bs-secondary-color, #6b7280);
    border: 1px solid var(--bs-border-color, #e5e7eb);

    &:hover {
        background: var(--bs-secondary-bg, #f3f4f6);
        color: var(--bs-body-color, #1f2937);
    }
}

.waiting-btn {
    background: var(--bs-secondary-bg, #f3f4f6);
    color: var(--bs-secondary-color, #6b7280);
    cursor: not-allowed;
}

.waiting-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

// ==========================================
// 7. ЭКРАН УСПЕХА
// ==========================================
.success-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 40px 20px;
    max-height: 90vh;
    overflow-y: auto;

    .success-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin-bottom: 8px;
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    h3 {
        margin: 0;
        font-size: 1.3rem;
        color: var(--bs-body-color, #1f2937);
    }

    p {
        margin: 0;
        font-size: 0.95rem;
        color: var(--bs-secondary-color, #6b7280);
        line-height: 1.5;
        text-align: center;
    }

    .success-btn {
        margin-top: 16px;
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
    }
}

@keyframes popIn {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

// ==========================================
// 8. АДАПТИВ ДЛЯ МОБИЛЬНЫХ
// ==========================================
@media (max-width: 576px) {
    .checkout-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .checkout-modal {
        border-radius: 20px 20px 0 0;
        max-height: 85vh;
        animation: modalSlideUpMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalSlideUpMobile {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }

    .payment-card-content small {
        display: none;
    }
}

// ==========================================
// 🆕 АНИМАЦИЯ РАСКРЫТИЯ ЧЕРЕЗ CSS
// ==========================================
.delivery-details-expanded {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.3s ease,
    margin-top 0.3s ease,
    transform 0.3s ease;
    margin-top: 0;
    transform: translateY(-10px);

    // Состояние: РАСКРЫТО
    &.is-expanded {
        max-height: 800px; /* Достаточно большое значение, чтобы вместить любой список */
        opacity: 1;
        margin-top: 12px;
        transform: translateY(0);
    }
}

.delivery-info-hint {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--bs-border-color, #e5e7eb);
    font-size: 0.8rem;
    color: var(--bs-primary, #3b82f6);
    font-weight: 600;
    cursor: pointer;

    .hint-text {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .chevron-icon {
        transition: transform 0.3s ease;
        &.rotated {
            transform: rotate(180deg);
        }
    }
}
</style>
