import { storeToRefs } from 'pinia';
import { useStatisticStore } from '@/MobileClient/stores/Shop/statistic.js';

export function useStatistic() {
    const store = useStatisticStore();

    const {
        // State
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

        // Getters
        hasData,
        preparedCashback,
        totalTraffic,
        totalOrdersSum,
        totalOrdersCount,
        totalUsersRegistered,
    } = storeToRefs(store);

    return {
        // State
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

        // Getters
        hasData,
        preparedCashback,
        totalTraffic,
        totalOrdersSum,
        totalOrdersCount,
        totalUsersRegistered,

        // Actions
        loadStatistic: store.loadStatistic.bind(store),
        loadTraffic: store.loadTraffic.bind(store),
        exportStatistic: store.exportStatistic.bind(store),
        $reset: store.$reset.bind(store),
    };
}
