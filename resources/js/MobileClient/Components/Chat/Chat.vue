<template>
    <div class="chat-container">

        <!-- ========================================== -->
        <!-- HEADER С КНОПКОЙ НАЗАД -->
        <!-- ========================================== -->
        <div class="chat-header">
            <div class="header-content">

                <!-- Кнопка "Назад" -->
                <button
                    class="back-btn"
                    @click="closeDialog"
                    title="Вернуться к списку чатов"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                </button>

                <!-- Аватар и информация -->
                <div class="header-info" @click="showDialogInfo">
                    <div class="header-avatar">
                        <img v-if="dialog?.avatar" :src="dialog.avatar" :alt="dialog.title">
                        <div v-else class="avatar-initials" :style="getAvatarGradient(dialog?.id)">
                            {{ getInitials(dialog?.title) }}
                        </div>

                        <!-- Онлайн индикатор -->
                        <div v-if="dialog?.is_online" class="online-dot"></div>
                    </div>

                    <div class="header-text">
                        <h6 class="header-name">{{ dialog?.title || 'Чат' }}</h6>
                        <p class="header-status">
                            <template v-if="dialog?.is_typing">
                                <span class="typing-text">печатает...</span>
                            </template>
                            <template v-else-if="dialog?.is_online">
                                <span class="online-text">в сети</span>
                            </template>
                            <template v-else-if="dialog?.last_seen">
                                <span class="last-seen-text">
                                    {{ formatLastSeen(dialog.last_seen) }}
                                </span>
                            </template>
                            <template v-else>
                                <span class="offline-text">не в сети</span>
                            </template>
                        </p>
                    </div>
                </div>

                <!-- Действия справа -->
                <div class="header-actions">
                    <button class="action-btn" title="Поиск">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button class="action-btn" title="Ещё">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- СООБЩЕНИЯ -->
        <!-- ========================================== -->
        <div
            ref="messagesContainer"
            class="messages-container"
            @scroll="handleScroll"
        >

            <!-- Пустое состояние -->
            <div v-if="!dialog" class="empty-state">
                <div class="empty-icon-wrapper">
                    <div class="empty-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                </div>
                <h5 class="empty-title">Выберите чат</h5>
                <p class="empty-text">Выберите диалог из списка слева, чтобы начать общение</p>
            </div>

            <!-- Загрузка -->
            <div v-else-if="isLoading" class="loading-state">
                <div class="loading-spinner"></div>
                <p class="loading-text">Загружаем сообщения...</p>
            </div>

            <!-- Сообщения -->
            <template v-else>
                <div class="messages-wrapper">

                    <div
                        v-for="(msg, index) in messages"
                        :key="msg.id"
                        class="message-row"
                        :class="{ 'is-mine': isMine(msg) }"
                    >

                        <!-- Разделитель по дате -->
                        <div
                            v-if="shouldShowDateSeparator(index)"
                            class="date-separator"
                        >
                            <span class="date-label">
                                {{ formatDateSeparator(msg.created_at) }}
                            </span>
                        </div>

                        <!-- Пузырёк сообщения -->
                        <div class="message-bubble" :class="isMine(msg) ? 'mine' : 'theirs'">

                            <!-- Текст сообщения -->
                            <div class="message-content">
                                <div class="message-text">{{ msg.message }}</div>

                                <!-- Время и статус -->
                                <div class="message-meta">
                                    <span class="message-time">
                                        {{ formatTime(msg.created_at) }}
                                    </span>
                                    <i
                                        v-if="isMine(msg)"
                                        class="message-status"
                                        :class="getMessageStatusClass(msg)"
                                    ></i>
                                </div>
                            </div>

                            <!-- "Хвостик" пузырька -->
                            <div class="bubble-tail"></div>
                        </div>

                    </div>

                    <!-- Индикатор "печатает..." -->
                    <div v-if="isTyping" class="typing-bubble">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                </div>
            </template>

        </div>

        <!-- ========================================== -->
        <!-- ПОЛЕ ВВОДА -->
        <!-- ========================================== -->
        <div v-if="dialog" class="chat-input-wrapper">
            <ChatInput :dialog="dialog" />
        </div>

    </div>
</template>

<script>
import { useChatStore } from '@/MobileClient/stores/Shop/chat';
import ChatInput from "@/MobileClient/Components/Chat/ChatInput.vue";

export default {
    name: "ChatComponent",

    components: {
        ChatInput,
    },

    setup() {
        const store = useChatStore();
        return { store };
    },

    data() {
        return {
            isLoading: false,
            isTyping: false,
            autoScroll: true,
        };
    },

    computed: {
        dialog() {
            return this.store.getCurrentDialog;
        },

        messages() {
            return this.store.getMessages || [];
        },

        user() {
            return window.TenantUser;
        },
    },

    watch: {
        messages: {
            handler(newMessages, oldMessages) {
                if (newMessages.length > (oldMessages?.length || 0)) {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            },
            deep: true,
        },

        dialog: {
            immediate: true,
            handler(newDialog) {
                if (newDialog) {
                    this.loadMessages();
                }
            },
        },
    },

    methods: {
        isMine(msg) {
            return msg.meta?.user_id === this.user?.id;
        },

        formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        formatDateSeparator(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const today = new Date();

            if (date.toDateString() === today.toDateString()) {
                return 'Сегодня';
            }

            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === yesterday.toDateString()) {
                return 'Вчера';
            }

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
            });
        },

        formatLastSeen(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);

            if (minutes < 1) return 'только что';
            if (minutes < 60) return `был(а) ${minutes} мин. назад`;

            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `был(а) ${hours} ч. назад`;

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
            });
        },

        shouldShowDateSeparator(index) {
            if (index === 0) return true;

            const currentMsg = this.messages[index];
            const prevMsg = this.messages[index - 1];

            const currentDate = new Date(currentMsg.created_at).toDateString();
            const prevDate = new Date(prevMsg.created_at).toDateString();

            return currentDate !== prevDate;
        },

        getMessageStatusClass(msg) {
            const status = msg.status;
            if (status === 'read') return 'status-read';
            if (status === 'delivered') return 'status-delivered';
            return 'status-sent';
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container && this.autoScroll) {
                container.scrollTop = container.scrollHeight;
            }
        },

        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            this.autoScroll = isAtBottom;
        },

        async loadMessages() {
            this.isLoading = true;
            try {
                await this.store.loadMessages(this.dialog.id);
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            } catch (error) {
                console.error('Error loading messages:', error);
            } finally {
                this.isLoading = false;
            }
        },

        closeDialog() {
            this.store.closeDialog();
        },

        showDialogInfo() {
            // Можно открыть модалку с информацией о чате
            console.log('Show dialog info', this.dialog);
        },

        getInitials(name) {
            if (!name) return '?';
            const words = name.trim().split(/\s+/);
            if (words.length >= 2) {
                return (words[0][0] + words[1][0]).toUpperCase();
            }
            return name.slice(0, 2).toUpperCase();
        },

        getAvatarGradient(id) {
            const gradients = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            ];
            const index = (id || 0) % gradients.length;
            return { background: gradients[index] };
        },
    },
};
</script>

<style scoped>
/* ==========================================
   КОНТЕЙНЕР ЧАТА
   ========================================== */
.chat-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: var(--bs-body-bg);
    position: relative;
}

/* ==========================================
   HEADER
   ========================================== */
.chat-header {
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
}

/* Кнопка "Назад" */
.back-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #f5f5f5);
    border: none;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.back-btn:hover {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.05);
}

.back-btn:active {
    transform: scale(0.95);
}

/* Информация о чате */
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
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.header-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #22c55e;
    border: 2px solid var(--bs-body-bg);
    box-shadow: 0 0 6px rgba(34, 197, 94, 0.5);
}

.header-text {
    flex: 1;
    min-width: 0;
}

.header-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-status {
    margin: 0;
    font-size: 0.8rem;
    line-height: 1.2;
}

.typing-text {
    color: var(--bs-primary);
    font-weight: 500;
}

.online-text {
    color: #22c55e;
    font-weight: 500;
}

.last-seen-text,
.offline-text {
    color: var(--bs-secondary-color);
}

/* Действия */
.header-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: var(--bs-secondary-bg);
    color: var(--bs-primary);
}

/* ==========================================
   СООБЩЕНИЯ
   ========================================== */
.messages-container {
    flex: 1;
    overflow-y: auto;
    background:
        linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.02) 0%, transparent 100%),
        var(--bs-body-bg);
    padding: 16px;
    -webkit-overflow-scrolling: touch;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 3px;
}

.messages-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Пустое состояние */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    padding: 40px 20px;
}

.empty-icon-wrapper {
    margin-bottom: 20px;
}

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
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
}

/* Загрузка */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--bs-border-color);
    border-top-color: var(--bs-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
}

/* ==========================================
   СООБЩЕНИЯ
   ========================================== */
.message-row {
    display: flex;
    margin-bottom: 4px;
}

.message-row.is-mine {
    justify-content: flex-end;
}

/* Пузырёк */
.message-bubble {
    position: relative;
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 18px;
    animation: messageAppear 0.3s ease;
}

@keyframes messageAppear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-bubble.theirs {
    background: var(--bs-secondary-bg, #f0f0f0);
    color: var(--bs-body-color);
    border-bottom-left-radius: 4px;
}

.message-bubble.mine {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.message-text {
    font-size: 0.95rem;
    line-height: 1.4;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.message-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    font-size: 0.7rem;
    opacity: 0.7;
}

.message-time {
    font-weight: 500;
}

.message-status {
    font-size: 0.75rem;
}

.message-status::before {
    content: '✓';
}

.message-status.status-sent {
    opacity: 0.6;
}

.message-status.status-delivered::before {
    content: '✓✓';
}

.message-status.status-read {
    opacity: 1;
}

.message-status.status-read::before {
    content: '✓✓';
}

/* Разделитель по дате */
.date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px 0;
}

.date-label {
    padding: 6px 14px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Индикатор "печатает..." */
.typing-bubble {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: var(--bs-secondary-bg);
    border-radius: 18px;
    border-bottom-left-radius: 4px;
    width: fit-content;
}

.typing-dots {
    display: flex;
    gap: 4px;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--bs-secondary-color);
    animation: typing 1.4s ease-in-out infinite;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-6px);
        opacity: 1;
    }
}

/* ==========================================
   ПОЛЕ ВВОДА
   ========================================== */
.chat-input-wrapper {
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
    position: sticky;
    bottom: 0;
    z-index: 100;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .header-content {
        padding: 10px 12px;
        gap: 10px;
    }

    .back-btn {
        width: 36px;
        height: 36px;
    }

    .header-avatar {
        width: 40px;
        height: 40px;
    }

    .header-name {
        font-size: 0.95rem;
    }

    .header-status {
        font-size: 0.75rem;
    }

    .action-btn {
        width: 32px;
        height: 32px;
    }

    .messages-container {
        padding: 12px;
    }

    .message-bubble {
        max-width: 80%;
    }

    .message-text {
        font-size: 0.9rem;
    }
}
</style>
