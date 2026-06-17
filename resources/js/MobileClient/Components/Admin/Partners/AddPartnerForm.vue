<template>
    <div class="add-partner-form">

        <!-- Кнопка добавления -->
        <button class="btn-add-partner" @click="openModal">
            <i class="fa-solid fa-plus"></i>
            <span>Добавить партнера</span>
        </button>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ДОБАВЛЕНИЕ ПАРТНЕРА (BOTTOM SHEET) -->
        <!-- ========================================== -->
        <div v-if="showModal" class="modal-overlay bottom-sheet" @click.self="closeModal">
            <div class="modal-container add-modal">
                <div class="modal-header">
                    <h3>Добавление партнера</h3>
                    <button class="modal-close" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Подсказка -->
                    <div class="info-alert">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Настройка партнера происходит после его добавления</span>
                    </div>

                    <!-- Форма -->
                    <form @submit.prevent="handleSubmit" class="partner-form">
                        <div class="form-group">
                            <label class="form-label" for="telegram-input">
                                <i class="fa-brands fa-telegram"></i>
                                Ссылка на Telegram-бота <span class="required">*</span>
                            </label>
                            <input
                                id="telegram-input"
                                type="text"
                                v-model="telegramInput"
                                class="form-input"
                                placeholder="https://t.me/your_bot или @your_bot"
                                :disabled="isLoading"
                                required
                                @input="validateTelegram"
                            >
                            <span v-if="errors.telegram" class="form-error">{{ errors.telegram }}</span>
                            <span v-else class="form-hint">
                                Пример: https://t.me/my_bot или @my_bot
                            </span>
                        </div>

                        <div class="form-actions">
                            <button
                                type="button"
                                class="btn-secondary-modern"
                                @click="closeModal"
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
                                    <i class="fa-solid fa-plus"></i>
                                    Добавить
                                </template>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'AddPartnerForm',

    emits: ['callback'],

    data() {
        return {
            showModal: false,
            isLoading: false,
            telegramInput: '',
            errors: {
                telegram: '',
            },
        };
    },

    computed: {
        isValid() {
            return this.telegramInput.trim().length > 0 && !this.errors.telegram;
        },
    },

    methods: {
        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.telegramInput = '';
            this.errors.telegram = '';
        },

        validateTelegram() {
            this.errors.telegram = '';

            const input = this.telegramInput.trim();

            if (!input) {
                return; // Пустое поле — не показываем ошибку, но isValid будет false
            }

            // Проверяем формат
            const processed = this.processTelegramLink(input);

            // Telegram username: 5-32 символа, буквы, цифры, подчеркивания
            const telegramRegex = /^[a-zA-Z0-9_]{5,32}$/;

            if (!telegramRegex.test(processed)) {
                this.errors.telegram = 'Некорректный формат. Используйте @username или https://t.me/username';
            }
        },

        processTelegramLink(link) {
            let processedLink = link.trim();

            // Убираем https://t.me/ если это ссылка
            if (processedLink.startsWith('https://t.me/')) {
                processedLink = processedLink.replace('https://t.me/', '');
            }
            // Убираем http://t.me/ (на всякий случай)
            else if (processedLink.startsWith('http://t.me/')) {
                processedLink = processedLink.replace('http://t.me/', '');
            }
            // Убираем @ если это домен
            else if (processedLink.startsWith('@')) {
                processedLink = processedLink.replace('@', '');
            }

            // Убираем лишние символы в конце (/, ?, и т.д.)
            processedLink = processedLink.split(/[/?#]/)[0];

            return processedLink;
        },

        async handleSubmit() {
            if (!this.isValid) return;

            this.isLoading = true;

            try {
                const processedTelegram = this.processTelegramLink(this.telegramInput);

                const data = new FormData();
                data.append('telegram_domain', processedTelegram);

                await this.$store.dispatch('storePartner', { form: data });

                this.$notify?.({
                    title: 'Успех',
                    text: 'Партнер успешно добавлен',
                    type: 'success',
                });

                this.closeModal();
                this.$emit('callback');
            } catch (err) {
                console.error('Ошибка добавления партнера:', err);

                const errorMessage = err?.response?.data?.message || 'Не удалось добавить партнера';

                this.$notify?.({
                    title: 'Ошибка',
                    text: errorMessage,
                    type: 'error',
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
};
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
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);
    min-height: 48px;

    i {
        font-size: 1rem;
    }

    &:active {
        transform: scale(0.98);
        background:  color.adjust($admin-primary, $lightness: -5%);
    }
}

// ==========================================
// МОДАЛКА (BOTTOM SHEET)
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.2s ease;

    &.bottom-sheet {
        align-items: flex-end;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border-radius: 16px 16px 0 0;
    animation: slideUp 0.3s ease;

    .bottom-sheet & {
        border-radius: 16px 16px 0 0;
        max-height: 80vh;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.add-modal {
    max-width: 100%;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    background: $admin-card-bg;
    z-index: 10;

    h3 {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-danger;
        color: white;
    }
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// ИНФОРМАЦИОННОЕ СООБЩЕНИЕ
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

// ==========================================
// ФОРМА
// ==========================================
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
    to {
        transform: rotate(360deg);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .add-partner-form {
        max-width: 500px;
        margin: 0 auto;
    }

    .modal-overlay {
        padding: 20px;
        align-items: center;

        &.bottom-sheet {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 500px;
        border-radius: 16px;
        max-height: 90vh;

        .bottom-sheet & {
            border-radius: 16px;
            max-height: 90vh;
        }
    }
}
</style>
