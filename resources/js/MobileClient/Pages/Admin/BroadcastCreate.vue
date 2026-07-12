<template>
    <div class="broadcast-create-page">

        <!-- ========================================== -->
        <!-- HERO -->
        <!-- ========================================== -->
        <div class="create-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <button
                    type="button"
                    class="back-btn" @click="goBack">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="hero-icon">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <h2 class="hero-title">Создание рассылки</h2>
                <p class="hero-subtitle">
                    Настройте сообщение, медиа и получателей
                </p>
            </div>
        </div>

        <div class="container px-3">

            <form @submit.prevent="submitForm" class="create-form">

                <!-- Основная информация -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-info-circle"></i>
                        <h3>Основная информация</h3>
                    </div>

                    <div class="form-field">
                        <label>
                            Название рассылки
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.title"
                            placeholder="Например: Акция выходного дня"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label>
                            Текст сообщения
                            <span class="char-counter">
                                {{ (form.message || '').length }}/4000
                            </span>
                        </label>
                        <textarea
                            v-model="form.message"
                            maxlength="4000"
                            rows="6"
                            placeholder="Введите текст сообщения..."
                        ></textarea>
                    </div>
                </div>

                <!-- Медиафайлы -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-images"></i>
                        <h3>Медиафайлы</h3>
                        <span class="section-hint">(необязательно)</span>
                    </div>

                    <MediaUploader
                        v-model="mediaFiles"
                        :broadcast-id="broadcastId"
                    />
                </div>

                <!-- Кнопки -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-keyboard"></i>
                        <h3>Мини-клавиатура</h3>
                        <span class="section-hint">(необязательно)</span>
                    </div>

                    <KeyboardBuilder v-model="form.buttons" />
                </div>

                <!-- Получатели -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-users"></i>
                        <h3>Получатели</h3>
                    </div>

                    <RecipientsSelector
                        v-model="form.recipient_type"
                        v-model:filters="form.recipient_filters"
                    />
                </div>

                <!-- Планирование -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fa-solid fa-clock"></i>
                        <h3>Время отправки</h3>
                    </div>

                    <div class="schedule-options">
                        <label class="radio-option">
                            <input
                                type="radio"
                                v-model="scheduleType"
                                value="now"
                            >
                            <span class="radio-label">
                                <i class="fa-solid fa-bolt"></i>
                                Отправить сейчас
                            </span>
                        </label>

                        <label class="radio-option">
                            <input
                                type="radio"
                                v-model="scheduleType"
                                value="scheduled"
                            >
                            <span class="radio-label">
                                <i class="fa-solid fa-calendar"></i>
                                Запланировать
                            </span>
                        </label>

                        <label class="radio-option">
                            <input
                                type="radio"
                                v-model="scheduleType"
                                value="draft"
                            >
                            <span class="radio-label">
                                <i class="fa-solid fa-file-pen"></i>
                                Сохранить как черновик
                            </span>
                        </label>
                    </div>

                    <div v-if="scheduleType === 'scheduled'" class="form-field">
                        <label>Дата и время отправки</label>
                        <input
                            type="datetime-local"
                            v-model="form.scheduled_at"
                            :min="minDateTime"
                        >
                    </div>
                </div>

                <!-- Предпросмотр -->
                <div class="form-section preview-section">
                    <div class="section-header">
                        <i class="fa-solid fa-eye"></i>
                        <h3>Предпросмотр</h3>
                    </div>

                    <MessagePreview
                        :message="form.message"
                        :media="mediaFiles"
                        :buttons="form.buttons"
                    />
                </div>

                <!-- Действия -->
                <div class="form-actions">
                    <button
                        type="button"
                        class="btn-cancel"
                        @click="goBack"
                    >
                        Отмена
                    </button>

                    <button
                        type="submit"
                        class="btn-submit"
                        :disabled="isSubmitting || !isFormValid"
                    >
                        <span v-if="isSubmitting" class="btn-spinner"></span>
                        <i v-else :class="submitIcon"></i>
                        <span>{{ submitText }}</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>

<script>
import { useBroadcasts } from '@/MobileClient/Composables/useBroadcasts.js';
import MediaUploader from '@/MobileClient/Components/Admin/Broadcasts/MediaUploader.vue';
import KeyboardBuilder from '@/MobileClient/Components/Admin/Broadcasts/KeyboardBuilder.vue';
import RecipientsSelector from '@/MobileClient/Components/Admin/Broadcasts/RecipientsSelector.vue';
import MessagePreview from '@/MobileClient/Components/Admin/Broadcasts/MessagePreview.vue';

export default {
    name: 'BroadcastCreate',

    components: {
        MediaUploader,
        KeyboardBuilder,
        RecipientsSelector,
        MessagePreview,
    },

    setup() {
        const broadcasts = useBroadcasts();
        return { ...broadcasts };
    },

    data() {
        return {
            isSubmitting: false,
            scheduleType: 'now',
            broadcastId: null,
            mediaFiles: [],

            form: {
                title: '',
                message: '',
                recipient_type: 'all',
                recipient_filters: null,
                scheduled_at: null,
                buttons: [],
            },
        };
    },

    computed: {
        isFormValid() {
            return this.form.title.trim().length > 0;
        },

        submitText() {
            if (this.isSubmitting) return 'Отправка...';
            if (this.scheduleType === 'now') return 'Отправить сейчас';
            if (this.scheduleType === 'scheduled') return 'Запланировать';
            return 'Сохранить черновик';
        },

        submitIcon() {
            if (this.scheduleType === 'now') return 'fa-solid fa-paper-plane';
            if (this.scheduleType === 'scheduled') return 'fa-solid fa-clock';
            return 'fa-solid fa-save';
        },

        minDateTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            return now.toISOString().slice(0, 16);
        },
    },

    methods: {
        async submitForm() {
            if (!this.isFormValid || this.isSubmitting) return;

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('title', this.form.title);
                formData.append('message', this.form.message || '');
                formData.append('recipient_type', this.form.recipient_type);

                if (this.form.recipient_filters) {
                    formData.append('recipient_filters', JSON.stringify(this.form.recipient_filters));
                }

                if (this.scheduleType === 'scheduled' && this.form.scheduled_at) {
                    formData.append('scheduled_at', this.form.scheduled_at);
                }

                // 🆕 Кнопки как JSON строка
                if (this.form.buttons && this.form.buttons.length > 0) {
                    formData.append('buttons', JSON.stringify(this.form.buttons));
                }

                // Добавляем медиафайлы
                this.mediaFiles.forEach((file, index) => {
                    if (file instanceof File) {
                        const type = file.type.startsWith('image/') ? 'images' :
                            file.type.startsWith('video/') ? 'videos' : 'audios';
                        formData.append(`${type}[${index}]`, file);
                    }
                });

                console.log('[BroadcastCreate] Отправка формы:', {
                    title: this.form.title,
                    buttons: this.form.buttons,
                    scheduleType: this.scheduleType,
                });

                const broadcast = await this.createBroadcast(formData);

                this.$notify?.({
                    title: 'Успешно',
                    text: this.scheduleType === 'now'
                        ? 'Рассылка отправлена'
                        : this.scheduleType === 'scheduled'
                            ? 'Рассылка запланирована'
                            : 'Черновик сохранён',
                    type: 'success',
                });

                if (this.scheduleType === 'now') {
                    await this.sendBroadcast(broadcast.id);
                }

                this.goBack();

            } catch (error) {
                console.error('[BroadcastCreate] Ошибка:', error);

                const message = error.response?.data?.message || 'Не удалось создать рассылку';

                this.$notify?.({
                    title: 'Ошибка',
                    text: message,
                    type: 'error',
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        goBack() {
            this.$router.push({ name: 'AdminBroadcasts' });
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.broadcast-create-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

.create-hero {
    position: relative;
    padding: 30px 20px 60px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.08) 0%, transparent 40%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateX(-2px);
    }
}

.hero-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 8px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
}

.create-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: -30px;
    position: relative;
    z-index: 2;
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
    color: $primary;

    i {
        font-size: 1.1rem;
    }

    h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }

    .section-hint {
        margin-left: auto;
        font-size: 0.75rem;
        color: $text-muted;
        font-weight: 500;
    }
}

.form-field {
    margin-bottom: 16px;

    &:last-child {
        margin-bottom: 0;
    }

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;

        .required {
            color: #ef4444;
            font-weight: 700;
        }

        .char-counter {
            margin-left: auto;
            font-size: 0.75rem;
            font-weight: 500;
            color: $text-muted;
        }
    }

    input,
    textarea {
        width: 100%;
        padding: 12px 16px;
        background: $bg;
        border: 2px solid $border;
        border-radius: 10px;
        font-size: 0.95rem;
        color: $text;
        font-family: inherit;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &::placeholder {
            color: $text-muted;
        }
    }

    textarea {
        resize: vertical;
        min-height: 120px;
        line-height: 1.5;
    }
}

.schedule-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 16px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg-secondary;
    border: 2px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
    }

    input[type="radio"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    input[type="radio"]:checked ~ .radio-label {
        color: $primary;
        font-weight: 700;
    }

    &:has(input:checked) {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: $text;

    i {
        color: $primary;
    }
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.btn-cancel {
    padding: 14px 24px;
    background: transparent;
    border: 2px solid $border;
    border-radius: 12px;
    color: $text;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: #ef4444;
        color: #ef4444;
    }
}

.btn-submit {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 576px) {
    .create-hero {
        padding: 20px 16px 50px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn-cancel,
    .btn-submit {
        width: 100%;
    }
}
</style>
