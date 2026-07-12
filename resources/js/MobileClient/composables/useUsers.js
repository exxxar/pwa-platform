import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useUsersStore } from '@/MobileClient/stores/Shop/users.js';

export function useUsers() {
    const store = useUsersStore();

    const {
        users,
        users_paginate_object,
        isLoading,
        isHydrated,
        isDownloading,
        lastError,
        filters,
    } = storeToRefs(store);

    const usersCount = computed(() => store.usersCount);
    const admins = computed(() => store.admins);
    const vips = computed(() => store.vips);
    const deliverymen = computed(() => store.deliverymen);

    return {
        // State
        users,
        users_paginate_object,
        isLoading,
        isHydrated,
        isDownloading,
        lastError,
        filters,

        // Getters
        usersCount,
        admins,
        vips,
        deliverymen,

        // Methods
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



        $reset: store.$reset,
    };
}
