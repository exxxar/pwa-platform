// src/stores/cashback.js
import { defineStore } from 'pinia';

export const useCashbackStore = defineStore('cashback', {
    state: () => ({
        balance: 0,
        history: [],
        specialSubs: [],
        loading: false,
        loadingHistory: false,
        currentPage: 1,
        hasMore: true,
    }),

    getters: {
        formattedBalance: (state) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(state.balance);
        },

        hasSpecialSubs: (state) => {
            return state.specialSubs.length > 0;
        },
    },

    actions: {
        async fetchCashbackData() {
            this.loading = true;
            try {
                const response = await axios.get('/cashback');
                this.balance = response.data.balance;
                this.history = response.data.history;
                this.specialSubs = response.data.special_subs;

                // Обновляем глобальный объект пользователя
                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = response.data.balance;
                    window.TenantUser.cashback_subs = response.data.special_subs;
                }
            } catch (error) {
                console.error('Ошибка загрузки кэшбэка:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async loadMoreHistory() {
            if (this.loadingHistory || !this.hasMore) return;

            this.loadingHistory = true;
            try {
                const response = await axios.get('/cashback/history', {
                    params: {
                        page: this.currentPage + 1,
                        limit: 20,
                    }
                });

                this.history.push(...response.data.data);
                this.currentPage = response.data.current_page;
                this.hasMore = response.data.has_more;
            } catch (error) {
                console.error('Ошибка загрузки истории:', error);
                throw error;
            } finally {
                this.loadingHistory = false;
            }
        },

        async downloadHistory() {
            try {
                const response = await axios.get('/cashback/download', {
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'cashback_history.csv');
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Ошибка скачивания истории:', error);
                throw error;
            }
        },

        reset() {
            this.balance = 0;
            this.history = [];
            this.specialSubs = [];
            this.currentPage = 1;
            this.hasMore = true;
        }
    }
});
