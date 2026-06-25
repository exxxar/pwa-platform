import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/partners';

export const usePartnersStore = defineStore('partners', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        partners: [],
        partners_paginate_object: null,
        categories: [],
        selfPartner: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,
        isCategoriesLoading: false,

        // Загрузка по конкретным партнёрам: { [partnerId]: 'update' | 'delete' | 'toggle-fav' }
        partnerActions: {},

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
         * Все партнёры
         */
        getPartners: (state) => state.partners || [],

        /**
         * Пагинация
         */
        getPartnersPaginateObject: (state) => state.partners_paginate_object || null,

        /**
         * Партнёры отсортированные (избранные сверху, затем по имени)
         */
        sortedPartners: (state) => {
            return [...(state.partners || [])].sort((a, b) => {
                // Избранные сверху
                if (a.is_favorite && !b.is_favorite) return -1;
                if (!a.is_favorite && b.is_favorite) return 1;
                // Затем по имени
                const nameA = (a.name || '').toLowerCase();
                const nameB = (b.name || '').toLowerCase();
                return nameA.localeCompare(nameB);
            });
        },

        /**
         * Только активные партнёры
         */
        activePartners: (state) => {
            return (state.partners || []).filter(p => p.is_active !== false);
        },

        /**
         * Избранные партнёры
         */
        favoritePartners: (state) => {
            return (state.partners || []).filter(p => p.is_favorite);
        },

        /**
         * Найти партнёра по ID
         */
        getPartnerById: (state) => (id) => {
            return state.partners.find(p => String(p.id) === String(id)) || null;
        },

        /**
         * Проверка, является ли партнёр избранным
         */
        isFavorite: (state) => (id) => {
            const partner = state.partners.find(p => String(p.id) === String(id));
            return partner?.is_favorite || false;
        },

        /**
         * Загружается ли конкретный партнёр
         */
        isPartnerLoading: (state) => (id) => {
            return !!state.partnerActions[String(id)];
        },

        /**
         * Список категорий
         */
        getCategories: (state) => state.categories || [],

        /**
         * Текущий пользователь как партнёр
         */
        getSelfPartner: (state) => state.selfPartner,

        /**
         * Общее количество партнёров
         */
        partnersCount: (state) => state.partners?.length || 0,
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ------------------------------------------
        // ЗАГРУЗКА ДАННЫХ
        // ------------------------------------------

        /**
         * Загрузка списка партнёров
         */
        async loadPartners(payload = {}) {
            this.isLoading = true;
            this.lastError = null;
            this.errors = [];

            try {
                const response = await axios.post(BASE, payload);
                const dataObject = response.data;

                this.partners = dataObject.data || [];

                const { data, ...pagination } = dataObject;
                this.partners_paginate_object = pagination;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                return dataObject;
            } catch (err) {
                console.error('[Partners Store] Ошибка загрузки партнёров:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить партнёров';
                this.errors = err.response?.data?.errors || [];
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка категорий партнёров
         */
        async loadCategories(payload = {}) {
            this.isCategoriesLoading = true;

            try {
                const response = await axios.post(`${BASE}/partners-categories`, payload);
                this.categories = response.data?.data || response.data || [];
                return this.categories;
            } catch (err) {
                console.error('[Partners Store] Ошибка загрузки категорий:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить категории';
                throw err;
            } finally {
                this.isCategoriesLoading = false;
            }
        },

        /**
         * Загрузка данных текущего пользователя как партнёра
         */
        async loadSelfPartner() {
            try {
                const response = await axios.get(`${BASE}/self`);
                this.selfPartner = response.data?.data || response.data;
                return this.selfPartner;
            } catch (err) {
                console.error('[Partners Store] Ошибка загрузки self partner:', err);
                throw err;
            }
        },

        // ------------------------------------------
        // CRUD ПАРТНЁРОВ
        // ------------------------------------------

        /**
         * Создание нового партнёра
         */
        async storePartner(payload = { form: null }) {
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/store`, payload.form);
                const newPartner = response.data?.data || response.data;

                // Добавляем в начало списка
                this.partners.unshift(newPartner);

                return newPartner;
            } catch (err) {
                console.error('[Partners Store] Ошибка создания партнёра:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать партнёра';
                throw err;
            }
        },

        /**
         * Обновление партнёра
         */
        async updatePartner(payload = { form: null }) {
            const partnerId = payload.form?.id;
            if (partnerId) {
                this.partnerActions[String(partnerId)] = 'update';
            }

            try {
                const response = await axios.post(`${BASE}/update`, payload.form);
                const updatedPartner = response.data?.data || response.data;

                // Обновляем в списке
                if (partnerId) {
                    const index = this.partners.findIndex(p => String(p.id) === String(partnerId));
                    if (index !== -1) {
                        this.partners[index] = { ...this.partners[index], ...updatedPartner };
                    }
                }

                // Если обновляли себя — обновляем selfPartner
                if (this.selfPartner && String(this.selfPartner.id) === String(partnerId)) {
                    this.selfPartner = { ...this.selfPartner, ...updatedPartner };
                }

                return updatedPartner;
            } catch (err) {
                console.error('[Partners Store] Ошибка обновления партнёра:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить партнёра';
                throw err;
            } finally {
                if (partnerId) {
                    delete this.partnerActions[String(partnerId)];
                }
            }
        },

        /**
         * Обновление данных текущего пользователя-партнёра
         */
        async updateSelfPartner(payload = { form: null }) {
            try {
                const response = await axios.post(`${BASE}/update-self`, payload.form);
                const updated = response.data?.data || response.data;

                // Обновляем selfPartner
                if (this.selfPartner) {
                    this.selfPartner = { ...this.selfPartner, ...updated };
                }

                // Обновляем в общем списке
                if (updated?.id) {
                    const index = this.partners.findIndex(p => String(p.id) === String(updated.id));
                    if (index !== -1) {
                        this.partners[index] = { ...this.partners[index], ...updated };
                    }
                }

                return updated;
            } catch (err) {
                console.error('[Partners Store] Ошибка обновления self partner:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить данные';
                throw err;
            }
        },

        /**
         * Удаление партнёра
         */
        async removePartner(payload = { partnerId: null }) {
            if (!payload.partnerId) {
                throw new Error('Не указан ID партнёра');
            }

            this.partnerActions[String(payload.partnerId)] = 'delete';

            // Сохраняем для отката
            const previousPartners = [...this.partners];
            const removedIndex = this.partners.findIndex(p => String(p.id) === String(payload.partnerId));
            const removedPartner = removedIndex !== -1 ? this.partners[removedIndex] : null;

            // Оптимистично удаляем
            if (removedIndex !== -1) {
                this.partners.splice(removedIndex, 1);
            }

            try {
                const response = await axios.post(`${BASE}/remove/${payload.partnerId}`);
                return response.data;
            } catch (err) {
                // Откатываем
                if (removedPartner && removedIndex !== -1) {
                    this.partners.splice(removedIndex, 0, removedPartner);
                }
                console.error('[Partners Store] Ошибка удаления партнёра:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить партнёра';
                throw err;
            } finally {
                delete this.partnerActions[String(payload.partnerId)];
            }
        },

        // ------------------------------------------
        // СТАТУСЫ И НАСТРОЙКИ
        // ------------------------------------------

        /**
         * Переключение статуса активности партнёра
         */
        async updatePartnersActiveStatus(payload) {
            const partnerId = payload?.id || payload?.partner_id;
            if (partnerId) {
                this.partnerActions[String(partnerId)] = 'toggle-active';
            }

            // Сохраняем предыдущее состояние для отката
            let previousState = null;
            if (partnerId) {
                const partner = this.getPartnerById(partnerId);
                if (partner) {
                    previousState = partner.is_active;
                    // Оптимистично переключаем
                    partner.is_active = !partner.is_active;
                }
            }

            try {
                const response = await axios.post(`${BASE}/update-active-status`, payload);
                const updated = response.data?.data || response.data;

                // Синхронизируем с ответом сервера
                if (partnerId && updated) {
                    const index = this.partners.findIndex(p => String(p.id) === String(partnerId));
                    if (index !== -1) {
                        this.partners[index] = { ...this.partners[index], ...updated };
                    }
                }

                return updated;
            } catch (err) {
                // Откатываем
                if (partnerId && previousState !== null) {
                    const partner = this.getPartnerById(partnerId);
                    if (partner) {
                        partner.is_active = previousState;
                    }
                }
                console.error('[Partners Store] Ошибка изменения статуса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось изменить статус';
                throw err;
            } finally {
                if (partnerId) {
                    delete this.partnerActions[String(partnerId)];
                }
            }
        },

        /**
         * Обновление настроек партнёра
         */
        async updatePartnersSettings(payload) {
            try {
                const response = await axios.post(`${BASE}/update-settings`, payload);
                const updated = response.data?.data || response.data;

                // Обновляем в списке
                const partnerId = payload?.id || payload?.partner_id;
                if (partnerId) {
                    const index = this.partners.findIndex(p => String(p.id) === String(partnerId));
                    if (index !== -1) {
                        this.partners[index] = { ...this.partners[index], ...updated };
                    }
                }

                return updated;
            } catch (err) {
                console.error('[Partners Store] Ошибка обновления настроек:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить настройки';
                throw err;
            }
        },

        /**
         * Изменение статуса товара партнёра
         */
        async changePartnerProductStatus(payload) {
            try {
                const response = await axios.post(`${BASE}/change-status`, payload);
                return response.data;
            } catch (err) {
                console.error('[Partners Store] Ошибка изменения статуса товара:', err);
                this.lastError = err.response?.data?.message || 'Не удалось изменить статус товара';
                throw err;
            }
        },

        // ------------------------------------------
        // ИЗБРАННОЕ
        // ------------------------------------------

        /**
         * Переключение партнёра в избранном (оптимистично)
         */
        async togglePartnerInFavorites(payload = { form: null }) {
            const partnerId = payload.form?.id || payload.form?.partner_id;
            if (!partnerId) {
                throw new Error('Не указан ID партнёра');
            }

            this.partnerActions[String(partnerId)] = 'toggle-fav';

            // Сохраняем предыдущее состояние
            const partner = this.getPartnerById(partnerId);
            const previousState = partner?.is_favorite;

            // Оптимистично переключаем
            if (partner) {
                partner.is_favorite = !partner.is_favorite;
            }

            try {
                const response = await axios.post(`${BASE}/toggle-favorite`, payload.form);
                const result = response.data?.data || response.data;

                // Синхронизируем с сервером
                if (partner && result) {
                    partner.is_favorite = result.is_favorite ?? !previousState;
                }

                return result;
            } catch (err) {
                // Откатываем
                if (partner && previousState !== undefined) {
                    partner.is_favorite = previousState;
                }
                console.error('[Partners Store] Ошибка переключения избранного:', err);
                this.lastError = err.response?.data?.message || 'Не удалось обновить избранное';
                throw err;
            } finally {
                delete this.partnerActions[String(partnerId)];
            }
        },

        // ------------------------------------------
        // СБРОС
        // ------------------------------------------

        /**
         * Полный сброс состояния
         */
        $reset() {
            this.partners = [];
            this.partners_paginate_object = null;
            this.categories = [];
            this.selfPartner = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isCategoriesLoading = false;
            this.partnerActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('mypwa_partners');
            localStorage.removeItem('mypwa_partners_paginate_object');
        },

        // Legacy методы для совместимости
        setErrors(errors = []) {
            this.errors = errors;
        },
        setPartners(payload) {
            this.partners = payload || [];
            localStorage.setItem('mypwa_partners', JSON.stringify(this.partners));
        },
        setPartnersPaginateObject(payload) {
            this.partners_paginate_object = payload || null;
            localStorage.setItem('mypwa_partners_paginate_object', JSON.stringify(this.partners_paginate_object));
        },
    },
});
