<template>
    <div class="promo-code-form">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК С ПОДСКАЗКОЙ -->
        <!-- ========================================== -->
        <div class="promo-header">
            <div class="header-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div class="header-content">
                <h6 class="header-title">Промокод</h6>
                <p class="header-subtitle">Введите код для получения скидки</p>
            </div>
            <button
                type="button"
                class="info-btn"
                @click="showInfo = !showInfo"
                :title="showInfo ? 'Скрыть подсказку' : 'Показать подсказку'"
            >
                <i class="fa-solid fa-circle-info"></i>
            </button>
        </div>

        <!-- Подсказка (раскрывающаяся) -->
        <transition name="slide-down">
            <div v-if="showInfo" class="info-banner">
                <i class="fa-solid fa-lightbulb"></i>
                <div class="info-text">
                    <strong>Как использовать?</strong> Введите промокод и нажмите "Активировать".
                    Скидка будет применена автоматически к вашему заказу.
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- ПРЕДУПРЕЖДЕНИЕ -->
        <!-- ========================================== -->
        <div class="warning-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Промокод можно использовать только один раз</span>
        </div>

        <!-- ========================================== -->
        <!-- ПОЛЕ ВВОДА -->
        <!-- ========================================== -->
        <div class="input-section">
            <div class="input-wrapper" :class="{ 'has-value': promocodeForm.code, 'has-error': inputError }">
                <div class="input-icon">
                    <i class="fa-solid fa-barcode"></i>
                </div>
                <input
                    type="text"
                    v-model="promocodeForm.code"
                    :disabled="isDisabled"
                    class="promo-input"
                    placeholder="Например: WELCOME2024"
                    @input="onInputChange"
                    @keyup.enter="submit"
                    autocomplete="off"
                    spellcheck="false"
                >
                <button
                    v-if="promocodeForm.code"
                    type="button"
                    class="clear-btn"
                    @click="clearInput"
                    :disabled="isDisabled"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div v-if="inputError" class="input-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ inputError }}</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- РЕЗУЛЬТАТ АКТИВАЦИИ -->
        <!-- ========================================== -->
        <transition name="fade-up">
            <div v-if="discount > 0" class="success-result">
                <div class="result-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="result-content">
                    <div class="result-title">Промокод активирован!</div>
                    <div class="result-discount">
                        Ваша скидка:
                        <strong>
                            {{ discount }}{{ discount_in_percent ? '%' : ' ₽' }}
                        </strong>
                    </div>
                    <div v-if="activate_price > 0" class="result-condition">
                        Минимальная сумма заказа: {{ formatPrice(activate_price) }}
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- КНОПКА АКТИВАЦИИ -->
        <!-- ========================================== -->
        <button
            type="button"
            class="submit-btn"
            :class="{
                'is-loading': isRequested,
                'is-disabled': isDisabled,
                'has-success': discount > 0
            }"
            :disabled="isDisabled || !promocodeForm.code?.trim()"
            @click="submit"
        >
            <template v-if="isRequested">
                <div class="btn-spinner"></div>
                <span>Проверяем промокод...</span>
            </template>
            <template v-else-if="spentTime > 0">
                <i class="fa-solid fa-hourglass-half"></i>
                <span>Подождите {{ spentTime }} сек.</span>
            </template>
            <template v-else-if="discount > 0">
                <i class="fa-solid fa-check"></i>
                <span>Промокод применён</span>
            </template>
            <template v-else>
                <i class="fa-solid fa-tags"></i>
                <span>Активировать промокод</span>
            </template>
        </button>

    </div>
</template>

<script>
export default {
    name: "PromoCodeForm",

    emits: ['callback'],

    data() {
        return {
            spentTime: 0,
            isRequested: false,
            showInfo: false,
            inputError: '',
            discount: 0,
            discount_in_percent: false,
            activate_price: 0,
            timerId: null,
            promocodeForm: {
                code: '',
            },
        };
    },

    computed: {
        isDisabled() {
            return this.isRequested || this.spentTime > 0;
        },
    },

    watch: {
        'promocodeForm.code'(newValue) {
            // Сбрасываем скидку при изменении кода
            if (this.discount > 0) {
                this.discount = 0;
                this.discount_in_percent = false;
                this.activate_price = 0;
                this.$emit('callback', {
                    code: null,
                    discount: 0,
                    discount_in_percent: false,
                    activate_price: 0,
                });
            }
            // Сбрасываем ошибку
            this.inputError = '';
        },
    },

    mounted() {
        // Восстанавливаем таймер из localStorage
        const savedAt = localStorage.getItem('mypwa_promocode_timer_saved_at');
        if (savedAt) {
            const elapsed = Math.floor((Date.now() - Number(savedAt)) / 1000);
            const remaining = 10 - elapsed;
            if (remaining > 0) {
                this.startTimer(remaining);
            } else {
                localStorage.removeItem('mypwa_promocode_timer_saved_at');
            }
        }
    },

    beforeUnmount() {
        if (this.timerId) {
            clearInterval(this.timerId);
        }
    },

    methods: {
        onInputChange() {
            // Автоматический uppercase для промокодов
            this.promocodeForm.code = this.promocodeForm.code.toUpperCase();
        },

        clearInput() {
            this.promocodeForm.code = '';
            this.inputError = '';
        },

        async submit() {
            // Валидация
            if (!this.promocodeForm.code?.trim()) {
                this.inputError = 'Введите промокод';
                return;
            }

            if (this.promocodeForm.code.trim().length < 3) {
                this.inputError = 'Промокод слишком короткий';
                return;
            }

            this.isRequested = true;
            this.inputError = '';

            try {
                // TODO: Замени на Pinia action
                // const resp = await this.promoStore.activateShopDiscountPromocode(this.promocodeForm);

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1200));

                // Пример успешного ответа (замени на реальный)
                const resp = {
                    discount: 500,
                    discount_in_percent: false,
                    activate_price: 1000,
                };

                this.discount = resp.discount || 0;
                this.discount_in_percent = resp.discount_in_percent || false;
                this.activate_price = resp.activate_price || 0;

                this.$notify?.({
                    title: 'Промокод',
                    text: 'Промокод успешно активирован!',
                    type: 'success',
                });

                this.$emit('callback', {
                    code: this.promocodeForm.code,
                    discount: this.discount,
                    discount_in_percent: this.discount_in_percent,
                    activate_price: this.activate_price,
                });

            } catch (error) {
                console.error('Ошибка активации промокода:', error);

                this.discount = 0;
                this.activate_price = 0;

                this.inputError = error.response?.data?.message || 'Неверный промокод';

                this.$emit('callback', {
                    code: this.promocodeForm.code,
                    discount: 0,
                    discount_in_percent: false,
                    activate_price: 0,
                });

                this.$notify?.({
                    title: 'Ошибка',
                    text: this.inputError,
                    type: 'error',
                });
            } finally {
                this.isRequested = false;
                this.startTimer(10);
            }
        },

        startTimer(seconds) {
            if (this.timerId) clearInterval(this.timerId);

            this.spentTime = Math.min(seconds, 10);
            localStorage.setItem('mypwa_promocode_timer_saved_at', Date.now());

            this.timerId = setInterval(() => {
                if (this.spentTime > 0) {
                    this.spentTime--;
                } else {
                    clearInterval(this.timerId);
                    this.timerId = null;
                    this.spentTime = 0;
                    localStorage.removeItem('mypwa_promocode_timer_saved_at');
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
.promo-code-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ==========================================
   ЗАГОЛОВОК
   ========================================== */
.promo-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.header-content {
    flex: 1;
}

.header-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.header-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.info-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.info-btn:hover {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.1);
}

/* ==========================================
   ПОДСКАЗКА
   ========================================== */
.info-banner {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 12px;
}

.info-banner i {
    color: var(--bs-primary);
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-text {
    font-size: 0.85rem;
    color: var(--bs-body-color);
    line-height: 1.5;
}

.info-text strong {
    color: var(--bs-primary);
}

/* ==========================================
   ПРЕДУПРЕЖДЕНИЕ
   ========================================== */
.warning-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba(255, 193, 7, 0.08);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 10px;
    font-size: 0.8rem;
    color: #856404;
}

.warning-banner i {
    color: #ffc107;
    flex-shrink: 0;
}

:root[data-bs-theme="dark"] .warning-banner {
    color: #ffda6a;
}

/* ==========================================
   ПОЛЕ ВВОДА
   ========================================== */
.input-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.input-wrapper {
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.input-wrapper:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.input-wrapper.has-value {
    border-color: var(--bs-primary);
}

.input-wrapper.has-error {
    border-color: #dc3545;
}

.input-wrapper.has-error:focus-within {
    box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
}

.input-icon {
    width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.1rem;
    flex-shrink: 0;
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.promo-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 14px 0;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 1px;
    color: var(--bs-body-color);
    outline: none;
    text-transform: uppercase;
}

.promo-input::placeholder {
    color: var(--bs-secondary-color);
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
}

.promo-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.clear-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-right: 8px;
}

.clear-btn:hover:not(:disabled) {
    background: #dc3545;
    color: white;
}

.clear-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.input-error {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #dc3545;
}

.input-error i {
    font-size: 0.85rem;
}

/* ==========================================
   РЕЗУЛЬТАТ
   ========================================== */
.success-result {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.08) 0%, rgba(32, 201, 151, 0.03) 100%);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 14px;
}

.result-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.result-content {
    flex: 1;
}

.result-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #198754;
    margin-bottom: 4px;
}

.result-discount {
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.result-discount strong {
    color: #198754;
    font-size: 1.1rem;
}

.result-condition {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КНОПКА АКТИВАЦИИ
   ========================================== */
.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
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

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.submit-btn.is-loading {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    box-shadow: none;
}

.submit-btn.has-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    box-shadow: 0 4px 16px rgba(25, 135, 84, 0.3);
}

.btn-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 200px;
}

.fade-up-enter-active,
.fade-up-leave-active {
    transition: all 0.4s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .promo-input {
        font-size: 0.95rem;
    }

    .submit-btn {
        font-size: 0.95rem;
        padding: 14px 20px;
    }
}
</style>
