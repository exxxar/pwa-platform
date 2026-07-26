<template>
    <div class="chat-input-wrapper">

        <!-- 📎 ОБЛАСТЬ ПРЕВЬЮ (Иконка над полем ввода) -->
        <div v-if="audioBlob || pendingFile" class="attachment-preview-area">
            <div class="preview-icon-btn" :class="getPreviewIconClass()" @click="showFileDetailsModal = true" title="Нажмите для подробностей">
                <i :class="getPreviewIcon()"></i>

                <!-- Крестик для удаления (появляется при наведении) -->
                <button type="button" class="preview-clear-btn" @click.stop="clearAttachment" title="Удалить вложение">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <form class="chat-input-form" @submit.prevent="handleSend">
            <!-- Кнопка вложений -->
            <button
                type="button"
                class="attach-btn"
                @click="toggleAttachMenu"
                title="Прикрепить файл"
                :disabled="disabled || isRecording || isSending"
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
                    <button type="button" @click="startVoiceRecording">
                        <i class="fa-solid fa-microphone"></i>
                        <span>Голосовое</span>
                    </button>
                </div>
            </transition>

            <!-- Область ввода -->
            <div class="input-wrapper">
                <!-- 📝 ТЕКСТОВОЕ ПОЛЕ -->
                <textarea
                    v-if="!isRecording"
                    ref="messageInput"
                    v-model="messageText"
                    @input="autoResize"
                    @keydown.enter.exact.prevent="handleSend"
                    placeholder="Сообщение..."
                    class="message-input"
                    :disabled="disabled"
                    rows="1"
                ></textarea>

                <!-- 🎙️ ИНДИКАТОР ЗАПИСИ -->
                <div v-else class="recording-indicator">
                    <div class="recording-pulse"></div>
                    <span>Запись...</span>
                </div>

                <!-- Эмодзи-кнопка -->
                <button
                    v-if="!isRecording"
                    type="button"
                    class="emoji-btn"
                    title="Эмодзи"
                >
                    <i class="fa-regular fa-face-smile"></i>
                </button>
            </div>

            <!-- Кнопка отправки -->
            <button
                v-if="messageText.trim() || audioBlob || pendingFile"
                type="submit"
                class="send-btn"
                :disabled="disabled || isSending"
                title="Отправить"
            >
                <i v-if="isSending" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-paper-plane"></i>
            </button>

            <!-- Кнопка записи голоса -->
            <button
                v-else
                type="button"
                class="voice-btn"
                :disabled="disabled"
                @click="toggleVoiceRecording"
                :class="{ 'is-recording': isRecording }"
                :title="isRecording ? 'Остановить запись' : 'Записать голосовое'"
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

        <!-- 🆕 МОДАЛЬНОЕ ОКНО С ДЕТАЛЯМИ ФАЙЛА -->
        <teleport to="body">
            <transition name="modal-fade">
                <div v-if="showFileDetailsModal" class="modal-overlay" @click.self="showFileDetailsModal = false">
                    <div class="file-details-modal">
                        <div class="modal-header">
                            <h3>Вложение</h3>
                            <button class="modal-close" @click="showFileDetailsModal = false">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="file-large-icon" :class="getPreviewIconClass()">
                                <i :class="getPreviewIcon()"></i>
                            </div>
                            <div class="file-details-list">
                                <div class="detail-row">
                                    <span class="detail-label">Имя:</span>
                                    <span class="detail-value">{{ getFileName() }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Размер:</span>
                                    <span class="detail-value">{{ getFileSize() }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Тип:</span>
                                    <span class="detail-value">{{ getFileType() }}</span>
                                </div>
                            </div>
                            <button class="modal-remove-btn" @click="clearAttachmentAndClose">
                                <i class="fa-solid fa-trash"></i> Удалить вложение
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
export default {
    name: "ChatInput",
    props: {
        dialog: { type: Object, required: true },
        disabled: { type: Boolean, default: false },
    },
    emits: ['send'],

    data() {
        return {
            messageText: '',
            isSending: false,
            isRecording: false,
            showAttachMenu: false,
            fileAccept: '*/*',
            showFileDetailsModal: false,

            mediaRecorder: null,
            audioChunks: [],
            audioBlob: null,
            audioUrl: null,
            pendingFile: null,
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
            if (this.disabled || this.isSending) return;
            const text = this.messageText.trim();
            if (!text && !this.audioBlob && !this.pendingFile) return;

            this.isSending = true;
            try {
                const attachments = [];
                if (this.audioBlob) {
                    attachments.push(new File([this.audioBlob], `voice_${Date.now()}.webm`, { type: 'audio/webm' }));
                }
                if (this.pendingFile) {
                    attachments.push(this.pendingFile);
                }

                this.$emit('send', text, attachments);

                this.messageText = '';
                this.clearAudio();
                this.clearFile();

                if (this.$refs.messageInput) {
                    this.$refs.messageInput.style.height = 'auto';
                    this.$refs.messageInput.focus();
                }
            } catch (error) {
                console.error('[ChatInput] Ошибка:', error);
            } finally {
                this.isSending = false;
            }
        },

        async toggleVoiceRecording() {
            if (this.isRecording) {
                this.stopVoiceRecording();
            } else {
                await this.startVoiceRecording();
            }
        },

        async startVoiceRecording() {
            this.showAttachMenu = false;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream);
                this.audioChunks = [];

                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = () => {
                    this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    this.audioUrl = URL.createObjectURL(this.audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };

                this.mediaRecorder.start();
                this.isRecording = true;
            } catch (error) {
                console.error('Ошибка доступа к микрофону:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Нет доступа к микрофону', type: 'error' });
            }
        },

        stopVoiceRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
                this.isRecording = false;
            }
        },

        clearAudio() {
            if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
            this.audioBlob = null;
            this.audioUrl = null;
            this.audioChunks = [];
        },

        isImage(file) {
            return file && file.type.startsWith('image/');
        },

        toggleAttachMenu() {
            this.showAttachMenu = !this.showAttachMenu;
        },

        selectFile(type) {
            this.fileAccept = type === 'image' ? 'image/*' : '*/*';
            this.showAttachMenu = false;
            this.$nextTick(() => {
                this.$refs.fileInput?.click();
            });
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.$notify?.({ title: 'Ошибка', text: 'Файл слишком большой (макс. 10 МБ)', type: 'error' });
                event.target.value = '';
                return;
            }

            this.pendingFile = file;
            event.target.value = '';
            this.$nextTick(() => {
                this.$refs.messageInput?.focus();
            });
        },

        clearFile() {
            this.pendingFile = null;
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        // 🎯 Методы для иконки превью и модального окна
        getPreviewIconClass() {
            if (this.audioBlob) return 'is-audio';
            return this.isImage(this.pendingFile) ? 'is-image' : 'is-doc';
        },
        getPreviewIcon() {
            if (this.audioBlob) return 'fa-solid fa-microphone-lines';
            return this.isImage(this.pendingFile) ? 'fa-solid fa-image' : 'fa-solid fa-file-lines';
        },
        getFileName() {
            return this.audioBlob ? 'Голосовое сообщение' : (this.pendingFile?.name || 'Неизвестный файл');
        },
        getFileSize() {
            if (this.audioBlob) return this.formatFileSize(this.audioBlob.size);
            return this.pendingFile ? this.formatFileSize(this.pendingFile.size) : '0 B';
        },
        getFileType() {
            if (this.audioBlob) return 'Аудио (webm)';
            return this.pendingFile?.type || 'Неизвестный тип';
        },
        clearAttachment() {
            this.clearAudio();
            this.clearFile();
        },
        clearAttachmentAndClose() {
            this.clearAttachment();
            this.showFileDetailsModal = false;
        },
    },

    beforeUnmount() {
        this.stopVoiceRecording();
        this.clearAudio();
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// БАЗОВЫЕ СТИЛИ КОНТЕЙНЕРА
// ==========================================
.chat-input-wrapper {
    border-top: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    flex-shrink: 0;
    position: relative; // Или fixed, в зависимости от родительского layout
    width: 100%;
    z-index: 1047;
}

.chat-input-form {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 12px;
    padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
}

// ==========================================
// 📎 ОБЛАСТЬ ПРЕВЬЮ НАД ПОЛЕМ ВВОДА
// ==========================================
.attachment-preview-area {
    position: absolute;
    bottom: 100%; // Располагаем прямо над формой
    left: 12px;
    margin-bottom: 8px;
    z-index: 10;
    animation: popIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes popIn {
    from { opacity: 0; transform: scale(0.8) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.preview-icon-btn {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    cursor: pointer;
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &.is-audio { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    &.is-image { background: linear-gradient(135deg, #10b981, #059669); }
    &.is-doc { background: linear-gradient(135deg, #64748b, #475569); }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);

        .preview-clear-btn {
            opacity: 1;
            transform: scale(1);
        }
    }

    .preview-clear-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ef4444;
        color: white;
        border: 2px solid var(--bs-body-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.2s ease;
        cursor: pointer;

        &:hover {
            background: #dc2626;
        }
    }
}

// ==========================================
// КНОПКИ УПРАВЛЕНИЯ
// ==========================================
.attach-btn, .emoji-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: var(--bs-secondary-bg);
        color: var(--bs-primary);
    }
    &:disabled { opacity: 0.4; cursor: not-allowed; }
}

.send-btn, .voice-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--bs-primary);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 102, 126, 234), 0.4);
    }
    &:active:not(:disabled) { transform: scale(0.95); }
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.voice-btn {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);

    &:hover:not(:disabled) {
        background: var(--bs-border-color);
        box-shadow: none;
        color: var(--bs-body-color);
    }

    &.is-recording {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        animation: pulse-record-btn 1.5s infinite;
    }
}

@keyframes pulse-record-btn {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

// ==========================================
// ПОЛЕ ВВОДА
// ==========================================
.input-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    background: var(--bs-secondary-bg);
    border-radius: 22px;
    padding: 6px 8px 6px 16px;
    min-height: 44px;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:focus-within {
        background: var(--bs-body-bg);
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb, 102, 126, 234), 0.1);
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

    &::placeholder { color: var(--bs-secondary-color); }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.recording-indicator {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ef4444;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 8px 0;

    .recording-pulse {
        width: 10px;
        height: 10px;
        background: #ef4444;
        border-radius: 50%;
        animation: pulse-record-dot 1.5s infinite;
    }
}

@keyframes pulse-record-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.6; }
}

// ==========================================
// МЕНЮ ВЛОЖЕНИЙ
// ==========================================
.attach-menu {
    position: absolute;
    bottom: 100%;
    left: 12px;
    margin-bottom: 8px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 170px;
    z-index: 10;

    button {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        background: transparent;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        color: var(--bs-body-color);
        font-size: 0.9rem;
        font-weight: 500;
        transition: background 0.2s;
        text-align: left;

        i {
            width: 20px;
            text-align: center;
            color: var(--bs-primary);
            font-size: 1rem;
        }

        &:hover { background: var(--bs-secondary-bg); }
    }
}

// ==========================================
// МОДАЛЬНОЕ ОКНО ДЕТАЛЕЙ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.file-details-modal {
    background: var(--bs-body-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 360px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--bs-border-color);

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-body-color);
    }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: #ef4444;
        color: white;
    }
}

.modal-body {
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.file-large-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);

    &.is-audio { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    &.is-image { background: linear-gradient(135deg, #10b981, #059669); }
    &.is-doc { background: linear-gradient(135deg, #64748b, #475569); }
}

.file-details-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;

    .detail-label {
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
        font-weight: 500;
    }

    .detail-value {
        font-size: 0.9rem;
        color: var(--bs-body-color);
        font-weight: 600;
        text-align: right;
        word-break: break-all;
        max-width: 60%;
    }
}

.modal-remove-btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;

    &:hover {
        background: #ef4444;
        color: white;
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(10px); }
</style>
