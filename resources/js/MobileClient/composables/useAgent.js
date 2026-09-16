import { useAgentStore } from '@/MobileClient/stores/agent.js';
import { computed } from 'vue';

export function useAgent() {
    const store = useAgentStore();

    // ==========================================
    // STATE (Пробрасываем реактивные данные для template)
    // ==========================================
    const agent = computed(() => store.agent);
    const tenants = computed(() => store.tenants);
    const clients = computed(() => store.clients); // Новое: список клиентов агента
    const transactions = computed(() => store.transactions);
    const documents = computed(() => store.documents); // Теперь это объект { list, progress, status }
    const marketingCategories = computed(() => store.marketingCategories);
    const referralStats = computed(() => store.referralStats); // Новое: статистика рефералов

    const isLoading = computed(() => store.isLoading);
    const error = computed(() => store.error);
    const notifications = computed(() => store.notifications);



    // ==========================================
    // GETTERS / UI HELPERS (Форматирование для UI)
    // ==========================================
    const agentInitials = computed(() => {
        // Берем имя либо из корня, либо из профиля (на случай разных структур)
        const name = store.agent.name || store.agent.profile?.name || '';
        return name
            ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
            : 'А';
    });

    const statusText = computed(() => {
        const statuses = {
            pending: 'На проверке',
            active: 'Активен',
            suspended: 'Приостановлен',
            rejected: 'Отклонен'
        };
        return statuses[store.agent.status] || 'Неизвестно';
    });

    const verificationStatusText = computed(() => {
        const vStatuses = {
            not_started: 'Верификация не начата',
            partial: 'Частично заполнено',
            verified: 'Верифицирован'
        };
        return vStatuses[store.agent.verification_status] || 'Неизвестно';
    });

    // Используем новый геттер из стора (бывший recentActivities)
    const recentActivities = computed(() => store.recentTransactions);

    // Удобный алиас для прогресс-бара документов
    const documentsProgressPercent = computed(() => store.documents.progress?.percent || 0);
    const isFullyVerified = computed(() => store.isVerified);
    const canOperate = computed(() => store.canOperate);

    // ==========================================
    // UTILS (Утилиты)
    // ==========================================
    const formatPrice = (price) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        }).format(price || 0);
    };


    /**
     * Унифицированный метод уведомлений.
     * Теперь он использует встроенную систему уведомлений стора,
     * но сохраняет обратную совместимость со старым вызовом notify({ type, text }).
     */
    const notify = (typeOrPayload, message) => {
        if (typeof typeOrPayload === 'object' && typeOrPayload !== null) {
            // Старый стиль: notify({ type: 'success', text: '...' })
            store.addNotification(typeOrPayload.type || 'info', typeOrPayload.text || typeOrPayload.message || '');
        } else {
            // Новый стиль: notify('success', 'Текст сообщения')
            store.addNotification(typeOrPayload, message);
        }
    };

    // ==========================================
    // ACTIONS (Пробрасываем методы стора для чистоты интерфейса)
    // ==========================================
    // Компоненты смогут вызывать их напрямую: const { uploadDocument } = useAgent();
    const actions = {
        fetchInitialData: store.fetchInitialData,
        updateProfile: store.updateProfile,
        uploadDocument: store.uploadDocument,
        fetchDocuments: store.fetchDocuments,
        requestPayout: store.requestPayout,
        fetchTransactions: store.fetchTransactions,
        createClient: store.createClient,
        deleteClient: store.deleteClient,
        createInvoice: store.createInvoice,
        sendInvoice: store.sendInvoice,
        downloadMarketingMaterial: store.downloadMarketingMaterial,
        clearError: () => { store.error = null; }
    };

    return {
        store, // <--- ДОБАВЛЕНО: теперь this.store будет работать

        // State
        agent,
        tenants,
        clients,
        transactions,
        documents,
        marketingCategories,
        referralStats,
        isLoading,
        error,
        notifications,

        // Getters / UI
        agentInitials,
        statusText,
        verificationStatusText,
        recentActivities,
        documentsProgressPercent,
        isFullyVerified,
        canOperate,

        // Utils
        formatPrice,
        notify,

        // Actions (распаковываем, чтобы можно было писать this.fetchInitialData)
        ...actions
    };
}
