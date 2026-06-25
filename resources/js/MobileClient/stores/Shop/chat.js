import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/dialogs';

export const useChatStore = defineStore('chat', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        dialogs: [],
        messages: [],
        currentDialog: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,
        isSending: false,
        isMessagesLoading: false,

        // Ошибки
        lastError: null,

        // Счётчики
        unreadCount: 0,

        // Пагинация сообщений
        messagesPage: 1,
        hasMoreMessages: true,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все диалоги (отсортированы по дате)
         */
        sortedDialogs: (state) => {
            return [...(state.dialogs || [])].sort((a, b) => {
                // Непрочитанные сверху
                const aUnread = a.unread_count || 0;
                const bUnread = b.unread_count || 0;
                if (aUnread !== bUnread) return bUnread - aUnread;

                // Затем по дате последнего сообщения
                const aDate = new Date(a.last_message_at || a.updated_at || 0);
                const bDate = new Date(b.last_message_at || b.updated_at || 0);
                return bDate - aDate;
            });
        },

        /**
         * Общее количество непрочитанных сообщений
         */
        totalUnread: (state) => {
            return state.dialogs.reduce((sum, d) => sum + (d.unread_count || 0), 0);
        },

        /**
         * Текущий диалог с собеседником
         */
        currentInterlocutor: (state) => {
            if (!state.currentDialog) return null;
            return state.currentDialog.interlocutor
                || state.currentDialog.user
                || state.currentDialog.companion
                || null;
        },

        /**
         * Сообщения текущего диалога (отсортированы)
         */
        sortedMessages: (state) => {
            return [...(state.messages || [])].sort((a, b) => {
                return new Date(a.created_at) - new Date(b.created_at);
            });
        },

        /**
         * Найти диалог по ID
         */
        getDialogById: (state) => (id) => {
            return state.dialogs.find(d => String(d.id) === String(id)) || null;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ------------------------------------------
        // ДИАЛОГИ
        // ------------------------------------------

        /**
         * Загрузка списка диалогов
         */
        async loadDialogs() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE);
                const data = response.data?.data || response.data || [];

                this.dialogs = Array.isArray(data) ? data : [];
                this.isHydrated = true;

                // Обновляем счётчик непрочитанных
                this.unreadCount = this.dialogs.reduce(
                    (sum, d) => sum + (d.unread_count || 0), 0
                );

                return this.dialogs;
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки диалогов:', error);
                this.lastError = error.response?.data?.message || 'Не удалось загрузить диалоги';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Открыть диалог и загрузить сообщения
         */
        async openDialog(dialogId) {
            const dialog = this.getDialogById(dialogId);
            if (!dialog) {
                console.warn(`[Chat Store] Диалог #${dialogId} не найден`);
                return null;
            }

            this.currentDialog = dialog;
            this.messages = [];
            this.messagesPage = 1;
            this.hasMoreMessages = true;

            try {
                await this.loadMessages(dialogId);

                // Отмечаем диалог как прочитанный
                if (dialog.unread_count > 0) {
                    this.markDialogAsRead(dialogId);
                }

                return dialog;
            } catch (error) {
                console.error('[Chat Store] Ошибка открытия диалога:', error);
                throw error;
            }
        },

        /**
         * Закрыть текущий диалог
         */
        closeDialog() {
            this.currentDialog = null;
            this.messages = [];
            this.messagesPage = 1;
            this.hasMoreMessages = true;
        },

        /**
         * Отметить диалог как прочитанный (локально)
         */
        markDialogAsRead(dialogId) {
            const dialog = this.getDialogById(dialogId);
            if (!dialog) return;

            const unreadBefore = dialog.unread_count || 0;
            dialog.unread_count = 0;
            this.unreadCount = Math.max(0, this.unreadCount - unreadBefore);

            // Отправляем запрос на бэк (не блокируем UI)
            axios.post(`${BASE}/${dialogId}/read`).catch(err => {
                console.warn('[Chat Store] Не удалось отметить как прочитанное:', err);
            });
        },

        /**
         * Удалить диалог
         */
        async deleteDialog(dialogId) {
            try {
                await axios.delete(`${BASE}/${dialogId}`);

                this.dialogs = this.dialogs.filter(d => String(d.id) !== String(dialogId));

                if (this.currentDialog && String(this.currentDialog.id) === String(dialogId)) {
                    this.closeDialog();
                }

                this.unreadCount = this.dialogs.reduce(
                    (sum, d) => sum + (d.unread_count || 0), 0
                );

                return true;
            } catch (error) {
                console.error('[Chat Store] Ошибка удаления диалога:', error);
                throw error;
            }
        },

        // ------------------------------------------
        // СООБЩЕНИЯ
        // ------------------------------------------

        /**
         * Загрузка сообщений диалога
         */
        async loadMessages(dialogId, page = 1) {
            this.isMessagesLoading = true;

            try {
                const response = await axios.get(`${BASE}/${dialogId}/messages`, {
                    params: { page, per_page: 20 }
                });

                const data = response.data?.data || response.data || [];
                const messages = Array.isArray(data) ? data : [];

                if (page === 1) {
                    this.messages = messages;
                } else {
                    // Добавляем старые сообщения в начало
                    this.messages = [...messages, ...this.messages];
                }

                this.messagesPage = page;
                this.hasMoreMessages = messages.length >= 20;

                return messages;
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки сообщений:', error);
                this.lastError = error.response?.data?.message || 'Не удалось загрузить сообщения';
                throw error;
            } finally {
                this.isMessagesLoading = false;
            }
        },

        /**
         * Загрузить более старые сообщения
         */
        async loadOlderMessages(dialogId) {
            if (!this.hasMoreMessages || this.isMessagesLoading) return [];

            try {
                return await this.loadMessages(dialogId, this.messagesPage + 1);
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки старых сообщений:', error);
                throw error;
            }
        },

        /**
         * Отправка сообщения (с оптимистичным обновлением)
         */
        async sendMessage(dialogId, messageText, attachments = []) {
            if (!messageText?.trim() && attachments.length === 0) {
                throw new Error('Сообщение не может быть пустым');
            }

            // Создаём временное сообщение для оптимистичного UI
            const tempMessage = {
                id: `temp-${Date.now()}`,
                text: messageText,
                message: messageText,
                attachments,
                sender_id: 'me',
                is_mine: true,
                created_at: new Date().toISOString(),
                status: 'sending',
                temp: true,
            };

            // Оптимистично добавляем в UI
            this.messages.push(tempMessage);

            this.isSending = true;

            try {
                const response = await axios.post(`${BASE}/${dialogId}/messages`, {
                    message: messageText,
                    text: messageText,
                    attachments,
                    dialog_id: dialogId,
                });

                const serverMessage = response.data?.data || response.data;

                // Заменяем временное сообщение на реальное
                const index = this.messages.findIndex(m => m.id === tempMessage.id);
                if (index !== -1) {
                    this.messages[index] = { ...serverMessage, status: 'sent' };
                }

                // Обновляем последний диалог
                const dialog = this.getDialogById(dialogId);
                if (dialog) {
                    dialog.last_message = messageText;
                    dialog.last_message_at = serverMessage.created_at || new Date().toISOString();
                }

                return serverMessage;
            } catch (error) {
                // Помечаем сообщение как ошибочное
                const index = this.messages.findIndex(m => m.id === tempMessage.id);
                if (index !== -1) {
                    this.messages[index].status = 'error';
                    this.messages[index].error = error.response?.data?.message || 'Ошибка отправки';
                }

                console.error('[Chat Store] Ошибка отправки сообщения:', error);
                throw error;
            } finally {
                this.isSending = false;
            }
        },

        /**
         * Повторная отправка ошибочного сообщения
         */
        async retryMessage(message) {
            if (!message || message.status !== 'error') return;

            const index = this.messages.findIndex(m => m.id === message.id);
            if (index === -1) return;

            // Удаляем ошибочное сообщение
            this.messages.splice(index, 1);

            try {
                await this.sendMessage(
                    this.currentDialog?.id,
                    message.text || message.message,
                    message.attachments || []
                );
            } catch (error) {
                console.error('[Chat Store] Ошибка повторной отправки:', error);
            }
        },

        /**
         * Удалить сообщение
         */
        async deleteMessage(messageId) {
            try {
                await axios.delete(`${BASE}/messages/${messageId}`);
                this.messages = this.messages.filter(m => String(m.id) !== String(messageId));
                return true;
            } catch (error) {
                console.error('[Chat Store] Ошибка удаления сообщения:', error);
                throw error;
            }
        },

        // ------------------------------------------
        // WEBSOCKET / REALTIME (заготовка)
        // ------------------------------------------

        /**
         * Обработка нового сообщения из WebSocket
         */
        handleIncomingMessage(message) {
            // Если это сообщение для текущего диалога — добавляем
            if (this.currentDialog &&
                String(message.dialog_id) === String(this.currentDialog.id)) {
                this.messages.push(message);
            }

            // Обновляем диалог
            const dialog = this.getDialogById(message.dialog_id);
            if (dialog) {
                dialog.last_message = message.text || message.message;
                dialog.last_message_at = message.created_at;

                // Если диалог не открыт — увеличиваем счётчик непрочитанных
                if (!this.currentDialog ||
                    String(this.currentDialog.id) !== String(message.dialog_id)) {
                    dialog.unread_count = (dialog.unread_count || 0) + 1;
                    this.unreadCount++;
                }
            } else {
                // Новый диалог — перезагружаем список
                this.loadDialogs();
            }
        },

        // ------------------------------------------
        // СБРОС
        // ------------------------------------------

        /**
         * Полный сброс состояния
         */
        $reset() {
            this.dialogs = [];
            this.messages = [];
            this.currentDialog = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSending = false;
            this.isMessagesLoading = false;
            this.lastError = null;
            this.unreadCount = 0;
            this.messagesPage = 1;
            this.hasMoreMessages = true;
        },
    },
});
