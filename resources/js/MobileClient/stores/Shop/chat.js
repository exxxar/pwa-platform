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
        totalUnread: 0,
        totalCount: 0,
        isLoading: false,
        isHydrated: false,
        isSending: false,
        isMessagesLoading: false,
        isAttachmentsLoading: false,

        // Действия над диалогами
        dialogActions: {},

        // Ошибки
        lastError: null,

        // Пагинация сообщений
        messagesPage: 1,
        hasMoreMessages: true,

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все диалоги (отсортированы)
         */
        sortedDialogs: (state) => {
            return [...(state.dialogs || [])].sort((a, b) => {
                // Закреплённые сверху
                if (a.is_pinned && !b.is_pinned) return -1;
                if (!a.is_pinned && b.is_pinned) return 1;

                // Непрочитанные сверху (кроме архивных)
                const aUnread = a.is_archived ? 0 : (a.unread_count || 0);
                const bUnread = b.is_archived ? 0 : (b.unread_count || 0);
                if (aUnread !== bUnread) return bUnread - aUnread;

                // Затем по дате
                const dateA = new Date(a.last_message_at || a.updated_at || 0);
                const dateB = new Date(b.last_message_at || b.updated_at || 0);
                return dateB - dateA;
            });
        },

        /**
         * 🆕 Активные (не архивные) диалоги
         */
        activeDialogs: (state) => {
            return (state.dialogs || []).filter(d => !d.is_archived);
        },

        /**
         * 🆕 Архивные диалоги
         */
        archivedDialogs: (state) => {
            return (state.dialogs || []).filter(d => d.is_archived);
        },

        /**
         * Общее количество непрочитанных (только активные)
         */
        totalUnread: (state) => {
            return (state.dialogs || [])
                .filter(d => !d.is_archived)
                .reduce((sum, d) => sum + (d.unread_count || 0), 0);
        },

        /**
         * Количество активных диалогов
         */
        activeDialogsCount: (state) => {
            return (state.dialogs || []).filter(d => !d.is_archived).length;
        },

        /**
         * Количество архивных диалогов
         */
        archivedDialogsCount: (state) => {
            return (state.dialogs || []).filter(d => d.is_archived).length;
        },

        /**
         * Текущий собеседник
         */
        currentInterlocutor: (state) => {
            if (!state.currentDialog) return null;
            return state.currentDialog.interlocutor
                || state.currentDialog.user
                || state.currentDialog.companion
                || null;
        },

        /**
         * Сообщения текущего диалога
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

        /**
         * Проверка, загружается ли диалог
         */
        isDialogLoading: (state) => (id) => {
            return !!state.dialogActions[String(id)];
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        setTotalUnreadCount(val){
          this.totalUnread = val
        },
        // ==========================================
        // ЗАГРУЗКА ДИАЛОГОВ
        // ==========================================
        setInitialData(data) {
            if (!data) return;

            this.dialogs = data.items || [];
            this.totalUnread = data.total_unread || 0;
            this.totalCount = data.total_count || 0;
            this.isHydrated = true;
        },

        /**
         * 🆕 Получение количества непрочитанных сообщений
         */
        async fetchUnreadCount() {
            try {
                const response = await axios.get(`${BASE}/unread-count`);
                const data = response.data;

                let sumUnreadCount = 0
                // Обновляем unread_count у каждого диалога
                if (data.by_dialog) {
                    this.dialogs.forEach(dialog => {
                        dialog.unread_count = data.by_dialog[String(dialog.id)] || 0;
                        sumUnreadCount += dialog.unread_count;
                    });
                }

                this.totalUnread = sumUnreadCount;
                this.totalCount = data.total || 0;
                return data.total || 0;
            } catch (error) {
                console.error('[Chat Store] Ошибка получения непрочитанных:', error);
                throw error;
            }
        },

        /**
         * 🆕 Получение непрочитанных конкретного диалога
         */
        async fetchDialogUnreadCount(dialogId) {
            try {
                const response = await axios.get(`${BASE}/${dialogId}/unread-count`);
                return response.data.unread_count || 0;
            } catch (error) {
                console.error('[Chat Store] Ошибка получения непрочитанных диалога:', error);
                throw error;
            }
        },
        /**
         * Загрузка списка диалогов (включая архивные)
         */
        async loadDialogs() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE);
                const data = response.data?.data || response.data || [];

                this.dialogs = Array.isArray(data) ? data : [];

                this.totalUnread = data.total_unread || 0;
                this.totalCount = data.total_count || 0;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

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
         * Открыть диалог
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
         * Отметить диалог как прочитанный
         */
        markDialogAsRead(dialogId) {
            const dialog = this.getDialogById(dialogId);
            if (!dialog) return;

            const unreadBefore = dialog.unread_count || 0;
            dialog.unread_count = 0;

            axios.post(`${BASE}/${dialogId}/read`).catch(err => {
                console.warn('[Chat Store] Не удалось отметить как прочитанное:', err);
            });
        },

        // ==========================================
        // 🆕 АРХИВИРОВАНИЕ
        // ==========================================

        /**
         * Архивировать диалог (оптимистично)
         */
        async archiveDialog(dialogId) {
            this.dialogActions[String(dialogId)] = 'archive';

            const dialog = this.getDialogById(dialogId);
            const previousState = dialog ? {
                is_archived: dialog.is_archived,
                archived_at: dialog.archived_at,
            } : null;

            // Оптимистичное обновление
            if (dialog) {
                dialog.is_archived = true;
                dialog.archived_at = new Date().toISOString();
            }

            try {
                const response = await axios.post(`${BASE}/${dialogId}/archive`);
                return response.data;
            } catch (error) {
                // Откат
                if (dialog && previousState) {
                    dialog.is_archived = previousState.is_archived;
                    dialog.archived_at = previousState.archived_at;
                }
                console.error('[Chat Store] Ошибка архивации:', error);
                throw error;
            } finally {
                delete this.dialogActions[String(dialogId)];
            }
        },

        /**
         * Восстановить диалог из архива (оптимистично)
         */
        async restoreDialog(dialogId) {
            this.dialogActions[String(dialogId)] = 'restore';

            const dialog = this.getDialogById(dialogId);
            const previousState = dialog ? {
                is_archived: dialog.is_archived,
                archived_at: dialog.archived_at,
            } : null;

            if (dialog) {
                dialog.is_archived = false;
                dialog.archived_at = null;
            }

            try {
                const response = await axios.post(`${BASE}/${dialogId}/restore`);
                return response.data;
            } catch (error) {
                if (dialog && previousState) {
                    dialog.is_archived = previousState.is_archived;
                    dialog.archived_at = previousState.archived_at;
                }
                console.error('[Chat Store] Ошибка восстановления:', error);
                throw error;
            } finally {
                delete this.dialogActions[String(dialogId)];
            }
        },

        /**
         * Массовое архивирование
         */
        async archiveMultiple(dialogIds) {
            // Сохраняем предыдущее состояние для отката
            const previousStates = {};
            dialogIds.forEach(id => {
                const dialog = this.getDialogById(id);
                if (dialog) {
                    previousStates[id] = {
                        is_archived: dialog.is_archived,
                        archived_at: dialog.archived_at,
                    };
                    dialog.is_archived = true;
                    dialog.archived_at = new Date().toISOString();
                }
            });

            try {
                const response = await axios.post(`${BASE}/archive-multiple`, {
                    ids: dialogIds,
                });
                return response.data;
            } catch (error) {
                // Откат
                dialogIds.forEach(id => {
                    const dialog = this.getDialogById(id);
                    if (dialog && previousStates[id]) {
                        dialog.is_archived = previousStates[id].is_archived;
                        dialog.archived_at = previousStates[id].archived_at;
                    }
                });
                console.error('[Chat Store] Ошибка массового архивирования:', error);
                throw error;
            }
        },

        /**
         * Очистить весь архив
         */
        async emptyArchive() {
            const archivedBefore = this.archivedDialogs.map(d => ({ ...d }));

            // Оптимистично удаляем из списка
            this.dialogs = this.dialogs.filter(d => !d.is_archived);

            try {
                const response = await axios.delete(`${BASE}/archive`);
                return response.data;
            } catch (error) {
                // Откат
                this.dialogs = [...this.dialogs, ...archivedBefore];
                console.error('[Chat Store] Ошибка очистки архива:', error);
                throw error;
            }
        },

        // ==========================================
        // 🆕 УДАЛЕНИЕ
        // ==========================================

        /**
         * Удалить диалог навсегда (оптимистично)
         */
        async deleteDialogPermanently(dialogId) {
            this.dialogActions[String(dialogId)] = 'delete';

            // Сохраняем для отката
            const dialogIndex = this.dialogs.findIndex(d => String(d.id) === String(dialogId));
            const removedDialog = dialogIndex !== -1 ? this.dialogs[dialogIndex] : null;

            if (dialogIndex !== -1) {
                this.dialogs.splice(dialogIndex, 1);
            }

            // Если удаляем текущий открытый диалог — закрываем его
            if (this.currentDialog && String(this.currentDialog.id) === String(dialogId)) {
                this.closeDialog();
            }

            try {
                const response = await axios.delete(`${BASE}/${dialogId}`);
                return response.data;
            } catch (error) {
                // Откат
                if (removedDialog && dialogIndex !== -1) {
                    this.dialogs.splice(dialogIndex, 0, removedDialog);
                }
                console.error('[Chat Store] Ошибка удаления диалога:', error);
                throw error;
            } finally {
                delete this.dialogActions[String(dialogId)];
            }
        },

        // ==========================================
        // 🆕 ВЛОЖЕНИЯ
        // ==========================================

        /**
         * Получить вложения диалога
         */
        async getDialogAttachments(dialogId) {
            this.isAttachmentsLoading = true;

            try {
                const response = await axios.get(`${BASE}/${dialogId}/attachments`);
                return response.data?.data || response.data || [];
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки вложений:', error);
                throw error;
            } finally {
                this.isAttachmentsLoading = false;
            }
        },

        // ==========================================
        // СООБЩЕНИЯ
        // ==========================================

        /**
         * Загрузка сообщений
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
         * Загрузить старые сообщения
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
         * Отправка сообщения
         */
        async sendMessage(dialogId, messageText, attachments = []) {
            if (!messageText?.trim() && attachments.length === 0) {
                throw new Error('Сообщение не может быть пустым');
            }

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

                const index = this.messages.findIndex(m => m.id === tempMessage.id);
                if (index !== -1) {
                    this.messages[index] = { ...serverMessage, status: 'sent' };
                }

                const dialog = this.getDialogById(dialogId);
                if (dialog) {
                    dialog.last_message = messageText;
                    dialog.last_message_at = serverMessage.created_at || new Date().toISOString();
                }

                return serverMessage;
            } catch (error) {
                const index = this.messages.findIndex(m => m.id === tempMessage.id);
                if (index !== -1) {
                    this.messages[index].status = 'error';
                    this.messages[index].error = error.response?.data?.message || 'Ошибка отправки';
                }
                console.error('[Chat Store] Ошибка отправки:', error);
                throw error;
            } finally {
                this.isSending = false;
            }
        },

        /**
         * Повторная отправка
         */
        async retryMessage(message) {
            if (!message || message.status !== 'error') return;

            const index = this.messages.findIndex(m => m.id === message.id);
            if (index === -1) return;

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

        // ==========================================
        // WEBSOCKET / REALTIME
        // ==========================================

        /**
         * Обработка входящего сообщения
         */
        handleIncomingMessage(message) {
            if (this.currentDialog &&
                String(message.dialog_id) === String(this.currentDialog.id)) {
                this.messages.push(message);
            }

            const dialog = this.getDialogById(message.dialog_id);
            if (dialog) {
                dialog.last_message = message.text || message.message;
                dialog.last_message_at = message.created_at;

                if (!this.currentDialog ||
                    String(this.currentDialog.id) !== String(message.dialog_id)) {
                    dialog.unread_count = (dialog.unread_count || 0) + 1;
                }
            } else {
                this.loadDialogs();
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.dialogs = [];
            this.messages = [];
            this.currentDialog = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSending = false;
            this.isMessagesLoading = false;
            this.isAttachmentsLoading = false;
            this.dialogActions = {};
            this.lastError = null;
            this.messagesPage = 1;
            this.hasMoreMessages = true;
            this.lastSyncAt = null;
        },
    },
});
