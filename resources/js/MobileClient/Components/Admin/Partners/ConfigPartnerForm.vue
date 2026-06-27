<template>
    <form class="partner-config-form" @submit.prevent="handleSubmit">

        <!-- Основная информация -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-id-card"></i>
                Основная информация
            </div>

            <!-- Статус -->
            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon status">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Активен</h4>
                        <p class="setting-description">Партнёр отображается в системе</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="partner-is-active"
                        type="checkbox"
                        v-model="form.is_active"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>

            <!-- Заголовок -->
            <div class="form-group">
                <label class="form-label" for="partner-title">
                    <i class="fa-solid fa-heading"></i>
                    Заголовок
                    <span class="required">*</span>
                </label>
                <input
                    id="partner-title"
                    type="text"
                    v-model="form.title"
                    class="form-input"
                    :class="{ 'has-error': errors.title }"
                    placeholder="Название партнёра"
                    :disabled="isLoading"
                    required
                >
                <span v-if="errors.title" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ errors.title }}
                </span>
            </div>

            <!-- Позиция -->
            <div class="form-group">
                <label class="form-label" for="partner-position">
                    <i class="fa-solid fa-arrow-down-1-9"></i>
                    Позиция в выдаче
                </label>
                <input
                    id="partner-position"
                    type="number"
                    v-model.number="form.order_position"
                    class="form-input"
                    placeholder="0"
                    min="0"
                    :disabled="isLoading"
                >
                <span class="form-hint">Чем меньше число, тем выше в списке</span>
            </div>
        </div>

        <!-- Описание -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-align-left"></i>
                Описание
            </div>
            <div class="form-group">
                <label class="form-label" for="partner-description">
                    <i class="fa-solid fa-file-lines"></i>
                    Текст описания
                    <span v-if="form.description" class="char-count">
                        {{ (form.description || '').length }} символов
                    </span>
                </label>
                <textarea
                    id="partner-description"
                    v-model="form.description"
                    class="form-textarea"
                    placeholder="Расскажите о партнёре"
                    rows="4"
                    :disabled="isLoading"
                ></textarea>
            </div>
        </div>

        <!-- Изображение -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-image"></i>
                Изображение
            </div>

            <div
                class="file-uploader"
                :class="{ 'is-dragging': isDragging, 'has-image': previewImage }"
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onFileDrop"
            >
                <template v-if="previewImage">
                    <div class="preview-container">
                        <img :src="previewImage" class="preview-image" alt="Превью">
                        <button
                            type="button"
                            class="remove-btn"
                            @click="removeImage"
                            :disabled="isLoading"
                            title="Удалить изображение"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="file-info">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>{{ file ? file.name : 'Текущее изображение' }}</span>
                    </div>
                </template>
                <template v-else>
                    <div class="upload-prompt">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span class="upload-title">Загрузить изображение</span>
                        <span class="upload-hint">Перетащите файл или нажмите для выбора</span>
                    </div>
                </template>

                <input
                    ref="fileInput"
                    type="file"
                    class="file-input"
                    accept="image/*"
                    @change="onFileChange"
                    :disabled="isLoading"
                >
            </div>

            <button
                v-if="!previewImage"
                type="button"
                class="btn-upload"
                @click="triggerFileInput"
                :disabled="isLoading"
            >
                <i class="fa-solid fa-folder-open"></i>
                Выбрать файл
            </button>
        </div>

        <!-- Финансы -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-coins"></i>
                Финансы
            </div>
            <div class="form-group">
                <label class="form-label" for="partner-charge">
                    <i class="fa-solid fa-percent"></i>
                    Дополнительная плата (наценка)
                </label>
                <div class="input-with-suffix">
                    <input
                        id="partner-charge"
                        type="number"
                        v-model.number="form.extra_charge"
                        class="form-input"
                        placeholder="0"
                        min="0"
                        step="0.01"
                        :disabled="isLoading"
                    >
                    <span class="input-suffix">%</span>
                </div>
                <span class="form-hint">Процент наценки на товары партнёра</span>
            </div>
        </div>

        <!-- Действия -->
        <div class="form-actions">
            <button
                type="button"
                class="btn-cancel"
                @click="$emit('cancel')"
                :disabled="isLoading"
            >
                Отмена
            </button>
            <button
                type="submit"
                class="btn-submit"
                :disabled="isLoading || !isValid"
            >
                <span v-if="isLoading" class="spinner-small"></span>
                <template v-else>
                    <i class="fa-solid fa-floppy-disk"></i>
                    Сохранить
                </template>
            </button>
        </div>

    </form>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'ConfigPartnerForm',

    props: {
        initialData: {
            type: Object,
            default: null,
        },
    },

    emits: ['success', 'cancel', 'select'],

    setup() {
        const partners = usePartners()
        return {
            updatePartner: partners.updatePartner,
        }
    },

    data() {
        return {
            isLoading: false,
            file: null,
            preview: null,
            isDragging: false,
            errors: {
                title: '',
            },
            form: {
                id: null,
                title: '',
                description: '',
                image: '',
                order_position: 0,
                is_active: true,
                extra_charge: 0,
                demo_mode: true,
                config: {
                    excludes: [],
                    bg_color: 'transparent',
                },
            }
        }
    },

    computed: {
        bot() {
            return window.currentBot || null
        },

        isValid() {
            return this.form.title?.trim().length > 0
        },

        previewImage() {
            if (this.preview) return this.preview
            if (this.form.image && this.bot?.id) {
                return `/images-by-bot-id/${this.bot.id}/${this.form.image}`
            }
            return null
        }
    },

    mounted() {
        if (this.initialData) {
            this.form = { ...this.form, ...this.initialData }
        }
    },

    beforeUnmount() {
        if (this.preview) {
            URL.revokeObjectURL(this.preview)
        }
    },

    methods: {
        triggerFileInput() {
            this.$refs.fileInput?.click()
        },

        onFileChange(e) {
            const file = e.target.files?.[0]
            this.handleFile(file)
        },

        onFileDrop(e) {
            this.isDragging = false
            const file = e.dataTransfer?.files?.[0]
            if (file && file.type.startsWith('image/')) {
                this.handleFile(file)
            }
        },

        handleFile(file) {
            if (!file) return

            if (this.preview) {
                URL.revokeObjectURL(this.preview)
            }

            this.file = file
            this.preview = URL.createObjectURL(file)
            this.$emit('select', file)
        },

        removeImage() {
            if (this.preview) {
                URL.revokeObjectURL(this.preview)
            }
            this.file = null
            this.preview = null
            this.form.image = ''

            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = ''
            }
        },

        validateForm() {
            this.errors.title = ''

            if (!this.form.title?.trim()) {
                this.errors.title = 'Заголовок обязателен для заполнения'
                return false
            }

            if (this.form.title.trim().length < 2) {
                this.errors.title = 'Заголовок должен содержать минимум 2 символа'
                return false
            }

            return true
        },

        async handleSubmit() {
            if (!this.validateForm()) return

            this.isLoading = true

            try {
                const data = new FormData()

                Object.keys(this.form).forEach(key => {
                    const item = this.form[key]
                    if (item !== null && item !== undefined) {
                        if (typeof item === 'object') {
                            data.append(key, JSON.stringify(item))
                        } else {
                            data.append(key, item)
                        }
                    }
                })

                if (this.file) {
                    data.append('file', this.file)
                }

                await this.updatePartner({ form: data })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Параметры партнёра сохранены',
                    type: 'success',
                })

                this.$emit('success')
            } catch (err) {
                console.error('Ошибка сохранения партнёра:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить параметры',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },
    }
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;

.partner-config-form {
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
// НАСТРОЙКИ
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
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.status {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }
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
    border-radius: 10px;
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

    &.has-error {
        border-color: $admin-danger;

        &:focus {
            box-shadow: 0 0 0 3px rgba($admin-danger, 0.1);
        }
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
}

.input-with-suffix {
    position: relative;
    display: flex;
    align-items: center;

    .form-input {
        padding-right: 40px;
        width: 100%;
    }
}

.input-suffix {
    position: absolute;
    right: 16px;
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text-muted;
    pointer-events: none;
}

// ==========================================
// ЗАГРУЗКА ФАЙЛОВ
// ==========================================
.file-uploader {
    position: relative;
    border: 2px dashed $admin-border;
    border-radius: 12px;
    background: $admin-bg;
    transition: all 0.2s;
    overflow: hidden;

    &.is-dragging {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }

    &.has-image {
        border-style: solid;
        border-color: $admin-border;
    }
}

.file-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;

    &:disabled {
        cursor: not-allowed;
    }
}

.upload-prompt {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 16px;
    color: $admin-text-muted;

    i {
        font-size: 2rem;
        color: $admin-primary;
        margin-bottom: 4px;
    }

    .upload-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: $admin-text;
    }

    .upload-hint {
        font-size: 0.8rem;
    }
}

.preview-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: $admin-bg;
    overflow: hidden;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);

    &:hover {
        background: $admin-danger;
        transform: scale(1.1);
    }

    &:active {
        transform: scale(0.9);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.file-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    font-size: 0.85rem;
    color: $admin-text;
    background: $admin-card-bg;
    border-top: 1px solid $admin-border;

    i {
        color: $admin-success;
    }

    span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.btn-upload {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    color: $admin-primary;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:hover:not(:disabled) {
        background: rgba($admin-primary, 0.04);
        border-color: $admin-primary;
    }

    &:active:not(:disabled) {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// ==========================================
// SWITCH
// ==========================================
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
        background: $admin-success;

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
// ДЕЙСТВИЯ
// ==========================================
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 8px;
}

.btn-cancel, .btn-submit {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 10px;
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
    }
}

.btn-cancel {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;

    &:hover:not(:disabled) {
        background: $admin-border;
    }
}

.btn-submit {
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba($admin-primary, 0.4);
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

@media (min-width: 768px) {
    .partner-config-form {
        max-width: 700px;
        margin: 0 auto;
    }

    .form-actions {
        justify-content: flex-end;
    }

    .btn-cancel, .btn-submit {
        flex: 0 1 auto;
        min-width: 140px;
    }
}
</style>
