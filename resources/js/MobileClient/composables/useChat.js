import { storeToRefs } from 'pinia';
import { useChatStore } from '@/MobileClient/stores/Shop/chat.js';

/**
 * Composable для работы с чатом.
 * Оптимизирован: убрана лишняя реактивность и шаблонный код.
 */
export function useChat() {
    const store = useChatStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ (State + Getters)
    // ==========================================
    // Геттеры Pinia УЖЕ являются вычисляемыми (computed) свойствами.
    // Оборачивать их в computed() — это анти-паттерн, создающий лишний слой реактивности.
    const {
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

        // Геттеры (берем напрямую, они уже реактивны!)
        sortedDialogs,
        activeDialogs,
        archivedDialogs,
        activeDialogsCount,
        archivedDialogsCount,
        totalUnread,
        currentInterlocutor,
        sortedMessages,
        getDialogById,
    } = storeToRefs(store);

    // ==========================================
    // 2. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // ==========================================
    const isDialogLoading = (dialogId) => store.isDialogLoading(dialogId);

    // ==========================================
    // 3. МЕТОДЫ (Без избыточного try/catch)
    // ==========================================
    // Мы просто возвращаем Promise из стора.
    // Обработкой ошибок должен заниматься компонент (например, через watch или try/catch в методе компонента).

    const fetchUnreadCount = () => store.fetchUnreadCount();
    const fetchDialogUnreadCount = (dialogId) => store.fetchDialogUnreadCount(dialogId);
    const loadDialogs = () => store.loadDialogs();
    const openDialog = (id) => store.openDialog(id);

    const closeDialog = (router = null, routeName = 'Chat') => {
        store.closeDialog();
        if (router) {
            router.push({ name: routeName }).catch(() => {});
        }
    };

    const markDialogAsRead = (dialogId) => store.markDialogAsRead(dialogId);

    // Архивирование
    const archiveDialog = (dialogId) => store.archiveDialog(dialogId);
    const restoreDialog = (dialogId) => store.restoreDialog(dialogId);
    const archiveMultiple = (dialogIds) => store.archiveMultiple(dialogIds);
    const emptyArchive = () => store.emptyArchive();

    // Удаление
    const deleteDialogPermanently = (dialogId) => store.deleteDialogPermanently(dialogId);

    // Вложения
    const getDialogAttachments = (dialogId) => store.getDialogAttachments(dialogId);

    // Сообщения
    const sendMessage = (dialogId, payload) => {
        if (!dialogId) {
            throw new Error('Не указан ID диалога');
        }
        return store.sendMessage(dialogId, payload);
    };

    const loadOlderMessages = async () => {
        if (!store.currentDialog) return [];
        try {
            return await store.loadOlderMessages(store.currentDialog.id);
        } catch (error) {
            console.error('Ошибка загрузки старых сообщений:', error);
            return []; // Здесь try/catch оправдан, так как мы возвращаем fallback-значение
        }
    };

    // ==========================================
    // 4. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
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
        getDialogById,

        // Методы: Базовые
        loadDialogs,
        openDialog,
        closeDialog,
        markDialogAsRead,
        fetchUnreadCount,
        fetchDialogUnreadCount,
        setTotalUnreadCount: store.setTotalUnreadCount,

        // Методы: Архивирование и Удаление
        archiveDialog,
        restoreDialog,
        archiveMultiple,
        emptyArchive,
        deleteDialogPermanently,

        // Методы: Вложения и Сообщения
        getDialogAttachments,
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
