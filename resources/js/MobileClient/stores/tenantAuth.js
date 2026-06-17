import { defineStore } from 'pinia'

export const useTenantAuthStore = defineStore('tenantAuth', {
    state: () => ({
        user: window.TenantUser || null,
        tenant: window.Tenant || null
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,

        roles: (state) => state.user?.roles?.map(r => r.name) ?? [],

        permissions: (state) =>
            state.user?.roles?.flatMap(r => r.permissions?.map(p => p.name)) ?? []
    },

    actions: {
        setUser(user) {
            this.user = user
        },

        logout() {
            this.user = null
        },

        hasRole(role) {
            return this.roles.includes(role)
        },

        hasAnyRole(roles) {
            return roles.some(r => this.roles.includes(r))
        },

        hasPermission(permission) {
            return this.permissions.includes(permission)
        },

        hasAnyPermission(permissions) {
            return permissions.some(p => this.permissions.includes(p))
        }
    }
})
