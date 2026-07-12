import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/broadcasts';

export const useBroadcastsStore = defineStore('broadcasts', {
    state: () => ({
        broadcasts: [],
        currentBroadcast: null,
        statistics: null,

        isLoading: false,
        isHydrated: false,
        isSending: false,

        lastError: null,

        // Фильтры
        filters: {
            status: null,
        },

        pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1,
        },
    }),

    getters: {
        draftBroadcasts: (state) =>
            state.broadcasts.filter(b => b.status === 'draft'),

        scheduledBroadcasts: (state) =>
            state.broadcasts.filter(b => b.status === 'scheduled'),

        sentBroadcasts: (state) =>
            state.broadcasts.filter(b => b.status === 'sent'),

        activeBroadcasts: (state) =>
            state.broadcasts.filter(b =>
                ['sending', 'scheduled'].includes(b.status)
            ),
    },

    actions: {
        async loadBroadcasts(params = {}) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE, {
                    params: {
                        ...this.filters,
                        ...params,
                    },
                });

                const data = response.data.data;

                if (data.data) {
                    this.broadcasts = data.data;
                    this.pagination = {
                        total: data.total,
                        per_page: data.per_page,
                        current_page: data.current_page,
                        last_page: data.last_page,
                    };
                } else {
                    this.broadcasts = data;
                }

                this.isHydrated = true;
            } catch (error) {
                console.error('[Broadcasts] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async loadBroadcast(id) {
            this.isLoading = true;

            try {
                const response = await axios.get(`${BASE}/${id}`);
                this.currentBroadcast = response.data.data;
                this.statistics = response.data.statistics;
                return this.currentBroadcast;
            } catch (error) {
                console.error('[Broadcasts] Ошибка загрузки:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async createBroadcast(formData) {
            this.isLoading = true;

            try {
                const response = await axios.post(BASE, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                this.broadcasts.unshift(response.data.data);
                return response.data.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка создания:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async updateBroadcast(id, data) {
            try {
                const response = await axios.put(`${BASE}/${id}`, data);

                const index = this.broadcasts.findIndex(b => b.id === id);
                if (index !== -1) {
                    this.broadcasts[index] = response.data.data;
                }

                return response.data.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка обновления:', error);
                throw error;
            }
        },

        async sendBroadcast(id) {
            this.isSending = true;

            try {
                const response = await axios.post(`${BASE}/${id}/send`);

                const index = this.broadcasts.findIndex(b => b.id === id);
                if (index !== -1) {
                    this.broadcasts[index].status = 'sent';
                }

                return response.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка отправки:', error);
                throw error;
            } finally {
                this.isSending = false;
            }
        },

        async cancelBroadcast(id) {
            try {
                const response = await axios.post(`${BASE}/${id}/cancel`);

                const index = this.broadcasts.findIndex(b => b.id === id);
                if (index !== -1) {
                    this.broadcasts[index].status = 'cancelled';
                }

                return response.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка отмены:', error);
                throw error;
            }
        },

        async deleteBroadcast(id) {
            try {
                await axios.delete(`${BASE}/${id}`);
                this.broadcasts = this.broadcasts.filter(b => b.id !== id);
                return true;
            } catch (error) {
                console.error('[Broadcasts] Ошибка удаления:', error);
                throw error;
            }
        },

        async duplicateBroadcast(id) {
            try {
                const response = await axios.post(`${BASE}/${id}/duplicate`);
                this.broadcasts.unshift(response.data.data);
                return response.data.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка дублирования:', error);
                throw error;
            }
        },

        async uploadMedia(broadcastId, file, type) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);

            try {
                const response = await axios.post(
                    `${BASE}/${broadcastId}/media`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );
                return response.data.data;
            } catch (error) {
                console.error('[Broadcasts] Ошибка загрузки медиа:', error);
                throw error;
            }
        },

        async deleteMedia(mediaId) {
            try {
                await axios.delete(`${BASE}/media/${mediaId}`);
                return true;
            } catch (error) {
                console.error('[Broadcasts] Ошибка удаления медиа:', error);
                throw error;
            }
        },

        setFilter(key, value) {
            this.filters[key] = value;
        },

        $reset() {
            this.broadcasts = [];
            this.currentBroadcast = null;
            this.statistics = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isSending = false;
            this.lastError = null;
            this.filters = { status: null };
        },
    },
});
