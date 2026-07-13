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
        isAttachmentsLoading,
        dialogActions,
        lastError,
        lastSyncAt,
        hasMoreMessages,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedDialogs = computed(() => store.sortedDialogs);
    const activeDialogs = computed(() => store.activeDialogs);
    const archivedDialogs = computed(() => store.archivedDialogs);
    const activeDialogsCount = computed(() => store.activeDialogsCount);
    const archivedDialogsCount = computed(() => store.archivedDialogsCount);
    const totalUnread = computed(() => store.totalUnread);
    const currentInterlocutor = computed(() => store.currentInterlocutor);
    const sortedMessages = computed(() => store.sortedMessages);

    /**
     * Проверка, загружается ли диалог
     */
    const isDialogLoading = (dialogId) => {
        return store.isDialogLoading(dialogId);
    };

    // ==========================================
    // БАЗОВЫЕ МЕТОДЫ
    // ==========================================


    /**
     * 🆕 Получить общее число непрочитанных
     */
    const fetchUnreadCount = async () => {
        try {
            return await store.fetchUnreadCount();
        } catch (error) {
            console.error('Ошибка получения непрочитанных:', error);
            throw error;
        }
    };

    /**
     * 🆕 Получить число непрочитанных в диалоге
     */
    const fetchDialogUnreadCount = async (dialogId) => {
        try {
            return await store.fetchDialogUnreadCount(dialogId);
        } catch (error) {
            console.error('Ошибка:', error);
            throw error;
        }
    };

    const loadDialogs = async () => {
        try {
            return await store.loadDialogs();
        } catch (error) {
            console.error('Ошибка загрузки диалогов:', error);
            throw error;
        }
    };

    const openDialog = async (dialogId) => {
        try {
            return await store.openDialog(dialogId);
        } catch (error) {
            console.error('Ошибка открытия диалога:', error);
            throw error;
        }
    };

    const closeDialog = (router = null, routeName = 'Chat') => {
        store.closeDialog();
        if (router) {
            router.push({ name: routeName }).catch(() => {});
        }
    };

    const markDialogAsRead = (dialogId) => {
        store.markDialogAsRead(dialogId);
    };

    // ==========================================
    // 🆕 АРХИВИРОВАНИЕ
    // ==========================================

    const archiveDialog = async (dialogId) => {
        try {
            return await store.archiveDialog(dialogId);
        } catch (error) {
            console.error('Ошибка архивации:', error);
            throw error;
        }
    };

    const restoreDialog = async (dialogId) => {
        try {
            return await store.restoreDialog(dialogId);
        } catch (error) {
            console.error('Ошибка восстановления:', error);
            throw error;
        }
    };

    const archiveMultiple = async (dialogIds) => {
        try {
            return await store.archiveMultiple(dialogIds);
        } catch (error) {
            console.error('Ошибка массового архивирования:', error);
            throw error;
        }
    };

    const emptyArchive = async () => {
        try {
            return await store.emptyArchive();
        } catch (error) {
            console.error('Ошибка очистки архива:', error);
            throw error;
        }
    };

    // ==========================================
    // 🆕 УДАЛЕНИЕ
    // ==========================================

    const deleteDialogPermanently = async (dialogId) => {
        try {
            return await store.deleteDialogPermanently(dialogId);
        } catch (error) {
            console.error('Ошибка удаления диалога:', error);
            throw error;
        }
    };

    // ==========================================
    // 🆕 ВЛОЖЕНИЯ
    // ==========================================

    const getDialogAttachments = async (dialogId) => {
        try {
            return await store.getDialogAttachments(dialogId);
        } catch (error) {
            console.error('Ошибка загрузки вложений:', error);
            throw error;
        }
    };

    // ==========================================
    // СООБЩЕНИЯ
    // ==========================================

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
        isAttachmentsLoading,
        dialogActions,
        lastError,
        lastSyncAt,
        hasMoreMessages,

        // Геттеры
        sortedDialogs,
        activeDialogs,
        archivedDialogs,
        activeDialogsCount,
        archivedDialogsCount,
        totalUnread,
        currentInterlocutor,
        sortedMessages,
        isDialogLoading,
        getDialogById: store.getDialogById,

        // Базовые методы
        loadDialogs,
        openDialog,
        closeDialog,
        markDialogAsRead,
        fetchUnreadCount,
        fetchDialogUnreadCount,
        setTotalUnreadCount: store.setTotalUnreadCount,

        // 🆕 Архивирование
        archiveDialog,
        restoreDialog,
        archiveMultiple,
        emptyArchive,

        // 🆕 Удаление
        deleteDialogPermanently,

        // 🆕 Вложения
        getDialogAttachments,

        // Сообщения
        sendMessage,
        loadOlderMessages,
        retryMessage: store.retryMessage,
        deleteMessage: store.deleteMessage,

        // Realtime
        handleIncomingMessage: store.handleIncomingMessage,

        // Сброс
        $reset: store.$reset,
    };
}
