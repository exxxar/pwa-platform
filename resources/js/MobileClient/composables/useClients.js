import { storeToRefs } from 'pinia';
import { useClientsStore } from '@/MobileClient/stores/Shop/clients.js';

export function useClients() {
    const store = useClientsStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        currentClient,
        receiverUserData,
        messages,
        messagesPagination,
        isLoadingClient,
        isLoadingMessages,
        isSendingMessage,
        isUploading,
        lastError,

        // --- Геттеры (Getters) ---
        hasClient,
        clientName,
        clientPhone,
        sortedMessages,
        unreadCount,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        currentClient,
        receiverUserData,
        messages,
        messagesPagination,
        isLoadingClient,
        isLoadingMessages,
        isSendingMessage,
        isUploading,
        lastError,

        // Геттеры (Refs)
        hasClient,
        clientName,
        clientPhone,
        sortedMessages,
        unreadCount,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        loadReceiverUserData: store.loadReceiverUserData,
        loadMessages: store.loadMessages,
        sendMessage: store.sendMessage,
        sendFile: store.sendFile,
        markAsRead: store.markAsRead,
        deleteMessage: store.deleteMessage,
        selectClient: store.selectClient,
        clearClient: store.clearClient,

        // Сброс стора
        $reset: store.$reset,
    };
}
