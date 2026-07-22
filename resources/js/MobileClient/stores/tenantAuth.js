import { defineStore } from 'pinia';
import axios from 'axios';

export const useTenantAuthStore = defineStore('tenantAuth', {
    state: () => ({
        user: window.TenantUser || null,
        tenant: window.Tenant || null,
        isLoading: false,
        errorMessage: '',
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,
        roles: (state) => state.user?.roles?.map(r => r.name) ?? [],
        permissions: (state) => {
            const perms = state.user?.roles?.flatMap(r => r.permissions?.map(p => p.name)) ?? [];
            return [...new Set(perms)];
        }
    },

    actions: {
        hasRole(role) {
            return this.roles.includes(role)
        },

        hasAnyRole(rolesArray) {
            return rolesArray.some(r => this.roles.includes(r))
        },

        hasPermission(permission) {
            if (this.hasRole('super_admin')) return true
            return this.permissions.includes(permission)
        },

        hasAnyPermission(permissionsArray) {
            if (this.hasRole('super_admin')) return true
            return permissionsArray.some(p => this.permissions.includes(p))
        },

        clearError() {
            this.errorMessage = ''; // Просто обнуляем строку ошибки
        },
        setUser(user) {
            this.user = user;
            window.TenantUser = user; // Синхронизируем с глобальной переменной
        },

        async login(credentials) {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const response = await axios.post('/auth/login', {
                    identifier: credentials.identifier,
                    password: credentials.password
                });

                // Бэкенд вернул { message: '...', user: {...} }
                this.setUser(response.data.user);
                return response.data;
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Ошибка входа. Проверьте данные.';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async register(credentials) {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const response = await axios.post('/auth/register', {
                    name: credentials.name,
                    phone: credentials.phone,
                    password: credentials.password
                });

                this.setUser(response.data.user);
                return response.data;
            } catch (error) {
                // При регистрации ошибки валидации приходят в формате { phone: ['Телефон уже занят'] }
                const errors = error.response?.data?.errors;
                if (errors) {
                    this.errorMessage = Object.values(errors)[0][0]; // Берем первое сообщение об ошибке
                } else {
                    this.errorMessage = error.response?.data?.message || 'Ошибка регистрации.';
                }
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/auth/logout');
            } catch (e) {
                console.error('Logout error', e);
            } finally {
                this.user = null;
                window.TenantUser = null;
            }
        }
    }
});
