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
        isLoadingMore,
        // --- Геттеры (Getters) ---
        usersCount,
        admins,
        vips,
        deliverymen,
    } = storeToRefs(store);

    const manageCashback = (userId, payload) => store.manageCashback(userId, payload);

    return {
        // Состояние (Refs)
        users,
        users_paginate_object,
        isLoading,
        isHydrated,
        isDownloading,
        lastError,
        filters,
        isLoadingMore,

        // Геттеры (Refs)
        usersCount,
        admins,
        vips,
        deliverymen,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        manageCashback,
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


        loadMoreUsers: store.loadMoreUsers,
        addCashback: store.addCashback,
        startChat: store.startChat,

        // Сброс стора
        $reset: store.$reset,
    };
}
