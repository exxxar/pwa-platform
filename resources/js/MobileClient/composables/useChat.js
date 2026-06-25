import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useChatStore } from '@/MobileClient/stores/Shop/chat.js';

/**
 * Composable для работы с чатом
 */
export function useChat() {
    const store = useChatStore();

    // Реактивные ссылки на состояние
    const {
        dialogs,
        messages,
        currentDialog,
        isLoading,
        isHydrated,
        isSending,
        isMessagesLoading,
        lastError,
        unreadCount,
        hasMoreMessages,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedDialogs = computed(() => store.sortedDialogs);
    const totalUnread = computed(() => store.totalUnread);
    const currentInterlocutor = computed(() => store.currentInterlocutor);
    const sortedMessages = computed(() => store.sortedMessages);

    /**
     * Безопасная загрузка диалогов
     */
    const loadDialogs = async () => {
        try {
            await store.loadDialogs();
        } catch (error) {
            console.error('Ошибка загрузки диалогов:', error);
        }
    };

    /**
     * Открытие диалога с обработкой ошибок
     */
    const openDialog = async (dialogId) => {
        try {
            return await store.openDialog(dialogId);
        } catch (error) {
            console.error('Ошибка открытия диалога:', error);
            throw error;
        }
    };

    /**
     * Отправка сообщения с уведомлением
     */
    const sendMessage = async (messageText, attachments = []) => {
        if (!store.currentDialog) {
            throw new Error('Нет активного диалога');
        }

        try {
            return await store.sendMessage(
                store.currentDialog.id,
                messageText,
                attachments
            );
        } catch (error) {
            console.error('Ошибка отправки сообщения:', error);
            throw error;
        }
    };

    /**
     * Загрузка более старых сообщений
     */
    const loadOlderMessages = async () => {
        if (!store.currentDialog) return [];

        try {
            return await store.loadOlderMessages(store.currentDialog.id);
        } catch (error) {
            console.error('Ошибка загрузки старых сообщений:', error);
            return [];
        }
    };

    return {
        // Состояние
        dialogs,
        messages,
        currentDialog,
        isLoading,
        isHydrated,
        isSending,
        isMessagesLoading,
        lastError,
        unreadCount,
        hasMoreMessages,

        // Геттеры
        sortedDialogs,
        totalUnread,
        currentInterlocutor,
        sortedMessages,

        // Методы
        loadDialogs,
        openDialog,
        closeDialog: store.closeDialog,
        sendMessage,
        loadOlderMessages,
        retryMessage: store.retryMessage,
        deleteMessage: store.deleteMessage,
        deleteDialog: store.deleteDialog,
        markDialogAsRead: store.markDialogAsRead,
        handleIncomingMessage: store.handleIncomingMessage,
        getDialogById: store.getDialogById,
    };
}
