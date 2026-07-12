import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useBroadcastsStore } from '@/MobileClient/stores/Shop/broadcasts.js';

export function useBroadcasts() {
    const store = useBroadcastsStore();

    const {
        broadcasts,
        currentBroadcast,
        statistics,
        isLoading,
        isHydrated,
        isSending,
        lastError,
        filters,
        pagination,
    } = storeToRefs(store);

    const draftBroadcasts = computed(() => store.draftBroadcasts);
    const scheduledBroadcasts = computed(() => store.scheduledBroadcasts);
    const sentBroadcasts = computed(() => store.sentBroadcasts);
    const activeBroadcasts = computed(() => store.activeBroadcasts);

    return {
        // State
        broadcasts,
        currentBroadcast,
        statistics,
        isLoading,
        isHydrated,
        isSending,
        lastError,
        filters,
        pagination,

        // Getters
        draftBroadcasts,
        scheduledBroadcasts,
        sentBroadcasts,
        activeBroadcasts,

        // Methods
        loadBroadcasts: store.loadBroadcasts,
        loadBroadcast: store.loadBroadcast,
        createBroadcast: store.createBroadcast,
        updateBroadcast: store.updateBroadcast,
        sendBroadcast: store.sendBroadcast,
        cancelBroadcast: store.cancelBroadcast,
        deleteBroadcast: store.deleteBroadcast,
        duplicateBroadcast: store.duplicateBroadcast,
        uploadMedia: store.uploadMedia,
        deleteMedia: store.deleteMedia,
        setFilter: store.setFilter,

        $reset: store.$reset,
    };
}
