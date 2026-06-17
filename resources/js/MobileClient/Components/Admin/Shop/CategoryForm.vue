<template>
    <form class="category-form" @submit.prevent="saveCategory">
        <div class="form-section">
            <div class="form-group">
                <label class="form-label" for="category-title">
                    <i class="fa-solid fa-tag"></i>
                    Название категории <span class="required">*</span>
                </label>
                <input
                    id="category-title"
                    type="text"
                    v-model="categoryForm.title"
                    class="form-input"
                    placeholder="Введите название"
                    required
                    :disabled="isLoading"
                >
                <span v-if="errors.title" class="form-error">{{ errors.title }}</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="category-position">
                    <i class="fa-solid fa-arrow-down-1-9"></i>
                    Позиция в выдаче
                </label>
                <input
                    id="category-position"
                    type="number"
                    v-model.number="categoryForm.order_position"
                    class="form-input"
                    placeholder="0"
                    min="0"
                    :disabled="isLoading"
                >
                <span class="form-hint">Чем меньше число, тем выше категория в списке</span>
            </div>
        </div>

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
                <span v-else>
                    <i class="fa-solid fa-check"></i>
                    {{ categoryForm.id ? 'Обновить' : 'Добавить' }}
                </span>
            </button>
        </div>
    </form>
</template>

<script>
import { mapActions } from 'pinia'
import { useProductsStore } from '@/MobileClient/stores/Shop/products'

export default {
    name: 'CategoryForm',

    props: {
        item: {
            type: Object,
            default: null,
        },
    },

    emits: ['callback', 'cancel'],

    data() {
        return {
            isLoading: false,
            categoryForm: {
                id: null,
                title: '',
                order_position: 0,
            },
            errors: {
                title: '',
            },
        }
    },

    computed: {
        tenant() {
            return window.Tenant || null
        },

        isValid() {
            return this.categoryForm.title?.trim().length > 0
        },
    },

    mounted() {
        if (this.item) {
            this.categoryForm.id = this.item.id || null
            this.categoryForm.title = this.item.title || ''
            this.categoryForm.order_position = this.item.order_position || 0
        }
    },

    methods: {
        ...mapActions(useProductsStore, ['addProductCategory', 'storeProductCategory']),

        validateForm() {
            this.errors.title = ''

            if (!this.categoryForm.title?.trim()) {
                this.errors.title = 'Название обязательно для заполнения'
                return false
            }

            if (this.categoryForm.title.trim().length < 2) {
                this.errors.title = 'Название должно содержать минимум 2 символа'
                return false
            }

            return true
        },

        async saveCategory() {
            if (!this.validateForm()) return

            this.isLoading = true

            try {
                const payload = {
                    category: {
                        ...this.categoryForm,
                        tenant_id: this.tenant?.id,
                    },
                }

                // Если есть ID — обновляем, иначе создаём
                if (this.categoryForm.id) {
                    await this.storeProductCategory(payload)
                } else {
                    await this.addProductCategory(payload)
                }

                this.$notify?.({
                    title: 'Успех',
                    text: this.categoryForm.id ? 'Категория обновлена' : 'Категория добавлена',
                    type: 'success',
                })

                this.$emit('callback')
            } catch (err) {
                console.error('Ошибка сохранения категории:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить категорию',
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
$admin-danger: #ef4444;

.category-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
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
}

.form-input {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    color: $admin-text;
    background: $admin-card-bg;
    transition: all 0.2s;
    min-height: 48px;

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

    &:active {
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
    to {
        transform: rotate(360deg);
    }
}

// Адаптив для десктопа
@media (min-width: 768px) {
    .category-form {
        max-width: 600px;
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
