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
                                <i class="fa-brands fa-telegram"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">Добавление партнёра</h3>
                                <p class="modal-subtitle">Укажите ссылку на Telegram-бот</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body">
                        <div class="info-alert">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Настройка партнёра происходит после его добавления</span>
                        </div>

                        <form @submit.prevent="handleSubmit" class="partner-form">
                            <div class="form-group">
                                <label class="form-label" for="telegram-input">
                                    <i class="fa-brands fa-telegram"></i>
                                    Ссылка на Telegram-бота
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="telegram-input"
                                    type="text"
                                    v-model="telegramInput"
                                    class="form-input"
                                    :class="{ 'has-error': errors.telegram }"
                                    placeholder="https://t.me/your_bot или @your_bot"
                                    :disabled="isLoading"
                                    required
                                    @input="validateTelegram"
                                >
                                <span v-if="errors.telegram" class="form-error">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ errors.telegram }}
                                </span>
                                <span v-else class="form-hint">
                                    Пример: https://t.me/my_bot или @my_bot
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
            telegramInput: '',
            errors: {
                telegram: '',
            },
        }
    },

    computed: {
        isValid() {
            return this.telegramInput.trim().length > 0 && !this.errors.telegram
        },
    },

    methods: {
        openModal() {
            this.showModal = true
            document.body.style.overflow = 'hidden'
        },

        closeModal() {
            this.showModal = false
            this.telegramInput = ''
            this.errors.telegram = ''
            document.body.style.overflow = ''
        },

        validateTelegram() {
            this.errors.telegram = ''
            const input = this.telegramInput.trim()

            if (!input) return

            const processed = this.processTelegramLink(input)
            const telegramRegex = /^[a-zA-Z0-9_]{5,32}$/

            if (!telegramRegex.test(processed)) {
                this.errors.telegram = 'Некорректный формат. Используйте @username или https://t.me/username'
            }
        },

        processTelegramLink(link) {
            let processedLink = link.trim()

            if (processedLink.startsWith('https://t.me/')) {
                processedLink = processedLink.replace('https://t.me/', '')
            } else if (processedLink.startsWith('http://t.me/')) {
                processedLink = processedLink.replace('http://t.me/', '')
            } else if (processedLink.startsWith('@')) {
                processedLink = processedLink.replace('@', '')
            }

            processedLink = processedLink.split(/[/?#]/)[0]
            return processedLink
        },

        async handleSubmit() {
            if (!this.isValid) return

            this.isLoading = true

            try {
                const processedTelegram = this.processTelegramLink(this.telegramInput)

                const data = new FormData()
                data.append('telegram_domain', processedTelegram)

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
$admin-telegram: #0088cc;

.add-partner-form {
    padding: 16px 0;
}

// ==========================================
// КНОПКА ДОБАВЛЕНИЯ
// ==========================================
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

// ==========================================
// МОДАЛКА
// ==========================================
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
    background: linear-gradient(135deg, $admin-telegram 0%, #0066aa 100%);
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

// ==========================================
// ФОРМА
// ==========================================
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
        color: $admin-telegram;
        font-size: 1rem;
    }

    .required {
        color: $admin-danger;
        font-weight: 700;
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

// ==========================================
// АНИМАЦИИ
// ==========================================
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
