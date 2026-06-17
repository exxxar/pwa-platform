<template>
    <div class="payment-form-page">
        <div class="form-header">
            <div class="header-icon">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <h2 class="header-title">Генерация ссылок на оплату</h2>
        </div>

        <form class="payment-form" @submit.prevent="submit">
            <!-- Тип платежа -->
            <div class="form-section">
                <div class="switch-group">
                    <label class="switch-label" for="is-recurrent">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Рекуррентный платёж</span>
                    </label>
                    <div class="switch-control">
                        <input
                            id="is-recurrent"
                            type="checkbox"
                            v-model="linkForm.is_recurrent"
                            class="switch-input"
                            :disabled="sending"
                        >
                        <span class="switch-slider"></span>
                    </div>
                </div>
            </div>

            <!-- Основная информация -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa-solid fa-receipt"></i>
                    Платёжная информация
                </div>

                <div class="form-group">
                    <label class="form-label" for="payment-amount">
                        <i class="fa-solid fa-ruble-sign"></i>
                        Сумма <span class="required">*</span>
                    </label>
                    <input
                        id="payment-amount"
                        type="number"
                        v-model.number="linkForm.amount"
                        class="form-input"
                        placeholder="0"
                        min="0"
                        step="0.01"
                        required
                        :disabled="sending"
                    >
                    <span v-if="errors.amount" class="form-error">{{ errors.amount }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="payment-description">
                        <i class="fa-solid fa-file-lines"></i>
                        Описание услуги <span class="required">*</span>
                        <span v-if="linkForm.description" class="char-count">
                            {{ linkForm.description.length }}/255
                        </span>
                    </label>
                    <textarea
                        id="payment-description"
                        v-model="linkForm.description"
                        class="form-textarea"
                        placeholder="Опишите услугу или товар"
                        maxlength="255"
                        rows="4"
                        required
                        :disabled="sending"
                    ></textarea>
                    <span v-if="errors.description" class="form-error">{{ errors.description }}</span>
                </div>
            </div>

            <!-- Данные клиента -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa-solid fa-user"></i>
                    Данные о клиенте
                </div>

                <div class="form-group">
                    <label class="form-label" for="client-name">
                        <i class="fa-solid fa-id-badge"></i>
                        Имя клиента
                    </label>
                    <input
                        id="client-name"
                        type="text"
                        v-model="linkForm.name"
                        class="form-input"
                        placeholder="Иван Иванов"
                        :disabled="sending"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="client-phone">
                        <i class="fa-solid fa-phone"></i>
                        Телефон <span class="required">*</span>
                    </label>
                    <input
                        id="client-phone"
                        type="tel"
                        v-mask="'+7(###) ###-##-##'"
                        v-model="linkForm.phone"
                        class="form-input"
                        placeholder="+7 (999) 123-45-67"
                        required
                        :disabled="sending"
                    >
                    <span v-if="errors.phone" class="form-error">{{ errors.phone }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="client-email">
                        <i class="fa-solid fa-envelope"></i>
                        Email
                    </label>
                    <input
                        id="client-email"
                        type="email"
                        v-model="linkForm.email"
                        class="form-input"
                        placeholder="example@mail.ru"
                        :disabled="sending"
                    >
                    <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="form-actions">
                <button
                    type="submit"
                    class="btn-primary-modern"
                    :disabled="sending || !isValid"
                >
                    <span v-if="sending" class="spinner-small"></span>
                    <template v-else>
                        <i class="fa-solid fa-paper-plane"></i>
                        Отправить ссылку в чат
                    </template>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { mapActions } from 'pinia'

export default {
    name: 'PaymentLinkForm',

    data() {
        return {
            sending: false,
            linkForm: {
                phone: '',
                name: '',
                email: '',
                amount: 0,
                is_recurrent: false,
                description: 'Оплата заказа',
            },
            errors: {
                amount: '',
                description: '',
                phone: '',
                email: '',
            },
        }
    },

    computed: {
        tg() {
            return window.Telegram?.WebApp
        },

        isValid() {
            return (
                this.linkForm.amount > 0 &&
                this.linkForm.description?.trim().length > 0 &&
                this.linkForm.phone?.trim().length > 0
            )
        },
    },

    mounted() {
        if (this.tg?.BackButton) {
            this.tg.BackButton.show()
            this.tg.BackButton.onClick(() => {
                this.$router.back()
            })
        }
    },

    methods: {
        validateForm() {
            // Сброс ошибок
            Object.keys(this.errors).forEach(key => {
                this.errors[key] = ''
            })

            let isValid = true

            // Валидация суммы
            if (!this.linkForm.amount || this.linkForm.amount <= 0) {
                this.errors.amount = 'Укажите сумму платежа'
                isValid = false
            }

            // Валидация описания
            if (!this.linkForm.description?.trim()) {
                this.errors.description = 'Добавьте описание услуги'
                isValid = false
            }

            // Валидация телефона
            if (!this.linkForm.phone?.trim()) {
                this.errors.phone = 'Укажите телефон клиента'
                isValid = false
            } else if (this.linkForm.phone.replace(/\D/g, '').length < 11) {
                this.errors.phone = 'Некорректный номер телефона'
                isValid = false
            }

            // Валидация email (если указан)
            if (this.linkForm.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.linkForm.email)) {
                this.errors.email = 'Некорректный email'
                isValid = false
            }

            return isValid
        },

        async submit() {
            if (!this.validateForm()) return

            this.sending = true

            try {
                await this.$store.dispatch('sendSBPInvoice', {
                    dataObject: this.linkForm,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Ссылка на оплату отправлена в чат',
                    type: 'success',
                })

                // Сброс формы
                this.resetForm()
            } catch (err) {
                console.error('Ошибка отправки ссылки:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить ссылку на оплату',
                    type: 'error',
                })
            } finally {
                this.sending = false
            }
        },

        resetForm() {
            this.linkForm = {
                phone: '',
                name: '',
                email: '',
                amount: 0,
                is_recurrent: false,
                description: 'Оплата заказа',
            }
        },
    },
}
</script>

<style lang="scss" scoped>
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;

.payment-form-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.form-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0;
}

// ==========================================
// ФОРМА
// ==========================================
.payment-form {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    padding-bottom: 8px;
    border-bottom: 1px solid $admin-border;

    i {
        color: $admin-primary;
    }
}

// ==========================================
// SWITCH (ПЕРЕКЛЮЧАТЕЛЬ)
// ==========================================
.switch-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.switch-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    color: $admin-text;
    cursor: pointer;

    i {
        color: $admin-primary;
        font-size: 1rem;
    }
}

.switch-control {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-primary;

        &::before {
            transform: translateX(20px);
        }
    }

    &:disabled + .switch-slider {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: $admin-border;
    transition: 0.3s;
    border-radius: 28px;

    &::before {
        position: absolute;
        content: '';
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}

// ==========================================
// ФОРМЫ
// ==========================================
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;

    i {
        color: $admin-primary;
        font-size: 0.85rem;
    }

    .required {
        color: $admin-danger;
        font-weight: 700;
    }

    .char-count {
        margin-left: auto;
        font-size: 0.75rem;
        color: $admin-text-muted;
        font-weight: 400;
    }
}

.form-input,
.form-textarea {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    color: $admin-text;
    background: $admin-card-bg;
    transition: all 0.2s;
    min-height: 48px;
    font-family: inherit;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &:disabled {
        background: $admin-bg;
        cursor: not-allowed;
        opacity: 0.6;
    }

    &::placeholder {
        color: $admin-text-muted;
    }
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

.form-error {
    font-size: 0.85rem;
    color: $admin-danger;
    display: flex;
    align-items: center;
    gap: 6px;

    &::before {
        content: '⚠';
        font-size: 0.9rem;
    }
}

// ==========================================
// КНОПКИ
// ==========================================
.form-actions {
    padding-top: 8px;
}

.btn-primary-modern {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;
    background: $admin-primary;
    color: white;

    &:active:not(:disabled) {
        transform: scale(0.98);
        background:  color.adjust($admin-primary, $lightness: -5%);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
}

.spinner-small {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .payment-form {
        max-width: 600px;
        margin: 0 auto;
    }

    .form-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }
}
</style>
