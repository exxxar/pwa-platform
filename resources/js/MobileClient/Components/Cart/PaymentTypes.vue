<template>
    <div v-if="deliveryForm" class="payment-section">
        <div class="payment-methods">
            <div class="methods-label">Выберите способ оплаты</div>

            <div class="methods-list">
                <!-- 🆕 СБП: проверяем, включен ли хоть один банк -->
                <button
                    v-if="isSbpEnabled"
                    type="button"
                    class="method-card"
                    :class="{ 'active': deliveryForm.payment_type === 4 }"
                    @click="deliveryForm.payment_type = 4"
                >
                    <div class="method-icon sbp-icon">
                        <img src="/images/sbp.png" alt="СБП">
                    </div>
                    <div class="method-info">
                        <div class="method-title">Система быстрых платежей</div>
                        <div class="method-desc">Мгновенный перевод по QR-коду</div>
                    </div>
                    <div class="method-check"><i class="fa-solid fa-check"></i></div>
                </button>

                <!-- 🆕 Онлайн картой: исправлен путь к настройке -->
                <button
                    v-if="settings.can_use_card && settings.payment_token"
                    type="button"
                    class="method-card"
                    :class="{ 'active': deliveryForm.payment_type === 0 }"
                    @click="deliveryForm.payment_type = 0"
                >
                    <div class="method-icon online-icon"><i class="fa-solid fa-credit-card"></i></div>
                    <div class="method-info">
                        <div class="method-title">Онлайн картой</div>
                        <div class="method-desc">Безопасная оплата на сайте</div>
                    </div>
                    <div class="method-check"><i class="fa-solid fa-check"></i></div>
                </button>

                <!-- Картой в заведении -->
                <button
                    v-if="deliveryForm.pick_up_type == 0 && settings.can_use_card"
                    type="button"
                    class="method-card"
                    :class="{ 'active': deliveryForm.payment_type === 1 }"
                    @click="deliveryForm.payment_type = 1"
                >
                    <div class="method-icon card-icon"><i class="fa-regular fa-credit-card"></i></div>
                    <div class="method-info">
                        <div class="method-title">Картой в заведении</div>
                        <div class="method-desc">Оплата при получении</div>
                    </div>
                    <div class="method-check"><i class="fa-solid fa-check"></i></div>
                </button>

                <!-- 🆕 Переводом и Наличными: исправлен путь к can_use_cash -->
                <button
                    v-if="settings.can_use_qr"
                    type="button"
                    class="method-card"
                    :class="{ 'active': deliveryForm.payment_type === 2 }"
                    @click="deliveryForm.payment_type = 2"
                >
                    <div class="method-icon transfer-icon"><i class="fa-solid fa-building-columns"></i></div>
                    <div class="method-info">
                        <div class="method-title">Переводом</div>
                        <div class="method-desc">По реквизитам или QR-коду</div>
                    </div>
                    <div class="method-check"><i class="fa-solid fa-check"></i></div>
                </button>

                <button
                    v-if="settings.can_use_cash"
                    type="button"
                    class="method-card"
                    :class="{ 'active': deliveryForm.payment_type === 3 }"
                    @click="deliveryForm.payment_type = 3"
                >
                    <div class="method-icon cash-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="method-info">
                        <div class="method-title">Наличными</div>
                        <div class="method-desc">Оплата наличными курьеру</div>
                    </div>
                    <div class="method-check"><i class="fa-solid fa-check"></i></div>
                </button>
            </div>
        </div>

        <!-- 🆕 Бонусы: исправлен путь к need_bonuses_section -->
        <div v-if="settings.need_bonuses_section && cashbackLimit > 0" class="bonuses-section">
            <div class="bonuses-label"><i class="fa-solid fa-gift"></i><span>Бонусы</span></div>
            <button
                type="button"
                class="bonuses-card"
                :class="{ 'active': deliveryForm.use_cashback }"
                @click="deliveryForm.use_cashback = !deliveryForm.use_cashback"
            >
                <div class="bonuses-icon"><i class="fa-solid fa-coins"></i></div>
                <div class="bonuses-info">
                    <div class="bonuses-title">Списать бонусы</div>
                    <div class="bonuses-desc">Доступно {{ formatPrice(cashbackLimit) }}</div>
                </div>
                <div class="bonuses-toggle">
                    <div class="toggle-track" :class="{ 'active': deliveryForm.use_cashback }">
                        <div class="toggle-thumb"></div>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentTypes",
    props: { modelValue: { type: Object, required: true } },
    emits: ['update:modelValue'],

    data() { return { deliveryForm: null }; },

    watch: {
        deliveryForm: { handler(newValue) { this.$emit('update:modelValue', newValue); }, deep: true },
        modelValue: { handler(newValue) { this.deliveryForm = newValue; }, deep: true },
    },

    computed: {
        tenant() { return window.Tenant || null; },
        settings() { return this.tenant?.settings || {}; },

        // 🆕 НОВОЕ: Динамическая проверка, включен ли хоть один СБП банк
        isSbpEnabled() {
            const banks = this.settings.sbp_banks || {};
            return Object.values(banks).some(bank => bank.enabled);
        },

        // 🆕 ИСПРАВЛЕНО: правильный путь к cashback.max_cashback_use_percent
        cashbackLimit() {
            if (!this.deliveryForm?.use_cashback || !this.settings.need_bonuses_section) return 0;
            const self = window.TenantUser;
            const cartTotalPrice = window.basketStore?.cartTotalPrice || 0;
            const maxUserCashback = self?.cashBack?.amount || 0;
            const botCashbackPercent = this.settings.cashback?.max_cashback_use_percent ?? this.settings.max_cashback_use_percent ?? 0;
            const cashBackAmount = (cartTotalPrice * (botCashbackPercent / 100));
            return Math.min(cashBackAmount, maxUserCashback);
        },
    },

    mounted() {
        this.deliveryForm = this.modelValue;
        // 🆕 Если СБП включен, выбираем его по умолчанию
        if (this.isSbpEnabled) {
            this.deliveryForm.payment_type = 4;
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price || 0);
        },
    },
};
</script>

<style scoped>
.payment-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ==========================================
   СПОСОБЫ ОПЛАТЫ
   ========================================== */
.methods-label,
.bonuses-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bonuses-label i {
    color: var(--bs-primary);
}

.methods-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.method-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    width: 100%;
}

.method-card:hover {
    border-color: var(--bs-primary);
    transform: translateX(2px);
}

.method-card.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.method-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.sbp-icon {
    background: linear-gradient(135deg, #00a86b 0%, #00c97f 100%);
    padding: 8px;
}

.sbp-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.online-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.card-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.transfer-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.cash-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.method-card.active .method-icon {
    transform: scale(1.05);
}

.method-info {
    flex: 1;
    min-width: 0;
}

.method-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.method-desc {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    line-height: 1.3;
}

.method-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.method-card.active .method-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   БОНУСЫ
   ========================================== */
.bonuses-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    text-align: left;
}

.bonuses-card:hover {
    border-color: var(--bs-primary);
}

.bonuses-card.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.bonuses-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.bonuses-info {
    flex: 1;
    min-width: 0;
}

.bonuses-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.bonuses-desc {
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 600;
}

/* Toggle switch */
.bonuses-toggle {
    flex-shrink: 0;
}

.toggle-track {
    width: 44px;
    height: 24px;
    border-radius: 12px;
    background: var(--bs-border-color);
    position: relative;
    transition: background 0.3s ease;
}

.toggle-track.active {
    background: var(--bs-primary);
}

.toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.toggle-track.active .toggle-thumb {
    transform: translateX(20px);
}

/* Адаптив */
@media (max-width: 576px) {
    .method-card {
        padding: 12px;
        gap: 12px;
    }

    .method-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .method-title {
        font-size: 0.9rem;
    }

    .method-desc {
        font-size: 0.7rem;
    }
}
</style>
