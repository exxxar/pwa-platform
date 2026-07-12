import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/clients';

export const useClientsStore = defineStore('clients', {
    state: () => ({
        // Текущий выбранный клиент
        currentClient: null,

        // Данные пользователя (для диалога)
        receiverUserData: null,

        // История сообщений
        messages: [],
        messagesPagination: null,

        // Состояние загрузки
        isLoadingClient: false,
        isLoadingMessages: false,
        isSendingMessage: false,
        isUploading: false,

        lastError: null,
    }),

    getters: {
        hasClient: (state) => !!state.currentClient,
        clientName: (state) => state.currentClient?.name || 'Клиент',
        clientPhone: (state) => state.currentClient?.phone || '',

        sortedMessages: (state) => {
            return [...state.messages].sort((a, b) => {
                return new Date(a.created_at) - new Date(b.created_at);
            });
        },

        unreadCount: (state) => {
            return state.messages.filter(m => !m.is_read && m.sender_type !== 'admin').length;
        },
    },

    actions: {
        /**
         * 🆕 Загрузка данных пользователя
         */
        async loadReceiverUserData(userId) {
            this.isLoadingClient = true;
            this.lastError = null;

            try {
                const response = await axios.get(`${BASE}/${userId}`);
                this.receiverUserData = response.data.data;
                return this.receiverUserData;
            } catch (error) {
                console.error('[Clients] Ошибка загрузки пользователя:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoadingClient = false;
            }
        },

        /**
         * 🆕 Загрузка истории сообщений
         */
        async loadMessages(userId, page = 1) {
            this.isLoadingMessages = true;

            try {
                const response = await axios.get(`${BASE}/${userId}/messages`, {
                    params: { page },
                });

                const data = response.data;

                if (page === 1) {
                    this.messages = data.data || [];
                } else {
                    // Добавляем старые сообщения в начало
                    this.messages = [...(data.data || []), ...this.messages];
                }

                this.messagesPagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    total: data.total || 0,
                };

                return this.messages;
            } catch (error) {
                console.error('[Clients] Ошибка загрузки сообщений:', error);
                throw error;
            } finally {
                this.isLoadingMessages = false;
            }
        },

        /**
         * 🆕 Отправка текстового сообщения
         */
        async sendMessage(userId, message) {
            this.isSendingMessage = true;

            try {
                const response = await axios.post(`${BASE}/${userId}/send`, {
                    message,
                });

                const newMessage = response.data.data;
                this.messages.push(newMessage);

                return newMessage;
            } catch (error) {
                console.error('[Clients] Ошибка отправки:', error);
                throw error;
            } finally {
                this.isSendingMessage = false;
            }
        },

        /**
         * 🆕 Отправка файла (изображение, видео, документ)
         */
        async sendFile(userId, file, type = 'file') {
            this.isUploading = true;

            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', type);

                const response = await axios.post(
                    `${BASE}/${userId}/send-file`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );

                const newMessage = response.data.data;
                this.messages.push(newMessage);

                return newMessage;
            } catch (error) {
                console.error('[Clients] Ошибка загрузки файла:', error);
                throw error;
            } finally {
                this.isUploading = false;
            }
        },

        /**
         * 🆕 Пометить сообщения как прочитанные
         */
        async markAsRead(userId) {
            try {
                await axios.post(`${BASE}/${userId}/read`);

                // Локально помечаем все сообщения
                this.messages.forEach(m => {
                    if (m.sender_type !== 'admin') {
                        m.is_read = true;
                    }
                });
            } catch (error) {
                console.error('[Clients] Ошибка пометки прочтения:', error);
            }
        },

        /**
         * 🆕 Удалить сообщение
         */
        async deleteMessage(messageId) {
            try {
                await axios.delete(`${BASE}/messages/${messageId}`);
                this.messages = this.messages.filter(m => m.id !== messageId);
                return true;
            } catch (error) {
                console.error('[Clients] Ошибка удаления:', error);
                throw error;
            }
        },

        /**
         * 🆕 Выбрать клиента
         */
        async selectClient(user) {
            this.currentClient = user;

            // Параллельно загружаем данные и сообщения
            await Promise.all([
                this.loadReceiverUserData(user.id),
                this.loadMessages(user.id, 1),
            ]);

            // Помечаем как прочитанные
            await this.markAsRead(user.id);
        },

        /**
         * 🆕 Сброс состояния
         */
        clearClient() {
            this.currentClient = null;
            this.receiverUserData = null;
            this.messages = [];
            this.messagesPagination = null;
        },

        $reset() {
            this.clearClient();
            this.isLoadingClient = false;
            this.isLoadingMessages = false;
            this.isSendingMessage = false;
            this.isUploading = false;
            this.lastError = null;
        },
    },
});
