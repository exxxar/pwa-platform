import { storeToRefs } from 'pinia';
import { useTenantAuthStore } from '@/MobileClient/stores/tenantAuth.js';

export function useAuth() {
    const store = useTenantAuthStore();

    const {
        user,
        tenant,
        isLoading,
        errorMessage,
        isAuthenticated,
        roles,
        permissions
    } = storeToRefs(store);

    return {
        // State
        user,
        tenant,
        isLoading,
        errorMessage,

        // Getters
        isAuthenticated,
        roles,
        permissions,

        // Actions
        setUser: store.setUser,
        logout: store.logout,
        clearError: store.clearError,
        hasRole: store.hasRole,
        hasAnyRole: store.hasAnyRole,
        hasPermission: store.hasPermission,
        hasAnyPermission: store.hasAnyPermission,

        // Auth Actions
        login: store.login,
        register: store.register,
    };
}
