import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/statistic';

export const useStatisticStore = defineStore('statistic', {
    state: () => ({
        statistic: null,
        users: [],
        orders: [],
        products: [],
        cashback_up: [],
        cashback_down: [],
        traffics: [],

        isLoading: false,
        isTrafficLoading: false,
        isExporting: false,
        isHydrated: false,
        lastError: null,

        // 🆕 Кэш последних параметров для повторных запросов
        lastParams: null,
    }),

    getters: {
        hasData: (state) => !!state.statistic,

        /**
         * 🆕 ИСПРАВЛЕНО: объединяем ВСЕ месяцы из up и down
         * Раньше терялись месяцы, которые были только в списаниях
         */
        preparedCashback: (state) => {
            const up = state.cashback_up || [];
            const down = state.cashback_down || [];

            if (up.length === 0 && down.length === 0) {
                return [];
            }

            // Собираем все уникальные ключи year-month
            const map = new Map();

            up.forEach(item => {
                const key = `${item.y}-${item.m}`;
                map.set(key, {
                    y: item.y,
                    m: item.m,
                    up: item.sum || 0,
                    down: 0,
                });
            });

            down.forEach(item => {
                const key = `${item.y}-${item.m}`;
                if (map.has(key)) {
                    map.get(key).down = item.sum || 0;
                } else {
                    map.set(key, {
                        y: item.y,
                        m: item.m,
                        up: 0,
                        down: item.sum || 0,
                    });
                }
            });

            // Сортируем по году и месяцу
            return Array.from(map.values()).sort((a, b) => {
                if (a.y !== b.y) return a.y - b.y;
                return a.m - b.m;
            });
        },

        totalTraffic: (state) => {
            return (state.traffics || []).reduce((sum, t) => sum + (t.count || 0), 0);
        },

        // 🆕 Геттеры для totals (удобно в UI)
        totalOrdersSum: (state) => {
            return (state.orders || []).reduce((sum, o) => sum + (o.sump || 0), 0);
        },

        totalOrdersCount: (state) => {
            return (state.orders || []).reduce((sum, o) => sum + (o.count || 0), 0);
        },

        totalUsersRegistered: (state) => {
            return (state.users || []).reduce((sum, u) => sum + (u.count || 0), 0);
        },
    },

    actions: {
        /**
         * 🆕 Загрузка основной статистики
         */
        async loadStatistic(params = {}) {
            // 🆕 Защита от повторных идентичных запросов
            const paramsKey = JSON.stringify(params);
            if (this.isLoading && this.lastParams === paramsKey) {
                return;
            }

            this.isLoading = true;
            this.lastError = null;
            this.lastParams = paramsKey;

            try {
                const response = await axios.get(`${BASE}/main`, { params });
                const data = response.data?.statistic || response.data || {};

                this.statistic = data;

                // Безопасное извлечение массивов
                this.orders = data.orders?.sum || [];
                this.products = data.orders?.products || [];
                this.users = data.users?.sum || [];
                this.cashback_up = data.cashback_up?.sum || [];
                this.cashback_down = data.cashback_down?.sum || [];

                this.isHydrated = true;

                return data;
            } catch (error) {
                console.error('[Statistic] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message
                    || error.message
                    || 'Ошибка загрузки статистики';
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
                this.traffics = response.data?.traffics || response.data?.data || [];
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

                // Определяем имя файла из заголовков
                const disposition = response.headers['content-disposition'];
                let filename = `statistic_${Date.now()}.csv`;

                if (disposition) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        filename = match[1].replace(/['"]/g, '');
                    }
                }

                // Создаём blob и скачиваем
                const blob = new Blob([response.data], {
                    type: response.headers['content-type'] || 'text/csv'
                });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();

                // Очистка
                setTimeout(() => {
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                }, 100);

                return true;
            } catch (error) {
                console.error('[Statistic] Ошибка экспорта:', error);
                throw error;
            } finally {
                this.isExporting = false;
            }
        },

        /**
         * 🆕 Полный сброс
         */
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
            this.lastParams = null;
        },
    },
});
