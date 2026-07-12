<template>
    <form @submit.prevent="save" class="promo-form">

        <!-- Код промокода -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fa-solid fa-ticket"></i>
                Промокод
            </h4>

            <div class="form-field">
                <label>
                    Код
                    <span class="required">*</span>
                </label>
                <div class="code-input-wrapper">
                    <input
                        type="text"
                        v-model="form.code"
                        placeholder="SUMMER2024"
                        :class="{ 'has-error': errors.code }"
                        @input="normalizeCode"
                        :disabled="isEdit"
                    >
                    <button
                        type="button"
                        class="generate-btn"
                        @click="generateCode"
                        :disabled="isEdit"
                        title="Сгенерировать"
                    >
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </button>
                </div>
                <span v-if="errors.code" class="field-error">{{ errors.code }}</span>
                <span class="field-hint">Только заглавные буквы и цифры</span>
            </div>

            <div class="form-field">
                <label>Описание</label>
                <textarea
                    v-model="form.description"
                    rows="2"
                    placeholder="Например: Летняя акция 2024"
                ></textarea>
            </div>
        </div>

        <!-- Скидка -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fa-solid fa-percent"></i>
                Скидка
            </h4>

            <div class="discount-type-selector">
                <button
                    type="button"
                    class="type-btn"
                    :class="{ 'is-active': form.discount_type === 'percent' }"
                    @click="form.discount_type = 'percent'"
                >
                    <i class="fa-solid fa-percent"></i>
                    <span>Процент</span>
                </button>
                <button
                    type="button"
                    class="type-btn"
                    :class="{ 'is-active': form.discount_type === 'fixed' }"
                    @click="form.discount_type = 'fixed'"
                >
                    <i class="fa-solid fa-ruble-sign"></i>
                    <span>Фиксированная</span>
                </button>
            </div>

            <div class="form-field">
                <label>
                    Размер скидки
                    <span class="required">*</span>
                </label>
                <div class="input-with-suffix">
                    <input
                        type="number"
                        v-model.number="form.discount"
                        :min="form.discount_type === 'percent' ? 1 : 0"
                        :max="form.discount_type === 'percent' ? 100 : 999999"
                        step="1"
                        placeholder="0"
                        :class="{ 'has-error': errors.discount }"
                    >
                    <span class="input-suffix">
                        {{ form.discount_type === 'percent' ? '%' : '₽' }}
                    </span>
                </div>
                <span v-if="errors.discount" class="field-error">{{ errors.discount }}</span>
            </div>

            <div class="form-field">
                <label>Минимальная сумма заказа</label>
                <div class="input-with-suffix">
                    <input
                        type="number"
                        v-model.number="form.min_order_amount"
                        min="0"
                        step="1"
                        placeholder="0 — без ограничений"
                    >
                    <span class="input-suffix">₽</span>
                </div>
            </div>
        </div>

        <!-- Срок действия -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fa-solid fa-calendar"></i>
                Срок действия

            </h4>

            <div class="form-row">
                <div class="form-field">
                    <label>Начало действия
                        <span class="required">*</span>
                    </label>
                    <input
                        type="date"
                        v-model="form.starts_at"
                        :class="{ 'has-error': errors.starts_at }"
                    >
                    <span v-if="errors.starts_at" class="field-error">{{ errors.starts_at }}</span>
                </div>

                <div class="form-field">
                    <label>Окончание
                        <span class="required">*</span>
                    </label>
                    <input
                        type="date"
                        v-model="form.expires_at"
                        :min="form.starts_at || today"
                    >
                </div>
            </div>

            <div class="form-field">
                <label>Лимит использований</label>
                <input
                    type="number"
                    v-model.number="form.usage_limit"
                    min="0"
                    placeholder="0 — без ограничений"
                >
                <span class="field-hint">Сколько раз можно активировать промокод</span>
            </div>
        </div>

        <!-- Дополнительно -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fa-solid fa-sliders"></i>
                Дополнительно
            </h4>

            <div class="toggle-card">
                <div class="toggle-info">
                    <div class="toggle-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <h5>Только для новых пользователей</h5>
                        <p>Промокод доступен только при первой покупке</p>
                    </div>
                </div>
                <label class="switch">
                    <input type="checkbox" v-model="form.for_new_users">
                    <span class="switch-slider"></span>
                </label>
            </div>

            <div class="toggle-card">
                <div class="toggle-info">
                    <div class="toggle-icon">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <h5>Только для VIP</h5>
                        <p>Доступно только VIP-клиентам</p>
                    </div>
                </div>
                <label class="switch">
                    <input type="checkbox" v-model="form.for_vip">
                    <span class="switch-slider"></span>
                </label>
            </div>

            <div class="toggle-card">
                <div class="toggle-info">
                    <div class="toggle-icon">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div>
                        <h5>Активен</h5>
                        <p>Промокод сразу доступен пользователям</p>
                    </div>
                </div>
                <label class="switch">
                    <input type="checkbox" v-model="form.is_active">
                    <span class="switch-slider"></span>
                </label>
            </div>
        </div>

        <!-- Действия -->
        <div class="form-actions">
            <button
                type="button"
                class="action-btn cancel"
                @click="$emit('cancel')"
                :disabled="promocodes.isSaving"
            >
                Отмена
            </button>
            <button
                type="submit"
                class="action-btn save"
                :disabled="promocodes.isSaving"
            >
                <span v-if="promocodes.isSaving" class="btn-spinner"></span>
                <i v-else class="fa-solid fa-check"></i>
                <span>{{ promocodes.isSaving ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}</span>
            </button>
        </div>

    </form>
</template>

<script>
import { usePromocodes } from '@/MobileClient/Composables/usePromocodes.js';

export default {
    name: 'PromoCodesForm',

    props: {
        code: {
            type: Object,
            default: null,
        },
    },

    emits: ['callback', 'cancel'],

    setup() {
        const promocodes = usePromocodes();
        return { ...promocodes };
    },

    data() {
        return {
            form: {
                code: '',
                description: '',
                discount_type: 'percent',
                discount: 0,
                min_order_amount: 0,
                starts_at: '',
                expires_at: '',
                usage_limit: 0,
                for_new_users: false,
                for_vip: false,
                is_active: true,
            },
            errors: {},
        };
    },

    computed: {
        isEdit() {
            return !!this.code?.id;
        },

        today() {
            return new Date().toISOString().split('T')[0];
        },
    },

    watch: {
        code: {
            immediate: true,
            handler(code) {
                if (code) {
                    this.initializeForm(code);
                } else {
                    this.resetForm();
                }
            },
        },
    },

    methods: {
        initializeForm(code) {
            this.form = {
                code: code.code || '',
                description: code.description || '',
                discount_type: code.discount_type || 'percent',
                discount: code.discount || 0,
                min_order_amount: code.min_order_amount || 0,
                starts_at: code.starts_at ? code.starts_at.split(' ')[0] : '',
                expires_at: code.expires_at ? code.expires_at.split(' ')[0] : '',
                usage_limit: code.usage_limit || 0,
                for_new_users: !!code.for_new_users,
                for_vip: !!code.for_vip,
                is_active: code.is_active ?? true,
            };
        },

        resetForm() {
            this.form = {
                code: '',
                description: '',
                discount_type: 'percent',
                discount: 0,
                min_order_amount: 0,
                starts_at: '',
                expires_at: '',
                usage_limit: 0,
                for_new_users: false,
                for_vip: false,
                is_active: true,
            };
            this.errors = {};
        },

        normalizeCode() {
            this.form.code = this.form.code.toUpperCase().replace(/[^A-Z0-9]/g, '');
        },

        generateCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars[Math.floor(Math.random() * chars.length)];
            }
            this.form.code = code;
        },

        validate() {
            this.errors = {};

            if (!this.form.code || this.form.code.length < 3) {
                this.errors.code = 'Код должен содержать минимум 3 символа';
            }

            if (!this.form.discount || this.form.discount <= 0) {
                this.errors.discount = 'Укажите размер скидки';
            }

            if (this.form.discount_type === 'percent' && this.form.discount > 100) {
                this.errors.discount = 'Процент не может быть больше 100';
            }

            if (!this.form.starts_at) {
                this.errors.starts_at = 'Укажите дату начала';
            }

            if (this.form.expires_at && this.form.starts_at &&
                this.form.expires_at < this.form.starts_at) {
                this.errors.expires_at = 'Дата окончания должна быть позже начала';
            }

            return Object.keys(this.errors).length === 0;
        },

        async save() {
            if (!this.validate()) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Проверьте правильность заполнения полей',
                    type: 'error',
                });
                return;
            }

            try {
                if (this.isEdit) {
                    await this.updatePromocode(this.code.id, this.form);
                } else {
                    await this.createPromocode(this.form);
                }

                this.$emit('callback');
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить',
                    type: 'error',
                });
            }
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
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.promo-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid $border;

    i {
        color: $primary;
    }
}

.form-field {
    margin-bottom: 14px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;

        .required {
            color: $danger;
        }
    }

    input,
    textarea,
    select {
        width: 100%;
        padding: 10px 12px;
        background: $bg;
        border: 1.5px solid $border;
        border-radius: 10px;
        font-size: 0.9rem;
        color: $text;
        font-family: inherit;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &.has-error {
            border-color: $danger;
        }

        &:disabled {
            background: $bg-secondary;
            cursor: not-allowed;
        }
    }

    textarea {
        resize: vertical;
        min-height: 60px;
    }

    .field-error {
        display: block;
        margin-top: 4px;
        font-size: 0.75rem;
        color: $danger;
    }

    .field-hint {
        display: block;
        margin-top: 4px;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

// Код
.code-input-wrapper {
    display: flex;
    gap: 8px;
}

.generate-btn {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, $purple, #7c3aed);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        transform: scale(1.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// Тип скидки
.discount-type-selector {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}

.type-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: $bg-secondary;
    border: 2px solid $border;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        color: $primary;
    }

    &.is-active {
        background: rgba($primary, 0.08);
        border-color: $primary;
        color: $primary;
    }
}

// Input с суффиксом
.input-with-suffix {
    display: flex;
    align-items: stretch;

    input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-suffix {
        padding: 10px 14px;
        background: $bg-secondary;
        border: 1.5px solid $border;
        border-left: none;
        border-radius: 0 10px 10px 0;
        font-weight: 700;
        color: $primary;
        display: flex;
        align-items: center;
    }
}

// Toggle-карточки
.toggle-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    margin-bottom: 10px;

    &:last-child {
        margin-bottom: 0;
    }
}

.toggle-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;

    h5 {
        margin: 0 0 2px;
        font-size: 0.85rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.7rem;
        color: $text-muted;
    }
}

.toggle-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

// Switch
.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 26px;
    flex-shrink: 0;

    input {
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .switch-slider {
            background: $primary;

            &::before {
                transform: translateX(18px);
            }
        }
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: $border;
    border-radius: 26px;
    transition: 0.3s;

    &::before {
        position: absolute;
        content: '';
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

// Действия
.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 8px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &.cancel {
        background: $bg-secondary;
        color: $text;
        border: 1px solid $border;

        &:hover:not(:disabled) {
            border-color: $danger;
            color: $danger;
        }
    }

    &.save {
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.3);

        &:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba($primary, 0.4);
        }
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
