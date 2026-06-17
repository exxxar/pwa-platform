<template>
    <div v-if="deliveryForm" class="summary-container">

        <!-- ========================================== -->
        <!-- ТОВАРЫ -->
        <!-- ========================================== -->
        <div class="summary-section">
            <div class="section-header">
                <div class="section-icon products-icon">
                    <i class="fa-solid fa-box"></i>
                </div>
                <h6 class="section-title">Товары</h6>
            </div>

            <div class="summary-card">
                <div class="summary-row clickable" @click="goToProductCart">
                    <div class="row-label">
                        <i class="fa-solid fa-cubes"></i>
                        <span>Количество</span>
                    </div>
                    <div class="row-value link-value">
                        {{ cartTotalCount }}
                        <span class="unit">{{ pluralize(cartTotalCount, 'шт.', 'шт.', 'шт.') }}</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>

                <div class="summary-row">
                    <div class="row-label">
                        <i class="fa-solid fa-tag"></i>
                        <span>Стоимость</span>
                    </div>
                    <div class="row-value">
                        {{ formatPrice(cartTotalPrice) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СКИДКИ -->
        <!-- ========================================== -->
        <div v-if="hasDiscounts" class="summary-section">
            <div class="section-header">
                <div class="section-icon discounts-icon">
                    <i class="fa-solid fa-percent"></i>
                </div>
                <h6 class="section-title">Скидки</h6>
            </div>

            <div class="summary-card">
                <!-- Кэшбэк -->
                <div v-if="deliveryForm.use_cashback" class="summary-row discount-row">
                    <div class="row-label">
                        <i class="fa-solid fa-coins"></i>
                        <span>Оплата бонусами</span>
                    </div>
                    <div class="row-value discount-value">
                        −{{ formatPrice(cashbackLimit) }}
                    </div>
                </div>

                <!-- Промокод -->
                <div
                    v-if="settings.need_promo_code"
                    class="summary-row clickable"
                    @click="openPromocodeModal"
                >
                    <div class="row-label">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Промокод</span>
                    </div>
                    <div class="row-value" :class="{ 'link-value': !deliveryForm.discount }">
                        <template v-if="deliveryForm.discount > 0">
                            <span class="discount-value">−{{ formatPrice(deliveryForm.discount) }}</span>
                        </template>
                        <template v-else>
                            <span class="promo-link">
                                <i class="fa-solid fa-plus-circle"></i>
                                Ввести промокод
                            </span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </template>
                    </div>
                </div>

                <!-- Активный промокод (инфо) -->
                <div v-if="deliveryForm.discount > 0" class="promo-active-banner">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Промокод применён</span>
                    <button class="remove-promo" @click="removePromocode">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ДОСТАВКА -->
        <!-- ========================================== -->
        <div v-if="showDeliverySection" class="summary-section">
            <div class="section-header">
                <div class="section-icon delivery-icon">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <h6 class="section-title">Доставка</h6>
            </div>

            <div class="summary-card">
                <!-- Самовывоз -->
                <div v-if="deliveryForm.need_pickup" class="summary-row">
                    <div class="row-label">
                        <i class="fa-solid fa-store"></i>
                        <span>Самовывоз</span>
                    </div>
                    <div class="row-value free-value">
                        Бесплатно
                    </div>
                </div>

                <!-- Обычная доставка -->
                <template v-else-if="settings.shop_display_type === 0">
                    <div class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-motorcycle"></i>
                            <span>Стоимость доставки</span>
                        </div>
                        <div class="row-value">
                            <template v-if="settings.need_automatic_delivery_request">
                                <template v-if="deliveryForm.delivery_price > 0">
                                    {{ formatPrice(deliveryForm.delivery_price) }}
                                </template>
                                <template v-else>
                                    <span class="pending-value">не рассчитана</span>
                                </template>
                            </template>
                            <template v-else>
                                <span class="pending-value">рассчитает курьер</span>
                            </template>
                        </div>
                    </div>

                    <!-- Бесплатная доставка -->
                    <div v-if="isFreeDelivery" class="free-delivery-banner">
                        <i class="fa-solid fa-gift"></i>
                        <span>Бесплатная доставка от {{ formatPrice(settings.free_shipping_starts_from) }}</span>
                    </div>
                </template>

                <!-- СДЭК -->
                <template v-else-if="settings.shop_display_type === 1 && deliveryForm.cdek?.tariff">
                    <div class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-box-archive"></i>
                            <span>Тариф СДЭК</span>
                        </div>
                        <div class="row-value">
                            {{ deliveryForm.cdek.tariff.tariff_name }}
                        </div>
                    </div>

                    <div v-if="!settings.need_hide_delivery_period" class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Срок доставки</span>
                        </div>
                        <div class="row-value">
                            {{ deliveryForm.cdek.tariff.calendar_min }}–{{ deliveryForm.cdek.tariff.calendar_max }} дн.
                        </div>
                    </div>

                    <div v-if="settings.need_automatic_delivery_request" class="summary-row">
                        <div class="row-label">
                            <i class="fa-solid fa-ruble-sign"></i>
                            <span>Стоимость</span>
                        </div>
                        <div class="row-value">
                            {{ formatPrice(deliveryForm.cdek.tariff.delivery_sum) }}
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ДОПОЛНИТЕЛЬНО -->
        <!-- ========================================== -->
        <div v-if="showAdditionalSection" class="summary-section">
            <div class="section-header">
                <div class="section-icon additional-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h6 class="section-title">Дополнительно</h6>
            </div>

            <div class="summary-card">
                <!-- Количество гостей -->
                <div
                    v-if="settings.need_person_counter && settings.shop_display_type === 0"
                    class="summary-row clickable"
                    @click="openPersonsModal"
                >
                    <div class="row-label">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Количество гостей</span>
                    </div>
                    <div class="row-value link-value">
                        {{ deliveryForm.persons }}
                        <span class="unit">{{ pluralize(deliveryForm.persons, 'чел.', 'чел.', 'чел.') }}</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>

                <!-- Сдача -->
                <div v-if="deliveryForm.payment_type === 3 && settings.can_use_cash" class="change-section">
                    <div class="change-label">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <span>Подготовить сдачу с</span>
                    </div>

                    <!-- Быстрые кнопки -->
                    <div class="change-variants">
                        <button
                            v-for="money in moneyVariants"
                            :key="money"
                            class="variant-btn"
                            :class="{ 'active': deliveryForm.money === money }"
                            @click="deliveryForm.money = money"
                        >
                            {{ money }}₽
                        </button>
                    </div>

                    <!-- Свой ввод -->
                    <div class="custom-change-input">
                        <div class="input-wrapper">
                            <input
                                type="number"
                                min="0"
                                v-model.number="deliveryForm.money"
                                placeholder="Или введите сумму..."
                                class="change-input"
                            >
                            <span class="input-suffix">₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИТОГО -->
        <!-- ========================================== -->
        <div class="total-section">
            <div class="total-card">
                <div class="total-row">
                    <span class="total-label">К оплате</span>
                    <span class="total-value">{{ formatPrice(finallyPrice) }}</span>
                </div>

                <!-- Экономия -->
                <div v-if="totalDiscount > 0" class="savings-row">
                    <i class="fa-solid fa-piggy-bank"></i>
                    <span>Ваша выгода: <strong>{{ formatPrice(totalDiscount) }}</strong></span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПРЕДУПРЕЖДЕНИЯ -->
        <!-- ========================================== -->
        <div v-if="settings.min_price > cartTotalPrice" class="warning-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="warning-text">
                Минимальная сумма заказа: <strong>{{ formatPrice(settings.min_price) }}</strong>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПРОМОКОД -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="promocodeModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content promocode-modal">
                    <div class="modal-header">
                        <div class="modal-icon promocode-icon">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Промокод</h5>
                            <small class="text-muted">Введите код для получения скидки</small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <PromoCodeForm @callback="activateDiscount" />

                        <div v-if="deliveryForm.discount > 0" class="active-promo-card">
                            <i class="fa-solid fa-circle-check"></i>
                            <div class="promo-info">
                                <div class="promo-label">Промокод активирован</div>
                                <div class="promo-discount">
                                    Скидка: <strong>{{ formatPrice(deliveryForm.discount) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: КОЛИЧЕСТВО ГОСТЕЙ -->
        <!-- ========================================== -->
        <div
            class="modal fade"
            id="personsModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content persons-modal">
                    <div class="modal-header">
                        <div class="modal-icon persons-icon">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Количество гостей</h5>
                            <small class="text-muted">Сколько человек будет?</small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="persons-stepper">
                            <button
                                class="stepper-btn"
                                :disabled="deliveryForm.persons <= 1"
                                @click="decPersons"
                            >
                                <i class="fa-solid fa-minus"></i>
                            </button>

                            <div class="stepper-display">
                                <div class="stepper-value">{{ deliveryForm.persons }}</div>
                                <div class="stepper-label">
                                    {{ pluralize(deliveryForm.persons, 'гость', 'гостя', 'гостей') }}
                                </div>
                            </div>

                            <button
                                class="stepper-btn"
                                :disabled="deliveryForm.persons >= 100"
                                @click="incPersons"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <!-- Быстрый выбор -->
                        <div class="quick-persons">
                            <button
                                v-for="n in [1, 2, 3, 4, 6]"
                                :key="n"
                                class="quick-btn"
                                :class="{ 'active': deliveryForm.persons === n }"
                                @click="deliveryForm.persons = n"
                            >
                                {{ n }}
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn-done"
                            data-bs-dismiss="modal"
                        >
                            Готово
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { useBasketStore } from '@/MobileClient/stores/Shop/basket.js';
import PromoCodeForm from '@/MobileClient/Components/Shop/PromoCodeForm.vue';

export default {
    name: "OrderSummary",

    components: {
        PromoCodeForm
    },

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },

    emits: ['update:modelValue', 'calc-delivery-price'],

    setup() {
        const basketStore = useBasketStore();
        return { basketStore };
    },

    data() {
        return {
            moneyVariants: [500, 1000, 2000, 5000],
            promocodeModal: null,
            personsModal: null,
        };
    },

    computed: {
        deliveryForm: {
            get() { return this.modelValue; },
            set(value) { this.$emit('update:modelValue', value); },
        },

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

        // Лимит кэшбэка
        cashbackLimit() {
            if (!this.modelValue?.use_cashback) return 0;

            const self = window.TenantUser;
            const maxUserCashback = self?.cashBack?.amount || 0;
            const botCashbackPercent = this.settings?.max_cashback_use_percent || 0;
            const cashBackAmount = (this.cartTotalPrice * (botCashbackPercent / 100));

            return Math.min(cashBackAmount, maxUserCashback);
        },

        // Общая скидка
        totalDiscount() {
            return (this.modelValue?.discount || 0) + this.cashbackLimit;
        },

        // Бесплатная доставка
        isFreeDelivery() {
            return this.settings?.free_shipping_starts_from > 0
                && this.cartTotalPrice >= this.settings.free_shipping_starts_from;
        },

        // Финальная цена
        finallyPrice() {
            let price = this.cartTotalPrice;

            // Вычитаем кэшбэк
            if (this.modelValue?.use_cashback) {
                price -= this.cashbackLimit;
            }

            // Вычитаем скидку промокода
            if (this.modelValue?.discount) {
                price -= this.modelValue.discount;
            }

            // Прибавляем стоимость доставки
            if (this.settings?.need_automatic_delivery_request) {
                const cdekPrice = this.modelValue?.cdek?.tariff?.delivery_sum || 0;
                const deliveryPrice = this.modelValue?.delivery_price || 0;
                price += cdekPrice + deliveryPrice;
            }

            return Math.max(0, price);
        },

        // Показывать секцию скидок?
        hasDiscounts() {
            return this.modelValue?.use_cashback || this.settings?.need_promo_code;
        },

        // Показывать секцию доставки?
        showDeliverySection() {
            if (this.modelValue?.need_pickup) return true;
            if (this.settings?.shop_display_type === 0) return true;
            if (this.settings?.shop_display_type === 1 && this.modelValue?.cdek?.tariff) return true;
            return false;
        },

        // Показывать секцию дополнительно?
        showAdditionalSection() {
            const hasPersons = this.settings?.need_person_counter && this.settings?.shop_display_type === 0;
            const hasChange = this.modelValue?.payment_type === 3 && this.settings?.can_use_cash;
            return hasPersons || hasChange;
        },
    },

    watch: {
        cartTotalPrice(newValue) {
            if (this.settings?.free_shipping_starts_from <= newValue) {
                this.deliveryForm = { ...this.modelValue, delivery_price: 0 };
            }
        },
    },

    mounted() {
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                const promocodeEl = document.getElementById('promocodeModal');
                const personsEl = document.getElementById('personsModal');

                if (promocodeEl) this.promocodeModal = new bootstrap.Modal(promocodeEl);
                if (personsEl) this.personsModal = new bootstrap.Modal(personsEl);
            }
        });
    },

    beforeUnmount() {
        if (this.promocodeModal) this.promocodeModal.dispose();
        if (this.personsModal) this.personsModal.dispose();
    },

    methods: {
        goToProductCart() {
            document.dispatchEvent(new Event('switch-to-cart'));
        },

        openPromocodeModal() {
            this.promocodeModal?.show();
        },

        openPersonsModal() {
            this.personsModal?.show();
        },

        activateDiscount(item) {
            this.deliveryForm = { ...this.modelValue, discount: item.discount || 0 };
        },

        removePromocode() {
            this.deliveryForm = { ...this.modelValue, discount: 0 };
        },

        decPersons() {
            if (this.modelValue.persons > 1) {
                this.deliveryForm = { ...this.modelValue, persons: this.modelValue.persons - 1 };
            }
        },

        incPersons() {
            if (this.modelValue.persons < 100) {
                this.deliveryForm = { ...this.modelValue, persons: this.modelValue.persons + 1 };
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
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
.summary-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.summary-section {
    margin-bottom: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding: 0 4px;
}

.section-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.products-icon {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
}

.discounts-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
}

.delivery-icon {
    background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
}

.additional-icon {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

/* ==========================================
   КАРТОЧКИ СВОДКИ
   ========================================== */
.summary-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    overflow: hidden;
}

.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--bs-border-color-translucent);
    transition: background 0.2s ease;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.clickable {
    cursor: pointer;
}

.summary-row.clickable:hover {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.row-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

.row-label i {
    font-size: 0.85rem;
    width: 16px;
    text-align: center;
    color: var(--bs-primary);
}

.row-value {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.row-value .unit {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    font-weight: 500;
}

.link-value {
    color: var(--bs-primary);
    cursor: pointer;
}

.link-value i {
    font-size: 0.7rem;
    opacity: 0.6;
    transition: transform 0.2s ease;
}

.summary-row.clickable:hover .link-value i {
    transform: translateX(2px);
}

.discount-value {
    color: #198754;
}

.free-value {
    color: #198754;
    font-weight: 700;
}

.pending-value {
    color: var(--bs-secondary-color);
    font-style: italic;
    font-weight: 400;
    font-size: 0.85rem;
}

/* ==========================================
   ПРОМОКОД
   ========================================== */
.promo-link {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--bs-primary);
    font-weight: 600;
}

.promo-link i {
    font-size: 0.9rem;
}

.promo-active-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: rgba(25, 135, 84, 0.08);
    border-top: 1px solid rgba(25, 135, 84, 0.15);
    font-size: 0.85rem;
    color: #198754;
    font-weight: 500;
}

.promo-active-banner i {
    font-size: 0.9rem;
}

.remove-promo {
    margin-left: auto;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(25, 135, 84, 0.15);
    border: none;
    color: #198754;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-promo:hover {
    background: #198754;
    color: white;
}

/* ==========================================
   БЕСПЛАТНАЯ ДОСТАВКА
   ========================================== */
.free-delivery-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-top: 1px solid rgba(var(--bs-primary-rgb), 0.1);
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 500;
}

.free-delivery-banner i {
    font-size: 0.85rem;
}

/* ==========================================
   СДАЧА
   ========================================== */
.change-section {
    padding: 16px;
    border-top: 1px solid var(--bs-border-color-translucent);
}

.change-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 12px;
}

.change-label i {
    color: var(--bs-primary);
}

.change-variants {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.variant-btn {
    padding: 10px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 10px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.variant-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.variant-btn.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.3);
}

.custom-change-input {
    margin-top: 8px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.change-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid var(--bs-border-color);
    border-radius: 10px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.95rem;
    font-weight: 500;
    outline: none;
    transition: all 0.2s ease;
}

.change-input:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.input-suffix {
    position: absolute;
    right: 16px;
    color: var(--bs-secondary-color);
    font-weight: 600;
}

/* ==========================================
   ИТОГО
   ========================================== */
.total-section {
    margin-top: 8px;
}

.total-card {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 16px;
    padding: 20px;
    color: white;
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.3);
}

.total-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.total-label {
    font-size: 1rem;
    font-weight: 600;
    opacity: 0.95;
}

.total-value {
    font-size: 1.6rem;
    font-weight: 800;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.savings-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.85rem;
    opacity: 0.95;
}

.savings-row i {
    font-size: 0.9rem;
}

.savings-row strong {
    font-weight: 700;
}

/* ==========================================
   ПРЕДУПРЕЖДЕНИЯ
   ========================================== */
.warning-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 12px;
    margin-top: 16px;
    color: #856404;
    font-size: 0.9rem;
}

.warning-banner i {
    font-size: 1.1rem;
    color: #ffc107;
    flex-shrink: 0;
}

.warning-text strong {
    color: #856404;
}

/* ==========================================
   МОДАЛКИ
   ========================================== */
.promocode-modal,
.persons-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
    display: flex;
    align-items: center;
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.promocode-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.persons-icon {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

.modal-body {
    padding: 20px;
}

/* Активный промокод в модалке */
.active-promo-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: rgba(25, 135, 84, 0.08);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 12px;
    margin-top: 16px;
}

.active-promo-card i {
    font-size: 1.3rem;
    color: #198754;
}

.promo-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.promo-discount {
    font-size: 0.95rem;
    color: #198754;
    font-weight: 600;
}

/* Счётчик гостей */
.persons-stepper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bs-secondary-bg, #f5f5f5);
    border-radius: 16px;
    padding: 12px;
    margin-bottom: 20px;
}

.stepper-btn {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stepper-btn:hover:not(:disabled) {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    transform: scale(1.05);
}

.stepper-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.stepper-display {
    text-align: center;
    padding: 0 20px;
}

.stepper-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.stepper-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Быстрый выбор */
.quick-persons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.quick-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    color: var(--bs-body-color);
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quick-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.quick-btn.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
}

.btn-done {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-done:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(var(--bs-primary-rgb), 0.4);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .change-variants {
        grid-template-columns: repeat(2, 1fr);
    }

    .total-value {
        font-size: 1.4rem;
    }

    .stepper-value {
        font-size: 2rem;
    }

    .quick-btn {
        width: 42px;
        height: 42px;
        font-size: 0.9rem;
    }
}
</style>
