import { storeToRefs } from 'pinia';
import { useBroadcastsStore } from '@/MobileClient/stores/Shop/broadcasts.js';

export function useBroadcasts() {
    const store = useBroadcastsStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        broadcasts,
        currentBroadcast,
        statistics,
        isLoading,
        isHydrated,
        isSending,
        lastError,
        filters,
        pagination,

        // --- Геттеры (Getters) ---
        draftBroadcasts,
        scheduledBroadcasts,
        sentBroadcasts,
        activeBroadcasts,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        broadcasts,
        currentBroadcast,
        statistics,
        isLoading,
        isHydrated,
        isSending,
        lastError,
        filters,
        pagination,

        // Геттеры (Refs)
        draftBroadcasts,
        scheduledBroadcasts,
        sentBroadcasts,
        activeBroadcasts,

        // Методы (Actions)
        // ✅ Прямое маппирование — это отлично и эффективно, если не требуется
        // дополнительная обертка (например, для перехвата ошибок).
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

        // Сброс стора
        $reset: store.$reset,
    };
}
