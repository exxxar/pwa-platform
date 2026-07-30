import { storeToRefs } from 'pinia';
import { useUsersStore } from '@/MobileClient/stores/Shop/users.js';

export function useUsers() {
    const store = useUsersStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        users,
        users_paginate_object,
        isLoading,
        isHydrated,
        isDownloading,
        lastError,
        filters,

        // --- Геттеры (Getters) ---
        usersCount,
        admins,
        vips,
        deliverymen,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        users,
        users_paginate_object,
        isLoading,
        isHydrated,
        isDownloading,
        lastError,
        filters,

        // Геттеры (Refs)
        usersCount,
        admins,
        vips,
        deliverymen,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        loadUsers: store.loadUsers,
        downloadUsers: store.downloadUsers,
        downloadCashbackHistory: store.downloadCashbackHistory,
        setFilters: store.setFilters,
        resetFilters: store.resetFilters,
        loadUserForEdit: store.loadUserForEdit,
        updateUser: store.updateUser,
        toggleBlock: store.toggleBlock,
        toggleVip: store.toggleVip,
        clearCurrentUser: store.clearCurrentUser,

        // Сброс стора
        $reset: store.$reset,
    };
}
