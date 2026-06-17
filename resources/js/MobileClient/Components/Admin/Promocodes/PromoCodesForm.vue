<template>
    <form class="promo-form" @submit.prevent="submitForm">

        <!-- ========================================== -->
        <!-- ОСНОВНАЯ ИНФОРМАЦИЯ -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-ticket"></i>
                Основная информация
            </div>

            <div class="form-group">
                <label class="form-label" for="promo-code">
                    <i class="fa-solid fa-barcode"></i>
                    Текст промокода <span class="required">*</span>
                </label>
                <input
                    id="promo-code"
                    type="text"
                    v-model="promoCodeForm.code"
                    class="form-input"
                    placeholder="Например: SUMMER2025"
                    maxlength="255"
                    required
                    :disabled="isLoading"
                >
                <span v-if="errors.code" class="form-error">{{ errors.code }}</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="promo-description">
                    <i class="fa-solid fa-align-left"></i>
                    Назначение промокода <span class="required">*</span>
                    <span v-if="promoCodeForm.description" class="char-count">
                        {{ promoCodeForm.description.length }}/255
                    </span>
                </label>
                <textarea
                    id="promo-description"
                    v-model="promoCodeForm.description"
                    class="form-textarea"
                    placeholder="Например: Скидка 10% на первый заказ"
                    maxlength="255"
                    rows="2"
                    required
                    :disabled="isLoading"
                ></textarea>
                <span v-if="errors.description" class="form-error">{{ errors.description }}</span>
            </div>

            <!-- Статус -->
            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Доступен для активации</h4>
                        <p class="setting-description">Клиенты смогут использовать этот промокод</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="promo-is-active"
                        type="checkbox"
                        v-model="promoCodeForm.is_active"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПАРАМЕТРЫ СКИДКИ -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-percent"></i>
                Параметры скидки
            </div>

            <!-- Тип скидки -->
            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Тип скидки</h4>
                        <p class="setting-description">
                            {{ promoCodeForm.config.discount_in_percent ? 'Процент от суммы заказа' : 'Фиксированная сумма в рублях' }}
                        </p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="promo-percent"
                        type="checkbox"
                        v-model="promoCodeForm.config.discount_in_percent"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>

            <!-- Величина скидки (единое поле с динамическим суффиксом) -->
            <div class="form-group">
                <label class="form-label" for="promo-amount">
                    <i class="fa-solid fa-coins"></i>
                    Величина скидки <span class="required">*</span>
                </label>
                <div class="input-with-suffix">
                    <input
                        id="promo-amount"
                        type="number"
                        v-model.number="promoCodeForm.cashback_amount"
                        class="form-input"
                        :placeholder="promoCodeForm.config.discount_in_percent ? '50' : '100'"
                        :min="1"
                        :max="promoCodeForm.config.discount_in_percent ? 100 : null"
                        step="1"
                        required
                        :disabled="isLoading"
                    >
                    <span class="input-suffix">
                        {{ promoCodeForm.config.discount_in_percent ? '%' : '₽' }}
                    </span>
                </div>
                <span v-if="errors.cashback_amount" class="form-error">{{ errors.cashback_amount }}</span>
                <span class="form-hint">
                    {{ promoCodeForm.config.discount_in_percent
                    ? 'От 1 до 100 процентов'
                    : 'Фиксированная сумма в рублях' }}
                </span>
            </div>

            <!-- Максимум активаций -->
            <div class="form-group">
                <label class="form-label" for="promo-max-count">
                    <i class="fa-solid fa-hashtag"></i>
                    Максимум активаций <span class="required">*</span>
                </label>
                <input
                    id="promo-max-count"
                    type="number"
                    v-model.number="promoCodeForm.max_activation_count"
                    class="form-input"
                    placeholder="1"
                    min="1"
                    step="1"
                    required
                    :disabled="isLoading"
                >
                <span class="form-hint">Сколько раз можно использовать этот промокод</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ОГРАНИЧЕНИЯ -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-lock"></i>
                Ограничения
            </div>

            <div class="form-group">
                <label class="form-label" for="promo-activate-price">
                    <i class="fa-solid fa-ruble-sign"></i>
                    Минимальная сумма заказа
                </label>
                <div class="input-with-suffix">
                    <input
                        id="promo-activate-price"
                        type="number"
                        v-model.number="promoCodeForm.activate_price"
                        class="form-input"
                        placeholder="0"
                        min="0"
                        step="1"
                        :disabled="isLoading"
                    >
                    <span class="input-suffix">₽</span>
                </div>
                <span class="form-hint">Промокод сработает только при заказе от этой суммы</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="promo-available-to">
                    <i class="fa-solid fa-calendar-days"></i>
                    Действует до
                </label>
                <input
                    id="promo-available-to"
                    type="datetime-local"
                    v-model="promoCodeForm.available_to"
                    class="form-input"
                    :disabled="isLoading"
                >
                <span class="form-hint">Оставьте пустым, если срок не ограничен</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СЕРТИФИКАТ -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-award"></i>
                Сертификат
            </div>

            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Красивый сертификат</h4>
                        <p class="setting-description">Сгенерировать изображение-сертификат для промокода</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="promo-certificate"
                        type="checkbox"
                        v-model="promoCodeForm.need_certificate"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ДЕЙСТВИЯ -->
        <!-- ========================================== -->
        <div class="form-actions">
            <button
                type="button"
                class="btn-secondary-modern"
                @click="$emit('cancel')"
                :disabled="isLoading"
            >
                Отмена
            </button>
            <button
                type="submit"
                class="btn-primary-modern"
                :disabled="isLoading || !isValid"
            >
                <span v-if="isLoading" class="spinner-small"></span>
                <template v-else>
                    <i class="fa-solid fa-floppy-disk"></i>
                    {{ promoCodeForm.id ? 'Обновить' : 'Создать' }}
                </template>
            </button>
        </div>

    </form>
</template>

<script>
export default {
    name: 'PromoCodesForm',

    props: {
        code: {
            type: Object,
            default: null,
        },
    },

    emits: ['callback', 'cancel'],

    data() {
        return {
            isLoading: false,
            promoCodeForm: this.getEmptyForm(),
            errors: {
                code: '',
                description: '',
                cashback_amount: '',
            },
        }
    },

    computed: {
        bot() {
            return window.currentBot
        },

        isValid() {
            return (
                this.promoCodeForm.code?.trim().length > 0 &&
                this.promoCodeForm.description?.trim().length > 0 &&
                this.promoCodeForm.cashback_amount > 0 &&
                this.promoCodeForm.max_activation_count > 0
            )
        },
    },

    watch: {
        // Если включили процент, но значение > 100 — сбрасываем до 50
        'promoCodeForm.config.discount_in_percent'(isPercent) {
            if (isPercent && this.promoCodeForm.cashback_amount > 100) {
                this.promoCodeForm.cashback_amount = 50
            }
        },
    },

    mounted() {
        if (this.code) {
            this.promoCodeForm = {
                id: this.code.id || null,
                code: this.code.code || '',
                description: this.code.description || '',
                cashback_amount: this.code.cashback_amount || 0,
                max_activation_count: this.code.max_activation_count || 1,
                is_active: this.code.is_active ?? true,
                available_to: this.formatDateForInput(this.code.available_to),
                activate_price: this.code.activate_price || 0,
                need_certificate: this.code.need_certificate ?? true,
                config: {
                    discount_in_percent: this.code.config?.discount_in_percent ?? false,
                },
            }
        }
    },

    methods: {
        getEmptyForm() {
            return {
                id: null,
                code: '',
                description: '',
                cashback_amount: 0,
                max_activation_count: 1,
                is_active: true,
                available_to: null,
                activate_price: 0,
                need_certificate: true,
                config: {
                    discount_in_percent: false,
                },
            }
        },

        // Конвертируем дату в формат для input[type=datetime-local]
        formatDateForInput(dateString) {
            if (!dateString) return null

            // Если уже в формате YYYY-MM-DDTHH:MM — возвращаем как есть
            if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(dateString)) {
                return dateString.slice(0, 16)
            }

            // Пробуем использовать глобальный фильтр, если он есть
            if (this.$filters?.local) {
                try {
                    const formatted = this.$filters.local(dateString)
                    if (formatted && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(formatted)) {
                        return formatted.slice(0, 16)
                    }
                } catch (e) {
                    // ignore
                }
            }

            // Fallback: нативное форматирование
            try {
                const date = new Date(dateString)
                if (isNaN(date.getTime())) return null
                const pad = (n) => String(n).padStart(2, '0')
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
            } catch (e) {
                return null
            }
        },

        validateForm() {
            this.errors.code = ''
            this.errors.description = ''
            this.errors.cashback_amount = ''

            let isValid = true

            if (!this.promoCodeForm.code?.trim()) {
                this.errors.code = 'Введите текст промокода'
                isValid = false
            }

            if (!this.promoCodeForm.description?.trim()) {
                this.errors.description = 'Укажите назначение промокода'
                isValid = false
            }

            if (!this.promoCodeForm.cashback_amount || this.promoCodeForm.cashback_amount <= 0) {
                this.errors.cashback_amount = 'Укажите величину скидки'
                isValid = false
            } else if (this.promoCodeForm.config.discount_in_percent && this.promoCodeForm.cashback_amount > 100) {
                this.errors.cashback_amount = 'Процент скидки не может быть больше 100'
                isValid = false
            }

            return isValid
        },

        async submitForm() {
            if (!this.validateForm()) return

            this.isLoading = true

            try {
                const data = new FormData()

                Object.keys(this.promoCodeForm).forEach(key => {
                    const item = this.promoCodeForm[key]
                    if (item === null || item === undefined) return

                    if (typeof item === 'object') {
                        data.append(key, JSON.stringify(item))
                    } else {
                        data.append(key, item)
                    }
                })

                data.append('bot_id', this.bot?.id)

                const response = await this.$store.dispatch('storePromoCodes', {
                    promoCodeForm: data,
                })

                this.$notify?.({
                    title: 'Успех',
                    text: this.promoCodeForm.id ? 'Промокод обновлён' : 'Промокод создан',
                    type: 'success',
                })

                // Сбрасываем форму
                this.promoCodeForm = this.getEmptyForm()

                this.$emit('callback', response?.data)
            } catch (err) {
                console.error('Ошибка сохранения промокода:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить промокод',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
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
$admin-success: #10b981;
$admin-danger: #ef4444;

.promo-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

// ==========================================
// СЕКЦИИ
// ==========================================
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
// НАСТРОЙКИ (SWITCH)
// ==========================================
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 4px 0;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.setting-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.setting-text {
    flex: 1;
    min-width: 0;
}

.setting-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 2px 0;
}

.setting-description {
    font-size: 0.75rem;
    color: $admin-text-muted;
    margin: 0;
    line-height: 1.3;
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
    inset: 0;
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
    width: 100%;

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
    min-height: 70px;
    line-height: 1.5;
}

// Поле с суффиксом (% или ₽)
.input-with-suffix {
    position: relative;
    display: flex;
    align-items: center;

    .form-input {
        padding-right: 44px;
        width: 100%;
    }
}

.input-suffix {
    position: absolute;
    right: 16px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-primary;
    pointer-events: none;
}

.form-hint {
    font-size: 0.8rem;
    color: $admin-text-muted;
    line-height: 1.4;
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
// ДЕЙСТВИЯ
// ==========================================
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 8px;
}

.btn-primary-modern,
.btn-secondary-modern {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;

    &:active:not(:disabled) {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;

    &:active:not(:disabled) {
        background:  color.adjust($admin-primary, $lightness: -5%);
    }
}

.btn-secondary-modern {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;

    &:active:not(:disabled) {
        background: color.adjust($admin-bg, $lightness: -3%);
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
    to { transform: rotate(360deg); }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .promo-form {
        max-width: 700px;
        margin: 0 auto;
    }

    .form-actions {
        justify-content: flex-end;
    }

    .btn-primary-modern,
    .btn-secondary-modern {
        flex: 0 1 auto;
        min-width: 140px;
    }
}
</style>
