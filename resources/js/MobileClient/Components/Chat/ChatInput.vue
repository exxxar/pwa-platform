<template>
    <div class="chat-input-wrapper">
        <form class="chat-input-form" @submit.prevent="handleSend">

            <!-- Кнопка вложений -->
            <button
                type="button"
                class="attach-btn"
                @click="toggleAttachMenu"
                title="Прикрепить файл"
            >
                <i class="fa-solid fa-paperclip"></i>
            </button>

            <!-- Меню вложений -->
            <transition name="fade">
                <div v-if="showAttachMenu" class="attach-menu">
                    <button type="button" @click="selectFile('image')">
                        <i class="fa-solid fa-image"></i>
                        <span>Фото</span>
                    </button>
                    <button type="button" @click="selectFile('file')">
                        <i class="fa-solid fa-file"></i>
                        <span>Файл</span>
                    </button>
                    <button type="button" @click="toggleVoiceRecording">
                        <i class="fa-solid fa-microphone"></i>
                        <span>Голос</span>
                    </button>
                </div>
            </transition>

            <!-- Текстовое поле -->
            <div class="input-wrapper">
                <textarea
                    ref="messageInput"
                    v-model="messageText"
                    @input="autoResize"
                    @keydown.enter.exact.prevent="handleSend"
                    placeholder="Сообщение..."
                    class="message-input"
                    :disabled="disabled"
                    rows="1"
                ></textarea>

                <!-- Эмодзи-кнопка -->
                <button
                    type="button"
                    class="emoji-btn"
                    title="Эмодзи"
                >
                    <i class="fa-regular fa-face-smile"></i>
                </button>
            </div>

            <!-- Кнопка отправки / голосового -->
            <button
                v-if="messageText.trim()"
                type="submit"
                class="send-btn"
                :disabled="disabled || isSending"
            >
                <i v-if="isSending" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-paper-plane"></i>
            </button>
            <button
                v-else
                type="button"
                class="voice-btn"
                :disabled="disabled"
                @click="toggleVoiceRecording"
                :class="{ 'is-recording': isRecording }"
            >
                <i :class="isRecording ? 'fa-solid fa-stop' : 'fa-solid fa-microphone'"></i>
            </button>

        </form>

        <!-- Скрытый input для файлов -->
        <input
            ref="fileInput"
            type="file"
            :accept="fileAccept"
            style="display: none"
            @change="handleFileSelect"
        >
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
        disabled: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['send'],

    data() {
        return {
            messageText: '',
            isSending: false,
            isRecording: false,
            showAttachMenu: false,
            fileAccept: '*/*',
            pendingFileType: null,
        };
    },

    methods: {
        autoResize() {
            const textarea = this.$refs.messageInput;
            if (!textarea) return;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        },

        async handleSend() {
            const text = this.messageText.trim();
            if (!text || this.isSending || this.disabled) return;

            this.isSending = true;

            try {
                this.$emit('send', text, []);

                // Очистка поля
                this.messageText = '';
                this.$refs.messageInput.style.height = 'auto';

                // Фокус обратно
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                });
            } catch (error) {
                console.error('Ошибка отправки:', error);
            } finally {
                this.isSending = false;
            }
        },

        toggleAttachMenu() {
            this.showAttachMenu = !this.showAttachMenu;
        },

        selectFile(type) {
            this.pendingFileType = type;
            this.fileAccept = type === 'image' ? 'image/*' : '*/*';
            this.showAttachMenu = false;
            this.$nextTick(() => {
                this.$refs.fileInput?.click();
            });
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // TODO: Реализовать загрузку файла
            console.log('Selected file:', file);

            // Сброс input
            event.target.value = '';
        },

        toggleVoiceRecording() {
            this.isRecording = !this.isRecording;
            // TODO: Реализовать запись голоса через MediaRecorder API
            if (this.isRecording) {
                console.log('Начало записи...');
            } else {
                console.log('Конец записи');
            }
        },
    },
};
</script>

<style lang="scss" scoped>
.chat-input-wrapper {

    border-top: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    flex-shrink: 0;

    position: fixed;
    bottom: 0;
    width: 100%;
    z-index: 1047;
}

.chat-input-form {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 12px;
}

.attach-btn,
.emoji-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: var(--bs-secondary-bg);
        color: var(--bs-primary);
    }
}

.input-wrapper {
    flex: 1;
    display: flex;
    align-items: flex-end;
    background: var(--bs-secondary-bg);
    border-radius: 20px;
    padding: 4px 8px 4px 14px;
    min-height: 40px;
    transition: all 0.2s;

    &:focus-within {
        background: var(--bs-body-bg);
        box-shadow: 0 0 0 2px var(--bs-primary);
    }
}

.message-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 8px 0;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    outline: none;
    resize: none;
    font-family: inherit;
    line-height: 1.4;
    max-height: 120px;

    &::placeholder {
        color: var(--bs-secondary-color);
    }

    &:disabled {
        opacity: 0.6;
    }
}

.send-btn,
.voice-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-primary);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.voice-btn {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);

    &:hover:not(:disabled) {
        background: var(--bs-border-color);
        box-shadow: none;
    }

    &.is-recording {
        background: var(--bs-danger);
        color: white;
        animation: pulse 1.5s infinite;
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

// Меню вложений
.attach-menu {
    position: absolute;
    bottom: 100%;
    left: 12px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 160px;
    z-index: 10;

    button {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        color: var(--bs-body-color);
        font-size: 0.9rem;
        transition: background 0.2s;
        text-align: left;

        i {
            width: 20px;
            color: var(--bs-primary);
        }

        &:hover {
            background: var(--bs-secondary-bg);
        }
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
