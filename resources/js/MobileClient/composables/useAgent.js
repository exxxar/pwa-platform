import { useAgentStore } from '@/MobileClient/stores/agent.js';
import { computed } from 'vue';

export function useAgent() {
    const store = useAgentStore();

    // Деструктурируем agent из store для удобства использования в template
    const agent = computed(() => store.agent);
    const tenants = computed(() => store.tenants);
    const transactions = computed(() => store.transactions);
    const documents = computed(() => store.documents);
    const marketingCategories = computed(() => store.marketingCategories);
    const verificationStatus = computed(() => store.verificationStatus);
    const notifications = computed(() => store.notifications);

    // Вычисляемые свойства для UI
    const agentInitials = computed(() => {
        return store.agent.name
            ? store.agent.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
            : 'А';
    });

    const statusText = computed(() => {
        const statuses = { pending: 'На проверке', verified: 'Верифицирован', suspended: 'Приостановлен' };
        return statuses[store.agent.status] || 'Неизвестно';
    });

    const recentActivities = computed(() => store.recentActivities);

    // Утилита форматирования цены
    const formatPrice = (price) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency', currency: 'RUB', minimumFractionDigits: 0
        }).format(price || 0);
    };

    // Хелпер для уведомлений
    const notify = (payload) => {
        if (typeof window !== 'undefined' && window.$notify) {
            window.$notify(payload);
        } else {
            console.log(`[${payload.type.toUpperCase()}] ${payload.title}: ${payload.text}`);
        }
    };

    return {
        store,
        agent,
        tenants,
        transactions,
        documents,
        marketingCategories,
        verificationStatus,
        notifications,
        agentInitials,
        statusText,
        recentActivities,
        formatPrice,
        notify
    };
}
