<template>
    <Teleport to="body">
        <!-- Overlay с анимацией -->
        <Transition name="fade">
            <div v-if="isOpen" class="cart-overlay" @click.self="handleClose">
                <div class="cart-sidebar" :class="{ 'is-open': isOpen }">

                    <!-- Шапка -->
                    <div class="cart-header">
                        <button v-if="currentStep > 0" class="back-btn" @click="goBack">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <h3>{{ currentStepConfig.title }}</h3>
                        <button class="close-btn" @click="handleClose">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Stepper (показывается на шагах оформления) -->
                    <div class="checkout-stepper" v-if="currentStep > 0 && !isCartEmpty">
                        <div class="stepper-container">
                            <div v-for="(step, index) in visibleSteps" :key="index" class="step-item"
                                 :class="{ 'active': currentStep === index, 'completed': currentStep > index }"
                                 @click="goToStep(index)">
                                <div class="step-circle">
                                    <i v-if="currentStep > index" class="fa-solid fa-check"></i>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="step-label">{{ step.label }}</div>
                                <div v-if="index < visibleSteps.length - 1" class="step-connector"
                                     :class="{ 'filled': currentStep > index }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Контент шагов -->
                    <div class="cart-content">
                        <Transition name="step-fade" mode="out-in">

                            <!-- ШАГ 0: КОРЗИНА -->
                            <div v-if="currentStep === 0" key="cart" class="step-content">
                                <div v-if="!isHydrated || isLoadingState" class="cart-loading">
                                    <SkeletonLoader type="list" :count="3"/>
                                </div>
                                <div v-else-if="isCartEmpty" class="cart-empty">
                                    <i class="fa-solid fa-basket-shopping"></i>
                                    <p>{{ config?.emptyText || 'Корзина пуста' }}</p>
                                    <button class="btn-primary" @click="handleClose">Перейти к меню</button>
                                </div>
                                <!-- Список товаров -->
                                <div v-else class="cart-items-list">
                                    <div v-for="item in cartItemsList" :key="item.basket_id || item.product_id"
                                         class="cart-item">
                                        <!-- 🆕 image — это строка, а не массив -->
                                        <img
                                            v-lazy="item.image || 'https://via.placeholder.com/80'"
                                            :alt="item.name || ''"
                                            class="item-image"
                                        >

                                        <div class="item-details">
                                            <!-- 🆕 name находится прямо в корне объекта -->
                                            <h4 class="item-name">{{ item.name || 'Без названия' }}</h4>
                                            <!-- 🆕 Используем final_price, если есть, иначе price -->
                                            <div class="item-price">{{
                                                    formatPrice(item.final_price || item.price || 0)
                                                }} ₽
                                            </div>

                                            <div class="item-controls">
                                                <button
                                                    class="qty-btn"
                                                    :disabled="basket.isProductLoading(item.product_id)"
                                                    @click="basket.decrementQuantity(item.product_id)"
                                                >
                                                    <i v-if="basket.isProductLoading(item.product_id)"
                                                       class="fa-solid fa-spinner fa-spin"></i>
                                                    <i v-else class="fa-solid fa-minus"></i>
                                                </button>

                                                <!-- 🆕 count, а не quantity -->
                                                <span class="qty-value">{{ item.count || 0 }}</span>

                                                <button
                                                    class="qty-btn"
                                                    :disabled="basket.isProductLoading(item.product_id)"
                                                    @click="basket.incrementQuantity(item.product_id)"
                                                >
                                                    <i v-if="basket.isProductLoading(item.product_id)"
                                                       class="fa-solid fa-spinner fa-spin"></i>
                                                    <i v-else class="fa-solid fa-plus"></i>
                                                </button>

                                                <button
                                                    class="remove-btn"
                                                    @click="basket.removeProductCompletely(item.product_id)"
                                                    title="Удалить"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ШАГ 1: ДАННЫЕ ДОСТАВКИ -->
                            <div v-else-if="currentStep === 1" key="delivery" class="step-content">
                                <CheckoutProductForm ref="step1Form" v-if="settings.shop_display_type === 0"
                                                     v-model="deliveryForm"/>
                            </div>

                            <!-- ШАГ 2: ОПЛАТА -->
                            <div v-else-if="currentStep === 2" key="payment" class="step-content p-2">
                                <div class="section-card">
                                    <div class="section-header">
                                        <i class="fa-solid fa-credit-card"></i>
                                        <h6>Способ оплаты</h6>
                                    </div>
                                    <PaymentTypes v-model="deliveryForm"/>
                                </div>
                                <div class="section-card mt-3">
                                    <Summary v-model="deliveryForm"/>
                                </div>
                            </div>

                            <!-- ШАГ 3: ЧЕК -->
                            <div v-else-if="currentStep === 3" key="receipt" class="step-content">
                                <ScreenPaymentForm ref="step3Form" v-model="deliveryForm"/>
                            </div>
                        </Transition>
                    </div>

                    <!-- Footer (Итого и кнопка действия) -->
                    <div class="cart-footer" v-if="!isCartEmpty || currentStep > 0">
                        <div class="footer-summary" v-if="currentStep > 0">
                            <span class="summary-label">К оплате:</span>
                            <span class="summary-value">{{ formatPrice(totalToPay) }} ₽</span>
                        </div>
                        <div class="cart-total" v-else>
                            <span>{{ config?.totalText || 'Итого:' }}</span>
                            <span class="total-price">{{ formatPrice(cartTotalPrice) }} ₽</span>
                        </div>

                        <button class="btn-checkout" :class="actionButtonClass"
                                :disabled="sending || !isActionButtonEnabled" @click="handleActionClick">
                            <span v-if="sending" class="spinner-border spinner-border-sm me-2"></span>
                            <template v-else>
                                <i v-if="actionButtonIcon" :class="actionButtonIcon" style="margin-right: 8px;"></i>
                                <span>{{ actionButtonText }}</span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- 🆕 МОДАЛКА УСПЕШНОГО ЗАКАЗА -->
        <Transition name="fade">
            <div v-if="orderJustPlaced" class="order-success-overlay" @click.self="dismissSuccessSheet">
                <div class="order-success-sheet">
                    <!-- Декоративный градиентный фон -->
                    <div class="success-bg"></div>

                    <div class="success-content">
                        <!-- Иконка успеха -->
                        <div class="success-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <!-- Заголовок -->
                        <h3 class="success-title">Заказ оформлен!</h3>
                        <p class="success-subtitle">
                            Ваш заказ <strong>#{{ lastOrderId }}</strong> успешно создан
                        </p>

                        <!-- Карточка с деталями -->
                        <div class="order-info-card">
                            <div class="info-row">
                                <span class="info-label">Номер заказа</span>
                                <span class="info-value">#{{ lastOrderId }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Сумма</span>
                                <span class="info-value price">{{ formatPrice(lastOrderTotal) }} ₽</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Статус</span>
                                <span class="info-value status">
                            <i class="fa-solid fa-circle-check"></i>
                            Принят
                        </span>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="success-actions">
                            <button class="success-btn primary" @click="dismissSuccessSheet">
                                <i class="fa-solid fa-store"></i>
                                <span>Продолжить покупки</span>
                            </button>
                        </div>

                        <!-- Автозакрытие -->
                        <div class="auto-close-hint">
                            <i class="fa-solid fa-clock"></i>
                            <span>Автоматическое закрытие через {{ autoCloseCountdown }} сек</span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- 🆕 МОДАЛКА ОШИБКИ МИНИМАЛЬНОЙ СУММЫ -->
        <Transition name="fade">
            <div v-if="showMinOrderError" class="min-order-error-overlay" @click.self="closeMinOrderError">
                <div class="min-order-error-modal">
                    <div class="modal-header error-header">
                        <div class="error-icon-wrapper"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <h3 class="modal-title">Не достигнута минимальная сумма</h3>
                    </div>
                    <div class="modal-body">
                        <div v-for="(error, index) in minOrderErrors" :key="error.partner_id" class="error-item">
                            <div class="error-item-header">
                                <div class="partner-info">
                                    <div class="partner-icon"><i class="fa-solid fa-store"></i></div>
                                    <div class="partner-details">
                                        <h4 class="partner-name">{{ error.partner_name }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="error-item-body">
                                <div class="info-grid">
                                    <div class="info-cell"><span class="info-label">Текущая</span><span
                                        class="info-value current-amount">{{
                                            formatPrice(error.current_amount)
                                        }} ₽</span></div>
                                    <div class="info-cell"><span class="info-label">Минимум</span><span
                                        class="info-value required-amount">{{
                                            formatPrice(error.min_required)
                                        }} ₽</span></div>
                                </div>
                                <div class="shortage-message">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Не хватает <strong>{{ formatPrice(error.shortage) }} ₽</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn primary-btn" @click="closeMinOrderError"><span>Добавить товары</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import {useBasket} from '@/MobileClient/composables/useBasket';
import CheckoutProductForm from "@/MobileClient/Components/Cart/CheckoutProductForm.vue";
import PaymentTypes from "@/MobileClient/Components/Cart/PaymentTypes.vue";
import Summary from "@/MobileClient/Components/Cart/Summary.vue";
import ScreenPaymentForm from "@/MobileClient/Components/Cart/ScreenPaymentForm.vue";
import SkeletonLoader from '@/MobileClient/Components/Common/SkeletonLoader.vue';

export default {
    name: "ShopCart",
    components: {CheckoutProductForm, PaymentTypes, Summary, ScreenPaymentForm, SkeletonLoader},
    props: {
        isOpen: {type: Boolean, default: false},
        config: {type: Object, default: () => ({})}
    },
    emits: ['close', 'success'],

    setup() {
        const basket = useBasket();
        // 🆕 КРИТИЧЕСКИ ВАЖНО: Деструктурируем refs для автоматического разворачивания в шаблоне
        const {
            isHydrated, isLoading, isSending, cartTotalCount, cartTotalPrice, basket_items,
            loadProductsInBasket, startCheckout
        } = basket;

        return {
            basket, // Оставляем объект для вызова методов (incrementQuantity и т.д.)
            isHydrated, isLoading, isSending, cartTotalCount, cartTotalPrice, basket_items,
            loadProductsInBasket, startCheckoutAction: startCheckout
        };
    },

    data() {
        return {
            currentStep: 0,
            sending: false,
            orderJustPlaced: false,
            lastOrderId: null,
            lastOrderTotal: 0,
            autoCloseCountdown: 5,
            autoCloseTimer: null,
            countdownTimer: null,
            showMinOrderError: false,
            minOrderErrors: [],

            allSteps: [
                {label: 'Корзина', title: 'Ваш заказ', icon: 'fa-solid fa-cart-shopping'},
                {label: 'Данные', title: 'Данные доставки', icon: 'fa-solid fa-truck'},
                {label: 'Оплата', title: 'Способ оплаты', icon: 'fa-solid fa-credit-card'},
                {label: 'Чек', title: 'Подтверждение', icon: 'fa-solid fa-receipt'},
            ],
            deliveryForm: {
                name: null, phone: null, address: null, payment_type: 2, cash: true,
                need_pickup: false, delivery_price: 0, distance: 0, image: null,
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
        totalToPay() {
            return Number(this.cartTotalPrice || 0) + Number(this.deliveryForm.delivery_price || 0);
        },
        visibleSteps() {
            const steps = [...this.allSteps];
            if (this.deliveryForm.payment_type !== 2) steps.splice(3, 1); // Скрываем шаг чека, если не перевод
            return steps;
        },
        currentStepConfig() {
            return this.visibleSteps[this.currentStep] || this.allSteps[0];
        },
        actionButtonText() {
            const pt = this.deliveryForm.payment_type;
            const label = this.currentStepConfig?.label;
            if (label === 'Корзина') return 'Оформить заказ';
            if (label === 'Данные') return 'Далее к оплате';
            if (label === 'Оплата') {
                if (pt === 2) return 'Загрузить чек';
                if (pt === 4) return 'Оплатить через СБП';
                if (pt === 0) return 'Оплатить картой';
            }
            if (label === 'Чек') return 'Подтвердить оплату';
            return 'Далее';
        },
        actionButtonIcon() {
            const label = this.currentStepConfig?.label;
            if (label === 'Корзина' || label === 'Данные') return 'fa-solid fa-arrow-right';
            if (label === 'Чек') return 'fa-solid fa-check';
            return 'fa-solid fa-arrow-right';
        },
        actionButtonClass() {
            return '';
        },
        isActionButtonEnabled() {
            if (this.currentStepConfig?.label === 'Корзина') return Number(this.cartTotalCount || 0) > 0;
            return true;
        },
        isLoadingState() {
            return this.isLoading || this.isSending;
        },

        cartItemsList() {
            const items = this.basket_items;

            // Если это массив — возвращаем его
            if (Array.isArray(items)) return items;

            // Если это Ref-объект, достаем .value
            if (items && typeof items === 'object') {
                // Если это объект с полем items (как в вашем ответе с бэка)
                if (items.items && Array.isArray(items.items)) {
                    return items.items;
                }
                // Если это Ref с .value
                if ('value' in items) {
                    const val = items.value;
                    if (Array.isArray(val)) return val;
                    if (val?.items && Array.isArray(val.items)) return val.items;
                }
            }

            // Фоллбэк
            return [];
        },
        cartTotalPrice() {
            // Если есть total_price в ответе — используем его
            const items = this.basket_items;
            if (items?.total_price) return items.total_price;
            if (items?.value?.total_price) return items.value.total_price;

            // Иначе считаем вручную
            return this.cartItemsList.reduce((sum, item) => {
                return sum + (item.final_price || item.price || 0) * (item.count || 0);
            }, 0);
        },
        isCartEmpty() {
            return this.cartItemsList.length === 0;
        }

    },

    watch: {
        isOpen(newVal) {
            if (newVal) {
                document.body.style.overflow = 'hidden';
                if (this.isCartEmpty && !this.isHydrated) this.loadProductsInBasket();
            } else {
                document.body.style.overflow = '';
            }
        },
        'deliveryForm.payment_type'(newType) {
            if (newType !== 2 && this.currentStep === 3) this.currentStep = 2;
        },
        'deliveryForm.need_pickup'(value) {
            if (value) {
                this.deliveryForm.delivery_price = 0;
                this.deliveryForm.distance = 0;
            }
        },
        cartTotalCount(newCount, oldCount) {
            const current = Number(newCount || 0);
            const prev = Number(oldCount || 0);
            if (current === 0 && prev > 0 && this.orderJustPlaced) this.showOrderSuccess();
            else if (current === 0 && this.currentStep > 0 && !this.orderJustPlaced) this.currentStep = 0;
        }
    },

    methods: {
        formatPrice(value) {
            if (!value && value !== 0) return '0';
            return Number(value).toLocaleString('ru-RU');
        },
        goToStep(index) {
            if (index <= this.currentStep) this.currentStep = index;
        },
        goBack() {
            if (this.currentStep > 0) this.currentStep--;
        },

        handleClose() {
            if (this.orderJustPlaced) {
                this.dismissSuccessSheet();
            } else {
                this.$emit('close');
                setTimeout(() => {
                    this.currentStep = 0;
                }, 300); // Сброс шага после анимации закрытия
            }
        },

        async handleActionClick() {
            const label = this.currentStepConfig?.label;
            if (label === 'Корзина') {
                this.currentStep = 1;
                return;
            }
            if (label === 'Данные') {
                const formRef = this.$refs.step1Form;
                if (formRef && typeof formRef.validate === 'function') {
                    if (!await formRef.validate()) return;
                }
                this.currentStep = 2;
                return;
            }
            if (label === 'Оплата') {
                if (this.deliveryForm.payment_type === 2) {
                    this.currentStep = 3;
                    return;
                }
                this.startCheckout();
                return;
            }
            if (label === 'Чек') {
                const formRef = this.$refs.step3Form;
                if (formRef && typeof formRef.validate === 'function') {
                    if (!await formRef.validate()) return;
                }
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
                if (item === null || item === undefined) data.append(key, '');
                else if (typeof item === 'object' && !(item instanceof File)) data.append(key, JSON.stringify(item));
                else data.append(key, item);
            });
            if (this.deliveryForm.image instanceof File) {
                data.append('photo', this.deliveryForm.image);
                data.delete("image");
            }

            this.sending = true;
            try {
                const response = await this.startCheckoutAction({deliveryForm: data});
                if (response?.success) {
                    this.lastOrderId = response.order_id;
                    this.lastOrderTotal = response.summary_price;
                    if (this.deliveryForm.payment_type === 4 && response.payment_data?.url) {
                        window.location.href = response.payment_data.url;
                        return;
                    }
                    this.orderJustPlaced = true;
                    this.$emit('success', response);
                } else {
                    if (response?.min_order_errors?.length > 0) {
                        this.showMinOrderErrorModal(response.min_order_errors);
                        return;
                    }
                    throw new Error(response?.message || 'Ошибка сервера');
                }
            } catch (error) {
                console.error('Ошибка оформления:', error);
                this.$notify?.({title: 'Ошибка', text: error.message || 'Не удалось оформить заказ', type: 'error'});
            } finally {
                this.sending = false;
            }
        },

        showOrderSuccess() {
            this.orderJustPlaced = true;
            this.autoCloseCountdown = 5;
            this.countdownTimer = setInterval(() => {
                this.autoCloseCountdown--;
                if (this.autoCloseCountdown <= 0) this.dismissSuccessSheet();
            }, 1000);
            this.autoCloseTimer = setTimeout(() => this.dismissSuccessSheet(), 5000);
        },
        dismissSuccessSheet() {
            this.orderJustPlaced = false;
            if (this.autoCloseTimer) clearTimeout(this.autoCloseTimer);
            if (this.countdownTimer) clearInterval(this.countdownTimer);
            this.currentStep = 0;
            this.lastOrderId = null;
            this.handleClose();
        },
        showMinOrderErrorModal(errors) {
            this.minOrderErrors = errors;
            this.showMinOrderError = true;
        },
        closeMinOrderError() {
            this.showMinOrderError = false;
            this.minOrderErrors = [];
            this.currentStep = 0; // Возвращаем в корзину, чтобы пользователь добавил товары
        }
    }
};
</script>

<style lang="scss" scoped>
// ==========================================
// БАЗОВЫЕ СТИЛИ САЙДБАРА И ОВЕРЛЕЯ
// ==========================================
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
    background: var(--light, #fffdf8);
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
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;

    h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--dark, #0f0f14);
        flex: 1;
        text-align: center;
    }

    .back-btn, .close-btn {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: var(--gray, #6c757d);
        padding: 8px;
        transition: color 0.2s;

        &:hover {
            color: var(--dark);
        }
    }
}

.cart-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}

.cart-footer {
    padding: 20px 24px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    background: white;
    flex-shrink: 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);

    .cart-total, .footer-summary {
        display: flex;
        justify-content: space-between;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--dark);
    }

    .total-price, .summary-value {
        font-size: 1.4rem;
        color: var(--primary, #ff7a00);
    }

    .btn-checkout {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ffb300) 100%);
        color: white;
        border: none;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1.05rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(255, 122, 0, 0.25);
        transition: all 0.2s;

        &:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(255, 122, 0, 0.35);
        }

        &:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
    }
}

// ==========================================
// 🆕 СТИЛИ СПИСКА ТОВАРОВ
// ==========================================
.cart-items-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.cart-item {
    display: flex;
    gap: 14px;
    padding: 14px;
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.2s;

    &:hover {
        border-color: rgba(0, 0, 0, 0.08);
    }

    .item-image {
        width: 72px;
        height: 72px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
        background: var(--light, #f8f9fa);
    }

    .item-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0; // Важно для text-overflow: ellipsis
    }

    .item-name {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: var(--dark, #0f0f14);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .item-price {
        font-size: 0.9rem;
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
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark, #0f0f14);
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
            min-width: 20px;
            text-align: center;
            color: var(--dark, #0f0f14);
            font-size: 0.95rem;
        }

        .remove-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--gray, #6c757d);
            cursor: pointer;
            font-size: 1rem;
            padding: 4px;
            transition: color 0.2s;

            &:hover {
                color: #ef4444;
            }
        }
    }
}

// ==========================================
// 🆕 ПУСТАЯ КОРЗИНА И ЗАГРУЗКА
// ==========================================
.cart-loading {
    padding: 20px 0;
}

.cart-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px 20px;
    color: var(--gray, #6c757d);

    i {
        font-size: 3.5rem;
        margin-bottom: 16px;
        opacity: 0.2;
        color: var(--primary, #ff7a00);
    }

    p {
        margin-bottom: 24px;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .btn-primary {
        background: var(--primary, #ff7a00);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
            background: var(--primary-dark, #e56f00);
            transform: translateY(-2px);
        }
    }
}

// ==========================================
// STEPPER (Шаги оформления)
// ==========================================
.checkout-stepper {
    padding: 16px 0;
    margin-bottom: 16px;
}

.stepper-container {
    display: flex;
    justify-content: space-between;
    position: relative;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 2;
    cursor: pointer;
}

.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    border: 2px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--gray);
    transition: all 0.3s;
}

.step-item.active .step-circle {
    background: var(--primary, #ff7a00);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
}

.step-item.completed .step-circle {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.step-label {
    margin-top: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--gray);
    text-align: center;
}

.step-item.active .step-label {
    color: var(--primary, #ff7a00);
}

.step-connector {
    position: absolute;
    top: 16px;
    left: calc(50% + 20px);
    right: calc(-50% + 20px);
    height: 2px;
    background: rgba(0, 0, 0, 0.1);
    z-index: 1;

    &.filled {
        background: #10b981;
    }
}

// Секции и формы
.section-card {
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 16px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    color: var(--primary);

    h6 {
        margin: 0;
        font-weight: 700;
    }
}

// ==========================================
// 🆕 МОДАЛКА УСПЕШНОГО ЗАКАЗА (ЧИСТАЯ ВЕРСИЯ)
// ==========================================
.order-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.order-success-sheet {
    width: 100%;
    max-width: 380px;
    background: white;
    border-radius: 24px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    animation: sheetPopIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

// Градиентный фон сверху
.success-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100px;
    background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ffb300) 100%);
    border-radius: 24px 24px 0 0;
}

// Контент
.success-content {
    position: relative;
    padding: 32px 24px 24px;
    text-align: center;
}

// Иконка успеха (поверх градиента)
.success-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 20px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--primary, #ff7a00);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    border: 4px solid var(--light, #fffdf8);
    animation: iconBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s backwards;
}

@keyframes iconBounce {
    0% {
        transform: scale(0) rotate(-90deg);
        opacity: 0;
    }
    100% {
        transform: scale(1) rotate(0);
        opacity: 1;
    }
}

// Заголовок
.success-title {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0 0 8px;
    color: var(--dark, #0f0f14);
}

// Подзаголовок
.success-subtitle {
    font-size: 0.9rem;
    color: var(--gray, #6c757d);
    margin: 0 0 20px;

    strong {
        color: var(--primary, #ff7a00);
        font-weight: 700;
    }
}

// Карточка информации
.order-info-card {
    background: var(--light, #f8f9fa);
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 20px;
    animation: cardSlideIn 0.4s ease-out 0.2s backwards;
}

@keyframes cardSlideIn {
    from {
        opacity: 0;
        transform: translateY(12px);
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
    padding: 8px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);

    &:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    &:first-child {
        padding-top: 0;
    }
}

.info-label {
    font-size: 0.85rem;
    color: var(--gray, #6c757d);
}

.info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark, #0f0f14);

    &.price {
        font-size: 1.1rem;
        color: var(--primary, #ff7a00);
    }

    &.status {
        color: #10b981;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;

        i {
            font-size: 0.85rem;
        }
    }
}

// Кнопка
.success-actions {
    margin-bottom: 12px;
}

.success-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 14px;
    border: none;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &.primary {
        background: linear-gradient(135deg, var(--primary, #ff7a00) 0%, var(--primary-light, #ffb300) 100%);
        color: white;
        box-shadow: 0 8px 20px rgba(255, 122, 0, 0.25);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(255, 122, 0, 0.35);
        }

        &:active {
            transform: translateY(0);
        }
    }
}

// Подсказка автозакрытия
.auto-close-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.75rem;
    color: var(--gray, #6c757d);

    i {
        color: var(--primary, #ff7a00);
    }
}

// Анимация появления модалки
@keyframes sheetPopIn {
    from {
        transform: scale(0.85);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

// Адаптив для мобильных
@media (max-width: 400px) {
    .order-success-overlay {
        padding: 16px;
    }

    .order-success-sheet {
        max-width: 100%;
    }

    .success-content {
        padding: 24px 16px 20px;
    }

    .success-icon {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }

    .success-title {
        font-size: 1.2rem;
    }
}

// ==========================================
// 🆕 МОДАЛКА МИНИМАЛЬНОЙ СУММЫ
// ==========================================
.min-order-error-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.min-order-error-modal {
    background: white;
    border-radius: 20px;
    max-width: 400px;
    width: 100%;
    overflow: hidden;
}

.error-header {
    background: #ef4444;
    color: white;
    padding: 24px;
    text-align: center;
}

.modal-title {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
}

.modal-body {
    padding: 20px;
}

.error-item {
    background: var(--light, #f8f9fa);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.info-cell {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.75rem;
    color: var(--gray);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
}

.info-value {
    font-size: 1rem;
    font-weight: 700;
}

.current-amount {
    color: var(--gray);
}

.required-amount {
    color: #ef4444;
}

.shortage-message {
    margin-top: 12px;
    padding: 10px;
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #856404;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.modal-btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    font-weight: 700;
    cursor: pointer;

    &.primary-btn {
        background: var(--primary);
        color: white;
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.step-fade-enter-active, .step-fade-leave-active {
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
</style>
