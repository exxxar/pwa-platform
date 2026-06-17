<template>
    <div class="chat-input-container">
        <div class="input-wrapper">

            <!-- Кнопка вложений -->
            <button
                class="attach-btn"
                @click="showAttachMenu = !showAttachMenu"
                title="Прикрепить файл"
            >
                <i class="fa-solid fa-paperclip"></i>
            </button>

            <!-- Поле ввода -->
            <div class="input-field-wrapper">
                <textarea
                    ref="messageInput"
                    v-model="messageText"
                    class="message-input"
                    placeholder="Напишите сообщение..."
                    rows="1"
                    @input="autoResize"
                    @keydown.enter.exact.prevent="sendMessage"
                ></textarea>
            </div>

            <!-- Кнопка отправки / голоса -->
            <button
                v-if="messageText.trim()"
                class="send-btn"
                @click="sendMessage"
                :disabled="isSending"
                title="Отправить"
            >
                <span v-if="isSending" class="send-spinner"></span>
                <i v-else class="fa-solid fa-paper-plane"></i>
            </button>
            <button
                v-else
                class="voice-btn"
                @click="toggleVoiceRecording"
                :class="{ 'is-recording': isRecording }"
                title="Голосовое сообщение"
            >
                <i class="fa-solid fa-microphone"></i>
            </button>

        </div>

        <!-- Меню вложений (опционально) -->
        <transition name="slide-up">
            <div v-if="showAttachMenu" class="attach-menu">
                <button class="attach-option">
                    <i class="fa-solid fa-image"></i>
                    <span>Фото</span>
                </button>
                <button class="attach-option">
                    <i class="fa-solid fa-file"></i>
                    <span>Файл</span>
                </button>
                <button class="attach-option">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Геолокация</span>
                </button>
            </div>
        </transition>

    </div>
</template>

<script>
export default {
    name: "ChatInput",

    props: {
        dialog: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            messageText: '',
            isSending: false,
            isRecording: false,
            showAttachMenu: false,
        };
    },

    methods: {
        autoResize() {
            const textarea = this.$refs.messageInput;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        },

        async sendMessage() {
            const text = this.messageText.trim();
            if (!text || this.isSending) return;

            this.isSending = true;

            try {
                // TODO: Замени на реальный API или Pinia action
                // await this.chatStore.sendMessage(this.dialog.id, text);

                // Имитация отправки
                await new Promise(resolve => setTimeout(resolve, 500));

                // Очистка поля
                this.messageText = '';
                this.$refs.messageInput.style.height = 'auto';

                // Фокус обратно на поле
                this.$nextTick(() => {
                    this.$refs.messageInput.focus();
                });

            } catch (error) {
                console.error('Ошибка отправки:', error);
            } finally {
                this.isSending = false;
            }
        },

        toggleVoiceRecording() {
            this.isRecording = !this.isRecording;
            // TODO: Реализовать запись голоса
        },
    },
};
</script>

<style scoped>
.chat-input-container {
    padding: 12px 16px;
    background: var(--bs-body-bg);
}

.input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: var(--bs-secondary-bg, #f5f5f5);
    border: 2px solid transparent;
    border-radius: 24px;
    padding: 6px 6px 6px 12px;
    transition: all 0.2s ease;
}

.input-wrapper:focus-within {
    border-color: var(--bs-primary);
    background: var(--bs-body-bg);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

/* Кнопка вложений */
.attach-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.attach-btn:hover {
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.1);
}

/* Поле ввода */
.input-field-wrapper {
    flex: 1;
    min-width: 0;
}

.message-input {
    width: 100%;
    border: none;
    background: transparent;
    padding: 8px 0;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    outline: none;
    resize: none;
    line-height: 1.4;
    max-height: 120px;
    font-family: inherit;
}

.message-input::placeholder {
    color: var(--bs-secondary-color);
}

/* Кнопка отправки */
.send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.3);
}

.send-btn:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
}

.send-btn:active:not(:disabled) {
    transform: scale(0.95);
}

.send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.send-spinner {
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

/* Кнопка голоса */
.voice-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.voice-btn:hover {
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.1);
}

.voice-btn.is-recording {
    background: #dc3545;
    color: white;
    animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
    }
}

/* Меню вложений */
.attach-menu {
    display: flex;
    gap: 12px;
    padding: 12px 0 0;
    justify-content: center;
}

.attach-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background: var(--bs-secondary-bg);
    border: none;
    border-radius: 12px;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.2s ease;
}

.attach-option:hover {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    transform: translateY(-2px);
}

.attach-option i {
    font-size: 1.2rem;
}

.attach-option span {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Анимация меню */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Адаптив */
@media (max-width: 576px) {
    .chat-input-container {
        padding: 10px 12px;
    }

    .message-input {
        font-size: 0.9rem;
    }

    .send-btn,
    .voice-btn {
        width: 36px;
        height: 36px;
    }
}
</style>
