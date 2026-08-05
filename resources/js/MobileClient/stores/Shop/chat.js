import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/dialogs';

export const useChatStore = defineStore('chat', {
    state: () => ({
        dialogs: [],
        messages: [],
        currentDialog: null,

        // Счётчики непрочитанных (source of truth)
        totalUnread: 0,
        byDialogUnread: {},

        isLoading: false,
        isHydrated: false,
        isSending: false,
        isMessagesLoading: false,
        isAttachmentsLoading: false,
        dialogActions: {},
        lastError: null,
        messagesPage: 1,
        hasMoreMessages: true,
        lastSyncAt: null,

        // 🆕 Polling статусов прочтения
        pollingInterval: null,
        pollingDialogId: null,
    }),

    getters: {
        sortedDialogs: (state) => {
            return [...(state.dialogs || [])].sort((a, b) => {
                if (a.is_pinned && !b.is_pinned) return -1;
                if (!a.is_pinned && b.is_pinned) return 1;

                const aUnread = a.is_archived ? 0 : (a.unread_count || 0);
                const bUnread = b.is_archived ? 0 : (b.unread_count || 0);
                if (aUnread !== bUnread) return bUnread - aUnread;

                const dateA = new Date(a.last_message_at || a.updated_at || 0);
                const dateB = new Date(b.last_message_at || b.updated_at || 0);
                return dateB - dateA;
            });
        },

        activeDialogs: (state) => (state.dialogs || []).filter(d => !d.is_archived),
        archivedDialogs: (state) => (state.dialogs || []).filter(d => d.is_archived),
        activeDialogsCount: (state) => (state.dialogs || []).filter(d => !d.is_archived).length,
        archivedDialogsCount: (state) => (state.dialogs || []).filter(d => d.is_archived).length,

        currentInterlocutor: (state) => {
            if (!state.currentDialog) return null;
            return state.currentDialog.interlocutor
                || state.currentDialog.user
                || state.currentDialog.companion
                || null;
        },

        sortedMessages: (state) => {
            return [...(state.messages || [])].sort((a, b) =>
                new Date(a.created_at) - new Date(b.created_at)
            );
        },

        getDialogById: (state) => (id) =>
            state.dialogs.find(d => String(d.id) === String(id)) || null,

        isDialogLoading: (state) => (id) => !!state.dialogActions[String(id)],

        getUnreadForDialog: (state) => (dialogId) =>
            state.byDialogUnread[String(dialogId)] || 0,

        // 🆕 Проверка, идёт ли polling для конкретного диалога
        isPollingActive: (state) => (dialogId) =>
            state.pollingInterval !== null &&
            String(state.pollingDialogId) === String(dialogId),
    },

    actions: {
        setInitialData(data) {
            if (!data) return;
            this.dialogs = data.items || [];
            this.totalUnread = data.total_unread || 0;
            this.isHydrated = true;
        },

        // ==========================================
        // НЕПРОЧИТАННЫЕ
        // ==========================================

        async loadUnreadCount() {
            try {
                const response = await axios.get(`${BASE}/unread-count`);
                const data = response.data;

                this.totalUnread = data.total || 0;
                this.byDialogUnread = data.by_dialog || {};

                this.dialogs.forEach(dialog => {
                    dialog.unread_count = this.byDialogUnread[String(dialog.id)] || 0;
                });

                return data;
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки unreadCount:', error);
                return { total: 0, by_dialog: {} };
            }
        },

        clearUnreadForDialog(dialogId) {
            const key = String(dialogId);
            const count = this.byDialogUnread[key] || 0;

            if (count > 0) {
                this.totalUnread = Math.max(0, this.totalUnread - count);
                this.byDialogUnread[key] = 0;

                const dialog = this.getDialogById(dialogId);
                if (dialog) dialog.unread_count = 0;
            }

            axios.post(`${BASE}/${dialogId}/read`).catch(err => {
                console.warn('[Chat Store] Не удалось отметить как прочитанное:', err);
            });
        },

        decrementUnread(dialogId) {
            const key = String(dialogId);
            if (this.byDialogUnread[key] > 0) {
                this.byDialogUnread[key]--;
                this.totalUnread = Math.max(0, this.totalUnread - 1);

                const dialog = this.getDialogById(dialogId);
                if (dialog) dialog.unread_count = Math.max(0, (dialog.unread_count || 1) - 1);
            }
        },

        // ==========================================
        // 🆕 POLLING СТАТУСОВ ПРОЧТЕНИЯ
        // ==========================================

        /**
         * Запустить опрос статусов прочтения (каждые 10 сек)
         */
        startReadStatusPolling(dialogId, intervalMs = 10000) {
            // Останавливаем предыдущий, если он был
            this.stopReadStatusPolling();

            if (!dialogId) return;

            this.pollingDialogId = dialogId;

            // Сразу делаем первый запрос
            this.refreshReadStatuses(dialogId);

            // Устанавливаем интервал
            this.pollingInterval = setInterval(async () => {
                try {
                    await this.refreshReadStatuses(dialogId);
                } catch (e) {
                    console.warn('[Chat] Polling error:', e);
                }
            }, intervalMs);
        },

        /**
         * Остановить опрос
         */
        stopReadStatusPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
            this.pollingDialogId = null;
        },

        /**
         * Запросить свежие статусы прочтения с сервера.
         *
         * Эндпоинт должен вернуть объект вида:
         * { "123": "2024-01-15T10:30:00Z", "456": "2024-01-15T10:31:00Z" }
         * где ключ — ID сообщения, значение — время прочтения.
         */
        async refreshReadStatuses(dialogId) {
            if (!dialogId) return false;

            try {
                const response = await axios.get(`${BASE}/${dialogId}/read-statuses`);
                const statuses = response.data?.statuses || {};

                // Обновляем read_at у своих сообщений
                let hasChanges = false;
                this.messages.forEach(msg => {
                    const msgKey = String(msg.id);
                    const newReadAt = statuses[msgKey];

                    // Если сервер вернул read_at, а у нас его нет — обновляем
                    if (newReadAt && !msg.read_at) {
                        msg.read_at = newReadAt;
                        msg.status = 'read';
                        hasChanges = true;
                    }
                });

                return hasChanges;
            } catch (error) {
                // 404 — эндпоинт не реализован, тихо выходим
                if (error.response?.status === 404) {
                    return false;
                }
                console.error('[Chat] refreshReadStatuses error:', error);
                return false;
            }
        },

        // ==========================================
        // ДИАЛОГИ
        // ==========================================

        async loadDialogs() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE);
                const data = response.data?.data || response.data || [];
                this.dialogs = Array.isArray(data) ? data : [];

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                await this.loadUnreadCount();

                return this.dialogs;
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки диалогов:', error);
                this.lastError = error.response?.data?.message || 'Не удалось загрузить диалоги';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        closeDialog() {
            // 🆕 Останавливаем polling при закрытии
            this.stopReadStatusPolling();

            this.currentDialog = null;
            this.messages = [];
            this.messagesPage = 1;
            this.hasMoreMessages = true;
        },

        markDialogAsRead(dialogId) {
            this.clearUnreadForDialog(dialogId);
        },

        // ==========================================
        // АРХИВИРОВАНИЕ
        // ==========================================

        async archiveDialog(dialogId) {
            this.dialogActions[String(dialogId)] = 'archive';
            const dialog = this.getDialogById(dialogId);
            const previousState = dialog ? {
                is_archived: dialog.is_archived,
                archived_at: dialog.archived_at,
            } : null;

            if (dialog) {
                dialog.is_archived = true;
                dialog.archived_at = new Date().toISOString();
            }

            try {
                const response = await axios.post(`${BASE}/${dialogId}/archive`);
                return response.data;
            } catch (error) {
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

        async archiveMultiple(dialogIds) {
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
                const response = await axios.post(`${BASE}/archive-multiple`, { ids: dialogIds });
                return response.data;
            } catch (error) {
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

        async emptyArchive() {
            const archivedBefore = this.archivedDialogs.map(d => ({ ...d }));
            this.dialogs = this.dialogs.filter(d => !d.is_archived);

            try {
                const response = await axios.delete(`${BASE}/archive`);
                return response.data;
            } catch (error) {
                this.dialogs = [...this.dialogs, ...archivedBefore];
                console.error('[Chat Store] Ошибка очистки архива:', error);
                throw error;
            }
        },

        // ==========================================
        // УДАЛЕНИЕ
        // ==========================================

        async deleteDialogPermanently(dialogId) {
            this.dialogActions[String(dialogId)] = 'delete';

            const dialogIndex = this.dialogs.findIndex(d => String(d.id) === String(dialogId));
            const removedDialog = dialogIndex !== -1 ? this.dialogs[dialogIndex] : null;

            if (dialogIndex !== -1) this.dialogs.splice(dialogIndex, 1);

            if (this.currentDialog && String(this.currentDialog.id) === String(dialogId)) {
                this.closeDialog();
            }

            try {
                const response = await axios.delete(`${BASE}/${dialogId}`);
                return response.data;
            } catch (error) {
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
        // ВЛОЖЕНИЯ
        // ==========================================

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

        async openDialog(dialogId) {
            let dialog = this.getDialogById(dialogId);

            if (!dialog) {
                console.warn(`[Chat Store] Диалог #${dialogId} не найден. Создаём временный.`);
                dialog = {
                    id: dialogId,
                    interlocutor: { name: 'Загрузка...' },
                    unread_count: 0
                };
                this.dialogs.unshift(dialog);
            }

            this.currentDialog = dialog;
            this.messages = [];
            this.messagesPage = 1;
            this.hasMoreMessages = true;

            try {
                await this.loadMessages(dialogId);

                // Обнуляем счётчик для этого диалога
                if ((this.byDialogUnread[String(dialogId)] || 0) > 0) {
                    this.clearUnreadForDialog(dialogId);
                }

                // 🆕 Запускаем polling статусов прочтения
                this.startReadStatusPolling(dialogId);

                return dialog;
            } catch (error) {
                console.error('[Chat Store] Ошибка открытия диалога:', error);
                throw error;
            }
        },

        async loadMessages(dialogId, page = 1) {
            this.isMessagesLoading = true;

            try {
                const response = await axios.get(`${BASE}/${dialogId}/messages`, {
                    params: { page, per_page: 20 }
                });

                let rawData = response.data?.data || response.data || [];

                if (rawData.data && Array.isArray(rawData.data)) {
                    rawData = rawData.data;
                } else if (rawData.messages && Array.isArray(rawData.messages)) {
                    rawData = rawData.messages;
                }

                const messages = Array.isArray(rawData) ? rawData : [];

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

        async loadOlderMessages() {
            if (!this.currentDialog || !this.hasMoreMessages || this.isMessagesLoading) return [];
            try {
                return await this.loadMessages(this.currentDialog.id, this.messagesPage + 1);
            } catch (error) {
                console.error('[Chat Store] Ошибка загрузки старых сообщений:', error);
                throw error;
            }
        },

        async sendMessage(dialogId, payload) {
            const { text = '', attachments = [] } = payload || {};

            const formData = new FormData();
            formData.append('dialog_id', dialogId);

            if (text) {
                formData.append('text', text);
                formData.append('message', text);
            }

            if (Array.isArray(attachments) && attachments.length > 0) {
                attachments.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });
            }

            try {
                const response = await axios.post(`${BASE}/${dialogId}/messages`, formData);

                // 🆕 После отправки — сразу обновляем статусы прочтения
                await this.refreshReadStatuses(dialogId);

                return response.data;
            } catch (error) {
                console.error('[Chat Store] Ошибка отправки сообщения:', error);
                throw error;
            }
        },

        async retryMessage(message) {
            if (!message || message.status !== 'error') return;
            if (!this.currentDialog) return;

            const index = this.messages.findIndex(m => m.id === message.id);
            if (index !== -1) this.messages.splice(index, 1);

            try {
                await this.sendMessage(this.currentDialog.id, {
                    text: message.text || message.message || '',
                    attachments: message.attachments || [],
                });
            } catch (error) {
                console.error('[Chat Store] Ошибка повторной отправки:', error);
            }
        },

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
        // REALTIME / VAPID
        // ==========================================

        handleIncomingMessage(message) {
            if (this.currentDialog &&
                String(message.dialog_id) === String(this.currentDialog.id)) {
                this.messages.push(message);

                // 🆕 При входящем — обновляем статусы своих сообщений
                this.refreshReadStatuses(message.dialog_id);
            }

            const dialog = this.getDialogById(message.dialog_id);
            if (dialog) {
                dialog.last_message = message.text || message.message;
                dialog.last_message_at = message.created_at;

                if (!this.currentDialog ||
                    String(this.currentDialog.id) !== String(message.dialog_id)) {

                    const key = String(message.dialog_id);
                    const newCount = (this.byDialogUnread[key] || 0) + 1;
                    this.byDialogUnread[key] = newCount;
                    this.totalUnread++;
                    dialog.unread_count = newCount;
                }
            } else {
                this.loadDialogs();
            }
        },

        $reset() {
            // 🆕 Останавливаем polling при сбросе
            this.stopReadStatusPolling();

            this.dialogs = [];
            this.messages = [];
            this.currentDialog = null;
            this.totalUnread = 0;
            this.byDialogUnread = {};
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
