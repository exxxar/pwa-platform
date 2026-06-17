<template>
    <div class="promo-page pb-5">

        <!-- ===== HERO СЕКЦИЯ ===== -->
        <div class="promo-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-gift"></i>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">✨</div>
                </div>
                <h2 class="hero-title">Промокод</h2>
                <p class="hero-subtitle">
                    Активируйте код и получите эксклюзивные бонусы
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ===== ФОРМА АКТИВАЦИИ ===== -->
            <div class="promo-card">
                <div class="promo-card-header">
                    <div class="promo-card-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div>
                        <h6 class="promo-card-title">Активация промокода</h6>
                        <p class="promo-card-subtitle">Введите ваш уникальный код</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="promo-form">

                    <!-- Поле ввода -->
                    <div class="input-wrapper">
                        <div class="input-icon-box">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <input
                            v-model="promocodeForm.code"
                            type="text"
                            class="promo-input"
                            placeholder="Например: WELCOME2024"
                            required
                            :disabled="isSubmitting || spent_time_counter > 0"
                            @input="onCodeInput"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <!-- Кнопка очистки -->
                        <button
                            v-if="promocodeForm.code"
                            type="button"
                            class="clear-btn"
                            @click="promocodeForm.code = ''"
                            :disabled="isSubmitting"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Подсказка -->
                    <div class="input-hint">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <span>Промокод обычно указан в письме или SMS</span>
                    </div>

                    <!-- Кнопка активации -->
                    <button
                        type="submit"
                        class="submit-btn"
                        :class="{
                            'is-loading': isSubmitting,
                            'is-waiting': spent_time_counter > 0,
                            'is-success': showSuccess
                        }"
                        :disabled="!promocodeForm.code?.trim() || isSubmitting || spent_time_counter > 0"
                    >
                        <!-- Состояние: загрузка -->
                        <template v-if="isSubmitting">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            <span>Активируем...</span>
                        </template>

                        <!-- Состояние: ожидание -->
                        <template v-else-if="spent_time_counter > 0">
                            <i class="fa-solid fa-clock me-2"></i>
                            <span>Подождите {{ spent_time_counter }} сек.</span>
                        </template>

                        <!-- Состояние: успех -->
                        <template v-else-if="showSuccess">
                            <i class="fa-solid fa-check me-2"></i>
                            <span>Активировано!</span>
                        </template>

                        <!-- Состояние: готов к активации -->
                        <template v-else>
                            <i class="fa-solid fa-bolt me-2"></i>
                            <span>Активировать промокод</span>
                        </template>
                    </button>

                </form>
            </div>

            <!-- ===== ПРЕИМУЩЕСТВА ===== -->
            <div class="benefits-section mt-4">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <h6 class="section-title">Что даёт промокод?</h6>
                </div>

                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                        <div class="benefit-text">
                            <div class="benefit-title">Скидки</div>
                            <div class="benefit-desc">На заказы и услуги</div>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="benefit-text">
                            <div class="benefit-title">Бонусы</div>
                            <div class="benefit-desc">Кэшбэк на баланс</div>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div class="benefit-text">
                            <div class="benefit-title">Подарки</div>
                            <div class="benefit-desc">Эксклюзивные товары</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== УСПЕШНАЯ АКТИВАЦИЯ ===== -->
            <transition name="fade-up">
                <div v-if="showSuccess" class="success-card mt-4">
                    <div class="success-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h5 class="success-title">Промокод активирован!</h5>
                    <p class="success-text">
                        Бонусы уже зачислены на ваш баланс.
                        Приятных покупок!
                    </p>
                </div>
            </transition>

        </div>
    </div>
</template>

<script>
export default {
    name: "PromoCode",

    data() {
        return {
            spent_time_counter: 0,
            is_requested: false,
            isSubmitting: false,
            showSuccess: false,
            timerId: null,
            promocodeForm: {
                code: '',
            },
        };
    },

    mounted() {
        // Восстанавливаем таймер из localStorage
        const savedCounter = localStorage.getItem("cashman_promocode_activate_counter");
        if (savedCounter && parseInt(savedCounter) > 0) {
            this.is_requested = true;
            this.startTimer(parseInt(savedCounter));
        }
    },

    beforeUnmount() {
        // Очищаем интервал при уничтожении компонента
        if (this.timerId) {
            clearInterval(this.timerId);
        }
    },

    methods: {
        // Форматирование ввода (только буквы, цифры, дефис)
        onCodeInput() {
            this.promocodeForm.code = this.promocodeForm.code
                .toUpperCase()
                .replace(/[^A-Z0-9А-ЯЁ\-]/g, '')
                .slice(0, 20);
        },

        startTimer(time) {
            if (this.timerId) {
                clearInterval(this.timerId);
            }

            this.spent_time_counter = time != null ? Math.min(time, 10) : 10;

            this.timerId = setInterval(() => {
                if (this.spent_time_counter > 0) {
                    this.spent_time_counter--;
                    localStorage.setItem(
                        "cashman_promocode_activate_counter",
                        this.spent_time_counter.toString()
                    );
                } else {
                    clearInterval(this.timerId);
                    this.timerId = null;
                    this.is_requested = false;
                    localStorage.removeItem("cashman_promocode_activate_counter");
                }
            }, 1000);
        },

        async submit() {
            if (!this.promocodeForm.code?.trim()) return;
            if (this.isSubmitting || this.spent_time_counter > 0) return;

            this.isSubmitting = true;
            this.showSuccess = false;

            try {
                // TODO: Замени на реальный API
                // const response = await axios.post('/api/promocode/activate', {
                //     code: this.promocodeForm.code.trim()
                // });

                // Имитация запроса (удали после подключения API)
                await new Promise(resolve => setTimeout(resolve, 1200));

                // Успех
                this.showSuccess = true;
                this.promocodeForm.code = '';

                this.$notify?.({
                    title: 'Промокод',
                    text: "Промокод успешно активирован!",
                    type: "success",
                });

                // Запускаем таймер защиты от спама
                this.startTimer(10);

                // Скрываем сообщение об успехе через 5 секунд
                setTimeout(() => {
                    this.showSuccess = false;
                }, 5000);

            } catch (error) {
                console.error('Ошибка активации:', error);

                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Неверный промокод или он уже использован',
                    type: "error",
                });
            } finally {
                this.isSubmitting = false;
            }
        },
    },
};
</script>

<style scoped>
.promo-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.promo-hero {
    position: relative;
    padding: 40px 24px 32px;
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
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.hero-sparkle {
    position: absolute;
    font-size: 1rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 {
    top: -8px;
    right: -8px;
    animation-delay: 0s;
}

.sparkle-2 {
    bottom: -8px;
    left: -8px;
    animation-delay: 1s;
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
    line-height: 1.4;
}

/* ==========================================
   КАРТОЧКА ФОРМЫ
   ========================================== */
.promo-card {
    margin-top: -20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 2;
}

.promo-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.promo-card-icon {
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

.promo-card-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.promo-card-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ПОЛЕ ВВОДА
   ========================================== */
.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    transition: all 0.2s ease;
    overflow: hidden;
}

.input-wrapper:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.input-icon-box {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.1rem;
    flex-shrink: 0;
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
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.clear-btn:hover {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.input-hint {
    margin-top: 10px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
}

/* ==========================================
   КНОПКА АКТИВАЦИИ
   ========================================== */
.submit-btn {
    width: 100%;
    margin-top: 20px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
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

.submit-btn.is-waiting {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
}

.submit-btn.is-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    box-shadow: 0 4px 16px rgba(25, 135, 84, 0.3);
    animation: successPulse 0.5s ease;
}

@keyframes successPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
    }
    100% {
        transform: scale(1);
    }
}

/* ==========================================
   ПРЕИМУЩЕСТВА
   ========================================== */
.benefits-section {
    animation: fadeInUp 0.5s ease-out 0.2s both;
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

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.benefit-item {
    padding: 16px 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    text-align: center;
    transition: all 0.2s ease;
}

.benefit-item:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.08);
}

.benefit-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    margin: 0 auto 10px;
}

.benefit-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.benefit-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.benefit-desc {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КАРТОЧКА УСПЕХА
   ========================================== */
.success-card {
    padding: 24px;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.05) 0%, rgba(32, 201, 151, 0.05) 100%);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 16px;
    text-align: center;
}

.success-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(25, 135, 84, 0.3);
    animation: successPop 0.5s ease;
}

@keyframes successPop {
    0% {
        transform: scale(0);
    }
    70% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.success-title {
    font-weight: 700;
    font-size: 1.2rem;
    color: #198754;
    margin-bottom: 8px;
}

.success-text {
    margin: 0;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
}

/* Анимация появления */
.fade-up-enter-active,
.fade-up-leave-active {
    transition: all 0.4s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .benefits-grid {
        grid-template-columns: 1fr;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .promo-input {
        font-size: 0.95rem;
    }
}
</style>
