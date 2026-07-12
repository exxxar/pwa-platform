<template>
    <div class="payment-link-page">

        <!-- ========================================== -->
        <!-- HERO -->
        <!-- ========================================== -->
        <div class="payment-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
                <h2 class="hero-title">Генерация ссылок на оплату</h2>
                <p class="hero-subtitle">
                    Создайте ссылку и отправьте клиенту для быстрой оплаты через СБП
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ========================================== -->
            <!-- УСПЕХ -->
            <!-- ========================================== -->
            <transition name="fade">
                <div v-if="showSuccess" class="success-card">
                    <div class="success-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="success-content">
                        <h4>Ссылка успешно создана!</h4>
                        <p>Она отправлена клиенту в чат. Вы можете создать ещё одну ссылку ниже.</p>
                    </div>
                    <button class="success-close" @click="showSuccess = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </transition>

            <!-- ========================================== -->
            <!-- ФОРМА -->
            <!-- ========================================== -->
            <form @submit.prevent="submit" class="payment-form">

                <!-- Настройки платежа -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-gear"></i>
                        <h3>Настройки платежа</h3>
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-info">
                            <div class="toggle-icon">
                                <i class="fa-solid fa-rotate"></i>
                            </div>
                            <div>
                                <h4>Рекуррентный платёж</h4>
                                <p>Автоматическое списание каждый период</p>
                            </div>
                        </div>
                        <label class="switch-control">
                            <input
                                type="checkbox"
                                v-model="linkForm.is_recurrent"
                            >
                            <span class="switch-slider"></span>
                        </label>
                    </div>

                    <div v-if="linkForm.is_recurrent" class="recurrent-hint">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Клиенту будут автоматически списываться средства согласно условиям подписки</span>
                    </div>
                </div>

                <!-- Сумма и описание -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-ruble-sign"></i>
                        <h3>Сумма и описание</h3>
                    </div>

                    <div class="form-field">
                        <label>
                            <i class="fa-solid fa-coins"></i>
                            Сумма оплаты
                            <span class="required">*</span>
                        </label>
                        <div class="input-with-suffix">
                            <input
                                type="number"
                                v-model.number="linkForm.amount"
                                min="1"
                                step="0.01"
                                placeholder="0"
                                required
                            >
                            <span class="input-suffix">₽</span>
                        </div>
                        <span class="field-hint">Минимальная сумма — 1 рубль</span>
                    </div>

                    <div class="form-field">
                        <label>
                            <i class="fa-solid fa-file-lines"></i>
                            Описание услуги
                            <span class="required">*</span>
                            <span v-if="linkForm.description" class="char-counter">
                                {{ linkForm.description.length }}/255
                            </span>
                        </label>
                        <textarea
                            v-model="linkForm.description"
                            maxlength="255"
                            rows="4"
                            placeholder="Например: Оплата заказа #12345"
                            required
                        ></textarea>
                    </div>
                </div>

                <!-- Данные клиента -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-user"></i>
                        <h3>Данные клиента</h3>
                    </div>

                    <div class="form-field">
                        <label>
                            <i class="fa-solid fa-id-card"></i>
                            Имя клиента
                        </label>
                        <input
                            type="text"
                            v-model="linkForm.name"
                            placeholder="Иван Иванов"
                        >
                    </div>

                    <div class="form-field">
                        <label>
                            <i class="fa-solid fa-phone"></i>
                            Телефон клиента
                            <span class="required">*</span>
                        </label>
                        <input
                            type="tel"
                            v-model="linkForm.phone"
                            placeholder="+7 (999) 123-45-67"
                            required
                            @input="formatPhone"
                        >
                        <span class="field-hint">На этот номер будет отправлена ссылка на оплату</span>
                    </div>

                    <div class="form-field">
                        <label>
                            <i class="fa-solid fa-envelope"></i>
                            Email клиента
                        </label>
                        <input
                            type="email"
                            v-model="linkForm.email"
                            placeholder="client@example.com"
                        >
                        <span class="field-hint">Необязательно, но рекомендуется для чеков</span>
                    </div>
                </div>

                <!-- Действия -->
                <div class="form-actions">
                    <button
                        type="button"
                        class="btn-reset"
                        @click="resetForm"
                        :disabled="sending"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Сбросить</span>
                    </button>

                    <button
                        type="submit"
                        class="btn-submit"
                        :disabled="sending || !isFormValid"
                    >
                        <span v-if="sending" class="btn-spinner"></span>
                        <i v-else class="fa-solid fa-paper-plane"></i>
                        <span>{{ sending ? 'Отправка...' : 'Отправить ссылку на оплату' }}</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>

<script>
export default {
    name: 'PaymentLinkForm',

    data() {
        return {
            sending: false,
            showSuccess: false,
            linkForm: {
                phone: null,
                name: null,
                email: null,
                amount: 0,
                is_recurrent: false,
                description: 'Оплата заказа',
            },
        };
    },

    computed: {
        /**
         * Валидность формы
         */
        isFormValid() {
            return (
                this.linkForm.amount > 0 &&
                this.linkForm.description &&
                this.linkForm.description.trim().length > 0 &&
                this.linkForm.phone &&
                this.linkForm.phone.replace(/\D/g, '').length === 11
            );
        },
    },

    methods: {
        /**
         * Отправка формы
         */
        async submit() {
            if (!this.isFormValid || this.sending) return;

            this.sending = true;

            try {
                await this.$store.dispatch('sendSBPInvoice', {
                    dataObject: this.linkForm,
                });

                // Показываем успех
                this.showSuccess = true;

                // Сбрасываем форму
                this.resetForm();

                // Скрываем уведомление через 5 секунд
                setTimeout(() => {
                    this.showSuccess = false;
                }, 5000);

                this.$notify?.({
                    title: 'Успешно',
                    text: 'Ссылка на оплату отправлена клиенту в чат',
                    type: 'success',
                });

            } catch (error) {
                console.error('[PaymentLink] Ошибка:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось отправить ссылку',
                    type: 'error',
                });
            } finally {
                this.sending = false;
            }
        },

        /**
         * Сброс формы
         */
        resetForm() {
            this.linkForm = {
                phone: null,
                name: null,
                email: null,
                amount: 0,
                is_recurrent: false,
                description: 'Оплата заказа',
            };
        },

        /**
         * Форматирование телефона
         */
        formatPhone(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            if (!value.startsWith('7') && value.length > 0) {
                value = '7' + value;
            }

            let formatted = '';
            if (value.length > 0) formatted = '+' + value[0];
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 4) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 7) formatted += '-' + value.slice(7, 9);
            if (value.length >= 9) formatted += '-' + value.slice(9, 11);

            this.linkForm.phone = formatted;
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.payment-link-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

// ==========================================
// HERO
// ==========================================
.payment-hero {
    position: relative;
    padding: 40px 20px 60px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.08) 0%, transparent 40%);
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 600px;
    margin: 0 auto;
}

.hero-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 8px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
    line-height: 1.5;
}

// ==========================================
// УСПЕХ
// ==========================================
.success-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba($success, 0.1) 0%, rgba($success, 0.05) 100%);
    border: 1px solid rgba($success, 0.3);
    border-left: 4px solid $success;
    border-radius: 14px;
    margin: -30px 0 20px;
    position: relative;
    z-index: 2;
    animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: $success;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.success-content {
    flex: 1;
    min-width: 0;

    h4 {
        margin: 0 0 4px;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.success-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

// ==========================================
// ФОРМА
// ==========================================
.payment-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: -30px;
    position: relative;
    z-index: 2;
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
    color: $primary;

    i {
        font-size: 1.1rem;
    }

    h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }
}

// ==========================================
// TOGGLE (РЕКУРРЕНТНЫЙ ПЛАТЁЖ)
// ==========================================
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.toggle-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.toggle-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.toggle-info {
    h4 {
        margin: 0 0 2px;
        font-size: 0.95rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.8rem;
        color: $text-muted;
    }
}

.switch-control {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 30px;
    flex-shrink: 0;

    input {
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .switch-slider {
            background: $primary;

            &::before {
                transform: translateX(22px);
            }
        }
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: $border;
    border-radius: 30px;
    transition: 0.3s;

    &::before {
        position: absolute;
        content: '';
        height: 24px;
        width: 24px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

.recurrent-hint {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 14px;
    padding: 12px 14px;
    background: rgba($warning, 0.08);
    border: 1px solid rgba($warning, 0.2);
    border-radius: 10px;
    font-size: 0.8rem;
    color: $text;
    line-height: 1.4;

    i {
        color: $warning;
        margin-top: 2px;
        flex-shrink: 0;
    }
}

// ==========================================
// ПОЛЯ ФОРМЫ
// ==========================================
.form-field {
    margin-bottom: 16px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;

        i {
            color: $primary;
            font-size: 0.85rem;
        }

        .required {
            color: $danger;
            font-weight: 700;
        }

        .char-counter {
            margin-left: auto;
            font-size: 0.75rem;
            font-weight: 500;
            color: $text-muted;
        }
    }

    input,
    textarea {
        width: 100%;
        padding: 12px 16px;
        background: $bg;
        border: 2px solid $border;
        border-radius: 10px;
        font-size: 0.95rem;
        color: $text;
        font-family: inherit;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &::placeholder {
            color: $text-muted;
        }
    }

    textarea {
        resize: vertical;
        min-height: 100px;
        line-height: 1.5;
    }

    .field-hint {
        display: block;
        margin-top: 6px;
        font-size: 0.75rem;
        color: $text-muted;
        line-height: 1.4;
    }
}

.input-with-suffix {
    display: flex;
    align-items: stretch;

    input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .input-suffix {
        padding: 12px 18px;
        background: $bg-secondary;
        border: 2px solid $border;
        border-left: none;
        border-radius: 0 10px 10px 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $primary;
        display: flex;
        align-items: center;
    }
}

// ==========================================
// ДЕЙСТВИЯ
// ==========================================
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.btn-reset {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: transparent;
    border: 2px solid $border;
    border-radius: 12px;
    color: $text;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        border-color: $danger;
        color: $danger;
        background: rgba($danger, 0.03);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-submit {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
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

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .payment-hero {
        padding: 30px 16px 50px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn-reset,
    .btn-submit {
        width: 100%;
    }

    .toggle-row {
        gap: 12px;
    }

    .toggle-info h4 {
        font-size: 0.85rem;
    }

    .toggle-info p {
        font-size: 0.75rem;
    }
}
</style>
