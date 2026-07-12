import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useReferralsStore } from '@/MobileClient/stores/Shop/referrals.js';

export function useReferrals() {
    const store = useReferralsStore();

    const {
        referralTree,
        rewardsHistory,
        referralLink,
        referralCode,
        friends,
        incomingRequests,
        isLoading,
        isHydrated,
        lastError,
    } = storeToRefs(store);

    const totalReferrals = computed(() => store.totalReferrals);
    const totalEarnings = computed(() => store.totalEarnings);
    const friendsCount = computed(() => store.friendsCount);
    const pendingRequestsCount = computed(() => store.pendingRequestsCount);

    return {
        // State
        referralTree,
        rewardsHistory,
        referralLink,
        referralCode,
        friends,
        incomingRequests,
        isLoading,
        isHydrated,
        lastError,

        // Getters
        totalReferrals,
        totalEarnings,
        friendsCount,
        pendingRequestsCount,

        // Methods
        loadReferralTree: store.loadReferralTree,
        loadRewardsHistory: store.loadRewardsHistory,
        loadReferralLink: store.loadReferralLink,
        loadFriends: store.loadFriends,
        loadIncomingRequests: store.loadIncomingRequests,
        sendFriendRequest: store.sendFriendRequest,
        acceptFriendRequest: store.acceptFriendRequest,
        rejectFriendRequest: store.rejectFriendRequest,
        removeFriend: store.removeFriend,

        $reset: store.$reset,
    };
}
