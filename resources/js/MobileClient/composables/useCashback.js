// src/composables/useCashback.js
import { storeToRefs } from 'pinia';
import { useCashbackStore } from '@/MobileClient/stores/Shop/cashback';

export function useCashback() {
    const store = useCashbackStore();

    const {
        balance,
        history,
        specialSubs,
        loading,
        loadingHistory,
        hasMore,
    } = storeToRefs(store);

    return {
        // State
        balance,
        history,
        specialSubs,
        loading,
        loadingHistory,
        hasMore,

        // Getters
        formattedBalance: store.formattedBalance,
        hasSpecialSubs: store.hasSpecialSubs,

        // Actions
        fetchCashbackData: store.fetchCashbackData,
        loadMoreHistory: store.loadMoreHistory,
        downloadHistory: store.downloadHistory,
        reset: store.reset,
    };
}
