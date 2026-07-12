import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/statistic';

export const useStatisticStore = defineStore('statistic', {
    state: () => ({
        // Основная статистика
        statistic: null,

        // Данные графиков
        users: [],
        orders: [],
        products: [],
        cashback_up: [],
        cashback_down: [],

        // Трафик
        traffics: [],

        // Состояния
        isLoading: false,
        isTrafficLoading: false,
        isExporting: false,
        isHydrated: false,

        lastError: null,
    }),

    getters: {
        hasData: (state) => !!state.statistic,

        preparedCashback: (state) => {
            if (state.cashback_up.length === 0 && state.cashback_down.length === 0) {
                return [];
            }

            return state.cashback_up.map(up => {
                const down = state.cashback_down.find(d => d.m === up.m && d.y === up.y);
                return {
                    y: up.y || 0,
                    m: up.m || 0,
                    up: up.sum || 0,
                    down: down ? down.sum : 0,
                };
            });
        },

        totalTraffic: (state) => {
            return state.traffics.reduce((sum, t) => sum + (t.count || 0), 0);
        },
    },

    actions: {
        /**
         * 🆕 Загрузка основной статистики
         */
        async loadStatistic(params = {}) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(`${BASE}/main`, { params });
                const data = response.data;

                this.statistic = data.statistic || data;
                this.orders = this.statistic.orders?.sum || [];
                this.products = this.statistic.orders?.products || [];
                this.users = this.statistic.users?.sum || [];
                this.cashback_up = this.statistic.cashback_up?.sum || [];
                this.cashback_down = this.statistic.cashback_down?.sum || [];
                this.isHydrated = true;

                return data;
            } catch (error) {
                console.error('[Statistic] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * 🆕 Загрузка трафика
         */
        async loadTraffic(params = {}) {
            this.isTrafficLoading = true;

            try {
                const response = await axios.get(`${BASE}/traffic`, { params });
                this.traffics = response.data.traffics || response.data.data || [];
                return this.traffics;
            } catch (error) {
                console.error('[Statistic] Ошибка загрузки трафика:', error);
                throw error;
            } finally {
                this.isTrafficLoading = false;
            }
        },

        /**
         * 🆕 Экспорт статистики
         */
        async exportStatistic(params = {}) {
            this.isExporting = true;

            try {
                const response = await axios.get(`${BASE}/export`, {
                    params,
                    responseType: 'blob',
                });

                // Создаём ссылку для скачивания
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `statistic_${Date.now()}.xlsx`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                return true;
            } catch (error) {
                console.error('[Statistic] Ошибка экспорта:', error);
                throw error;
            } finally {
                this.isExporting = false;
            }
        },

        $reset() {
            this.statistic = null;
            this.users = [];
            this.orders = [];
            this.products = [];
            this.cashback_up = [];
            this.cashback_down = [];
            this.traffics = [];
            this.isLoading = false;
            this.isTrafficLoading = false;
            this.isExporting = false;
            this.isHydrated = false;
            this.lastError = null;
        },
    },
});
