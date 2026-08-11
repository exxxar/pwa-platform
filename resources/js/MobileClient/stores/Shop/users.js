import {defineStore} from 'pinia';
import axios from 'axios';

const BASE = '/admin/users';

export const useUsersStore = defineStore('users', {
    state: () => ({
        users: [],
        users_paginate_object: null,
        currentUser: null,
        isLoading: false,
        isHydrated: false,
        isDownloading: false,
        isSaving: false,
        lastError: null,
        isLoadingMore: false,
        // Текущие фильтры
        filters: {
            search: '',
            need_admins: false,
            need_vip: false,
            need_not_vip: false,
            need_deliveryman: false,
            need_with_phone: false,
            need_without_phone: false,
        },
    }),

    getters: {
        getUsers: (state) => state.users,
        getUsersPaginateObject: (state) => state.users_paginate_object,

        usersCount: (state) => state.users.length,

        admins: (state) => state.users.filter(u => u.is_admin),
        vips: (state) => state.users.filter(u => u.is_vip),
        deliverymen: (state) => state.users.filter(u => u.is_deliveryman),
    },

    actions: {


        /**
         * 🆕 Загрузить следующую страницу и ДОБАВИТЬ к существующему списку
         */
        async loadMoreUsers() {
            const currentPage = this.users_paginate_object?.current_page || 1;
            const lastPage = this.users_paginate_object?.last_page || 1;

            if (currentPage >= lastPage) {
                console.log('[Users] Все страницы уже загружены');
                return;
            }

            this.isLoadingMore = true;
            try {
                const params = {
                    page: currentPage, // бэкенд ожидает 0-based, но мы уже инкрементировали
                    size: 20,
                    ...this.filters,
                };

                const response = await axios.get(`${BASE}/search`, {params});
                const data = response.data;

                if (data.data && Array.isArray(data.data)) {
                    // 🎯 КЛЮЧЕВОЕ: append вместо replace
                    this.users = [...this.users, ...data.data];

                    this.users_paginate_object = {
                        ...this.users_paginate_object,
                        current_page: (data.current_page || 0) + 1,
                        last_page: data.last_page || 1,
                        total: data.total || this.users.length,
                    };
                }
            } catch (error) {
                console.error('[Users] Ошибка подгрузки:', error);
                throw error;
            } finally {
                this.isLoadingMore = false;
            }
        },

        async manageCashback(userId, payload) {
            try {
                // Предполагается, что у вас есть роут вроде: Route::post('/admin/users/{id}/cashback', ...)
                const response = await axios.post(`/admin/users/${userId}/cashback`, payload);
                return response.data;
            } catch (error) {
                console.error('Ошибка управления баллами:', error);
                throw error;
            }
        },
        /**
         * 🆕 Начислить бонусные баллы
         */
        async addCashback(userId, payload) {
            try {
                const response = await axios.post(`${BASE}/${userId}/add-cashback`, payload);

                // Обновляем пользователя в списке
                const index = this.users.findIndex(u => u.id === userId);
                if (index !== -1 && response.data.data) {
                    this.users[index] = {...this.users[index], ...response.data.data};
                }

                return response.data;
            } catch (error) {
                console.error('[Users] Ошибка начисления:', error);
                throw error;
            }
        },

        /**
         * 🆕 Создать/получить диалог с пользователем
         */
        async startChat(userId) {
            try {
                const response = await axios.post(`${BASE}/${userId}/start-chat`);
                return response.data.dialog_id;
            } catch (error) {
                console.error('[Users] Ошибка создания чата:', error);
                throw error;
            }
        },


        async loadUsers(payload = {}) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const {dataObject = {}, page = 0} = payload;

                // 🎯 Объединяем переданные фильтры с текущими из state
                const mergedFilters = {...this.filters, ...dataObject};

                const params = {
                    page: page,
                    size: 20,
                    ...mergedFilters,
                };

                const response = await axios.get(`${BASE}/search`, {params});
                const data = response.data;

                if (data.data) {
                    // При новом поиске — ЗАМЕНЯЕМ список (не append)
                    this.users = data.data;

                    const {data: items, ...pagination} = data;
                    this.users_paginate_object = {
                        total: pagination.total || items.length,
                        per_page: pagination.per_page || 20,
                        current_page: (pagination.current_page || 0) + 1,
                        last_page: pagination.last_page || 1,
                    };
                } else {
                    this.users = data || [];
                    this.users_paginate_object = null;
                }

                this.isHydrated = true;
                return this.users;
            } catch (error) {
                console.error('[Users] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * 🆕 Загрузка данных пользователя для редактирования
         */
        async loadUserForEdit(userId) {
            this.isLoading = true;
            try {
                const response = await axios.get(`${BASE}/${userId}/edit`);
                this.currentUser = response.data.data;
                return this.currentUser;
            } catch (error) {
                console.error('[Users] Ошибка загрузки:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * 🆕 Обновление пользователя
         */
        async updateUser(userId, data) {
            this.isSaving = true;
            this.lastError = null;

            try {
                const response = await axios.put(`${BASE}/${userId}`, data);

                // Обновляем в списке
                const index = this.users.findIndex(u => u.id === userId);
                if (index !== -1) {
                    this.users[index] = {...this.users[index], ...response.data.data};
                }

                // Обновляем текущего пользователя
                this.currentUser = response.data.data;

                return response.data;
            } catch (error) {
                console.error('[Users] Ошибка обновления:', error);
                this.lastError = error.response?.data?.message || 'Ошибка сохранения';
                throw error;
            } finally {
                this.isSaving = false;
            }
        },

        /**
         * 🆕 Блокировка/разблокировка пользователя
         */
        async toggleBlock(userId, blockData = {}) {
            try {
                const response = await axios.post(`${BASE}/${userId}/toggle-block`, blockData);

                const index = this.users.findIndex(u => u.id === userId);
                if (index !== -1) {
                    this.users[index] = {...this.users[index], ...response.data.data};
                }

                if (this.currentUser?.id === userId) {
                    this.currentUser = {...this.currentUser, ...response.data.data};
                }

                return response.data;
            } catch (error) {
                console.error('[Users] Ошибка блокировки:', error);
                throw error;
            }
        },

        /**
         * 🆕 Выдача/отзыв VIP статуса
         */
        async toggleVip(userId, vipData = {}) {
            try {
                const response = await axios.post(`${BASE}/${userId}/toggle-vip`, vipData);

                const index = this.users.findIndex(u => u.id === userId);
                if (index !== -1) {
                    this.users[index] = {...this.users[index], ...response.data.data};
                }

                if (this.currentUser?.id === userId) {
                    this.currentUser = {...this.currentUser, ...response.data.data};
                }

                return response.data;
            } catch (error) {
                console.error('[Users] Ошибка VIP:', error);
                throw error;
            }
        },

        /**
         * 🆕 Сброс текущего пользователя
         */
        clearCurrentUser() {
            this.currentUser = null;
        },

        /**
         * 🆕 Скачать список пользователей
         */
        async downloadUsers() {
            this.isDownloading = true;

            try {
                const response = await axios.get(`${BASE}/download`, {
                    responseType: 'blob',
                });

                // Создаём ссылку для скачивания
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `users_${Date.now()}.xlsx`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                return true;

            } catch (error) {
                console.error('[Users] Ошибка скачивания:', error);
                throw error;
            } finally {
                this.isDownloading = false;
            }
        },

        /**
         * 🆕 Скачать историю начислений кэшбэка
         */
        async downloadCashbackHistory() {
            this.isDownloading = true;

            try {
                const response = await axios.get(`${BASE}/cashback-history`, {
                    responseType: 'blob',
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `cashback_history_${Date.now()}.xlsx`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                return true;

            } catch (error) {
                console.error('[Users] Ошибка скачивания истории:', error);
                throw error;
            } finally {
                this.isDownloading = false;
            }
        },

        /**
         * 🆕 Обновить фильтры
         */
        setFilters(filters) {
            this.filters = {...this.filters, ...filters};
        },

        /**
         * 🆕 Сбросить фильтры
         */
        resetFilters() {
            this.filters = {
                search: '',
                need_admins: false,
                need_vip: false,
                need_not_vip: false,
                need_deliveryman: false,
                need_with_phone: false,
                need_without_phone: false,
            };
        },

        /**
         * 🆕 Сброс состояния
         */
        $reset() {
            this.users = [];
            this.users_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isDownloading = false;
            this.lastError = null;
            this.resetFilters();
        },
    },
});
