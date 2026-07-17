<template>
    <Teleport to="body">
        <div v-if="isOpen" class="checkout-overlay" @click.self="$emit('close')">
            <div class="checkout-modal" :class="{ 'is-open': isOpen }">

                <!-- Шапка модалки -->
                <div class="checkout-modal-header">
                    <h3>Оформление заказа</h3>
                    <button class="close-btn" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Твоя форма (адаптированная) -->
                <form class="checkout-form-content" @submit.prevent="submitOrder">

                    <!-- СПОСОБ ПОЛУЧЕНИЯ -->
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
                        <DeliveryTypes v-model="localDeliveryForm" />
                    </div>

                    <!-- ИНФОРМАЦИЯ О ДОСТАВКЕ -->
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

                        <DeliveryForm v-model="localDeliveryForm" :mode="0">
                            <template #loadingDeliveryData>
                                <div v-if="!loadingDelivery" class="delivery-info-card" @click="getDeliveryDetails">
                                    <div class="delivery-info-row">
                                        <div class="info-label">
                                            <i class="fa-solid fa-route"></i>
                                            <span>Расстояние</span>
                                        </div>
                                        <div class="info-value">{{ localDeliveryForm.distance?.toFixed(2) || 0 }} км</div>
                                    </div>
                                    <div class="delivery-info-divider"></div>
                                    <div class="delivery-info-row">
                                        <div class="info-label">
                                            <i class="fa-solid fa-ruble-sign"></i>
                                            <span>Стоимость доставки</span>
                                        </div>
                                        <div class="info-value price-value">
                                            {{ formatPrice(localDeliveryForm.delivery_price) }}
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

                    <!-- СОГЛАШЕНИЕ -->
                    <div class="checkout-section">
                        <OfferForm v-model="offerAgreement" />
                    </div>

                    <!-- ОШИБКА ДОСТАВКИ -->
                    <div v-if="errorDeliveryPriceMessage" class="error-banner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>{{ errorDeliveryPriceMessage }}</span>
                    </div>

                    <!-- КНОПКИ ДЕЙСТВИЙ -->
                    <div v-if="offerAgreement" class="checkout-actions">
                        <button
                            v-if="spentTime <= 0"
                            type="submit"
                            class="next-btn"
                            :disabled="!canSubmitForm || isSubmitting"
                        >
                            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                            <template v-if="!sumIsValid && !isSubmitting">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Мин. сумма: {{ formatPrice(settings.min_price || 0) }}</span>
                            </template>
                            <template v-else-if="!isSubmitting">
                                <span>Оплатить {{ formatPrice(finalTotal) }}</span>
                            </template>
                        </button>

                        <button v-else type="button" class="waiting-btn" disabled>
                            <div class="waiting-spinner"></div>
                            <span>Осталось ждать {{ spentTime }} сек.</span>
                        </button>

                        <button type="button" class="back-btn" @click="$emit('close')">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Вернуться в корзину</span>
                        </button>
                    </div>
                </form>
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
        return { basket: useBasket() };
    },

    data() {
        return {
            localDeliveryForm: { ...this.initialData, delivery_price: 0, distance: 0, need_pickup: false },
            offerAgreement: true,
            spentTime: 0,
            loadingDelivery: false,
            errorDeliveryPriceMessage: null,
            isSubmitting: false,
            deliveryPriceModal: null,
        };
    },

    computed: {
        tenant() { return window.Tenant || null; },
        settings() { return this.tenant?.settings || {}; },
        cartTotalPrice() { return this.basket.cartTotalPrice || 0; },

        finalTotal() {
            const delivery = this.localDeliveryForm.need_pickup ? 0 : (this.localDeliveryForm.delivery_price || 0);
            return this.cartTotalPrice + delivery;
        },

        sumIsValid() {
            const minPrice = this.settings.min_price || 0;
            return this.finalTotal >= minPrice;
        },

        canSubmitForm() {
            return this.sumIsValid && this.spentTime <= 0;
        }
    },

    watch: {
        isOpen(newVal) {
            document.body.style.overflow = newVal ? 'hidden' : '';
        },
        'localDeliveryForm.need_pickup'(newValue) {
            if (newValue) {
                this.localDeliveryForm.delivery_price = 0;
                this.localDeliveryForm.distance = 0;
            }
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price || 0);
        },

        getDeliveryDetails() {
            // Здесь можно открыть модалку с деталями, если она есть в DeliveryForm
            this.$notify?.({ title: 'Инфо', text: 'Детали доставки', type: 'info' });
        },

        async submitOrder() {
            if (this.spentTime > 0 || this.isSubmitting) return;

            // Простая валидация (можно расширить в зависимости от полей DeliveryForm)
            if (!this.localDeliveryForm.phone || !this.localDeliveryForm.name) {
                this.$notify?.({ title: 'Ошибка', text: 'Заполните имя и телефон', type: 'error' });
                return;
            }

            this.isSubmitting = true;
            try {
                // Формируем payload для бэкенда
                const payload = {
                    ...this.localDeliveryForm,
                    total: this.finalTotal,
                    items: this.basket.basket_items
                };

                // Вызываем реальный метод оформления из useBasket
                await this.basket.startCheckout(payload);

                this.$emit('success'); // Сообщаем родителю об успехе
            } catch (error) {
                console.error('Ошибка оформления:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось оформить заказ', type: 'error' });
            } finally {
                this.isSubmitting = false;
            }
        },

        startTimer(seconds) {
            this.spentTime = seconds;
            const interval = setInterval(() => {
                if (this.spentTime > 0) this.spentTime--;
                else clearInterval(interval);
            }, 1000);
        }
    }
};
</script>

<style lang="scss" scoped>
/* Оверлей и модалка */
.checkout-overlay {
    position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999;
    backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: flex-end;
}
@media (min-width: 768px) {
    .checkout-overlay { align-items: center; padding: 20px; }
}

.checkout-modal {
    background: white; width: 100%; max-width: 600px; max-height: 90vh;
    border-radius: 24px 24px 0 0; display: flex; flex-direction: column;
    transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    &.is-open { transform: translateY(0); }
}
@media (min-width: 768px) {
    .checkout-modal { border-radius: 24px; max-height: 85vh; }
}

.checkout-modal-header {
    padding: 20px 24px; border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    h3 { margin: 0; font-size: 1.3rem; font-weight: 800; }
    .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--gray); }
}

.checkout-form-content {
    padding: 24px; overflow-y: auto; flex: 1;
}

/* Твои стили из образца (немного адаптированные) */
.checkout-section { margin-bottom: 24px; }
.section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.section-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; flex-shrink: 0; }
.delivery-icon { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
.info-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.section-title { margin: 0; font-weight: 700; font-size: 1.05rem; color: var(--dark); }
.section-subtitle { margin: 0; font-size: 0.75rem; color: var(--gray); }

.delivery-info-card {
    background: var(--light); border: 2px solid rgba(0,0,0,0.06); border-radius: 16px; padding: 16px; cursor: pointer; transition: all 0.2s ease;
    &:hover { border-color: var(--primary); }
}
.delivery-info-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; }
.info-label { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--gray); }
.info-label i { color: var(--primary); width: 16px; }
.info-value { font-weight: 700; font-size: 1rem; color: var(--dark); }
.price-value { color: var(--primary); font-size: 1.1rem; }
.delivery-info-divider { height: 1px; background: rgba(0,0,0,0.06); margin: 4px 0; }
.delivery-info-hint { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed rgba(0,0,0,0.1); font-size: 0.8rem; color: var(--gray); }

.delivery-loading-card { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px; background: var(--light); border: 2px solid rgba(0,0,0,0.06); border-radius: 16px; }
.loading-spinner { width: 32px; height: 32px; border: 3px solid rgba(0,0,0,0.1); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 12px; }
@keyframes spin { to { transform: rotate(360deg); } }
.loading-text { font-size: 0.9rem; color: var(--gray); margin: 0; }

.error-banner { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: rgba(220, 53, 69, 0.08); border: 1px solid rgba(220, 53, 69, 0.2); border-radius: 12px; margin-bottom: 16px; color: #dc3545; font-size: 0.9rem; }

.checkout-actions { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; }
.next-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 16px 24px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; &:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255, 122, 0, 0.3); } &:disabled { opacity: 0.6; cursor: not-allowed; } }
.waiting-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 16px 24px; background: #ffc107; border: none; border-radius: 14px; color: white; font-weight: 700; cursor: not-allowed; }
.waiting-spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
.back-btn { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 24px; background: transparent; border: 2px solid rgba(0,0,0,0.1); border-radius: 14px; color: var(--dark); font-weight: 600; cursor: pointer; transition: all 0.2s ease; &:hover { border-color: var(--primary); color: var(--primary); } }
</style>
