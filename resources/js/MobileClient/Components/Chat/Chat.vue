<template>
    <div class="chat-component">

        <!-- ========================================== -->
        <!-- ШАПКА ЧАТА -->
        <!-- ========================================== -->
        <div class="chat-header">
            <button class="back-btn" @click="goBack">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <div class="header-info">
                <div class="header-avatar" :style="avatarGradientStyle">
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
                            <span class="online-indicator"></span>
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

            <!-- 🆕 КНОПКА ОТКРЫТИЯ BOTTOM SHEET -->
            <button class="header-action" @click.stop="openDialogMenu">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- СООБЩЕНИЯ -->
        <!-- ========================================== -->
        <div
            ref="messagesContainer"
            class="messages-container"
            @scroll="handleScroll"
        >
            <!-- ... существующий код сообщений без изменений ... -->
            <div v-if="isMessagesLoading" class="messages-loader">
                <div class="loader-spinner"></div>
            </div>

            <button
                v-if="hasMoreMessages && !isMessagesLoading"
                class="load-more-btn"
                @click="loadOlderMessages"
            >
                <i class="fa-solid fa-arrow-up"></i>
                Загрузить ещё
            </button>

            <div v-if="sortedMessages.length === 0 && !isMessagesLoading" class="empty-chat">
                <div class="empty-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <p>Начните общение!</p>
                <span>Напишите первое сообщение</span>
            </div>

            <template v-else>
                <template
                    v-for="(message, index) in sortedMessages"
                    :key="message.id"
                >
                    <div v-if="shouldShowDateSeparator(index)" class="date-separator">
                        <span>{{ formatDateSeparator(message.created_at) }}</span>
                    </div>

                    <div
                        class="message-bubble"
                        :class="{
                            'is-mine': isMine(message),
                            'is-error': message.status === 'error',
                            'is-sending': message.status === 'sending',
                            'has-tail': shouldShowTail(index),
                            'is-order': isOrderMessage(message),
                            'is-system': isSystemMessage(message),
                        }"
                    >
                        <!-- СООБЩЕНИЕ С ЗАКАЗОМ -->
                        <template v-if="isOrderMessage(message)">
                            <div class="order-card">
                                <div class="order-header">
                                    <i class="fa-solid fa-receipt"></i>
                                    <span class="order-title">Заказ #{{ message.meta?.order_id }}</span>
                                </div>
                                <div class="order-body" v-html="message.text || message.message"></div>
                                <div class="order-footer">
                                    <span class="order-time">
                                        {{ formatMessageTime(message.created_at) }}
                                    </span>
                                    <a
                                        v-if="message.attachment?.url"
                                        :href="message.attachment.url"
                                        target="_blank"
                                        class="order-download"
                                    >
                                        <i class="fa-solid fa-file-pdf"></i>
                                        <span>Скачать чек</span>
                                    </a>
                                </div>
                            </div>
                        </template>

                        <!-- СООБЩЕНИЕ С ФАЙЛОМ -->
                        <template v-else-if="message.has_attachment">
                            <div class="message-content">
                                <div
                                    class="message-text"
                                    v-if="message.text || message.message"
                                    v-html="message.text || message.message"
                                ></div>
                                <a
                                    :href="message.attachment.url"
                                    target="_blank"
                                    class="attachment-card"
                                    :class="[`attachment-${message.attachment.type}`]"
                                >
                                    <div class="attachment-icon">
                                        <i :class="getAttachmentIcon(message.attachment.type)"></i>
                                    </div>
                                    <div class="attachment-info">
                                        <div class="attachment-name">{{ message.attachment.name }}</div>
                                        <div class="attachment-meta">
                                            <span>{{ message.attachment_size_formatted }}</span>
                                            <span>· {{ message.attachment.type.toUpperCase() }}</span>
                                        </div>
                                    </div>
                                    <div class="attachment-action">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                </a>

                                <div class="message-meta">
                                    <span class="message-time">
                                        {{ formatMessageTime(message.created_at) }}
                                    </span>
                                    <span v-if="isMine(message)" class="message-status">
                                        <i :class="getStatusIcon(message)"></i>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- ТЕКСТОВОЕ СООБЩЕНИЕ -->
                        <template v-else>
                            <div class="message-content">
                                <div
                                    class="message-text"
                                    v-if="message.text || message.message"
                                    v-html="message.text || message.message"
                                ></div>

                                <div class="message-meta">
                                    <span class="message-time">
                                        {{ formatMessageTime(message.created_at) }}
                                    </span>
                                    <span v-if="isMine(message)" class="message-status">
                                        <i :class="getStatusIcon(message)"></i>
                                    </span>
                                </div>
                            </div>
                        </template>

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

                <div v-if="isTyping" class="typing-indicator">
                    <div class="typing-bubble">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- ========================================== -->
        <!-- ПОЛЕ ВВОДА -->
        <!-- ========================================== -->
        <ChatInput
            :dialog="currentDialog"
            :disabled="isSending"
            @send="handleSend"
        />

        <!-- ========================================== -->
        <!-- 🆕 BOTTOM SHEET МЕНЮ ДИАЛОГА -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="sheet-fade">
                <div
                    v-if="showDialogMenu"
                    class="sheet-overlay"
                    @click="closeDialogMenu"
                >
                    <div class="sheet-container" @click.stop>
                        <!-- Заголовок с информацией о собеседнике -->
                        <div class="sheet-header">
                            <div class="sheet-avatar" :style="avatarGradientStyle">
                                <img
                                    v-if="currentInterlocutor?.avatar"
                                    :src="currentInterlocutor.avatar"
                                    :alt="currentInterlocutor?.name"
                                >
                                <span v-else class="avatar-initials">
                                    {{ getInitials(currentInterlocutor?.name) }}
                                </span>
                            </div>
                            <div class="sheet-info">
                                <div class="sheet-name">
                                    {{ currentInterlocutor?.name || 'Чат' }}
                                </div>
                                <div class="sheet-status">
                                    <template v-if="currentInterlocutor?.is_online">
                                        <span class="online-dot-small"></span>
                                        <span>в сети</span>
                                    </template>
                                    <template v-else>
                                        <span>был(а) недавно</span>
                                    </template>
                                </div>
                            </div>
                            <button class="sheet-close" @click="closeDialogMenu">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Разделитель -->
                        <div class="sheet-divider"></div>

                        <!-- Список действий -->
                        <div class="sheet-actions">
                            <button class="sheet-action" @click="viewAttachments">
                                <div class="sheet-action-icon attachments">
                                    <i class="fa-solid fa-paperclip"></i>
                                </div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Вложения</div>
                                    <div class="sheet-action-desc">Просмотр файлов и документов</div>
                                </div>
                                <span v-if="attachmentsCount > 0" class="sheet-action-badge">
                                    {{ attachmentsCount }}
                                </span>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>

                            <button class="sheet-action" @click="archiveCurrentDialog">
                                <div class="sheet-action-icon archive">
                                    <i class="fa-solid fa-box-archive"></i>
                                </div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">В архив</div>
                                    <div class="sheet-action-desc">Скрыть диалог из списка</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>

                            <button class="sheet-action" @click="clearHistory">
                                <div class="sheet-action-icon clear">
                                    <i class="fa-solid fa-broom"></i>
                                </div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Очистить историю</div>
                                    <div class="sheet-action-desc">Удалить все сообщения</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>

                            <div class="sheet-divider"></div>

                            <button class="sheet-action danger" @click="deleteCurrentDialog">
                                <div class="sheet-action-icon delete">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Удалить диалог</div>
                                    <div class="sheet-action-desc">Удалить навсегда без возможности восстановления</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ========================================== -->
        <!-- МОДАЛКА ВЛОЖЕНИЙ -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showAttachmentsModal" class="modal-overlay" @click.self="closeAttachmentsModal">
                    <div class="attachments-modal">
                        <div class="modal-header">
                            <h3>
                                <i class="fa-solid fa-paperclip"></i>
                                Вложения
                            </h3>
                            <button class="modal-close" @click="closeAttachmentsModal">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div v-if="isLoadingAttachments" class="attachments-loading">
                            <div class="loader-spinner"></div>
                        </div>

                        <div v-else-if="attachments.length === 0" class="attachments-empty">
                            <i class="fa-solid fa-inbox"></i>
                            <p>Вложений нет</p>
                        </div>

                        <div v-else class="attachments-grid">
                            <a
                                v-for="attachment in attachments"
                                :key="attachment.id"
                                :href="attachment.url"
                                target="_blank"
                                class="attachment-item"
                                :class="[`type-${attachment.type}`]"
                            >
                                <div class="attachment-icon-large">
                                    <i :class="getAttachmentIcon(attachment.type)"></i>
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-name">{{ attachment.name }}</div>
                                    <div class="attachment-meta">
                                        <span>{{ attachment.size_formatted }}</span>
                                        <span>{{ attachment.date_formatted }}</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-download attachment-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ========================================== -->
        <!-- МОДАЛКА ПОДТВЕРЖДЕНИЯ -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="confirmModal.visible" class="modal-overlay" @click.self="cancelConfirm">
                    <div class="confirm-modal">
                        <div class="confirm-icon" :class="confirmModal.type">
                            <i :class="confirmModal.icon"></i>
                        </div>
                        <h3>{{ confirmModal.title }}</h3>
                        <p>{{ confirmModal.message }}</p>
                        <div class="confirm-actions">
                            <button class="confirm-btn cancel" @click="cancelConfirm">
                                Отмена
                            </button>
                            <button
                                class="confirm-btn"
                                :class="confirmModal.type"
                                @click="executeConfirm"
                            >
                                {{ confirmModal.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
import { useChat } from '@/MobileClient/Composables/useChat.js';
import { useRouter } from 'vue-router';
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
        const router = useRouter();
        return { ...chat, router };
    },

    data() {
        return {
            autoScroll: true,
            isTyping: false,

            // 🆕 Bottom sheet меню
            showDialogMenu: false,

            // Модалка вложений
            showAttachmentsModal: false,
            attachments: [],
            isLoadingAttachments: false,
            attachmentsCount: 0,

            // Модалка подтверждения
            confirmModal: {
                visible: false,
                type: 'warning',
                icon: 'fa-solid fa-triangle-exclamation',
                title: '',
                message: '',
                confirmText: 'Подтвердить',
                callback: null,
            },
        };
    },

    computed: {
        user() {
            return window.TenantUser;
        },

        avatarGradientStyle() {
            if (!this.currentDialog) return {};
            const interlocutor = this.currentDialog.interlocutor
                || this.currentDialog.user
                || this.currentDialog.companion;

            if (interlocutor?.avatar) return {};
            return getAvatarGradient(this.currentDialog.id);
        },
    },

    watch: {
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

        currentDialog: {
            immediate: true,
            handler(newDialog) {
                if (newDialog) {
                    this.autoScroll = true;
                    this.showDialogMenu = false;
                }
            },
        },
    },

    methods: {
        // ==========================================
        // 🆕 BOTTOM SHEET
        // ==========================================

        openDialogMenu() {
            this.showDialogMenu = true;
            // Блокируем скролл body
            document.body.style.overflow = 'hidden';
        },

        closeDialogMenu() {
            this.showDialogMenu = false;
            // Возвращаем скролл
            document.body.style.overflow = '';
        },

        // ==========================================
        // ДЕЙСТВИЯ
        // ==========================================

        viewAttachments() {
            this.closeDialogMenu();
            this.showAttachmentsModal = true;
            this.isLoadingAttachments = true;
            this.attachments = [];

            this.getDialogAttachments(this.currentDialog.id)
                .then(data => {
                    this.attachments = (data || []).map(att => ({
                        ...att,
                        size_formatted: this.formatFileSize(att.size || 0),
                        date_formatted: this.formatAttachmentDate(att.created_at),
                    }));
                    this.attachmentsCount = this.attachments.length;
                })
                .catch(error => {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Не удалось загрузить вложения',
                        type: 'error',
                    });
                })
                .finally(() => {
                    this.isLoadingAttachments = false;
                });
        },

        closeAttachmentsModal() {
            this.showAttachmentsModal = false;
        },

        archiveCurrentDialog() {
            this.closeDialogMenu();

            this.showConfirm({
                type: 'warning',
                icon: 'fa-solid fa-box-archive',
                title: 'Архивировать диалог?',
                message: `Диалог с ${this.currentInterlocutor?.name || 'собеседником'} будет перемещен в архив. Вы сможете восстановить его позже.`,
                confirmText: 'В архив',
                callback: async () => {
                    try {
                        await this.archiveDialog(this.currentDialog.id);
                        this.$notify?.({
                            title: 'Архивировано',
                            text: 'Диалог перемещен в архив',
                            type: 'success',
                        });
                        this.goBack();
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось архивировать диалог',
                            type: 'error',
                        });
                    }
                },
            });
        },

        clearHistory() {
            this.closeDialogMenu();

            this.showConfirm({
                type: 'warning',
                icon: 'fa-solid fa-broom',
                title: 'Очистить историю?',
                message: 'Все сообщения в этом диалоге будут удалены. Это действие нельзя отменить.',
                confirmText: 'Очистить',
                callback: async () => {
                    try {
                        // TODO: Реализовать API для очистки истории
                        // await this.clearDialogHistory(this.currentDialog.id);

                        this.$notify?.({
                            title: 'История очищена',
                            type: 'success',
                        });

                        this.messages = [];
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось очистить историю',
                            type: 'error',
                        });
                    }
                },
            });
        },

        deleteCurrentDialog() {
            this.closeDialogMenu();

            this.showConfirm({
                type: 'danger',
                icon: 'fa-solid fa-trash',
                title: 'Удалить диалог навсегда?',
                message: `Диалог с ${this.currentInterlocutor?.name || 'собеседником'} и вся история переписки будут удалены безвозвратно.`,
                confirmText: 'Удалить',
                callback: async () => {
                    try {
                        await this.deleteDialogPermanently(this.currentDialog.id);
                        this.$notify?.({
                            title: 'Удалено',
                            text: 'Диалог удален',
                            type: 'success',
                        });
                        this.goBack();
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось удалить диалог',
                            type: 'error',
                        });
                    }
                },
            });
        },

        // ==========================================
        // МОДАЛКА ПОДТВЕРЖДЕНИЯ
        // ==========================================

        showConfirm(options) {
            this.confirmModal = {
                visible: true,
                ...options,
            };
        },

        cancelConfirm() {
            this.confirmModal.visible = false;
            this.confirmModal.callback = null;
        },

        executeConfirm() {
            const callback = this.confirmModal.callback;
            this.confirmModal.visible = false;
            if (callback) callback();
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        formatAttachmentDate(date) {
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        },

        goBack() {
            this.closeDialog(this.router, 'Chat');
        },

        // ==========================================
        // ТИПЫ СООБЩЕНИЙ
        // ==========================================

        isOrderMessage(message) {
            const type = message.meta?.type;
            return type === 'invoice'
                || type === 'order_created'
                || type === 'order_summary'
                || type === 'partner_order'
                || !!message.meta?.order_id;
        },

        isSystemMessage(message) {
            const type = message.meta?.type;
            return type === 'status_change'
                || type === 'payment'
                || type === 'dialog_closed';
        },

        getAttachmentIcon(type) {
            const icons = {
                pdf: 'fa-solid fa-file-pdf',
                image: 'fa-solid fa-image',
                doc: 'fa-solid fa-file-word',
                xls: 'fa-solid fa-file-excel',
                zip: 'fa-solid fa-file-zipper',
                video: 'fa-solid fa-file-video',
                audio: 'fa-solid fa-file-audio',
            };
            return icons[type] || 'fa-solid fa-file';
        },

        getStatusIcon(message) {
            if (message.status === 'sending') return 'fa-solid fa-clock';
            if (message.status === 'error') return 'fa-solid fa-circle-exclamation error-icon';
            if (message.status === 'read') return 'fa-solid fa-check-double status-read';
            if (message.status === 'delivered') return 'fa-solid fa-check-double status-delivered';
            return 'fa-solid fa-check status-sent';
        },

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

            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            this.autoScroll = isAtBottom;

            if (container.scrollTop < 100 && this.hasMoreMessages && !this.isMessagesLoading) {
                const previousHeight = container.scrollHeight;
                this.loadOlderMessages().then(() => {
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
            if (this.currentDialog) {
                this.markDialogAsRead(this.currentDialog.id);
            }
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
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: var(--bs-primary, #667eea);
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$success: #22c55e;
$danger: var(--bs-danger, #ef4444);
$warning: #f59e0b;

// ==========================================
// БАЗА
// ==========================================
.chat-component {
    display: flex;
    flex-direction: column;
    height: 100vh;
    height: 100dvh;
    background: $bg;
    overflow: hidden;
    position: relative;
}

// ==========================================
// ШАПКА
// ==========================================
.chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: $bg;
    border-bottom: 1px solid $border;
    flex-shrink: 0;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    padding-top: calc(12px + env(safe-area-inset-top, 0px));
    top: 0;
    position: fixed;
    width: 100%;
    transition: 0.5s;
}

.back-btn,
.header-action {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $bg-secondary;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $border;
        transform: scale(1.05);
    }

    &:active {
        transform: scale(0.95);
    }
}

.header-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    min-width: 0;
    padding: 4px 8px;
    border-radius: 12px;
    transition: background 0.2s;

    &:hover {
        background: rgba($primary, 0.05);
    }

    &:active {
        background: rgba($primary, 0.1);
    }
}

.header-avatar {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid white;

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
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.online-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: $success;
    border: 2px solid $bg;
    box-shadow: 0 0 0 2px rgba($success, 0.2);
}

.header-text {
    flex: 1;
    min-width: 0;
}

.header-name {
    font-weight: 700;
    font-size: 1rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.header-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 2px;

    .online-indicator {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: $success;
        animation: pulse 2s ease-in-out infinite;
    }

    .online-text {
        color: $success;
        font-weight: 600;
    }

    .offline-text {
        font-style: italic;
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

// ==========================================
// СООБЩЕНИЯ
// ==========================================
.messages-container {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    padding-bottom: 100px;
}

.messages-loader {
    display: flex;
    justify-content: center;
    padding: 12px;
}

.loader-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid $border;
    border-top-color: $primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.load-more-btn {
    align-self: center;
    padding: 6px 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 20px;
    color: $primary;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    margin-bottom: 8px;

    &:hover {
        background: rgba($primary, 0.1);
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
        background: rgba($primary, 0.1);
        color: $primary;
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
        color: $text-muted;
        font-size: 0.9rem;
    }
}

.date-separator {
    display: flex;
    justify-content: center;
    margin: 16px 0 8px;

    span {
        padding: 4px 12px;
        background: $bg-secondary;
        border-radius: 12px;
        font-size: 0.75rem;
        color: $text-muted;
        font-weight: 600;
    }
}

// ==========================================
// ПУЗЫРЬКИ СООБЩЕНИЙ
// ==========================================
.message-bubble {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    max-width: 85%;
    margin-bottom: 2px;
    animation: messageIn 0.3s ease;

    &:not(.is-mine):not(.is-order):not(.is-system) {
        align-self: flex-start;
    }

    &.is-mine {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    &.is-system {
        align-self: center;
        max-width: 90%;
        margin: 8px 0;
    }

    &.is-order {
        align-self: center;
        max-width: 95%;
        margin: 8px 0;
    }

    &.is-error .message-content {
        background: rgba($danger, 0.1);
        border: 1px solid rgba($danger, 0.3);
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
    background: $bg-secondary;
    max-width: 100%;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);

    .is-mine & {
        background: $primary;
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
    word-break: break-word;
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
    .error-icon { color: $danger; }
}

.retry-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: $danger;
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

// ==========================================
// КАРТОЧКА ЗАКАЗА
// ==========================================
.order-card {
    width: 100%;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.order-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(245, 158, 11, 0.15);
    border-bottom: 1px solid rgba(245, 158, 11, 0.3);
    color: #92400e;
    font-weight: 700;
    font-size: 0.9rem;

    i { font-size: 1rem; }
}

.order-body {
    padding: 12px 14px;
    color: #78350f;
    font-size: 0.85rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
}

.order-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 14px;
    background: rgba(245, 158, 11, 0.1);
    border-top: 1px solid rgba(245, 158, 11, 0.2);
}

.order-time {
    font-size: 0.7rem;
    color: #92400e;
    opacity: 0.8;
}

.order-download {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    background: #f59e0b;
    color: white;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;

    &:hover {
        background: #d97706;
        transform: translateY(-1px);
    }

    i { font-size: 0.8rem; }
}

// ==========================================
// КАРТОЧКА ВЛОЖЕНИЯ
// ==========================================
.attachment-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    margin-top: 6px;
    max-width: 280px;

    &:hover {
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    &.attachment-pdf {
        border-left: 3px solid #e53e3e;
        .attachment-icon {
            background: rgba(229, 62, 62, 0.1);
            color: #e53e3e;
        }
    }

    &.attachment-image {
        border-left: 3px solid #10b981;
        .attachment-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
    }

    &.attachment-doc,
    &.attachment-xls {
        border-left: 3px solid #3b82f6;
        .attachment-icon {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
    }

    .is-mine & {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
        color: white;

        &:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .attachment-name { color: white; }
        .attachment-meta { color: rgba(255, 255, 255, 0.8); }
    }
}

.attachment-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.attachment-info {
    flex: 1;
    min-width: 0;
}

.attachment-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.attachment-meta {
    font-size: 0.7rem;
    color: $text-muted;
    display: flex;
    gap: 4px;
}

.attachment-action {
    color: $primary;
    font-size: 0.9rem;
    flex-shrink: 0;
}

// ==========================================
// ИНДИКАТОР "ПЕЧАТАЕТ..."
// ==========================================
.typing-indicator {
    align-self: flex-start;
    padding: 4px 0;
}

.typing-bubble {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    background: $bg-secondary;
    border-radius: 16px;
    border-bottom-left-radius: 4px;

    span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: $text-muted;
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

// ==========================================
// 🆕 BOTTOM SHEET
// ==========================================
.sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 3000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.sheet-container {
    width: 100%;
    max-width: 500px;
    background: $bg;
    border-radius: 24px 24px 0 0;
    overflow: hidden;
    animation: sheetSlideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    // Safe area для iPhone
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

@keyframes sheetSlideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.sheet-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px 16px;
}

.sheet-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid white;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.sheet-info {
    flex: 1;
    min-width: 0;
}

.sheet-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.sheet-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    .online-dot-small {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: $success;
    }
}

.sheet-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg-secondary;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $danger;
        color: white;
    }
}

.sheet-divider {
    height: 1px;
    background: $border;
    margin: 0 20px;
}

.sheet-actions {
    padding: 8px 12px;
    overflow-y: auto;
}

.sheet-action {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 14px 12px;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    color: $text;

    &:hover {
        background: $bg-secondary;
    }

    &:active {
        transform: scale(0.98);
    }

    &.danger {
        color: $danger;

        .sheet-action-title { color: $danger; }
        .sheet-action-desc { color: rgba($danger, 0.7); }
        .sheet-action-icon {
            background: rgba($danger, 0.1);
            color: $danger;
        }
        .sheet-action-arrow { color: $danger; }

        &:hover {
            background: rgba($danger, 0.05);
        }
    }
}

.sheet-action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: $bg-secondary;
    color: $text;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.attachments {
        background: rgba($primary, 0.1);
        color: $primary;
    }

    &.archive {
        background: rgba($warning, 0.1);
        color: $warning;
    }

    &.clear {
        background: rgba(#8b5cf6, 0.1);
        color: #8b5cf6;
    }

    &.delete {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.sheet-action-info {
    flex: 1;
    min-width: 0;
}

.sheet-action-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 2px;
}

.sheet-action-desc {
    font-size: 0.75rem;
    color: $text-muted;
    line-height: 1.3;
}

.sheet-action-badge {
    padding: 3px 10px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.sheet-action-arrow {
    color: $text-muted;
    font-size: 0.8rem;
    flex-shrink: 0;
}

// Анимация bottom sheet
.sheet-fade-enter-active {
    transition: opacity 0.3s ease;
}

.sheet-fade-leave-active {
    transition: opacity 0.2s ease;
}

.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

// ==========================================
// МОДАЛКА ВЛОЖЕНИЙ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.attachments-modal {
    background: $bg;
    border-radius: 20px;
    width: 100%;
    max-width: 600px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;

    h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
        margin: 0;

        i { color: $primary; }
    }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: $bg-secondary;
    border: none;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
    }
}

.attachments-loading,
.attachments-empty {
    padding: 40px;
    text-align: center;
    color: $text-muted;

    i {
        font-size: 2.5rem;
        margin-bottom: 12px;
    }

    .loader-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto;
    }
}

.attachments-grid {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg-secondary;
    border-radius: 12px;
    text-decoration: none;
    color: $text;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:hover {
        background: $bg;
        border-color: $primary;
        transform: translateX(2px);
    }

    &.type-pdf {
        border-left: 3px solid #e53e3e;
        .attachment-icon-large { background: rgba(229, 62, 62, 0.1); color: #e53e3e; }
    }

    &.type-image {
        border-left: 3px solid $success;
        .attachment-icon-large { background: rgba(34, 197, 94, 0.1); color: $success; }
    }

    &.type-doc,
    &.type-xls {
        border-left: 3px solid #3b82f6;
        .attachment-icon-large { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    }
}

.attachment-icon-large {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.attachment-info {
    flex: 1;
    min-width: 0;
}

.attachment-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    display: flex;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
}

.attachment-download {
    color: $primary;
    font-size: 1rem;
    flex-shrink: 0;
}

// ==========================================
// МОДАЛКА ПОДТВЕРЖДЕНИЯ
// ==========================================
.confirm-modal {
    background: $bg;
    border-radius: 20px;
    padding: 28px 24px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    animation: modalSlideUp 0.3s ease;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    margin: 0 auto 16px;

    &.warning { background: linear-gradient(135deg, $warning, #d97706); }
    &.danger { background: linear-gradient(135deg, $danger, #dc2626); }
    &.success { background: linear-gradient(135deg, $success, #16a34a); }
}

.confirm-modal h3 {
    font-size: 1.2rem;
    margin: 0 0 8px;
    color: $text;
}

.confirm-modal p {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0 0 24px;
    line-height: 1.5;
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.confirm-btn {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;

    &.cancel {
        background: $bg-secondary;
        color: $text;
        border: 1px solid $border;

        &:hover {
            background: $border;
        }
    }

    &.warning {
        background: $warning;
        color: white;
        &:hover { background: #d97706; }
    }

    &.danger {
        background: $danger;
        color: white;
        &:hover { background: #dc2626; }
    }

    &.success {
        background: $success;
        color: white;
        &:hover { background: #16a34a; }
    }
}

// Анимации
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 480px) {
    .chat-header {
        padding: 10px 12px;
        gap: 10px;
    }

    .header-avatar {
        width: 40px;
        height: 40px;
    }

    .header-name {
        font-size: 0.95rem;
    }

    .message-bubble {
        max-width: 90%;

        &.is-order {
            max-width: 98%;
        }
    }

    .order-card {
        font-size: 0.8rem;
    }

    .attachment-card {
        max-width: 240px;
    }

    .sheet-container {
        max-width: 100%;
        border-radius: 20px 20px 0 0;
    }

    .sheet-header {
        padding: 16px 16px 12px;
    }

    .sheet-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.1rem;
    }

    .sheet-name {
        font-size: 1rem;
    }

    .sheet-action {
        padding: 12px 10px;
        gap: 12px;
    }

    .sheet-action-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .sheet-action-title {
        font-size: 0.9rem;
    }

    .sheet-action-desc {
        font-size: 0.7rem;
    }
}
</style>
