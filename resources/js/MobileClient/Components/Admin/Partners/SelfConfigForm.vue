<template>
    <form class="self-config-form" @submit.prevent="handleSubmit">

        <!-- Основная информация -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-id-card"></i>
                Основная информация
            </div>

            <div class="form-group">
                <label class="form-label" for="self-title">
                    <i class="fa-solid fa-heading"></i>
                    Заголовок
                    <span class="required">*</span>
                </label>
                <input
                    id="self-title"
                    type="text"
                    v-model="form.title"
                    class="form-input"
                    :class="{ 'has-error': errors.title }"
                    placeholder="Название вашей компании"
                    :disabled="isLoading"
                    required
                >
                <span v-if="errors.title" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ errors.title }}
                </span>
            </div>

            <div class="form-group">
                <label class="form-label" for="self-description">
                    <i class="fa-solid fa-align-left"></i>
                    Описание
                    <span v-if="form.description" class="char-count">
                        {{ (form.description || '').length }} символов
                    </span>
                </label>
                <textarea
                    id="self-description"
                    v-model="form.description"
                    class="form-textarea"
                    placeholder="Расскажите о вашей компании"
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
                        <img v-lazy="previewImage" class="preview-image" alt="Превью">
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
                        <span class="upload-hint">Перетащите файл сюда или нажмите для выбора</span>
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
    name: 'SelfConfigForm',

    props: {
        modelValue: {
            type: Object,
            default: null,
        },
    },

    emits: ['success', 'cancel'],

    setup() {
        const partners = usePartners()
        return {
            updateSelfPartner: partners.updateSelfPartner,
        }
    },

    data() {
        return {
            isLoading: false,
            file: null,
            preview: null,
            isDragging: false,
            form: {
                title: '',
                description: '',
                image: '',
                config: {
                    excludes: [],
                    bg_color: 'transparent',
                },
            },
            errors: {
                title: '',
            },
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
        },
    },

    mounted() {
        if (this.bot?.settings?.partners) {
            this.form = { ...this.form, ...this.bot.settings.partners }
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
                    const item = this.form[key] || ''
                    if (typeof item === 'object') {
                        data.append(key, JSON.stringify(item))
                    } else {
                        data.append(key, item)
                    }
                })

                if (this.file) {
                    data.append('file', this.file)
                }

                await this.updateSelfPartner({ form: data })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Параметры сохранены',
                    type: 'success',
                })

                this.$emit('success')
            } catch (err) {
                console.error('Ошибка сохранения параметров:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить параметры',
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
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;
$admin-success: #10b981;

.self-config-form {
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

.form-error {
    font-size: 0.85rem;
    color: $admin-danger;
    display: flex;
    align-items: center;
    gap: 6px;
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
    .self-config-form {
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
