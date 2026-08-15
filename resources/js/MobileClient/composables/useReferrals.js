import { storeToRefs } from 'pinia';
import { useReferralsStore } from '@/MobileClient/stores/Shop/referrals.js';

export function useReferrals() {
    const store = useReferralsStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        referralTree,
        rewardsHistory,

        friends,
        incomingRequests,
        isLoading,
        isHydrated,
        lastError,

        // --- Геттеры (Getters) ---
        totalReferrals,
        totalEarnings,
        friendsCount,
        pendingRequestsCount,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        referralTree,
        rewardsHistory,


        friends,
        incomingRequests,
        isLoading,
        isHydrated,
        lastError,

        // Геттеры (Refs)
        totalReferrals,
        totalEarnings,
        friendsCount,
        pendingRequestsCount,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        loadReferralTree: store.loadReferralTree,
        loadRewardsHistory: store.loadRewardsHistory,
        loadReferralLink: store.loadReferralLink,
        loadFriends: store.loadFriends,
        loadIncomingRequests: store.loadIncomingRequests,
        sendFriendRequest: store.sendFriendRequest,
        acceptFriendRequest: store.acceptFriendRequest,
        rejectFriendRequest: store.rejectFriendRequest,
        removeFriend: store.removeFriend,

        // Сброс стора
        $reset: store.$reset,
    };
}
