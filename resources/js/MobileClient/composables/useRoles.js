import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useRolesStore } from '@/MobileClient/stores/Shop/roles.js';

export function useRoles() {
    const store = useRolesStore();

    const {
        roles,
        permissions,
        isLoading,
        isSaving,
        lastError,
    } = storeToRefs(store);

    // Getters
    const sortedRoles = computed(() => store.sortedRoles);
    const hasRoles = computed(() => store.hasRoles);

    return {
        // State
        roles,
        permissions,
        isLoading,
        isSaving,
        lastError,

        // Getters
        sortedRoles,
        hasRoles,

        // Actions
        loadRoles: store.loadRoles,
        loadPermissions: store.loadPermissions,
        saveRole: store.saveRole,
        deleteRole: store.deleteRole,

        $reset: store.$reset,
    };
}
