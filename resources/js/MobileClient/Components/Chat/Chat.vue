<template>
    <div class="chat-component">

        <!-- Шапка чата -->
        <div class="chat-header">
            <button class="back-btn" @click="closeDialog">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <div
                class="header-info"
                :style="getAvatarStyle(currentDialog)"
                @click="showDialogInfo"
            >
                <div class="header-avatar">
                    <img
                        v-if="currentInterlocutor?.avatar"
                        :src="currentInterlocutor.avatar"
                        :alt="currentInterlocutor?.name"
                    >
                    <span v-else class="avatar-initials">
                        {{ getInitials(currentInterlocutor?.name) }}
                    </span>
                    <div v-if="currentInterlocutor?.is_online" class="online-dot"></div>
                </div>
                <div class="header-text">
                    <div class="header-name">
                        {{ currentInterlocutor?.name || 'Чат' }}
                    </div>
                    <div class="header-status">
                        <template v-if="currentInterlocutor?.is_online">
                            <span class="online-text">в сети</span>
                        </template>
                        <template v-else-if="currentInterlocutor?.last_seen_at">
                            {{ formatLastSeen(currentInterlocutor.last_seen_at) }}
                        </template>
                        <template v-else>
                            <span class="offline-text">был(а) недавно</span>
                        </template>
                    </div>
                </div>
            </div>

            <button class="header-action" @click="showDialogInfo">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>

        <!-- Сообщения -->
        <div
            ref="messagesContainer"
            class="messages-container"
            @scroll="handleScroll"
        >
            <!-- Загрузка старых сообщений -->
            <div v-if="isMessagesLoading" class="messages-loader">
                <div class="loader-spinner"></div>
            </div>

            <!-- Кнопка "Загрузить ещё" -->
            <button
                v-if="hasMoreMessages && !isMessagesLoading"
                class="load-more-btn"
                @click="loadOlderMessages"
            >
                <i class="fa-solid fa-arrow-up"></i>
                Загрузить ещё
            </button>

            <!-- Пустой чат -->
            <div v-if="sortedMessages.length === 0 && !isMessagesLoading" class="empty-chat">
                <div class="empty-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <p>Начните общение!</p>
                <span>Напишите первое сообщение</span>
            </div>

            <!-- Список сообщений -->
            <template v-else>
                <template
                    v-for="(message, index) in sortedMessages"
                    :key="message.id"
                >
                    <!-- Разделитель даты -->
                    <div v-if="shouldShowDateSeparator(index)" class="date-separator">
                        <span>{{ formatDateSeparator(message.created_at) }}</span>
                    </div>

                    <!-- Сообщение -->
                    <div
                        class="message-bubble"
                        :class="{
                            'is-mine': isMine(message),
                            'is-error': message.status === 'error',
                            'is-sending': message.status === 'sending',
                            'has-tail': shouldShowTail(index)
                        }"
                    >
                        <div class="message-content">
                            <div class="message-text" v-if="message.text || message.message">
                                {{ message.text || message.message }}
                            </div>

                            <div class="message-meta">
                                <span class="message-time">
                                    {{ formatMessageTime(message.created_at) }}
                                </span>

                                <!-- Статус для моих сообщений -->
                                <span v-if="isMine(message)" class="message-status">
                                    <i v-if="message.status === 'sending'" class="fa-solid fa-clock"></i>
                                    <i v-else-if="message.status === 'error'" class="fa-solid fa-circle-exclamation error-icon"></i>
                                    <i v-else-if="message.status === 'read'" class="fa-solid fa-check-double status-read"></i>
                                    <i v-else-if="message.status === 'delivered'" class="fa-solid fa-check-double status-delivered"></i>
                                    <i v-else class="fa-solid fa-check status-sent"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Кнопка повторной отправки -->
                        <button
                            v-if="message.status === 'error'"
                            class="retry-btn"
                            @click="retryMessage(message)"
                            title="Повторить отправку"
                        >
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </template>

                <!-- Индикатор "печатает..." -->
                <div v-if="isTyping" class="typing-indicator">
                    <div class="typing-bubble">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Поле ввода -->
        <ChatInput
            :dialog="currentDialog"
            :disabled="isSending"
            @send="handleSend"
        />

    </div>
</template>

<script>
import { useChat } from '@/MobileClient/Composables/useChat.js';
import ChatInput from "@/MobileClient/Components/Chat/ChatInput.vue";
import {
    formatMessageTime,
    formatDateSeparator,
    formatLastSeen,
    getInitials,
    getAvatarGradient,
    isMyMessage,
} from '@/MobileClient/utils/chatUtils.js';

export default {
    name: "ChatComponent",

    components: { ChatInput },

    setup() {
        const chat = useChat();
        return { ...chat };
    },

    data() {
        return {
            autoScroll: true,
            isTyping: false,
        };
    },

    computed: {
        user() {
            return window.TenantUser;
        },
    },

    watch: {
        // Авто-скролл при новых сообщениях
        sortedMessages: {
            handler(newMessages, oldMessages) {
                if (newMessages.length > (oldMessages?.length || 0)) {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            },
            deep: true,
        },

        // Загрузка сообщений при открытии диалога
        currentDialog: {
            immediate: true,
            handler(newDialog) {
                if (newDialog) {
                    this.autoScroll = true;
                }
            },
        },
    },

    methods: {
        // ==========================================
        // СООБЩЕНИЯ
        // ==========================================

        isMine(message) {
            return isMyMessage(message, this.user?.id);
        },

        shouldShowTail(index) {
            const current = this.sortedMessages[index];
            const next = this.sortedMessages[index + 1];
            if (!next) return true;
            return this.isMine(current) !== this.isMine(next);
        },

        shouldShowDateSeparator(index) {
            if (index === 0) return true;
            const current = this.sortedMessages[index];
            const prev = this.sortedMessages[index - 1];
            return new Date(current.created_at).toDateString() !==
                new Date(prev.created_at).toDateString();
        },

        async handleSend(text, attachments = []) {
            try {
                await this.sendMessage(text, attachments);
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить сообщение',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // СКРОЛЛ
        // ==========================================

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container && this.autoScroll) {
                container.scrollTop = container.scrollHeight;
            }
        },

        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            // Проверяем, находится ли пользователь внизу
            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            this.autoScroll = isAtBottom;

            // Подгрузка старых сообщений при скролле вверх
            if (container.scrollTop < 100 && this.hasMoreMessages && !this.isMessagesLoading) {
                const previousHeight = container.scrollHeight;
                this.loadOlderMessages().then(() => {
                    // Сохраняем позицию скролла
                    this.$nextTick(() => {
                        const newHeight = container.scrollHeight;
                        container.scrollTop = newHeight - previousHeight;
                    });
                });
            }
        },

        // ==========================================
        // ДИАЛОГ
        // ==========================================

        closeDialog() {
            this.$emit('close');
            // Отмечаем как прочитанное
            if (this.currentDialog) {
                this.markDialogAsRead(this.currentDialog.id);
            }
        },

        showDialogInfo() {
            // TODO: Открыть модалку с информацией о чате
            console.log('Show dialog info', this.currentDialog);
        },

        // ==========================================
        // ФОРМАТИРОВАНИЕ
        // ==========================================

        formatMessageTime(timestamp) {
            return formatMessageTime(timestamp);
        },

        formatDateSeparator(timestamp) {
            return formatDateSeparator(timestamp);
        },

        formatLastSeen(timestamp) {
            return formatLastSeen(timestamp);
        },

        getInitials(name) {
            return getInitials(name);
        },

        getAvatarStyle(dialog) {
            if (!dialog) return {};
            const interlocutor = dialog.interlocutor || dialog.user || dialog.companion;
            if (interlocutor?.avatar) return {};
            return getAvatarGradient(dialog.id);
        },
    },
};
</script>

<style lang="scss" scoped>
.chat-component {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--bs-body-bg);
}

// Шапка
.chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    flex-shrink: 0;
}

.back-btn,
.header-action {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-body-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: var(--bs-border-color);
    }
}

.header-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    min-width: 0;
}

.header-avatar {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    flex-shrink: 0;

    img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.online-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #22c55e;
    border: 2px solid var(--bs-body-bg);
}

.header-text {
    flex: 1;
    min-width: 0;
}

.header-name {
    font-weight: 700;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-status {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);

    .online-text {
        color: #22c55e;
        font-weight: 600;
    }
}

// Сообщения
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    -webkit-overflow-scrolling: touch;
}

.messages-loader {
    display: flex;
    justify-content: center;
    padding: 12px;
}

.loader-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid var(--bs-border-color);
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.load-more-btn {
    align-self: center;
    padding: 6px 14px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    color: var(--bs-primary);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;

    &:hover {
        background: rgba(var(--bs-primary-rgb), 0.1);
    }
}

.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px 20px;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 16px;
    }

    p {
        margin: 0 0 4px;
        font-weight: 700;
        font-size: 1.1rem;
    }

    span {
        color: var(--bs-secondary-color);
        font-size: 0.9rem;
    }
}

// Разделитель даты
.date-separator {
    display: flex;
    justify-content: center;
    margin: 16px 0 8px;

    span {
        padding: 4px 12px;
        background: var(--bs-secondary-bg);
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }
}

// Пузырьки сообщений
.message-bubble {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    max-width: 80%;
    margin-bottom: 2px;
    animation: messageIn 0.3s ease;

    &:not(.is-mine) {
        align-self: flex-start;
    }

    &.is-mine {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    &.is-error .message-content {
        background: rgba(var(--bs-danger-rgb), 0.1);
        border: 1px solid rgba(var(--bs-danger-rgb), 0.3);
    }

    &.is-sending {
        opacity: 0.7;
    }
}

@keyframes messageIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-content {
    padding: 8px 12px;
    border-radius: 16px;
    background: var(--bs-secondary-bg);
    max-width: 100%;
    word-wrap: break-word;

    .is-mine & {
        background: var(--bs-primary);
        color: white;
        border-bottom-right-radius: 4px;
    }

    &:not(.is-mine .message-content) {
        border-bottom-left-radius: 4px;
    }
}

.message-text {
    font-size: 0.95rem;
    line-height: 1.4;
    white-space: pre-wrap;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    justify-content: flex-end;
    margin-top: 2px;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.message-status {
    font-size: 0.7rem;
    display: flex;
    align-items: center;

    .status-read { color: #60a5fa; }
    .status-delivered { color: rgba(255, 255, 255, 0.9); }
    .status-sent { color: rgba(255, 255, 255, 0.7); }
    .error-icon { color: var(--bs-danger); }
}

.retry-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-danger);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: transform 0.2s;

    &:hover {
        transform: rotate(180deg);
    }
}

// Индикатор "печатает..."
.typing-indicator {
    align-self: flex-start;
    padding: 4px 0;
}

.typing-bubble {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    background: var(--bs-secondary-bg);
    border-radius: 16px;
    border-bottom-left-radius: 4px;

    span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--bs-secondary-color);
        animation: typing 1.4s infinite;

        &:nth-child(2) { animation-delay: 0.2s; }
        &:nth-child(3) { animation-delay: 0.4s; }
    }
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.5;
    }
    30% {
        transform: translateY(-6px);
        opacity: 1;
    }
}
</style>
