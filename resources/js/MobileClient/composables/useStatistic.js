import { storeToRefs } from 'pinia';
import { useStatisticStore } from '@/MobileClient/stores/Shop/statistic.js';

export function useStatistic() {
    const store = useStatisticStore();

    // ✅ ИСПРАВЛЕНИЕ: Извлекаем и состояние, и геттеры через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        statistic,
        users,
        orders,
        products,
        cashback_up,
        cashback_down,
        traffics,
        isLoading,
        isTrafficLoading,
        isExporting,
        isHydrated,
        lastError,

        // --- Геттеры (Getters) ---
        hasData,
        preparedCashback,
        totalTraffic,
    } = storeToRefs(store);

    return {
        // Состояние (Refs)
        statistic,
        users,
        orders,
        products,
        cashback_up,
        cashback_down,
        traffics,
        isLoading,
        isTrafficLoading,
        isExporting,
        isHydrated,
        lastError,

        // Геттеры (Refs)
        hasData,
        preparedCashback,
        totalTraffic,

        // Методы (Actions)
        // Прямое маппирование — это отлично и эффективно
        loadStatistic: store.loadStatistic,
        loadTraffic: store.loadTraffic,
        exportStatistic: store.exportStatistic,

        // Сброс стора
        $reset: store.$reset,
    };
}
