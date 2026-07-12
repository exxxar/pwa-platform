import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useClientsStore } from '@/MobileClient/stores/Shop/clients.js';

export function useClients() {
    const store = useClientsStore();

    const {
        currentClient,
        receiverUserData,
        messages,
        messagesPagination,
        isLoadingClient,
        isLoadingMessages,
        isSendingMessage,
        isUploading,
        lastError,
    } = storeToRefs(store);

    const hasClient = computed(() => store.hasClient);
    const clientName = computed(() => store.clientName);
    const clientPhone = computed(() => store.clientPhone);
    const sortedMessages = computed(() => store.sortedMessages);
    const unreadCount = computed(() => store.unreadCount);

    return {
        // State
        currentClient,
        receiverUserData,
        messages,
        messagesPagination,
        isLoadingClient,
        isLoadingMessages,
        isSendingMessage,
        isUploading,
        lastError,

        // Getters
        hasClient,
        clientName,
        clientPhone,
        sortedMessages,
        unreadCount,

        // Actions
        loadReceiverUserData: store.loadReceiverUserData,
        loadMessages: store.loadMessages,
        sendMessage: store.sendMessage,
        sendFile: store.sendFile,
        markAsRead: store.markAsRead,
        deleteMessage: store.deleteMessage,
        selectClient: store.selectClient,
        clearClient: store.clearClient,

        $reset: store.$reset,
    };
}
