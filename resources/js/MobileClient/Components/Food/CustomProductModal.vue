<template>
    <transition name="modal-fade">
        <div v-if="modelValue" class="custom-product-overlay" @click.self="closeModal">
            <div class="custom-product-modal">

                <!-- Шапка -->
                <div class="modal-header">
                    <div class="header-icon">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div class="header-info">
                        <h6 class="modal-title">Добавить свой товар</h6>
                        <p class="modal-subtitle">Укажите название и примерную стоимость</p>
                    </div>
                    <button class="close-btn" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Форма -->
                <div class="modal-body">
                    <!-- Название -->
                    <div class="form-group">
                        <label class="form-label">Название товара <span class="required">*</span></label>
                        <div class="input-wrapper" :class="{ 'has-error': errors.name }">
                            <i class="fa-solid fa-tag input-icon"></i>
                            <input
                                ref="nameInput"
                                v-model="form.name"
                                type="text"
                                class="form-input"
                                placeholder="Например: Сыр Камамбер, Авокадо Хасс..."
                                @input="errors.name = false"
                            >
                        </div>
                        <span v-if="errors.name" class="error-text">Введите название товара</span>
                    </div>

                    <!-- Ряд: Количество, Ед. изм., Цена -->
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Кол-во <span class="required">*</span></label>
                            <div class="input-wrapper" :class="{ 'has-error': errors.qty }">
                                <input
                                    v-model.number="form.qty"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    class="form-input"
                                    placeholder="1"
                                    @input="errors.qty = false"
                                >
                            </div>
                        </div>

                        <div class="form-group flex-1">
                            <label class="form-label">Ед. изм.</label>
                            <div class="input-wrapper">
                                <select v-model="form.unit" class="form-select">
                                    <option value="шт">шт</option>
                                    <option value="кг">кг</option>
                                    <option value="г">г</option>
                                    <option value="л">л</option>
                                    <option value="уп">уп</option>
                                    <option value="другое">другое</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group flex-2">
                            <label class="form-label">Примерная цена <span class="required">*</span></label>
                            <div class="input-wrapper price-input" :class="{ 'has-error': errors.price }">
                                <input
                                    v-model.number="form.price"
                                    type="number"
                                    min="0"
                                    class="form-input"
                                    placeholder="0"
                                    @input="errors.price = false"
                                >
                                <span class="currency-suffix">₽</span>
                            </div>
                        </div>
                    </div>

                    <!-- Комментарий к товару -->
                    <div class="form-group">
                        <label class="form-label">Пожелание к товару</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-comment input-icon"></i>
                            <textarea
                                v-model="form.comment"
                                class="form-textarea"
                                rows="2"
                                placeholder="Например: спелые, без зеленых боков, максимально свежие..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Подвал с кнопками -->
                <div class="modal-footer">
                    <button class="btn-cancel" @click="closeModal">Отмена</button>
                    <button class="btn-add" @click="submitCustomProduct" :disabled="isSubmitting">
                        <i class="fa-solid fa-plus"></i>
                        <span>Добавить в список</span>
                    </button>
                </div>

            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "CustomProductModal",

    props: {
        modelValue: {
            type: Boolean,
            default: false
        }
    },

    emits: ['update:modelValue', 'add-custom-product'],

    data() {
        return {
            isSubmitting: false,
            form: {
                name: '',
                qty: 1,
                unit: 'шт',
                price: null,
                comment: ''
            },
            errors: {
                name: false,
                qty: false,
                price: false
            }
        };
    },

    watch: {
        // Автофокус на поле имени при открытии
        modelValue(newValue) {
            if (newValue) {
                this.$nextTick(() => {
                    this.$refs.nameInput?.focus();
                });
            }
        }
    },

    methods: {
        closeModal() {
            this.resetForm();
            this.$emit('update:modelValue', false);
        },

        resetForm() {
            this.form = { name: '', qty: 1, unit: 'шт', price: null, comment: '' };
            this.errors = { name: false, qty: false, price: false };
            this.isSubmitting = false;
        },

        submitCustomProduct() {
            // Валидация
            let hasError = false;
            if (!this.form.name.trim()) { this.errors.name = true; hasError = true; }
            if (!this.form.qty || this.form.qty <= 0) { this.errors.qty = true; hasError = true; }
            if (this.form.price === null || this.form.price < 0) { this.errors.price = true; hasError = true; }

            if (hasError) return;

            this.isSubmitting = true;

            // Формируем объект товара
            const customProduct = {
                id: `custom_${Date.now()}`, // Уникальный ID
                name: this.form.name.trim(),
                qty: Number(this.form.qty),
                unit: this.form.unit,
                price: Number(this.form.price),
                comment: this.form.comment.trim(),
                isCustom: true, // Флаг, что это пользовательский товар
                priceType: 'open', // Свои товары всегда считаем как "примерная цена"
                emoji: '📝' // Дефолтная иконка
            };

            // Имитация небольшой задержки для UX
            setTimeout(() => {
                this.$emit('add-custom-product', customProduct);
                this.closeModal();
            }, 300);
        }
    }
};
</script>

<style scoped>
/* ==========================================
   OVERLAY & MODAL
   ========================================== */
.custom-product-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end; /* На мобильных снизу */
    justify-content: center;
}

@media (min-width: 576px) {
    .custom-product-overlay {
        align-items: center; /* На десктопе по центру */
    }
}

.custom-product-modal {
    width: 100%;
    max-width: 500px;
    background: var(--bs-body-bg);
    border-radius: 24px 24px 0 0;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@media (min-width: 576px) {
    .custom-product-modal {
        border-radius: 24px;
        max-height: 85vh;
        animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* ==========================================
   HEADER
   ========================================== */
.modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--bs-border-color);
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.header-info { flex: 1; }
.modal-title { margin: 0; font-weight: 700; font-size: 1.1rem; color: var(--bs-body-color); }
.modal-subtitle { margin: 2px 0 0; font-size: 0.8rem; color: var(--bs-secondary-color); }

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.close-btn:hover { background: #dc3545; color: white; transform: rotate(90deg); }

/* ==========================================
   BODY & FORM
   ========================================== */
.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.form-group { margin-bottom: 20px; }
.form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 8px;
}
.required { color: #dc3545; }

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--bs-secondary-bg);
    border: 2px solid transparent;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.input-wrapper:focus-within {
    background: var(--bs-body-bg);
    border-color: #43e97b;
    box-shadow: 0 0 0 4px rgba(67, 233, 123, 0.1);
}

.input-wrapper.has-error {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.input-icon {
    padding-left: 14px;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 14px;
    background: transparent;
    border: none;
    color: var(--bs-body-color);
    font-size: 0.95rem;
    outline: none;
    font-family: inherit;
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
    cursor: pointer;
}

.form-textarea {
    resize: none;
    line-height: 1.5;
}

.price-input .form-input { padding-right: 30px; }
.currency-suffix {
    position: absolute;
    right: 14px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    pointer-events: none;
}

.error-text {
    display: block;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #dc3545;
    animation: shake 0.3s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

/* Ряд полей */
.form-row {
    display: flex;
    gap: 12px;
}
.flex-1 { flex: 1; }
.flex-2 { flex: 2; }

/* ==========================================
   FOOTER
   ========================================== */
.modal-footer {
    padding: 16px 24px 24px;
    border-top: 1px solid var(--bs-border-color);
    display: flex;
    gap: 12px;
}

.btn-cancel, .btn-add {
    flex: 1;
    padding: 14px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}
.btn-cancel:hover { background: var(--bs-border-color); }

.btn-add {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);
}
.btn-add:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(67, 233, 123, 0.4); }
.btn-add:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Анимация исчезновения */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>
