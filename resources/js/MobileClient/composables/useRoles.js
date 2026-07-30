import { storeToRefs } from 'pinia';
import { useRolesStore } from '@/MobileClient/stores/Shop/roles.js';

export function useRoles() {
    const store = useRolesStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        roles,
        permissions,
        isLoading,
        isSaving,
        lastError,

        // --- Геттеры (Getters) ---
        sortedRoles,
        hasRoles,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        roles,
        permissions,
        isLoading,
        isSaving,
        lastError,

        // Геттеры (Refs)
        sortedRoles,
        hasRoles,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        loadRoles: store.loadRoles,
        loadPermissions: store.loadPermissions,
        saveRole: store.saveRole,
        deleteRole: store.deleteRole,

        // Сброс стора
        $reset: store.$reset,
    };
}
