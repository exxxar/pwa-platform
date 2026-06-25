import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/promocodes';

export const usePromocodesStore = defineStore('promocodes', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        promocodes: [],
        promocodes_paginate_object: null,

        // Состояние загрузки по секциям
        isLoading: false,
        isHydrated: false,
        isActivating: false,
        isShopDiscountActivating: false,

        // Действия над промокодами: { [promocodeId]: 'delete' | 'update' }
        promocodeActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все промокоды
         */
        getPromoCodes: (state) => state.promocodes || [],

        /**
         * Пагинация
         */
        getPromoCodesPaginateObject: (state) => state.promocodes_paginate_object || null,

        /**
         * Найти промокод по ID
         */
        getPromocodeById: (state) => (id) => {
            return (state.promocodes || []).find(p => String(p.id) === String(id)) || null;
        },

        /**
         * Найти промокод по коду
         */
        getPromocodeByCode: (state) => (code) => {
            if (!code) return null;
            const normalizedCode = String(code).toUpperCase().trim();
            return (state.promocodes || []).find(
                p => String(p.code || '').toUpperCase() === normalizedCode
            ) || null;
        },

        /**
         * Отсортированные промокоды (новые сверху)
         */
        sortedPromocodes: (state) => {
            return [...(state.promocodes || [])].sort((a, b) => {
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });
        },

        /**
         * Активные промокоды (действуют сейчас)
         */
        activePromocodes: (state) => {
            const now = new Date();
            return (state.promocodes || []).filter(p => {
                const startDate = p.starts_at ? new Date(p.starts_at) : null;
                const endDate = p.expires_at ? new Date(p.expires_at) : null;
                const isUsedUp = p.usage_limit && p.used_count >= p.usage_limit;

                const isStarted = !startDate || startDate <= now;
                const isNotExpired = !endDate || endDate >= now;

                return p.is_active !== false && isStarted && isNotExpired && !isUsedUp;
            });
        },

        /**
         * Просроченные промокоды
         */
        expiredPromocodes: (state) => {
            const now = new Date();
            return (state.promocodes || []).filter(p => {
                if (!p.expires_at) return false;
                return new Date(p.expires_at) < now;
            });
        },

        /**
         * Будущие промокоды (ещё не начались)
         */
        upcomingPromocodes: (state) => {
            const now = new Date();
            return (state.promocodes || []).filter(p => {
                if (!p.starts_at) return false;
                return new Date(p.starts_at) > now;
            });
        },

        /**
         * Промокоды, у которых заканчивается лимит использований
         */
        almostExhaustedPromocodes: (state) => {
            return (state.promocodes || []).filter(p => {
                if (!p.usage_limit) return false;
                const remaining = p.usage_limit - (p.used_count || 0);
                return remaining > 0 && remaining <= 5;
            });
        },

        /**
         * Промокоды по типу
         */
        promocodesByType: (state) => (type) => {
            return (state.promocodes || []).filter(p => p.type === type);
        },

        /**
         * Проверка, загружается ли промокод
         */
        isPromocodeLoading: (state) => (id) => {
            return !!state.promocodeActions[String(id)];
        },

        /**
         * Количество промокодов
         */
        promocodesCount: (state) => state.promocodes?.length || 0,

        /**
         * Количество активных промокодов
         */
        activePromocodesCount: (state) => {
            const now = new Date();
            return (state.promocodes || []).filter(p => {
                const endDate = p.expires_at ? new Date(p.expires_at) : null;
                const isNotExpired = !endDate || endDate >= now;
                const isUsedUp = p.usage_limit && p.used_count >= p.usage_limit;
                return p.is_active !== false && isNotExpired && !isUsedUp;
            }).length;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка промокодов
         */
        async loadPromoCodes(payload = { dataObject: null, page: 0, size: 50 }) {
            this.isLoading = true;
            this.lastError = null;
            this.errors = [];

            try {
                const page = payload.page || 0;
                const size = payload.size || 50;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.promocodes = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.promocodes_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage
                localStorage.setItem('cashman_promo_codes', JSON.stringify(this.promocodes));
                localStorage.setItem('cashman_promo_codes_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Promocodes Store] Ошибка загрузки промокодов:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить промокоды';
                this.errors = err.response?.data?.errors || [];

                // Fallback: пробуем загрузить из localStorage
                const cached = localStorage.getItem('cashman_promo_codes');
                if (cached && this.promocodes.length === 0) {
                    try {
                        this.promocodes = JSON.parse(cached);
                    } catch {
                        this.promocodes = [];
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        // ==========================================
        // АКТИВАЦИЯ
        // ==========================================

        /**
         * Активация промокода (пользователем)
         */
        async activatePromocode(payload = { promocodeForm: null }) {
            this.isActivating = true;
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/activate`, payload.promocodeForm);
                return response.data;
            } catch (err) {
                console.error('[Promocodes Store] Ошибка активации промокода:', err);
                this.lastError = err.response?.data?.message || 'Не удалось активировать промокод';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isActivating = false;
            }
        },

        /**
         * Активация промокода скидки магазина
         */
        async activateShopDiscountPromocode(payload = { promocodeForm: null }) {
            this.isShopDiscountActivating = true;
            this.lastError = null;

            try {
                const response = await axios.post(
                    `${BASE}/activate-shop-discount`,
                    payload.promocodeForm
                );
                return response.data;
            } catch (err) {
                console.error('[Promocodes Store] Ошибка активации скидки:', err);
                this.lastError = err.response?.data?.message || 'Не удалось активировать скидку';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isShopDiscountActivating = false;
            }
        },

        // ==========================================
        // CRUD
        // ==========================================

        /**
         * Создание промокода
         */
        async storePromoCodes(payload = { promoCodeForm: null }) {
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/store`, payload.promoCodeForm);
                const newPromocode = response.data?.data || response.data;

                // Добавляем в начало списка
                if (newPromocode?.id) {
                    this.promocodes.unshift(newPromocode);
                    localStorage.setItem('cashman_promo_codes', JSON.stringify(this.promocodes));
                }

                return newPromocode;
            } catch (err) {
                console.error('[Promocodes Store] Ошибка создания промокода:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать промокод';
                this.errors = err.response?.data?.errors || [];
                throw err;
            }
        },

        /**
         * Удаление промокода (оптимистично)
         */
        async removePromoCodes(payload = { promoCodeId: null }) {
            if (!payload.promoCodeId) throw new Error('Не указан ID промокода');

            this.promocodeActions[String(payload.promoCodeId)] = 'delete';

            // Сохраняем для отката
            const removedIndex = this.promocodes.findIndex(
                p => String(p.id) === String(payload.promoCodeId)
            );
            const removedPromocode = removedIndex !== -1 ? this.promocodes[removedIndex] : null;

            // Оптимистично удаляем
            if (removedIndex !== -1) {
                this.promocodes.splice(removedIndex, 1);
                localStorage.setItem('cashman_promo_codes', JSON.stringify(this.promocodes));
            }

            try {
                const response = await axios.delete(`${BASE}/${payload.promoCodeId}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removedPromocode && removedIndex !== -1) {
                    this.promocodes.splice(removedIndex, 0, removedPromocode);
                    localStorage.setItem('cashman_promo_codes', JSON.stringify(this.promocodes));
                }
                console.error('[Promocodes Store] Ошибка удаления промокода:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить промокод';
                throw err;
            } finally {
                delete this.promocodeActions[String(payload.promoCodeId)];
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.promocodes = [];
            this.promocodes_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isActivating = false;
            this.isShopDiscountActivating = false;
            this.promocodeActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_promo_codes');
            localStorage.removeItem('cashman_promo_codes_paginate_object');
        },
    },
});
