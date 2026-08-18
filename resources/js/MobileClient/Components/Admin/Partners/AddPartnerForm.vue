<template>
    <div class="add-partner-form">
        <button class="btn-add-partner" @click="openModal">
            <i class="fa-solid fa-plus"></i>
            <span>Добавить партнёра</span>
        </button>

        <!-- ========================================== -->
        <!-- МОДАЛКА ДОБАВЛЕНИЯ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <button class="modal-close" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fa-solid fa-globe"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">Добавление партнёра</h3>
                                <p class="modal-subtitle">Укажите ссылку на приложение и теги</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body">
                        <div class="info-alert">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Настройка партнёра происходит после его добавления</span>
                        </div>

                        <form @submit.prevent="handleSubmit" class="partner-form">
                            <!-- URL Input -->
                            <div class="form-group">
                                <label class="form-label" for="url-input">
                                    <i class="fa-solid fa-link"></i>
                                    Ссылка на приложение
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="url-input"
                                    type="text"
                                    v-model="urlInput"
                                    class="form-input"
                                    :class="{ 'has-error': errors.url }"
                                    placeholder="https://test.mypwa.ru"
                                    :disabled="isLoading"
                                    required
                                >
                                <span v-if="errors.url" class="form-error">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ errors.url }}
                                </span>
                                <span v-else class="form-hint">
                                    Пример: https://test.mypwa.ru
                                </span>
                            </div>

                            <!-- 🆕 Tags Input -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-tags"></i>
                                    Теги партнёра
                                    <span class="optional">(необязательно)</span>
                                </label>
                                <div
                                    class="tags-input-container"
                                    :class="{ 'has-error': errors.tags }"
                                    @click="focusTagInput"
                                >
                                    <div class="tags-list">
                                        <span v-for="(tag, index) in tags" :key="index" class="tag-chip">
                                            {{ tag }}
                                            <button type="button" class="tag-remove" @click.stop="removeTag(index)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </span>
                                        <input
                                            ref="tagInputRef"
                                            v-model="tagInput"
                                            @keydown.enter.prevent="addTag"
                                            @keydown.,.prevent="addTag"
                                            @keydown.backspace="handleBackspace"
                                            placeholder="Введите тег и нажмите Enter"
                                            class="tag-input-field"
                                            :disabled="isLoading"
                                        >
                                    </div>
                                </div>
                                <span v-if="errors.tags" class="form-error">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ errors.tags }}
                                </span>
                                <span v-else class="form-hint">
                                    Нажмите Enter или запятую, чтобы добавить. Максимум 10 тегов.
                                </span>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="button"
                                    class="btn-cancel"
                                    @click="closeModal"
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
                                        <i class="fa-solid fa-plus"></i>
                                        Добавить
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'AddPartnerForm',

    emits: ['callback'],

    setup() {
        const partners = usePartners()
        return {
            storePartner: partners.storePartner,
        }
    },

    data() {
        return {
            showModal: false,
            isLoading: false,
            urlInput: '',

            // 🆕 Данные для тегов
            tags: [],
            tagInput: '',

            errors: {
                url: '',
                tags: '',
            },
        }
    },

    computed: {
        isValid() {
            return this.urlInput.trim().length > 0 && !this.errors.url
        },
    },

    methods: {
        openModal() {
            this.showModal = true
            document.body.style.overflow = 'hidden'
            this.$nextTick(() => {
                document.getElementById('url-input')?.focus()
            })
        },

        closeModal() {
            this.showModal = false
            this.urlInput = ''
            this.errors.url = ''

            this.tags = []
            this.tagInput = ''
            this.errors.tags = ''

            document.body.style.overflow = ''
        },

        focusTagInput() {
            this.$refs.tagInputRef?.focus()
        },

        addTag() {
            const newTag = this.tagInput.trim().toLowerCase()
            if (!newTag) return

            if (this.tags.length >= 10) {
                this.errors.tags = 'Максимальное количество тегов: 10'
                return
            }

            if (this.tags.includes(newTag)) {
                this.errors.tags = 'Этот тег уже добавлен'
                this.tagInput = ''
                setTimeout(() => { this.errors.tags = '' }, 2000)
                return
            }

            if (!/^[a-zа-яё0-9\-]+$/i.test(newTag)) {
                this.errors.tags = 'Тег может содержать только буквы, цифры и дефис'
                setTimeout(() => { this.errors.tags = '' }, 2000)
                return
            }

            this.errors.tags = ''
            this.tags.push(newTag)
            this.tagInput = ''
        },

        removeTag(index) {
            this.tags.splice(index, 1)
            this.errors.tags = ''
        },

        handleBackspace() {
            if (!this.tagInput && this.tags.length > 0) {
                this.removeTag(this.tags.length - 1)
            }
        },

        async handleSubmit() {
            if (!this.isValid) return

            const inputUrl = this.urlInput.trim();

            // Добавляем протокол, если его нет, для корректного парсинга URL
            let urlToParse = inputUrl.startsWith('http') ? inputUrl : `https://${inputUrl}`;
            let urlObj;

            try {
                urlObj = new URL(urlToParse);
            } catch (e) {
                this.errors.url = 'Некорректная ссылка';
                return;
            }

            // Проверка домена
            if (!urlObj.hostname.endsWith('mypwa.ru')) {
                this.errors.url = 'Ссылка должна вести на домен mypwa.ru';
                return;
            }

            // Извлекаем slug (поддомен)
            const match = urlObj.hostname.match(/^([a-z0-9-]+)\.mypwa\.ru$/i);
            if (!match) {
                this.errors.url = 'Некорректный формат (ожидается slug.mypwa.ru)';
                return;
            }

            const slug = match[1];

            this.isLoading = true

            try {
                const data = new FormData()
                data.append('url', inputUrl)
                data.append('slug', slug) // 🆕 Передаем извлеченный slug

                // Добавляем теги в FormData
                this.tags.forEach(tag => {
                    data.append('tags[]', tag)
                })

                await this.storePartner({ form: data })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Партнёр успешно добавлен',
                    type: 'success',
                })

                this.closeModal()
                this.$emit('callback')
            } catch (err) {
                console.error('Ошибка добавления партнёра:', err)
                const errorMessage = err?.response?.data?.message || 'Не удалось добавить партнёра'

                this.$notify?.({
                    title: 'Ошибка',
                    text: errorMessage,
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

.add-partner-form {
    padding: 16px 0;
}

.btn-add-partner {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);
    min-height: 48px;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($admin-primary, 0.4);
    }

    &:active {
        transform: translateY(0);
    }
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid $admin-border;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $admin-bg;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: $admin-text;
}

.modal-subtitle {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.info-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    background: rgba($admin-primary, 0.06);
    border: 1px solid rgba($admin-primary, 0.15);
    border-radius: 10px;
    color: $admin-text;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 20px;

    i {
        color: $admin-primary;
        font-size: 1rem;
        flex-shrink: 0;
        margin-top: 1px;
    }
}

.partner-form {
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
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;

    i {
        color: $admin-primary;
        font-size: 1rem;
    }

    .required {
        color: $admin-danger;
        font-weight: 700;
    }

    .optional {
        color: $admin-text-muted;
        font-weight: 400;
        font-size: 0.8rem;
    }
}

.form-input {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 10px;
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

.tags-input-container {
    border: 1px solid $admin-border;
    border-radius: 10px;
    background: $admin-card-bg;
    padding: 8px 12px;
    min-height: 48px;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    cursor: text;

    &:focus-within {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &.has-error {
        border-color: $admin-danger;

        &:focus-within {
            box-shadow: 0 0 0 3px rgba($admin-danger, 0.1);
        }
    }
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    animation: tagPop 0.2s ease;
}

.tag-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: $admin-primary;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.15s;

    &:hover {
        background: $admin-primary;
        color: white;
    }
}

.tag-input-field {
    flex: 1;
    min-width: 120px;
    border: none;
    outline: none;
    font-size: 0.95rem;
    color: $admin-text;
    background: transparent;
    padding: 4px 0;

    &::placeholder {
        color: $admin-text-muted;
    }

    &:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
}

@keyframes tagPop {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
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

.modal-fade-enter-active {
    transition: opacity 0.3s ease;

    .modal-container {
        animation: modalSlideUp 0.3s ease;
    }
}

.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 640px) {
    .modal-overlay {
        padding: 0;
    }

    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
}
</style>
