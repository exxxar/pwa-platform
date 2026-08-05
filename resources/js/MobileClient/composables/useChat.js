import { storeToRefs } from 'pinia';
import { useChatStore } from '@/MobileClient/stores/Shop/chat.js';

export function useChat() {
    const store = useChatStore();

    const {
        // State
        dialogs,
        messages,
        currentDialog,
        totalUnread,
        byDialogUnread,
        isLoading,
        isHydrated,
        isSending,
        isMessagesLoading,
        isAttachmentsLoading,
        dialogActions,
        lastError,
        lastSyncAt,
        hasMoreMessages,

        // Getters
        sortedDialogs,
        activeDialogs,
        archivedDialogs,
        activeDialogsCount,
        archivedDialogsCount,
        currentInterlocutor,
        sortedMessages,
        getDialogById,
    } = storeToRefs(store);

    const isDialogLoading = (dialogId) => store.isDialogLoading(dialogId);

    // 🆕 Реактивный доступ к статусу polling
    const isPollingActive = (dialogId) => store.isPollingActive(dialogId);

    const closeDialog = (router = null, routeName = 'Chat') => {
        store.closeDialog();
        if (router) {
            router.push({ name: routeName }).catch(() => {});
        }
    };

    const loadOlderMessages = async () => {
        if (!store.currentDialog) return [];
        try {
            return await store.loadOlderMessages();
        } catch (error) {
            console.error('Ошибка загрузки старых сообщений:', error);
            return [];
        }
    };

    const sendMessage = (dialogId, payload) => {
        if (!dialogId) throw new Error('Не указан ID диалога');
        return store.sendMessage(dialogId, payload);
    };

    return {
        // State
        dialogs,
        messages,
        currentDialog,
        totalUnread,
        byDialogUnread,
        isLoading,
        isHydrated,
        isSending,
        isMessagesLoading,
        isAttachmentsLoading,
        dialogActions,
        lastError,
        lastSyncAt,
        hasMoreMessages,

        // Getters
        sortedDialogs,
        activeDialogs,
        archivedDialogs,
        activeDialogsCount,
        archivedDialogsCount,
        currentInterlocutor,
        sortedMessages,
        isDialogLoading,
        getDialogById,

        // Методы: базовые
        loadDialogs: () => store.loadDialogs(),
        openDialog: (id) => store.openDialog(id),
        closeDialog,
        markDialogAsRead: (id) => store.markDialogAsRead(id),
        loadUnreadCount: () => store.loadUnreadCount(),
        clearUnreadForDialog: (id) => store.clearUnreadForDialog(id),
        decrementUnread: (id) => store.decrementUnread(id),
        getUnreadForDialog: (id) => store.getUnreadForDialog(id),

        // 🆕 Polling статусов прочтения
        refreshReadStatuses: (id) => store.refreshReadStatuses(id),
        startReadStatusPolling: (id, intervalMs) => store.startReadStatusPolling(id, intervalMs),
        stopReadStatusPolling: () => store.stopReadStatusPolling(),
        isPollingActive,

        // Архив / Удаление
        archiveDialog: (id) => store.archiveDialog(id),
        restoreDialog: (id) => store.restoreDialog(id),
        archiveMultiple: (ids) => store.archiveMultiple(ids),
        emptyArchive: () => store.emptyArchive(),
        deleteDialogPermanently: (id) => store.deleteDialogPermanently(id),

        // Вложения и сообщения
        getDialogAttachments: (id) => store.getDialogAttachments(id),
        sendMessage,
        loadOlderMessages,
        retryMessage: (msg) => store.retryMessage(msg),
        deleteMessage: (id) => store.deleteMessage(id),

        // Realtime
        handleIncomingMessage: (msg) => store.handleIncomingMessage(msg),

        $reset: () => store.$reset(),
    };
}



