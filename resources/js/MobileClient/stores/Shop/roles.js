import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/admin/roles';

export const useRolesStore = defineStore('roles', {
    state: () => ({
        roles: [],
        permissions: [],

        isLoading: false,
        isSaving: false,
        lastError: null,
    }),

    getters: {
        // Сортировка: системные роли всегда сверху
        sortedRoles: (state) => {
            return [...state.roles].sort((a, b) => {
                const sysRoles = ['super_admin', 'user'];
                const aSys = sysRoles.includes(a.name) ? 0 : 1;
                const bSys = sysRoles.includes(b.name) ? 0 : 1;
                if (aSys !== bSys) return aSys - bSys;
                return a.label.localeCompare(b.label);
            });
        },

        hasRoles: (state) => state.roles.length > 0,
    },

    actions: {
        /**
         * Загрузить список ролей
         */
        async loadRoles() {
            this.isLoading = true;
            this.lastError = null;
            try {
                const response = await axios.get(BASE);
                this.roles = response.data.data || [];
                return this.roles;
            } catch (error) {
                console.error('[Roles] Ошибка загрузки ролей:', error);
                this.lastError = error.response?.data?.message || 'Ошибка загрузки';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузить все доступные разрешения (permissions)
         */
        async loadPermissions() {
            try {
                const response = await axios.get('/admin/permissions');
                this.permissions = response.data.data || [];
                return this.permissions;
            } catch (error) {
                console.error('[Roles] Ошибка загрузки разрешений:', error);
                throw error;
            }
        },

        /**
         * Создать или обновить роль
         */
        async saveRole(payload) {
            this.isSaving = true;
            this.lastError = null;
            try {
                let response;
                if (payload.id) {
                    // Обновление
                    response = await axios.put(`${BASE}/${payload.id}`, {
                        label: payload.label,
                        name: payload.name,
                        permission_ids: payload.permission_ids,
                    });
                } else {
                    // Создание
                    response = await axios.post(BASE, {
                        label: payload.label,
                        name: payload.name,
                        permission_ids: payload.permission_ids,
                    });
                }

                // Обновляем локальный список
                await this.loadRoles();
                return response.data.data;
            } catch (error) {
                console.error('[Roles] Ошибка сохранения:', error);
                this.lastError = error.response?.data?.message || 'Не удалось сохранить роль';
                throw error;
            } finally {
                this.isSaving = false;
            }
        },

        /**
         * Удалить роль
         */
        async deleteRole(roleId) {
            this.lastError = null;
            try {
                await axios.delete(`${BASE}/${roleId}`);
                await this.loadRoles(); // Перезагружаем список
                return true;
            } catch (error) {
                console.error('[Roles] Ошибка удаления:', error);
                this.lastError = error.response?.data?.message || 'Не удалось удалить роль';
                throw error;
            }
        },

        /**
         * Полный сброс состояния
         */
        $reset() {
            this.roles = [];
            this.permissions = [];
            this.isLoading = false;
            this.isSaving = false;
            this.lastError = null;
        }
    },
});
